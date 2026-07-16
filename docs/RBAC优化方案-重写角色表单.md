# RBAC 优化方案：重写角色表单（Form + html 注入）

> 目标：角色创建/编辑页一次完成「权限项 + 菜单项」绑定，省去"先去权限管理页手填 http_path 建权限条目"的繁琐流程。
>
> 方案：**放弃改造 dcat 原生 Form 字段，用 `$form->html()` 注入完全自定义的 UI**，绕开上一轮自定义字段的所有坑。

---

## 一、为什么放弃上轮的「自定义字段」方案

上一轮做 `PermissionMatrix` 自定义字段踩了三个坑（均已定位根因）：

| 坑 | 根因 | 为什么难修 |
|----|------|-----------|
| 路由识别空白 | `getActionMethod()` 只返回方法名 `index`，不含控制器名 | 用 `getAction('controller')` 可修，但要改字段内部逻辑 |
| saving 报 Fluent 错 | `$form->input('permissions')` 返回关联模型 Collection，不是提交的 slug 数组 | 字段名=关联名导致数据污染，是 dcat 字段机制的固有耦合 |
| 前缀误匹配 | `strpos('admin')` 误匹配 `admin-api/*` | 精确匹配 `admin/` 可修 |

**根本问题**：自定义字段要复用 dcat 的「字段→customFormat→inputs→saving→sync」链路，每一环都有隐藏行为（customFormat 的回显时机、saving 里 input 的污染、关联识别），调试成本极高，且改 input 影响保存这种操作很脆弱。

**新方案核心思路**：Html 字段**不进 inputs、不参与关联识别、不走 customFormat/saving**。我们把权限/菜单的 UI 完全用 `html()` 注入，提交值用 `request()->input()` 直接拿，保存用 `saved` 回调自己写 sync——每一步都是标准 Laravel，行为可预测。

---

## 二、已验证的关键事实（方案基础）

以下结论全部用真实路由数据验证过，不是推测：

### 1. 路由扫描能正确识别宿主业务资源
用 `$r->getAction('controller')`（拿 `App\Admin\Controllers\MemberUserController@index`）+ 精确匹配 `admin/` 前缀 + 限定 `App\Admin\Controllers` 命名空间，结果：

**资源路由（6 个，intersect≥4）**：
- MemberUserController、TaskGanttDatumController、TaskGanttInfoController、AdminOperationLogController、BisaiUserController、BisaiBmdianController
- demo 扩展（GridController、InTheaterController 等）、框架内置（Auth/User/Role）**全部被正确过滤**

**单路由（intersect<4，作为单路由 tab 候选）**：
- AuthController（登录相关，建议排除）
- WebConfigController（index + saveData）
- SwiperDemoController、OpenApiDocsController（demo，建议排除）

### 2. `$form->html()` 支持闭包且不污染保存
- 闭包内 `$this` 绑定到表单数据（`$this->values()`），**能拿到当前角色的 permission/menu 关联** → 回显可行
- 闭包接收 `$this->form` 参数 → 能拿 role id 等
- Html 字段无 column、不进 `$this->inputs` → **彻底绕开关联污染和 saving 改 input 的坑**

### 3. 提交与保存完全可控
- 自定义 checkbox `name="permissions[]"`、`name="menus[]"` 提交
- `saved` 回调里 `request()->input('permissions')` / `request()->input('menus')` 直接拿到提交数组
- 自己写 `role->permissions()->sync($ids)` / `role->menus()->sync($menuIds)`，不依赖 dcat 的关联识别

---

## 三、UI 结构（按你的思路）

```
角色编辑表单
├── slug、name（保留 dcat 原生 text 字段）
├──【权限项】$form->html() 注入
│   ├── Tab1：资源路由（每个资源一张卡片）
│   │   └── 卡片头：控制器名 + 全选/取消全选
│   │       卡片体：index/create/store/show/edit/update/destroy + 导出 + 导入（checkbox 网格，全选联动）
│   └── Tab2：单路由（checkbox 列表，可全选）
└──【菜单项】$form->html() 注入
    └── 菜单树（单选 + 全选/展开）
```

### 资源卡片设计（参考你的设计图）
- 每个资源一张可折叠 `.card`，头部「控制器名 + uri + 全选开关」
- 卡片体：动作按 4 列网格平铺，每个动作一个 checkbox，`value = slug`（如 `member-user.index`）
- **强制带导出/导入**：每个资源卡片固定追加这两个动作（即便控制器暂无对应路由，勾选后建权限，未来加路由即生效）
- 全选 JS：点卡片头全选 → 勾选该卡片所有动作 checkbox，文字切换"全选/取消全选"

### 单路由 Tab
- 宿主单路由（WebConfigController 等）的 checkbox 列表，`value = Controller@method` 或推导的 slug
- 带全选

### 菜单 Tab
- jstree 渲染菜单树（复用 dcat 现有 Tree 组件数据源），内置全选/展开

---

## 四、数据结构

### 4.1 权限 slug 规范
`{uri}.{action}`，如 `member-user.index`。稳定唯一，updateOrCreate 不重复。

