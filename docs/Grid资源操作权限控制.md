# Grid 资源操作权限控制

资源 Grid 默认操作会自动复用现有的 URL 权限，控制以下入口：

- 新增按钮：`GET /资源/create`
- 编辑、快捷编辑：`GET /资源/{id}/edit`
- 删除：`DELETE /资源/{id}`
- 批量删除：复用删除权限 `DELETE /资源/{ids}`
- 详情页右上角的“编辑”和“删除”：分别复用编辑、删除权限

后端 `Permission` 中间件仍然负责最终校验，按钮控制只用于改善界面体验，不能替代后端权限。

## 资源动作隔离

框架生成的资源权限会按 `index`、`show`、`create`、`store`、`edit`、`update`、`destroy` 等动作精确区分。例如 `member-user.show` 即使使用了 `GET /member-user/*` 路径，也不会因为通配符而误授权 `/member-user/create` 或 `/member-user/{id}/edit`。

旧项目中不带标准动作后缀的整体权限（如 `member-user`）继续按原来的路径规则判断，保持向后兼容。

## 配置

在 `config/admin.php` 的 `permission` 中配置：

```php
'resource_actions' => [
    // hide：无权限时不渲染操作；prompt：保留操作，点击时弹框提示。
    'denied' => 'hide',

    // 可以分别关闭某个默认操作的前端权限控制。
    'actions' => [
        'create'       => true,
        'edit'         => true,
        'quick_edit'   => true,
        'delete'       => true,
        'batch_delete' => true,
    ],
],
```

### 隐藏模式

```php
'denied' => 'hide',
```

角色没有“新建”权限时不展示新增按钮；没有编辑、删除权限时不展示相应行操作和详情页右上角工具；没有删除权限时不展示默认批量删除。

### 提示模式

```php
'denied' => 'prompt',
```

操作仍然展示，但不会打开页面或发送删除请求，点击后只弹出无权限提示。

## 性能说明

当 `admin.permission.enable` 为 `true` 时，资源操作权限控制自动生效。Grid 不会为了按钮状态查询权限表，也不会在渲染每一行时扫描 Laravel 路由：

1. 权限中间件和 Grid 共用当前登录用户的 `allPermissions()` 集合。
2. `roles.permissions` 使用预加载，避免按角色逐条查询。
3. 同一请求内按“用户 + HTTP 方法 + 目标路径”缓存判断结果。
4. 编辑、删除即使展示数百行，也只会分别计算一次权限结果。

权限修改后，下一个请求会读取新的角色权限，不存在跨请求长期缓存导致的权限延迟问题。

## 自定义操作

本功能自动控制框架默认的新增、编辑、快捷编辑、删除和批量删除。业务自定义的行操作或批量操作仍应在操作类的 `allowed()`、控制器或中间件中自行校验。
