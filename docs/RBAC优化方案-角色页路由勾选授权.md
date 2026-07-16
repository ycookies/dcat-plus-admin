# RBAC 优化方案：角色页路由勾选授权

> 目标（用户原话）：在创建/编辑角色时，**同时完成权限项和菜单项的绑定**，省去"先去权限管理页手填 http_path 建权限条目"这一步。
>
> 核心改造点：[src/Http/Controllers/RoleController.php](src/Http/Controllers/RoleController.php)

---

## 一、用户需求拆解

1. **角色页一次完成权限 + 菜单绑定** —— 当前 `form()` 已有 `tree('permissions')` 和 `tree('menus')`，菜单项已满足；权限项是本方案重点。
2. **权限项分两个 tab**：
   - 「资源路由」tab —— 列出面板所有 resource 控制器（如 member-user、task-gantt-info），每个资源是一个**可全选的勾选组**，组内是各 action（index/create/store/show/edit/update/destroy），支持全选。
   - 「单路由」tab —— 列出所有散装路由（如 `web-config`、`task-gantt-data-list`、导出导入接口），可单选可全选。
3. **菜单项**用树结构展示，支持单选和全选（框架已内置，无需开发）。
4. **勾选路由 = 自动授权**：用户勾选的路由在保存角色时**自动转成 Permission 记录**（不存在则创建，按 action 拆成多条细粒度权限），再绑定给该角色。

---

## 二、关键现状（已探明，决定方案可行性）

