# admin:scaffold 命令代码生成器

> 适用版本：dcat-plus-admin 内置。用于根据已有数据库表批量生成后台 CRUD 基础代码。

`admin:scaffold` 是面向命令行的代码生成器。它从数据库表结构读取字段信息，批量生成：

- Model
- Controller（后台 CRUD，继承 `AdminController`）
- Lang 语言包
- 后台 Resource Route 路由
- Permission 权限
- Menu 菜单
- （可选）member-api / admin-api 控制器（继承项目里的 `BaseApiController`）+ JsonResource
- （可选）JsonResource（每个 model 一个，`toArray()` 自动填充字段注释）

它不会生成 migration、repository，也不会执行 migrate。代码可以生成到**主项目**，也可以通过 `--extension=` 生成到**扩展包**（命名空间、目录、路由全部按扩展包约定落位）。

新生成的 Lang 文件会同时包含 `labels`、`fields`、`options` 和 `permissions`。
`permissions` 用于在角色编辑页展示资源路由的中文名称和功能说明。

详细配置见：

- [资源语言包（Lang）结构说明](./资源语言包-lang-结构说明.md)
- [路由权限中文说明开发指南](./路由权限中文说明开发指南.md)

---

## 一、命令总览

```bash
php artisan admin:scaffold [选项]
```

查看帮助：

```bash
php artisan admin:scaffold --help
```

可用选项：

| 选项 | 默认值 | 说明 |
|------|--------|------|
| `--connection=` | `database.default` | 数据库连接名，对应 `config/database.php` 中的连接 key |
| `--controller-namespace=` | `config('admin.route.namespace')` | 控制器命名空间，也决定控制器文件生成位置 |
| `--model-namespace=` | `App\Models` | 模型命名空间，也决定模型文件生成位置 |
| `--table=` | 全部表 | 指定要生成的表，多个表用英文逗号分隔 |
| `--force` | 否 | 覆盖已存在的 Model、Controller、Lang 文件 |
| `--menu-parent=` | `0` | 生成菜单的父级菜单 ID |
| `--menu-icon=` | `fa-file-text-o` | 生成菜单的图标 |
| `--role=` | `1` | 自动绑定菜单和权限到指定角色；传 `0` 表示不绑定角色 |
| `--api` | 否 | 为每个表生成 member-api（C 端）控制器 + JsonResource，路由写入 `app/Api/routes.php` |
| `--admin-api` | 否 | 为每个表生成 admin-api（管理端）控制器 + JsonResource，路由写入 `app/Admin/Api/routes.php` |
| `--resource` | 否 | 为每个 model 生成一个 JsonResource 类（`--api`/`--admin-api` 已隐含包含） |
| `--extension=` | （主项目） | 代码生成到指定扩展包（`vendor/name`），命名空间/目录/路由按扩展包约定；见下方「扩展包模式」 |

---

## 二、支持的数据库

命令通过 Laravel 数据库连接读取表结构，支持以下 driver：

| 数据库 | Laravel driver | 支持内容 |
|--------|----------------|----------|
| MySQL | `mysql` | 表列表、字段、主键、唯一索引、默认值、字段注释 |
| MariaDB | `mysql` 或 `mariadb` | 表列表、字段、主键、唯一索引、默认值、字段注释 |
| PostgreSQL | `pgsql` | 表列表、字段、主键、唯一索引、默认值、字段注释 |
| SQL Server | `sqlsrv` | 表列表、字段、主键、唯一索引、默认值、字段注释 |
| SQLite | `sqlite` | 表列表、字段、主键、唯一索引、默认值；SQLite 无原生字段注释 |

只要连接在 `config/database.php` 中配置完成，就可以通过 `--connection` 指定：

```bash
php artisan admin:scaffold --connection=pgsql --table=users,orders
```

```bash
php artisan admin:scaffold --connection=sqlsrv --table=users,orders
```

```bash
php artisan admin:scaffold --connection=mariadb --table=users,orders
```

### PostgreSQL schema

PostgreSQL 默认读取连接配置中的 `schema`，没有配置时使用 `public`：

```php
'pgsql' => [
    'driver' => 'pgsql',
    // ...
    'schema' => 'public',
],
```

也可以在表名中显式传 schema：

```bash
php artisan admin:scaffold --connection=pgsql --table=public.users,public.orders
```

### SQL Server schema

SQL Server 默认读取连接配置中的 `schema`，没有配置时使用 `dbo`：

```php
'sqlsrv' => [
    'driver' => 'sqlsrv',
    // ...
    'schema' => 'dbo',
],
```

也可以在表名中显式传 schema：

```bash
php artisan admin:scaffold --connection=sqlsrv --table=dbo.users,dbo.orders
```

---

## 三、最简单用法

