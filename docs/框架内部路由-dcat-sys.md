# 框架内部路由（dcat-sys）

## 目标

框架内部支撑接口不再作为普通业务路由进入角色权限扫描。统一使用当前后台面板前缀下的 `dcat-sys`：

```text
/admin/dcat-sys/*
/seller/dcat-sys/*
```

`dcat-api/*` 保持原有地址和处理机制。

## 安全模型

`dcat-sys` 只代表“内部路由”，不代表无条件放行。路由必须声明 `admin.internal` 策略：

| 策略 | 用途 |
|---|---|
| `authenticated` | 所有已登录后台用户可使用，例如当前用户通知和偏好 |
| `signed,<scope>` | 页面渲染时签发短期 Token，例如媒体、SKU 上传 |
| `administrator` | 仅超级管理员，例如全局布局和清缓存 |
| `capability,<slug>` | 预留给友好的系统能力权限 |

普通权限中间件只有在 `dcat_route_type=internal` 与 `admin.internal:*` 同时存在时，才会把请求交给内部策略中间件；缺少策略时会继续执行普通 RBAC，避免扩展路由因配置遗漏被误放行。角色编辑器仍根据该元数据排除内部路由。

## 当前路由

```text
dcat-sys/notifications/*       authenticated
dcat-sys/preferences/save     authenticated
dcat-sys/media/*              signed: media.read / media.write
dcat-sys/sku/*                signed: sku.write
dcat-sys/cache/clear          administrator
dcat-sys/layout/save          administrator
dcat-sys/iframe-tabs          authenticated
```

## 短期 Token

`Dcat\Admin\Support\InternalRouteToken` 生成的 Token 绑定：

- 当前后台用户 ID；
- 当前 Admin 应用；
- 能力 scope；
- 过期时间；
- 字段声明的磁盘、根目录等 claims。

默认有效期由 `admin.permission.internal.token_ttl` 配置，默认 3600 秒。FormMedia 和 SKU 字段只在页面成功渲染后产生 Token，因此角色不需要配置内部上传 URL，但仍必须先拥有来源业务页面权限。

## 旧地址兼容

以下旧地址继续注册一个兼容周期：

```text
layout-config/save
clear-cache
api/notifications/*
lake-form-media/*
sku-image-*
iframe-tabs
```

- 旧通知和 iframe 入口属于低风险用户能力，继续通过内部认证策略访问。
- 旧媒体、SKU、全局布局和清缓存地址标记为 `internal_legacy`，不会出现在新角色编辑器中，但也不会被自动放行；已有精确旧权限仍可继续工作。
- 新版框架字段和导航栏全部使用 `dcat-sys` 地址。

## 注册新的内部路由

```php
$router->post('dcat-sys/example', ExampleController::class.'@handle')
    ->middleware('admin.internal:signed,example.write')
    ->defaults('dcat_route_type', 'internal')
    ->name('dcat-sys.example');
```

不要仅把路径加入 `admin.permission.except`。涉及上传、删除、全局设置或系统维护的接口必须选择 `signed`、`administrator` 或 `capability` 策略。

## 已知边界

- SKU 图片仍使用共享 `sku/` 目录；Token 阻止无页面权限的账号调用接口，但文件级所有权需要业务模型进一步约束。
- 自定义 FormMedia URL 不会被框架自动改写，扩展开发者需要自行提供等价授权。
- `dcat-api` 继续独立审计；Form 上传和删除分支已补充 `passesAuthorization()` 检查。
