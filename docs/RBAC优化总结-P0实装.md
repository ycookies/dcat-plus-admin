# dcat-plus RBAC 权限体系优化总结

> 背景：现有 RBAC "调配太麻烦"——菜单、权限、角色三方两两交叉绑定（6 条边），每张表独立 CRUD，新功能配权限要走 4~6 步、跨 4 个页面。
>
> 本次实装 P0 中**经过验证有效**的两项，全部**向后兼容**：不改表结构、方法签名不变、新增开关默认退回旧行为。

---

## ⚠️ 重要：P0-3「菜单内联建权限」已撤回

最初设计的 P0-3（新建菜单时由 uri 自动推导生成一条权限）**存在根本性缺陷，已回退**：

**问题**：错误假设「菜单 uri ↔ 权限」是 1:1，但实际是多对多——
- 一个「订单管理」菜单（uri=`orders`）背后往往需要 `orders` / `orders.create` / `orders.edit` / `orders.delete` / `orders.export` / `orders.refund` 等多条权限（按钮级/操作级）。
- 自动生成一条 `/orders*` 粗权限会：
  1. **丢失细粒度**——没法做「只能看不能删」，而这正是细粒度 RBAC 的价值；
  2. **slug 冲突**——「订单管理」(`orders`) 和「订单统计」(`orders/stats`) 推导出相同 slug，后者复用前者、拿不到独立权限；
  3. **过度授权**——`/orders*` 把 `orders/refund`、`orders/export` 这些本应单独授权的敏感接口也一并放给有 `orders` 权限的人。

**根因**：菜单是「导航展示单元」，权限是「接口访问控制单元」，二者本就该**手动多对多绑定**（这正是原设计 `Menu::withPermission()` + `tree('permissions')` 的本意）。强行 1:1 自动化破坏了模型。

**结论**：菜单↔权限的绑定应保留手动多对多方式。批量生成权限的正确方向是 P2 的 `admin:permission:scan`（按 Controller 路由批量生成多条权限），而非按菜单 uri 推导。

---

## 一、保留并实装的两项优化

| # | 优化项 | 文件 | 默认行为 |
|---|--------|------|----------|
| P0-1 | **canSeeMenu 真实实现** | `src/Models/Administrator.php` | 开关默认**关**（行为不变）|
| P0-2 | **allPermissions 跨请求缓存** | `src/Traits/HasPermissions.php` | `ttl=0` **关**（行为不变）|

配套：`config/admin.php` 新增 2 个配置开关。完整分析见 [RBAC权限体系分析与优化建议.md](RBAC权限体系分析与优化建议.md)。

---

### P0-1：canSeeMenu 真实实现

**痛点**：`Administrator::canSeeMenu()` 历史上是空壳（恒 `return true`），菜单可见性完全靠 `Layout\Menu::checkPermission()` 一条腿，用户自定义子类想接管这个钩子却无效。

**方案**：
- `canSeeMenu($menu)` 不再恒返回 true，但**默认行为不变**（开关 `enforce_user_visibility` 默认 false）
- 新增独立方法 `isMenuVisible($menu)`：超管直通 → 未绑角色/权限的菜单对所有人可见 → 否则命中角色或权限即可见
- 开关开启后 `canSeeMenu` 委托给 `isMenuVisible`

**配置**：
```php
// config/admin.php  menu 块
'enforce_user_visibility' => false,   // 默认 false 保持历史行为
```

---

### P0-2：allPermissions 跨请求缓存

**痛点**：`HasPermissions::allPermissions()` 每个请求都要从用户的角色聚合权限（`roles→permissions` 懒加载），跨请求无缓存。

**说明**：原代码本就有**单次请求内**复用（`$allPermissions` 属性）。本次新增的是**跨请求可选缓存**。

**方案**：
- 缓存 key = 用户 id + 角色 slug 集合 + 角色 `updated_at`
- 角色/权限关联一旦变更，key 自动失效，**无需手动清缓存**
- 仅缓存扁平属性，避免序列化整个模型
- 新增 `forgetPermissionsCache()` 供手动失效

**配置**：
```php
// config/admin.php  permission 块
'cache' => [
    'ttl'    => 0,        // 0 = 关闭（默认，保持历史行为）；设 600 即缓存 10 分钟
    'store'  => 'file',
    'prefix' => 'admin:permissions:',
],
```

---

## 二、向后兼容性保证

| 保证项 | 说明 |
|--------|------|
| 不改表结构 | 无需迁移，无中间表变更 |
| 方法签名不变 | `canSeeMenu`/`allPermissions`/`can`/`cannot` 等全部原样 |
| 子类覆盖不受影响 | 新增方法 `isMenuVisible`/`forgetPermissionsCache` 是扩展 |
| 开关默认退回旧行为 | `enforce_user_visibility=false`、`cache.ttl=0` |
| P0-3 完全移除 | 无残留代码、配置、翻译 key |

---

## 三、使用建议

- **想启用菜单可见性控制**：打开 `admin.menu.enforce_user_visibility`，给菜单绑角色/权限后即可生效。
- **想提升性能**：打开 `admin.permission.cache.ttl`（如 600 秒）。
- **完全退回原状**：两个开关都保持默认（关）即可，框架行为与改动前完全一致。

---

## 四、验证步骤

1. **canSeeMenu**（开关打开后）：给某菜单绑一个角色 A，用非 A 角色的账号登录，确认看不到该菜单。
2. **权限缓存**（开关打开后）：用 Laravel Telescope 或 `Cache::store('file')` 确认 `admin:permissions:{id}:{hash}` key 存在；改用户角色后旧 key 自动失效。

---

## 五、改动文件清单（本次保留）

```
src/Models/Administrator.php                      + canSeeMenu 实现、isMenuVisible、extractMenuSlugs
src/Traits/HasPermissions.php                     + 跨请求缓存、forgetPermissionsCache
config/admin.php                                  + 2 个配置开关（permission.cache、menu.enforce_user_visibility）
docs/RBAC优化总结-P0实装.md                        本文档
docs/RBAC权限体系分析与优化建议.md                  完整分析
```

---

## 六、待办（后续，未实装）

| 优先级 | 项 | 说明 |
|--------|----|------|
| **P2** | `php artisan admin:permission:scan` | **替代撤回的 P0-3 的正确方向**：按 Controller 路由自动批量生成多条权限（列表/创建/编辑/删除/...），免手填 http_path，且保持细粒度 |
| P1 | 权限树「按组全选」按钮 | 角色编辑页前端增强，减少勾选疲劳 |
| P1 | 角色复制 Action | 复制 permissions+menus 关联到新角色 |
| P2 | 权限模板 | 预置常见角色模板，新角色一键套用 |

---

## ⚠️ 持久化提醒

本次改动位于 `vendor/dcat-plus/laravel-admin/`，**`composer update` 会覆盖**。建议效果验证后：
- Fork 该包到私有仓库，`composer.json` 改指向 fork；或
- 用 `cweagans/composer-patches` 等补丁机制管理；或
- 提 PR 上游合并。