使用默认数据库连接、默认后台控制器命名空间、默认模型命名空间，并为所有表生成代码：

```bash
php artisan admin:scaffold
```

默认情况下：

- 数据库连接：`config('database.default')`
- Controller 命名空间：`config('admin.route.namespace')`
- Model 命名空间：`App\Models`
- 表范围：当前连接下全部数据表

---

## 四、指定数据库连接

如果项目里配置了多个数据库连接，可以通过 `--connection` 指定：

```bash
php artisan admin:scaffold --connection=mysql
```

示例：生成 `tenant` 连接下的所有表：

```bash
php artisan admin:scaffold --connection=tenant
```

当显式传入 `--connection` 时，生成的 Model 会自动写入连接名：

```php
protected $connection = 'tenant';
```

如果不传 `--connection`，Model 不会写入 `$connection`，继续使用 Laravel 默认数据库连接。

---

## 五、指定控制器命名空间

通过 `--controller-namespace` 指定 Controller 生成到哪里：

```bash
php artisan admin:scaffold \
  --controller-namespace="App\\Admin\\Controllers"
```

生成示例：

| 表名 | 控制器类 |
|------|----------|
| `users` | `App\Admin\Controllers\UserController` |
| `orders` | `App\Admin\Controllers\OrderController` |
| `order_items` | `App\Admin\Controllers\OrderItemController` |

如果 Composer 的 PSR-4 配置是 Laravel 默认的 `App\\ => app/`，文件会生成到：

```text
app/Admin/Controllers/UserController.php
app/Admin/Controllers/OrderController.php
app/Admin/Controllers/OrderItemController.php
```

---

## 六、指定 Model 命名空间

通过 `--model-namespace` 指定 Model 生成到哪里：

```bash
php artisan admin:scaffold \
  --model-namespace="App\\Models"
```

也可以放到业务模块目录：

```bash
php artisan admin:scaffold \
  --model-namespace="App\\Tenant\\Models"
```

生成示例：

| 表名 | Model 类 |
|------|----------|
| `users` | `App\Models\User` |
| `orders` | `App\Models\Order` |
| `order_items` | `App\Models\OrderItem` |

---

## 七、指定要生成的表

多个表使用英文逗号分隔：

```bash
php artisan admin:scaffold --table=users,orders,order_items
```

### 不指定表：生成全部表

```bash
php artisan admin:scaffold --connection=mysql
```

如果数据库连接配置了 table prefix，命令会自动处理前缀。比如真实表名是 `pre_users`，传入 `users` 即可。

---

## 八、指定连接 + 命名空间 + 表

这是最常用的完整写法：

```bash
php artisan admin:scaffold \
  --connection=mysql \
  --controller-namespace="App\\Admin\\Controllers" \
  --model-namespace="App\\Models" \
  --table=users,orders
```

多租户示例：

```bash
php artisan admin:scaffold \
  --connection=tenant \
  --controller-namespace="App\\Admin\\Tenant\\Controllers" \
  --model-namespace="App\\Tenant\\Models" \
  --table=products,product_categories
```

---

## 九、覆盖已存在文件

默认情况下，如果 Model 或 Controller 已存在，命令会报错并跳过该表，避免覆盖手写代码。

确认要重新生成时使用 `--force`：

```bash
php artisan admin:scaffold \
  --table=users \
  --force
```

`--force` 会覆盖：

- Model 文件
- Controller 文件
- Lang 语言包文件
- JsonResource 文件（当指定 `--resource`/`--api`/`--admin-api` 时）
- member-api / admin-api 控制器文件（当指定 `--api`/`--admin-api` 时）

菜单和权限使用 `firstOrNew` / `updateOrCreate` 方式写入，不会重复创建同一个 URI/权限。

---

## 十、后台路由

每个成功生成的 Controller 都会自动写入 `app/Admin/routes.php` 的最后一个后台路由组：

```php
$router->resource('order', \App\Admin\Controllers\OrderController::class);
```

- 路由 URI 与生成菜单 URI 保持一致。
- 使用控制器完整类名，因此 `--controller-namespace` 自定义命名空间也可以正常工作。
- 如果同一路径的 `$router->resource()` 已存在，命令会跳过写入，不会重复添加。
- 路由文件必须存在且可写；如果无法写入，命令会在结果表中标记该表生成失败。

## 十一、菜单和权限

命令会根据表名自动生成菜单 URI 和权限。

| 表名 | URI | Permission slug | Permission http_path |
|------|-----|-----------------|----------------------|
| `users` | `user` | `user` | `/user/*` |
| `orders` | `order` | `order` | `/order/*` |
| `order_items` | `order-item` | `order-item` | `/order-item/*` |

默认菜单参数：

```bash
--menu-parent=0
--menu-icon=fa-file-text-o
--role=1
```

指定父级菜单：

