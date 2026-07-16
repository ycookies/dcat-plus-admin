# dcat-plus RBAC 权限体系分析与优化建议

> 目标：理解现状、定位"调配太麻烦"的根因，给出**向后兼容**（不改表结构、不破坏现有 API）的优化方案。

---

## 一、现状全景

### 1. 数据模型（4 主表 + 4 中间表）

| 关系 | 中间表 | 配置键 |
|------|--------|--------|
| 用户 ↔ 角色 | `admin_role_users` | `role_users_table` |
| 角色 ↔ 权限 | `admin_role_permissions` | `role_permissions_table` |
| 角色 ↔ 菜单 | `admin_role_menu` | `role_menu_table` |
| 权限 ↔ 菜单 | `admin_permission_menu` | `permission_menu_table` |

- `Administrator`（[src/Models/Administrator.php](src/Models/Administrator.php)）：只通过角色间接持有权限，**用户不直接挂权限**。
- `Role`（[src/Models/Role.php](src/Models/Role.php)）：`slug='administrator'` 为超管，靠 `isAdministrator()` 短路全通过。
- `Permission`（[src/Models/Permission.php](src/Models/Permission.php)）：本身是树（`parent_id`），`http_path`/`http_method` 为**逗号分隔字符串**。
- `Menu`（[src/Models/Menu.php](src/Models/Menu.php)）：树 + 缓存，可绑角色和权限。

### 2. 权限判定链路

```
请求 → Middleware\Permission → allPermissions()（遍历用户所有角色的权限）
     → 任一 Permission::shouldPassThrough($request) 命中即放行，否则 403
     → 超管 / 白名单(except) / 路由级 admin.permission:xxx 短路
```