| 现状 | 影响 |
|------|------|
| Form 原生支持 `tab()` 分组（[HasTabs.php:24](src/Form/Concerns/HasTabs.php#L24)） | ✅ 两个 tab 直接用 `$form->tab(...)` |
| Checkbox 原生支持 `canCheckAll()` 全选（[Checkbox.php:57](src/Form/Field/Checkbox.php#L57)） | ✅ tab 内勾选列表 + 全选零成本 |
| Tree 字段原生内置「全选/展开」按钮（[tree.blade.php:34](resources/views/form/tree.blade.php#L34)，jstree `check_all`） | ✅ 菜单树全选无需开发 |
| saving 钩子在 inputs 组装后、sync 前触发，改 input 影响保存（[Form.php:691](src/Form.php#L691) + [Form.php:1558](src/Form.php#L1558)） | ✅ "路由→建权限→合并 permissions id" 有干净切入点 |
| BelongsToMany 保存即 `$relation->sync($ids)`（[EloquentRepository.php:849](src/Repositories/EloquentRepository.php#L849)） | ✅ 改 permissions input 即可生效，无需重写 repository |
| `ScaffoldCommand::registerPermission()` 有现成"路由→Permission" `updateOrCreate` 模式 | ✅ 建权限逻辑可直接仿写 |
| **`getRoutes()` 现版只返回字符串数组，丢失 controller/action 信息**（[PermissionController.php:147](src/Http/Controllers/PermissionController.php#L147)） | ⚠️ 必须写**增强版**路由抓取，保留 `getActionMethod()` 才能识别资源 |
| **运行时 Route 对象不保留"是否 resource() 注册"信息** | ⚠️ Laravel 把 resource 展开成 7 条独立路由，原始 resource() 调用记录丢失。识别资源需另寻依据（见第三节） |

---

## 三、核心难点：如何识别"资源路由"

用户明确：**判断依据是 routes.php 里 `$router->resource()` 注册的，外加导出/导入这类成组接口**。

但运行时 `app('router')->getRoutes()` 已经没有 resource() 的标记。有三条可行路径，各有取舍：

### 方案 A：按 Controller 分组 + action 集合推断（推荐）
- 遍历路由，用 `$route->getActionMethod()`（形如 `MemberUserController@index`）按 Controller 分组
- 同一 Controller 的 action method 集合，与 Restful 7 动作 `{index,create,store,show,edit,update,destroy}` 交集 ≥ 4 个 → 判定为资源路由，归入「资源路由」tab
- 其余（单个 get/post，或 action 名不在 Restful 集合内，如 `datalist`/`export`/`import`）→ 归入「单路由」tab
- **优点**：纯运行时，不依赖源码；导出/导入会自然落入单路由 tab（因为 action 名是 export/import）
- **缺点**：判断阈值 4 是启发式；自定义 resource（只注册部分 action）可能误判

### 方案 B：扫描 routes.php 源码识别 resource() 调用
- 读 `app_path('Admin/routes.php')` 源文件，正则抓 `->resource('xxx', XxxController::class)` 调用
- **优点**：最贴合用户描述（"resource 注册的就是资源路由"），100% 准确
- **缺点**：routes.php 可能动态注册、闭包内拼接、或放别处，源码扫描脆弱；与运行时路由表需要做一次 join

### 方案 C：A + B 组合
- 先扫描 routes.php 标记资源控制器名集合（方案 B），运行时路由按 controller 名匹配该集合
- 匹配上的进资源 tab，其余进单路由 tab
- **准确度最高，实现略重**

**本报告默认推荐方案 A**（够用、健壮、不依赖源码可读性）。若你项目 routes.php 规范且静态，方案 B/C 更准。**等你定。**

---

## 四、权限生成粒度（用户已定：按 action 拆多条）

用户选择**细粒度**：勾选一个资源路由的某个 action，后台生成**一条独立 Permission**。

| 勾选 | 自动创建/复用的 Permission |
|------|----------------------------|
| `MemberUserController@index` | slug=`member-user.index`，http_path=`/member-user`，http_method=`GET` |
| `MemberUserController@store` | slug=`member-user.store`，http_path=`/member-user`，http_method=`POST` |
| `MemberUserController@destroy` | slug=`member-user.destroy`，http_path=`/member-user/*`，http_method=`DELETE` |
| 单路由 `web-config` | slug=`web-config`，http_path=`/web-config*` |

- 用 `Permission::updateOrCreate(['slug' => $slug], [...])` —— 不存在则创建，已存在则复用，**不重复、不覆盖 http_path**（避免破坏管理员在权限页的手动调整）。
- slug 命名规范：`{资源uri}.{action}`（资源）/ `{uri}`（单路由），保证唯一且可读。
- 生成的 permission id 合并进 `permissions` input 字段，由 Form 的 sync 自动绑定到角色。

---

## 五、数据结构设计

### 5.1 「资源路由」tab 的 checkbox options
```
按 Controller 分组的 checkbox（每组带全选）：
  MemberUserController
    ☐ 列表(index)        value = "MemberUserController@index"
    ☐ 新建(create)       value = "MemberUserController@create"
    ☐ 保存(store)        value = "MemberUserController@store"
    ☐ 查看(show)         ...
    ☐ 编辑(edit)
    ☐ 更新(update)
    ☐ 删除(destroy)
  TaskGanttInfoController
    ☐ ...（同上 7 项，全选）
```
> dcat 的 checkbox 是扁平的，要做"分组 + 组内全选"，需用多个 checkbox 字段（每控制器一个）或自定义前端。**见第六节 UI 方案。**

### 5.2 「单路由」tab 的 checkbox options
```
单个 checkbox 组（带全选）：
  ☐ 首页 /                  value = "HomeController@index"
  ☐ 网站配置 web-config      value = "WebConfigController@index"
  ☐ 保存配置 web-config/save value = "WebConfigController@saveData"
  ☐ 任务数据列表             value = "TaskGanttDatumController@datalist"
  ☐ 导出 ...                 value = "XxxController@export"
  ☐ 导入 ...                 value = "XxxController@import"
  [全选]
```

### 5.3 菜单 tab（沿用现有 tree）
```
$jstree 带内置 [全选] [展开] 按钮 —— 零开发
```

### 5.4 saving 钩子处理流程
```
saving 回调：
  1. 读 input('route_selections')  ← 所有 tab 勾选的 "Controller@action" 字符串数组
  2. 对每个 "Controller@action"：
       - 查运行时路由表，拿到 uri + methods
       - 推导 slug / http_path / http_method
       - Permission::updateOrCreate(['slug'=>...], [...]) → 拿到 id
  3. merged = 已有 input('permissions') ∪ 新建 permission ids（去重）
  4. input('permissions', merged)
  5. （虚拟字段 route_selections 已 ignore，不入库）
```

---

## 六、UI 实现方案（关键决策点）

dcat 的 `$form->checkbox()` 是**扁平**的，原生不支持"分组 + 每组全选"。要实现你画的"资源路由按控制器分组、每组带全选"，有三个选项：

### 选项 1：动态生成多个 checkbox 字段（推荐）
- 后端按 Controller 分组，每组生成一个 `$form->checkbox('rs_'.$controllerKey, $controllerName)->canCheckAll()->options($actionOptions)`
- 字段名用约定前缀（如 `rs_`），saving 时统一收集
- **优点**：纯用框架 API，零前端代码；每组天然带全选
- **缺点**：控制器多时字段多，但 tab 内可滚动

### 选项 2：自定义字段类型（新建一个 Field\RouteMatrix）
- 新建 `src/Form/Field/RouteMatrix.php`，模板里渲染"分组 + 每组全选"的自定义 UI
- **优点**：UI 最干净，可定制
- **缺点**：要写新 Field + blade 模板 + 前端 JS，工作量大

### 选项 3：用 checkbox + 前端 JS 重排成视觉分组
- 一个扁平 checkbox，options 里用 optgroup 思路渲染分组标题
- **缺点**：原生 checkbox 无 optgroup，需改模板

**本报告推荐选项 1**（务实、零新代码、每组全选原生支持）。**等你定。**

---

## 七、向后兼容性

| 维度 | 保证 |
|------|------|
| 表结构 | 不动，无迁移 |
| 现有权限树 `tree('permissions')` | **保留**，与新路由 tab 共存（用户既可手勾已有权限，也可用路由 tab 自动建） |
| 现有权限管理 CRUD | 完全不受影响 |
| 关闭开关 | 加 `admin.permission.route_picker`（默认 true），设 false 则隐藏路由 tab，退回纯权限树 |
| 超管角色 | saving 里跳过（admin 角色全通过，无需勾路由） |
| 已有手动权限 | `updateOrCreate` 仅在 slug 不存在时创建，**不覆盖** http_path |

---

## 八、改动文件清单

| 文件 | 改动 | 类型 |
|------|------|------|
| `src/Http/Controllers/RoleController.php` | form() 加 3 个 tab + saving 钩子；新增 `getResourceRoutes()` / `getSingleRoutes()` 路由抓取 | 改 |
| （或新建）`src/Support/RoutePermissionMapper.php` | 「Controller@action → slug/http_path/http_method」映射 + updateOrCreate 逻辑 | 新建 |
| `config/admin.php` | 加 `permission.route_picker` 开关 | 改 |
| `resources/lang/{zh_CN,en,zh_TW}/admin.php` | 加 tab 标题、action 中文名等翻译 key | 改 |

---

## 九、风险与边界

1. **资源识别误判**（方案 A 的固有风险）：自定义 resource（只注册 index/edit）或非标 action 名可能误归单路由。**缓解**：阈值设 ≥4，且单路由 tab 也照常授权，不影响功能正确性，只影响分组展示。
2. **路由量很大时表单膨胀**：若面板有几十个 resource + 上百单路由，tab 内 checkbox 量大。**缓解**：tab 内可滚动；或加搜索框（前端增强）。
3. **updateOrCreate 的 slug 冲突**：需确保 slug 命名规范稳定（`{uri}.{action}`），避免不同资源撞 slug。
4. **导出/导入识别**：用户提到导出导入算"成组接口"。方案 A 下它们会落入单路由 tab（action 名是 export/import）。若想让它们也"全选成组"，可在 routes.php 用约定命名（如 `XxxController@export`/`@import`），后端按 controller 把 export/import 配对成组。**这点需你确认是否要特殊处理。**
5. **路由动态注册**：若 routes.php 有闭包/动态拼接，方案 A 仍能工作（基于运行时路由表），方案 B/C 会漏。

---

## 十、待你拍板的 4 个决策

1. **资源识别方案**：A（运行时按 action 集合推断）/ B（扫 routes.php 源码）/ C（组合）？
2. **UI 分组方案**：1（动态多 checkbox 字段，每组全选）/ 2（新建 RouteMatrix 字段）/ 3（前端重排）？
3. **导出/导入**：归入单路由 tab 即可，还是要特殊"成组全选"？
4. **权限树 `tree('permissions')` 是否保留**：与新路由 tab 共存（推荐），还是用路由 tab 完全替代它？

定下这 4 点后，我按你的选择实现。