```bash
php artisan admin:scaffold \
  --table=orders \
  --menu-parent=12
```

指定菜单图标：

```bash
php artisan admin:scaffold \
  --table=orders \
  --menu-icon="fa-shopping-cart"
```

绑定到其他角色：

```bash
php artisan admin:scaffold \
  --table=orders \
  --role=2
```

只创建菜单和权限，不绑定角色：

```bash
php artisan admin:scaffold \
  --table=orders \
  --role=0
```

---

## 十二、生成 API 控制器与 JsonResource

除了后台 CRUD，命令还能为每张表生成接口层的 API 控制器和 Resource 类。三个开关任一开启都会生成对应的 JsonResource（`--api`/`--admin-api` 已隐含 `--resource`）。

| 开关 | 生成内容 | 控制器基类 | 命名空间（主项目） | 路由文件 |
|------|----------|------------|-------------------|----------|
| `--api` | member-api 控制器 + Resource | `App\Api\Controllers\BaseApiController` | `App\Api\Controllers` | `app/Api/routes.php` |
| `--admin-api` | admin-api 控制器 + Resource | `App\Admin\Api\Controllers\BaseApiController` | `App\Admin\Api\Controllers` | `app/Admin/Api/routes.php` |
| `--resource` | 仅 Resource | — | `App\Http\Resources` | — |

生成的 API 控制器完整继承项目的 `BaseApiController`，自带列表/详情/增/删/批量更新/批量删除，admin-api 还包含导入模板下载/导入/导出/字段元数据接口；`getValidationRules()` 已留好空实现待业务补充。生成的 Resource 类 `toArray()` 会按表字段自动填好 `/** 字段注释 @var 类型 */` 注释，供 Scramble 生成接口文档。

### 主项目用法

```bash
# 同时生成后台 CRUD + member-api + admin-api + Resource
php artisan admin:scaffold \
  --table=orders \
  --api --admin-api --resource
```

生成产物（表 `orders`）：

```text
app/Models/Order.php
app/Admin/Controllers/OrderController.php                 # 后台 CRUD
app/Http/Resources/OrderResource.php                      # JsonResource
app/Api/Controllers/OrderController.php                   # member-api
app/Admin/Api/Controllers/OrderController.php             # admin-api
lang/zh_CN/order.php
```

路由自动写入：

```text
app/Admin/routes.php        -> $router->resource('order', OrderController::class)
app/Api/routes.php          -> $router->apiResource('/order', ...) + 批量/导入导出辅助端点
app/Admin/Api/routes.php    -> $router->apiResource('/order', ...) + 批量/导入导出辅助端点
```

> member-api 控制器继承 `App\Api\Controllers\BaseApiController`，admin-api 控制器继承 `App\Admin\Api\Controllers\BaseApiController`。两者均有抽象方法 `getValidationRules(string $action): array`，生成后请按业务补全校验规则。

---

## 十三、扩展包模式（--extension）

传入 `--extension=vendor/name` 后，所有代码都生成到指定扩展包（`dcat-admin-extensions/<vendor>/<name>/`），命名空间、目录、路由全部按扩展包约定落位，不再写主项目。

```bash
php artisan admin:scaffold \
  --table=orders \
  --api --admin-api --resource \
  --extension=acme/shop
```

命名空间与目录约定（以扩展根命名空间 `Acme\Shop` 为例）：

| 产物 | 命名空间 | 文件 |
|------|----------|------|
| Model | `Acme\Shop\Models` | `src/Models/Order.php` |
| 后台 Controller | `Acme\Shop\Http\Controllers` | `src/Http/Controllers/OrderController.php` |
| Resource | `Acme\Shop\Http\Resources` | `src/Http/Resources/OrderResource.php` |
| member-api | `Acme\Shop\Http\Api\Controllers` | `src/Http/Api/Controllers/OrderController.php` |
| admin-api | `Acme\Shop\Http\AdminApi\Controllers` | `src/Http/AdminApi/Controllers/OrderController.php` |

路由写入扩展包自己的路由文件：

```text
src/Http/routes.php              -> Route::resource('order', OrderController::class)
src/Http/Api/routes.php          -> Route::apiResource('order', ...) + 辅助端点
src/Http/AdminApi/routes.php     -> Route::apiResource('order', ...) + 辅助端点
```

扩展包模式的关键点：

1. **根命名空间**优先读扩展包 `composer.json` 的 `psr-4`；缺失时按 `vendor/name` 推断（`acme/shop` → `Acme\Shop`）。
2. **扩展包必须已存在**（先用 `admin:ext-make-pro` 创建骨架）。命令只生成代码，不建包。
3. member-api / admin-api 控制器继承的仍是主项目的 `BaseApiController`（`App\Api\Controllers\...` / `App\Admin\Api\Controllers\...`），因为基类是项目全局的。
4. 后台路由、API 路由都用 `Route::` facade 风格 + 完整类名追加到扩展包路由文件末尾，与扩展包现有约定一致。
5. 菜单和权限仍写入主项目的后台菜单/权限表（扩展包的菜单通常由 `ext-install` 注册，这里直接写入可立即在后台看到）。