- 核心判定：[HasPermissions.php](src/Traits/HasPermissions.php) `can()` → 超管短路 → slug/id 匹配。
- URL 匹配：[Permission.php](src/Models/Permission.php) `shouldPassThrough()` → [Helper.php:259](src/Support/Helper.php#L259) `matchRequestPath()`，支持 `*` 通配、`GET,POST:path` 内联方法。

### 3. 管理界面分配方式

| 操作 | 控件 | 位置 |
|------|------|------|
| 用户 → 角色 | `multipleSelect` | [UserController.php:158](src/Http/Controllers/UserController.php#L158) |
| 角色 → 权限 | `tree` 权限树 | [RoleController.php:99](src/Http/Controllers/RoleController.php#L99) |
| 角色 → 菜单 | `tree` 菜单树 | [RoleController.php:115](src/Http/Controllers/RoleController.php#L115) |
| 权限 → 菜单 | `tree` 菜单树 | [PermissionController.php:119](src/Http/Controllers/PermissionController.php#L119) |
| 菜单 → 角色 | `multipleSelect` | [MenuController.php:131](src/Http/Controllers/MenuController.php#L131) |
| 菜单 → 权限 | `tree` 权限树 | [MenuController.php:142](src/Http/Controllers/MenuController.php#L142) |
| 权限.http_path | `tags` **手填** | [PermissionController.php:115](src/Http/Controllers/PermissionController.php#L115) |

---

## 二、"调配太麻烦"的根因（量化）

**从零给一个新功能（如"商品管理"）配权限，当前要走 4~6 步、跨 4 个页面：**

1. 【菜单】新建菜单，填 uri
2. 【权限】新建权限，**手填 http_path**（要懂 `*` 通配 / `METHOD:path` 语法）
3. 【菜单↔权限】双向绑定（菜单编辑勾权限 + 权限编辑勾菜单，**重复维护两次**）
4. 【角色↔权限 + 角色↔菜单】勾两棵树，且要保持一致
5. 【用户↔角色】勾角色

**根因：菜单、权限、角色三方两两交叉绑定（6 条边），且每张表独立 CRUD，要在多页面来回跳。**

### 6 个具体痛点

| 痛点 | 位置 | 说明 |
|------|------|------|
| **A. 三方交叉绑定** | `config/admin.php:250` 三个开关全开 | 菜单↔权限、角色↔菜单、角色↔权限，同一关系两边都能配，易不一致 |
| **B. http_path 手填** | `PermissionController.php:115` `tags` | 管理员要懂路径通配语法，`getRoutes()` 只是补全候选项，不自动建记录 |
| **C. canSeeMenu 空壳** | `Administrator.php:91` `return true` | 菜单显示控制默认失效，全靠菜单绑的 roles/permissions 才能控制显示 |
| **D. 缺自动生成** | `PermissionController::getRoutes()` | 没有"按 Controller 一键生成权限集"的命令 |
| **E. 缺便捷特性** | 全包无 `template/inherit/clone` | 角色继承、权限模板、角色复制、按组全选 全部缺失 |
| **F. N+1 性能** | `HasPermissions.php:18` `allPermissions()` | 每请求懒加载 roles→permissions，无缓存 |

---

## 三、优化建议（全部向后兼容）

### 优先级 P0 — 立竿见影，改动小

#### 1. 菜单新建时**内联同步建权限**（消灭步骤 2、3）
在 [MenuController](src/Http/Controllers/MenuController.php) 的新建表单加一个「同时创建权限」开关 + 自动从 `uri` 推导 `http_path`（如 uri=`goods` → http_path=`/goods*`）。
保存时在一个事务里：建菜单 + 建权限 + 双向绑定。
**用户从 6 步降到 3 步。**

#### 2. 给 `Administrator::canSeeMenu()` 补真实实现
```php
// Administrator.php — 默认实现，子类可覆盖
public function canSeeMenu($menu): bool
{
    if ($this->isAdministrator()) return true;
    // 复用 Layout\Menu 已有的 checkPermission 逻辑：菜单没绑角色/权限则对所有人可见
    $roles = $menu->roles;
    if ($roles->isEmpty() && $menu->permissions->isEmpty()) return true;
    return $this->visible($roles) || $menu->permissions->pluck('slug')->some(fn($s) => $this->can($s));
}
```
让菜单显示控制开箱即用（目前是空壳 `return true`）。**向后兼容**：方法签名不变，子类仍可覆盖。

#### 3. `allPermissions()` 加请求级缓存 + 可选 Cache
在 [HasPermissions.php](src/Traits/HasPermissions.php) 里把单次请求内复用做对（已有 `$allPermissions` 属性但 `allPermissions()` 每次重算），并加一个 `admin.permission.cache_ttl` 配置项，开了就按用户 id 缓存到 Laravel Cache，登出/角色变更时失效。**向后兼容**：默认 `cache_ttl=0` 即关闭，行为不变。

### 优先级 P1 — 减少重复操作

#### 4. 「按权限分组全选」前端增强
在 [RoleController](src/Http/Controllers/RoleController.php) 的权限树渲染时，给每个父节点加一个「全选本组」按钮（纯前端 JS）。不改后端。

#### 5. 角色复制 Action
新增一个 [RowAction](src/Grid/Action)，复制角色的 permissions + menus 关联到新角色。基于现有 Action 扩展点，不动核心。

#### 6. 文档化三个绑定开关，建议生产配置
`bind_permission` / `role_bind_menu` / `permission_bind_menu` 建议生产环境**关掉 `permission_bind_menu`**，只保留单向"菜单绑权限"，避免菜单↔权限双向维护。加到 README 或本文件。

### 优先级 P2 — 自动化（省掉手填 http_path）

#### 7. Artisan 命令 `admin:permission:scan`
扫描所有 `admin.route.prefix` 下的路由，按 Controller 分组，**批量生成权限记录**（slug=控制器名，http_path 自动填好）。复用 `PermissionController::getRoutes()` 现有逻辑。
```bash
php artisan admin:permission:scan           # 列出未配权限的路由
php artisan admin:permission:scan --create  # 批量建权限（按 Controller 分组）
```
**这是解决"调配麻烦"的根本手段**——新功能上线后跑一次命令，权限记录自动就位。

#### 8. 权限模板（可选）
预置几个常见角色模板（如「内容编辑」「运营」「财务」），新角色时一键套用。可做成数据 seeder + UI 选择器。

---

## 四、推荐落地顺序

| 步骤 | 改动 | 收益 | 风险 |
|------|------|------|------|
| ① canSeeMenu 补实现 | 1 方法 | 菜单显示控制开箱可用 | 极低（纯增强） |
| ② allPermissions 缓存 | 1 trait + 配置 | 性能提升 | 低（默认关） |
| ③ 菜单内联建权限 | MenuController + 表单 | 6步→3步 | 中（改保存逻辑） |
| ④ 分组全选按钮 | 前端 JS | 减少勾选疲劳 | 低 |
| ⑤ permission:scan 命令 | 新建 Artisan 命令 | 彻底免手填 http_path | 中（新功能） |
| ⑥ 角色复制 / 模板 | 新 Action | 重复角色配置 | 低 |

所有改动**不涉及迁移**、**不改表结构**、**方法签名不变**，子类和扩展点完全向后兼容。

---

## 五、P0 实装记录

下列改动已落地，默认全部「关/向后兼容」，按需开启。

### canSeeMenu 真实实现（P0-1）✅
**改动文件**：[src/Models/Administrator.php](src/Models/Administrator.php)

- `canSeeMenu($menu)` 不再恒返回 `true`，但默认行为不变（开关默认关）。
- 新增 `isMenuVisible($menu)` 独立判定方法：超管直通；未绑定角色/权限的菜单对所有人可见；否则命中角色或权限即可见。
- 开启 `admin.menu.enforce_user_visibility` 后，`canSeeMenu` 委托给 `isMenuVisible`。

**配置开关**：
```php
'enforce_user_visibility' => false,   // 默认 false 保持历史行为
```

### allPermissions 跨请求缓存（P0-2）✅
**改动文件**：[src/Traits/HasPermissions.php](src/Traits/HasPermissions.php)

- 修正探索结论：原代码本就有**单次请求内**复用（`$allPermissions` 属性），本次新增的是**跨请求可选缓存**。
- 缓存 key 含「角色 slug 集合 + 角色 updated_at」，角色或权限关联变更时 key 自动失效，无需手动清缓存。
- 新增 `forgetPermissionsCache()` 供手动失效。

**配置开关**（[config/admin.php](config/admin.php) `permission` 块）：
```php
'cache' => [
    'ttl'    => 0,        // 0 = 关闭（默认，保持历史行为）
    'store'  => 'file',
    'prefix' => 'admin:permissions:',
],
```

### ⚠️ P0-3「菜单内联建权限」已撤回
最初设想"新建菜单时由 uri 自动推导生成权限"，但**菜单与权限是多对多关系**（一个菜单背后常有多条按钮级/操作级权限），强行 1:1 自动化会丢失细粒度、产生 slug 冲突、过度授权。已回退全部代码、配置、翻译。批量生成权限的正确方向是 P2 的 `admin:permission:scan`（按 Controller 路由批量生成多条权限）。

详见 [RBAC优化总结-P0实装.md](RBAC优化总结-P0实装.md)。
