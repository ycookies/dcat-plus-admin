# RBAC 统一角色编辑器实施计划

> 目标：使用 Laravel Blade 独立实现角色创建/编辑页，一次完成路由权限、自定义权限和菜单绑定；不使用 Dcat Form，同时保持现有表结构、旧权限和多应用行为兼容。
>
> 本文件是中断恢复点。恢复工作时先查看本文件和 `git status --short`，从第一个未完成阶段继续。

## 兼容原则

- 不修改现有 RBAC 数据表结构。
- 不删除、不覆盖手工创建的权限。
- 路由扫描只辅助创建新权限，旧权限始终可以继续绑定。
- 资源权限只依据真实注册路由生成，不补造不存在的动作。
- 单路由保存真实 HTTP 方法，`PUT|PATCH` 等多方法必须完整保留。
- 菜单保存父节点和叶子节点，不因升级静默删除历史绑定。
- 超级管理员的运行时放行逻辑保持不变。
- 多应用仅扫描当前应用/面板注册的路由。

## 阶段状态

- [x] 阶段 1：盘点现有 RBAC、草稿改动、路由和表单事务机制。
- [x] 阶段 2：实现当前面板 `RouteCatalog` 和路由权限描述服务。
- [x] 阶段 3：实现统一权限矩阵（资源路由、单路由、现有权限）。
- [x] 阶段 4：实现菜单树与事务化角色授权同步。
- [x] 阶段 5：增加兼容配置、缓存清理和角色删除关系清理。
- [x] 阶段 6：补充测试并完成静态/运行时验证。
- [x] 阶段 7：整理变更说明和升级说明。
- [x] 阶段 8：根据运行反馈移除 Dcat Form，改为 Laravel Blade + Request + Validator + 数据库事务。
- [x] 阶段 9：移除 Blade 中的 Dcat `script init/require` 语法，改为原生 jQuery 初始化和 Blade JSON。
- [x] 阶段 10：处理 jsTree 资源晚于 Blade 内联脚本加载的问题，增加就绪等待与重复初始化保护。
- [x] 阶段 11：使用独立 CSS 重新设计 Blade 页面层级、基础信息区、权限区、菜单区和操作区，不套用 Dcat Form UI。
- [x] 阶段 12：根据实际截图移除权限区 Bootstrap 栅格依赖，改为紧凑自适应 Grid，并统一放大权限和菜单文字。
- [x] 阶段 13：简化单路由授权卡片，仅向操作者展示 HTTP 方法和路由路径，隐藏 Controller/action 开发信息。
- [x] 阶段 14：重新调整资源动作字号层级，美化权限与菜单树 Checkbox，并移除菜单区重复说明。
- [x] 阶段 15：增加“查看类权限”和“变更数据类”批量选择，支持资源动作、单路由、搜索结果及半选状态联动。
- [x] 阶段 16：将资源路由从逐动作授权整理为预览、新建、编辑、删除、导入、导出六类业务能力组。
- [x] 阶段 17：移除成组授权重复提示，将权限与菜单调整为 8:4 同行布局，并隐藏资源卡片 Controller 信息。
- [x] 阶段 18：引入 `dcat-sys` 内部路由、专用策略中间件和短期 Token，将框架支撑接口从角色路由扫描中剥离。

## 计划文件

预计新增：

```text
src/Support/Authorization/RouteCatalog.php
src/Support/Authorization/RoutePermissionResolver.php
src/Support/Authorization/RoleAuthorizationService.php
resources/views/auth/role-form.blade.php
resources/views/auth/partials/role-permissions.blade.php
resources/views/auth/partials/role-menus.blade.php
tests/Unit/Support/Authorization/RouteCatalogTest.php
tests/Unit/Support/Authorization/RoutePermissionResolverTest.php
```

预计修改：

```text
src/Http/Controllers/RoleController.php
src/Models/Role.php
resources/lang/en/admin.php
resources/lang/zh_CN/admin.php
resources/lang/zh_TW/admin.php
config/admin.php
```

## 恢复步骤

```bash
cd vendor/dcat-plus/laravel-admin
git status --short
git diff --check
```

然后阅读本文件“阶段状态”，从第一个未勾选阶段继续。不要重置工作区；现有未提交文件属于本次工作或维护者已有工作。

## 验收标准

1. 资源、`apiResource`、`only/except`、嵌套资源和自定义动作分类正确。
2. 当前应用之外的路由不会混入权限选择器。
3. 编辑角色后，未映射到路由的历史权限仍然保留。
4. 新建路由权限包含准确 URI 和 HTTP 方法。
5. 菜单父节点、子节点、全选和半选状态可正常保存。
6. 角色、权限创建、权限关系和菜单关系在同一连接事务中提交。
7. PJAX 多次进入角色页不会重复绑定事件。
8. `RoleController` 不创建或调用任何 Dcat Form 实例。
9. Laravel 验证失败后能回显基础信息、权限和菜单选择。

## 实施结果