### 类名与表名前缀

命令按 `Str::singular(Str::studly($table))` 生成类名。如果扩展包的表带统一前缀（如 `shop_orders`），默认会得到 `ShopOrder`。若希望类名是 `Order`，可显式指定命名空间下类名无法直接改类名，建议在建表阶段就用无前缀的逻辑表名，或生成后手动重命名类（同时改 `$table`）。表名前缀约定见扩展包 `docs/database/schema.md`。

---

## 十四、生成规则说明

### 类名规则

表名会先转单数，再转 StudlyCase：

| 表名 | 类名前缀 |
|------|----------|
| `users` | `User` |
| `orders` | `Order` |
| `order_items` | `OrderItem` |

最终生成：

- `{model-namespace}\User`
- `{controller-namespace}\UserController`

### 字段规则

命令会读取表字段、字段注释、主键、索引、默认值、是否可空。

生成 Grid/Form/Show 时会排除：

- 主键
- `created_at`
- `updated_at`
- `deleted_at`

Model 的 fillable 注释中还会排除：

- `id`
- `created_at`
- `updated_at`
- `deleted_at`
- `password`

### 时间字段

如果表里同时存在：

```text
created_at
updated_at
```

Controller 会生成对应的展示字段，Model 保持 Laravel 默认 timestamps。

如果不存在这两个字段，Model 会生成：

```php
public $timestamps = false;
```

### 软删除字段

如果表里存在：

```text
deleted_at
```

Model 会自动引入并使用 `SoftDeletes`。

---

## 十五、常见场景

### 场景一：给默认库全部表生成后台代码

```bash
php artisan admin:scaffold
```

### 场景二：只生成两个表

```bash
php artisan admin:scaffold --table=users,orders
```

### 场景三：从租户库生成到租户后台目录

```bash
php artisan admin:scaffold \
  --connection=tenant \
  --controller-namespace="App\\Admin\\Tenant\\Controllers" \
  --model-namespace="App\\Tenant\\Models"
```

### 场景四：重新生成某张表

```bash
php artisan admin:scaffold \
  --table=orders \
  --force
```

### 场景五：生成到父级菜单下

```bash
php artisan admin:scaffold \
  --table=orders \
  --menu-parent=8 \
  --menu-icon="fa-list"
```

### 场景六：只生成代码和菜单权限，不绑定角色

```bash
php artisan admin:scaffold \
  --table=orders \
  --role=0
```

### 场景七：同时生成后台 CRUD 与 member-api/admin-api

```bash
php artisan admin:scaffold \
  --table=orders \
  --api --admin-api
```

### 场景八：生成代码到扩展包

```bash
# 先确保扩展包已由 admin:ext-make-pro 创建
php artisan admin:scaffold \
  --table=orders,order_items \
  --extension=acme/shop
```

### 场景九：扩展包全套（后台 + 双端 API + Resource）

```bash
php artisan admin:scaffold \
  --table=orders \
  --api --admin-api --resource \
  --extension=acme/shop
```

---

## 十六、注意事项

1. 运行前确保目标数据库连接能正常访问。
2. 不传 `--table` 会处理当前连接下全部表，首次使用建议先指定少量表验证结果。
3. `--force` 会覆盖已存在文件，使用前请确认已有代码不需要保留。
4. 命令会写入后台菜单和权限表，请确保已执行 `php artisan admin:install`。
5. 命令会自动写入 `app/Admin/routes.php`；如果文件不存在、不可写或无法定位最后一个后台路由组，当前表会生成失败并显示错误。
6. 菜单 URI、资源路由和权限路径根据表名单数生成，如 `orders` 生成 `order`。
7. `--api` / `--admin-api` 生成的 API 控制器依赖主项目里的 `BaseApiController`（`App\Api\Controllers\...` / `App\Admin\Api\Controllers\...`），若项目未提供该基类会生成后报错，请确认基类存在。
8. API 控制器的 `getValidationRules()` 默认是空实现，生成后务必按业务补全校验规则，否则 `store`/`update` 不做任何字段约束。
9. `--extension=` 模式下扩展包必须已存在；命令只往 `src/` 下写代码和路由，不会创建扩展包骨架。扩展包的根命名空间从其 `composer.json` 的 `psr-4` 读取。
10. 扩展包模式的菜单/权限仍写入主项目后台表；若希望由扩展包自行注册菜单，可加 `--role=0` 并在扩展 `settingForm()` 或 `ext-install` 里配置。