### 4.2 卡片矩阵数据（html 闭包内构造）
```php
[
  [
    'title' => 'MemberUserController',
    'uri'   => 'member-user',
    'actions' => [
      ['key' => 'member-user.index',   'label' => '列表(index)'],
      ['key' => 'member-user.create',  'label' => '新建(create)'],
      ... // 7 个标准 + 导出 + 导入
    ],
  ],
  ...
]
```

### 4.3 saved 回调的 slug→permission 映射
| slug | http_path | http_method |
|------|-----------|-------------|
| `member-user.index` | `/member-user` | GET |
| `member-user.create` | `/member-user/create` | GET |
| `member-user.store` | `/member-user` | POST |
| `member-user.show` | `/member-user/*` | GET |
| `member-user.edit` | `/member-user/*/edit` | GET |
| `member-user.update` | `/member-user/*` | PUT |
| `member-user.destroy` | `/member-user/*` | DELETE |
| `member-user.export` | `/member-user/export` | GET |
| `member-user.import` | `/member-user/import` | POST |

---

## 五、改动清单

| 文件 | 改动 | 类型 |
|------|------|------|
| `src/Http/Controllers/RoleController.php` | form() 内用 `$form->html()` 注入权限矩阵和菜单树；加 `saved` 回调写 sync；加路由扫描/slug 映射辅助方法 | 改 |
| `resources/views/form/role-permissions.blade.php` | **新建**。权限矩阵 UI（tab + 资源卡片 + 单路由列表 + 全选 JS） | 新建 |
| `resources/views/form/role-menus.blade.php` | **新建**。菜单树 UI（jstree + 全选） | 新建 |
| `resources/lang/{zh_CN,en,zh_TW}/admin.php` | 加动作中文名（列表/新建/.../导出/导入）、select_all/deselect_all 等 | 改 |

**不改**：Form.php（不注册新字段）、Permission 模型、表结构、Repository。

---

## 六、关键实现要点（避免重蹈覆辙）

1. **路由扫描**：`getAction('controller')`（不是 `getActionMethod()`）+ 精确匹配 `rtrim($prefix,'/').'/'` + 限定 `admin.route.namespace` 命名空间
2. **html 闭包回显**：闭包内 `$this` 是表单数据，用 `$this->resource` / 或从 `$form->model()` 拿角色，再取 `$role->permissions->pluck('slug')` 标记勾选
3. **保存逻辑全在 saved 回调**：用 `request()->input()`（**不要用 `$form->input()`**，那个在关联字段会污染），自己 `firstOrCreate` 权限 + `sync` 关联
4. **菜单树**：复用现有 menu_model 的 `allNodes()` 数据，渲染成 jstree，checkbox name=`menus[]`，value=menu id
5. **回显**：编辑时 `$role->menus->pluck('id')` 标记菜单勾选；权限按 slug 匹配

---

## 七、向后兼容性

| 维度 | 保证 |
|------|------|
| 表结构 | 不动，无迁移 |
| 现有角色数据 | 完全保留（中间表数据不变） |
| 权限管理 CRUD | 不受影响，仍可手动管理权限 |
| 超管角色 | saved 回调跳过（全通过） |
| 关闭开关 | 可加 `admin.permission.route_matrix` 开关，设 false 退回原 tree 字段 |

---

## 八、验证步骤

1. **页面渲染**：角色编辑页看到 Tab（资源路由/单路由/菜单），资源卡片矩阵正确显示 6 个业务资源，每卡片 9 动作
2. **回显**：编辑已有角色，已绑的 permission/menu 正确标记勾选
3. **勾选保存**：勾某资源 index/store/destroy → 保存 → 查 `admin_permissions` 表生成 3 条 slug、http_path 正确；`admin_role_permissions` 有绑定
4. **菜单保存**：勾几个菜单 → 保存 → `admin_role_menu` 有绑定
5. **全选**：点卡片头全选 → 联动勾选；菜单树全选可用
6. **不重复创建**：再次保存同角色，permissions 表不新增（slug 已存在）

---

## 九、与上轮的本质区别

| 维度 | 上轮（自定义字段） | 本轮（html 注入） |
|------|-------------------|------------------|
| UI 数据来源 | 字段内部 buildMatrix | html 闭包内构造，所见即所得 |
| 提交值获取 | `$form->input()`（关联污染） | `request()->input()`（干净） |
| 保存 | 依赖 dcat 的关联 sync（脆弱） | saved 回调自己 sync（可控） |
| 回显 | customFormat（时机难控） | html 闭包直接读模型（直观） |
| 调试 | 每环都藏行为 | 标准 Laravel，可预测 |

**结论**：本轮把所有"魔法"去掉，UI 自己渲染、数据自己提交、关联自己 sync——每个环节都是显式的，不会再有"明明逻辑对却报 Fluent 错"这种问题。

---

## 十、待确认

1. **单路由 tab 要不要做**：宿主单路由只有 WebConfigController 算业务（其余是 Auth/demo）。本次可只做资源矩阵，单路由 tab 后续按需加。
2. **菜单树全选**：dcat 现有 tree 字段已内置全选，但我们要用 html 注入。是否复用 dcat 的 Tree Widget（`Dcat\Admin\Widgets\Tree`）渲染，还是纯手写 jstree？前者省事，后者更可控。
3. **导出/导入强制显示**：每个资源卡片固定带这两个动作（你上轮已确认要）。

确认这三点后即可动手实现。