- 新增当前应用路由目录，只扫描当前面板，支持多应用隔离。
- 资源路由按真实路由名称和动作分组，`only/except`、`apiResource` 不会补造动作。
- 单路由保存真实 HTTP 方法；资源更新动作保留 `PUT|PATCH`。
- 使用服务端校验的路由 key 提交，不能通过伪造 URI 创建任意权限。
- 新增“现有/自定义权限”Tab，旧权限、通配符权限和逻辑权限不会被路由扫描覆盖。
- 同 slug 的宽泛旧权限不会被错误复用；只有 HTTP 方法和路径都精确匹配才会复用。
- 角色、自动生成权限、角色权限和角色菜单在配置的后台数据库连接事务内提交。
- 菜单树支持搜索、级联、半选、全选和选中数量，并保存父节点及叶子节点。
- 角色创建、编辑、保存和删除全部由 Laravel Controller、Request、Validator、Eloquent 和 Blade 完成。
- 角色列表不再启用 Dcat 快捷编辑或弹窗创建，统一进入完整 Blade 页面操作。
- Laravel 验证失败会通过 `withInput` 回显角色信息、路由权限、自定义权限和菜单。
- 超级管理员 slug 在 Blade 页面只读，服务端也禁止修改。
- 前端脚本不再依赖 Dcat 注入的 `$this`；所有组件通过唯一 DOM ID 自行初始化。
- jsTree 资源由控制器显式注册，菜单节点和级联参数使用 Blade `@json` 输出，不产生 HTML 实体语法错误。
- 菜单编辑器最多等待 jsTree 5 秒，每 50ms 检测一次；PJAX 重载时通过实例标记避免重复初始化。
- 角色页使用自身颜色变量、卡片、按钮和响应式布局；浅色/深色模式均不依赖 Dcat Form 样式。
- 权限动作不再使用全局 `row/col-*` 栅格，避免宽屏下动作项被横向、纵向拉散；桌面、平板和手机分别采用四列、三列和单列紧凑网格。
- 资源标题、动作名称、路由说明、搜索框和菜单树节点字号已整体提高，菜单工具栏和选中数量改为独立紧凑组件。
- 单路由卡片只显示 HTTP 方法与路径；控制器名称和 action 仍可参与搜索，但不会作为授权界面信息暴露给操作者。
- 资源标题、动作名称和 HTTP/action 辅助信息使用三级字号层级；权限矩阵、全选、半选和菜单树统一使用圆角 Checkbox。
- 菜单绑定说明仅在“03 菜单”区块标题中展示一次，菜单组件内部不再重复输出。
- 查看类权限包含资源路由的列表、查看，以及单路由中的 GET、HEAD、OPTIONS；变更数据类包含新建、保存、编辑、更新、删除及其他非安全方法路由。
- 无法明确识别的路由默认归入变更数据类，避免只读角色被误授予写权限；分类批量选择与当前搜索结果、资源全选、Tab 全选同步更新。
- 资源能力组映射为：预览=`index+show`、新建=`create+store`、编辑=`edit+update`、删除=`destroy`、导入=`import`、导出=`export`。
- 能力组只改变授权交互，不改变权限表和角色权限关系；保存时仍提交组内每条真实路由 key。旧角色存在残缺组合时显示半选，重新勾选后补齐整组。
- 命名为 `*.import`、`*.export` 的路由会归入对应资源；无法完整分配的能力组会禁用，防止再次产生只有页面入口而没有提交动作的残缺授权。
- 桌面端权限区与菜单区按 8:4 同行展示，991px 以下自动改为上下布局；菜单工具栏针对窄栏使用纵向排列。
- 资源卡片头只显示资源名称与相对路径，Controller 仅保留为搜索数据，不再向授权操作者展示；成组授权说明不再重复渲染。
- 通知、用户偏好、FormMedia、SKU、缓存、全局布局和 iframe shell 使用当前面板下的 `dcat-sys/*`；`dcat-api/*` 保持不变。
- 内部路由按 authenticated、signed、administrator、capability 策略授权；角色编辑器不展示原始内部 URL。旧路由保留兼容标记，并从新权限扫描中排除。
- 删除角色时同步清理 `role_menu` 关系。
- 菜单关系变化会清理所有启用应用的两种菜单缓存 key。

## 配置说明

配置位置：`config/admin.php` 的 `permission.role_editor`。

```php
'role_editor' => [
    'auto_create' => true,
    'show_system_routes' => false,
    'include_unnamed_routes' => true,
    'menu_cascade' => true,
    'system_route_names' => [],
    'system_paths' => [
        'dcat-api/*',
        'dcat-sys/*',
        'lake-form-media/*',
        'sku-image-*',
    ],
    'system_controllers' => [],
],
```

旧项目尚未重新发布配置文件时，代码内置相同默认值，统一编辑器仍能正常工作。

## 验证记录

- PHP 8.3 语法检查：通过。
- Blade 全量缓存编译：通过。
- 单元测试：路由目录、权限解析、内部 Token 和失败关闭中间件共 7 个测试、35 个断言通过。
- 真实项目路由扫描：18 组资源路由、103 条单路由；`update` 为 `PUT|PATCH`。
- 内部路由审计：18 条 `internal` 路由全部挂载 `admin.internal:*` 策略，角色编辑器可见内部路由为 0。
- 事务回滚验证：自动权限、现有权限、父/子菜单关系同步正确，测试数据未落库。
- Blade 页面渲染验证：基础信息、权限矩阵和菜单树均正常输出。
- Laravel Controller 新增验证：HTTP 302，角色、权限和菜单同步正确。
- Laravel Controller 编辑验证：HTTP 302，权限和菜单取消选择后同步正确。
- Laravel Validator 验证：缺少名称、标识和授权标记时返回对应错误。
- 删除验证：普通角色删除成功并返回 Dcat JSON；超级管理员返回 403。
- 前端脚本验证：3 段角色编辑器脚本均无 `$this`、无 HTML 实体，并通过 JavaScript 语法解析。

## 已知边界

- 系统路由和显式 `admin.permission:*` 中间件路由默认不参与自动授权。
- 旧的宽泛权限仍可在“现有/自定义权限”中手工绑定，但不会自动映射到某一个精确路由动作。
- 浏览器自动化连接在本地工具环境中初始化失败；已使用完整页面渲染、脚本注入检查、Blade 编译和请求级表单更新测试替代。
