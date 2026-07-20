# 资源语言包（Lang）结构说明

> 适用于 Dcat Plus Admin 资源控制器、Grid、Form、Show 和角色路由权限界面。

## 一、文件位置与命名

资源语言包存放在 Laravel 的语言目录：

```text
lang/zh_CN/member-user.php
lang/zh_TW/member-user.php
lang/en/member-user.php
```

文件名默认根据 Controller 名称生成：

| Controller | 语言文件 |
|---|---|
| `MemberUserController` | `member-user.php` |
| `OrderItemController` | `order-item.php` |
| `SystemLogController` | `system-log.php` |

角色权限路由扫描会使用同样的规则自动定位语言文件。

扩展包内置资源的默认语言文件位于：

```text
vendor/dcat-plus/laravel-admin/resources/lang/zh_CN/*.php
```

扫描时会先读取扩展包默认值，再使用项目 `lang/zh_CN/*.php` 中的同名配置覆盖。
因此升级后无需立即重新发布语言文件；项目已有的自定义文案仍然拥有最高优先级。

## 二、完整结构

```php
<?php

return [
    // 资源名称。
    'labels' => [
        'MemberUser'  => '用户管理',
        'member-user' => '用户管理',
    ],

    // Grid、Form、Show 字段名称。
    'fields' => [
        'username' => '用户名',
        'phone'    => '手机号码',
        'status'   => '状态',
    ],

    // 字段枚举选项。
    'options' => [
        'status' => [
            0 => '禁用',
            1 => '启用',
        ],
    ],

    // 角色权限页使用的中文说明。
    'permissions' => [
        // 整个资源的名称、说明和业务分组。
        'resource' => [
            'title'       => '用户管理',
            'description' => '管理平台注册用户、账号状态和用户资料',
            'group'       => '用户中心',
        ],

        // Controller 方法的权限名称。
        'actions' => [
            'index'   => '查看用户列表',
            'show'    => '查看用户详情',
            'create'  => '新建用户',
            'store'   => '保存用户',
            'edit'    => '编辑用户',
            'update'  => '更新用户',
            'destroy' => '删除用户',
            'import'  => '导入用户',
            'export'  => '导出用户',

            // 自定义 Controller 方法。
            'resetPassword' => [
                'title'       => '重置用户密码',
                'description' => '为指定用户重新设置登录密码',
                'group'       => '账号安全',
            ],
        ],

        // 按路由名称精确覆盖。
        'routes' => [
            'member-user.statistics' => [
                'title'       => '用户统计',
                'description' => '查看用户注册与活跃数据',
                'group'       => '统计分析',
            ],
        ],
    ],
];
```

## 三、`labels`

`labels` 是资源的中文名称，会用于：

- 页面标题；
- Grid 导出文件名；
- 面包屑；
- 角色权限页的资源卡片标题。

建议同时保留 Controller 名和 slug 两个 key：

```php
'labels' => [
    'MemberUser'  => '用户管理',
    'member-user' => '用户管理',
],
```

只有 `labels`、没有 `permissions` 时，权限扫描仍会显示中文资源名，动作使用“列表、查看、新建、编辑、删除”等框架默认翻译。

## 四、`fields`

`fields` 为字段提供中文标题：

```php
'fields' => [
    'username' => '用户名',
    'phone'    => '手机号码',
],
```

使用：

```php
$grid->column('username');
$form->text('phone');
$show->field('username');
```

未显式传入 label 时，框架会读取语言包中的字段名。

## 五、`options`

`options` 适合状态、类型等枚举值：

```php
'options' => [
    'status' => [
        0 => '禁用',
        1 => '启用',
    ],
],
```

读取：

```php
admin_trans_option(1, 'status'); // 启用
```

## 六、`permissions.resource`

配置资源在角色权限页展示的名称、说明和业务分组：

```php
'permissions' => [
    'resource' => [
        'title'       => '用户管理',
        'description' => '管理平台注册用户和账号状态',
        'group'       => '用户中心',
    ],
],
```

`group` 会把多个资源卡片归入同一个业务分组，并支持分组全选。没有配置时统一显示在“其他资源”。建议面向角色权限分配者编写，不要描述 Controller、Model 等技术实现。

为兼容旧语言包，原来的 `permissions.title` 和 `permissions.description` 仍然可以继续使用；新增项目推荐使用结构更清晰的 `permissions.resource`。

## 七、`permissions.actions`

key 使用 Controller 方法名：

```php
'actions' => [
    'index' => '查看用户列表',

    'resetPassword' => [
        'title'       => '重置用户密码',
        'description' => '为指定用户重新设置密码',
        'group'       => '账号安全',
    ],
],
```

支持字符串简写和完整数组两种格式。

## 八、`permissions.routes`

当同一个 Controller 方法被多条路由复用，或需要按路由覆盖方法说明时，使用路由名称：

```php
'routes' => [
    'member-user.statistics' => '用户统计',
],
```

路由名使用当前 Admin 应用的相对名称，不需要写 `dcat.admin.` 前缀。

## 九、`title`、`description`、`group`

| key | 用途 | 是否必填 |
|---|---|---|
| `title` | 权限中文名称 | 是 |
| `description` | 功能说明 | 否 |
| `group` | 业务分组；`permissions.resource.group` 用于资源分组，动作或单路由中的 `group` 用于单路由分组 | 否 |

说明只影响角色权限界面的展示和搜索，不改变 URI、HTTP 方法和权限校验。

## 十、Scaffold 生成规则

`php artisan admin:scaffold` 新生成的语言包会自带：

```php
'permissions' => [
    'resource' => [
        'title'       => '',
        'description' => '',
        'group'       => '',
    ],
    'actions' => [
        'index'   => '列表',
        'show'    => '查看',
        'create'  => '新建',
        'store'   => '保存',
        'edit'    => '编辑',
        'update'  => '更新',
        'destroy' => '删除',
        'import'  => '导入',
        'export'  => '导出',
    ],
    'routes' => [],
],
```

生成后建议将通用动作名改成具体业务含义，并补充 `description`。

## 十一、多语言

每个 locale 使用相同的数组结构：

```text
lang/zh_CN/member-user.php
lang/zh_TW/member-user.php
lang/en/member-user.php
```

路由权限扫描使用当前后台语言加载对应文案。

## 十二、已有资源补充权限说明

如果 Scaffold 已经生成了语言文件，只需要在原数组中增加 `permissions`：

```php
// lang/zh_CN/admin-operation-log.php
return [
    'labels' => [
        'AdminOperationLog'  => '操作日志',
        'admin-operation-log' => '操作日志',
    ],

    // 原有 fields、options 保持不变。

    'permissions' => [
        'description' => '查看和维护后台管理员的操作记录',
        'actions' => [
            'index'   => '查看操作日志',
            'show'    => '查看日志详情',
            'destroy' => '删除操作日志',
            'export'  => '导出操作日志',
        ],
        'routes' => [],
    ],
];
```

如果尚无对应文件，可以按 Controller 名称新建。例如
`AdminOperationLogController` 对应 `admin-operation-log.php`。
