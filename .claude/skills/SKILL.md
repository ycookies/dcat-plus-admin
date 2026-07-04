---
name: dcat-plus-admin
version: 1.0.0
description: dcat-plus-admin (dcat-plus/laravel-admin) Laravel 后台框架开发技能 — 安装、CRUD、表单扩展、Grid、API、扩展开发、配置等全流程
category: web-development
triggers:
  - dcat-plus
  - dcat-admin
  - dcat plus admin
  - laravel admin
  - 后台管理
  - admin panel
---

# dcat-plus-admin 开发技能

## 概述

**dcat-plus-admin** (Composer: `dcat-plus/laravel-admin`) 是原 dcat/laravel-admin 的社区维护分支，由杨光(ycookies)持续维护。原版停更后 fork 而来，新增 JWT API、会员系统、SKU 字段、动态表单、OpenAPI 文档等功能，兼容 Laravel 8~12。

- 命名空间仍为 `Dcat\Admin\`，向后兼容
- 技术栈: Laravel + AdminLTE3 + Bootstrap4 + jQuery3 + PJAX
- PHP >= 7.4, 推荐 PHP 8.2 + Laravel 10
- 官方文档 http://docs.dcat-admin.com 已过时，**以代码为准**
- GitHub: https://github.com/ycookies/dcat-plus-admin

---

## 一、安装

```bash
# 1. 新建 Laravel 项目
composer create-project --prefer-dist laravel/laravel myproject 10.*

# 2. 配置 .env 数据库
# DB_CONNECTION=mysql / DB_DATABASE=...

# 3. 安装 dcat-plus-admin
composer require dcat-plus/laravel-admin

# 4. 发布资源与配置
php artisan admin:publish

# 5. 运行安装（自动迁移、种子、生成 app/Admin 目录、API 脚手架）
php artisan admin:install
# 安装命令会自动:
#   - 迁移 7 张表 (admin_users, admin_roles, admin_permissions, admin_menu, admin_settings, admin_extensions, sku_attribute, member_users, member_oauth)
#   - 创建 app/Admin/ 控制器目录
#   - 创建 app/Admin/Api/Controllers/ (JWT API 控制器)
#   - 创建 app/Api/Controllers/ (会员 API 控制器)
#   - 创建 Models: AdminUser.php, MemberUser.php, MemberOauth.php
#   - 创建中间件: AdminApiAuth.php, MemberApiAuth.php
#   - 发布 JWT 配置并生成密钥
#   - 发布并配置 Scramble (OpenAPI)
#   - 设置 locale 为 zh_CN

# 6. 权限
chmod -R 775 storage/ bootstrap/cache/ lang/
chmod -R 755 public/

# 7. 访问 http://localhost/admin (默认 admin/admin)
```

---

## 二、目录结构

```
app/Admin/
├── Controllers/        # 后台控制器
│   ├── AuthController.php
│   ├── HomeController.php
│   └── ...
├── Api/Controllers/    # Admin JWT API 控制器 (新增)
│   ├── BaseApiController.php
│   ├── AuthController.php
│   ├── UserController.php
│   ├── MenuController.php
│   ├── PermissionController.php
│   ├── RoleController.php
│   └── SettingsController.php
├── routes.php          # 后台路由
└── bootstrap.php       # 后台启动钩子

app/Api/Controllers/    # 会员 JWT API 控制器 (新增)
├── MemberBaseApiController.php
├── MemberAuthController.php
└── MemberUserController.php

app/Models/
├── AdminUser.php
├── MemberUser.php      # 会员模型 (新增)
└── MemberOauth.php     # 会员OAuth模型 (新增)
```

---

## 三、CRUD 控制器开发模式

### 3.1 基础 CRUD 控制器

```php
<?php
namespace App\Admin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use App\Models\Article;

class ArticleController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new Article(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('title', '标题');
            $grid->column('category.name', '分类');
            $grid->column('status', '状态')->using([0 => '禁用', 1 => '启用']);
            $grid->column('created_at', '创建时间');

            $grid->quickSearch('title', 'id');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('title');
                $filter->between('created_at', '创建时间')->datetime();
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new Article(), function (Show $show) {
            $show->field('id');
            $show->field('title');
            $show->field('content');
            $show->field('status')->using([0 => '禁用', 1 => '启用']);
            $show->field('created_at');
        });
    }

    protected function form()
    {
        return Form::make(new Article(), function (Form $form) {
            $form->text('title', '标题')->required();
            $form->select('category_id', '分类')->options(\App\Models\Category::pluck('name', 'id'));
            $form->editor('content', '内容');
            $form->switch('status', '状态');
        });
    }
}
```

### 3.2 路由注册

```php
// app/Admin/routes.php
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {
    $router->get('/', 'HomeController@index');
    $router->resource('articles', 'ArticleController');
});
```

---

## 四、Grid（数据表格）详解

### 4.1 常用列类型

```php
$grid->column('id', 'ID')->sortable();
$grid->column('title', '标题')->limit(30);
$grid->column('avatar', '头像')->image('', 40, 40);
$grid->column('price', '价格')->display(function($v) {
    return '¥' . number_format($v, 2);
});
$grid->column('status', '状态')->using([0 => '禁用', 1 => '启用'])->label([
    0 => 'danger', 1 => 'success'
]);
$grid->column('tags', '标签')->pluck('name')->label();
$grid->column('is_recommend', '推荐')->bool();
$grid->column('progress', '进度')->progressBar();
$grid->column('email', '邮箱')->editable();
```

### 4.2 筛选器

```php
$grid->filter(function (Grid\Filter $filter) {
    $filter->panel();
    $filter->equal('id', 'ID')->width(3);
    $filter->like('title', '标题')->width(3);
    $filter->equal('status', '状态')->select([0 => '禁用', 1 => '启用'])->width(3);
    $filter->between('created_at', '创建时间')->datetime()->width(4);

    $filter->where(function ($query) {
        $query->whereHas('category', function ($q) {
            $q->where('name', 'like', "%{$this->input}%");
        });
    }, '分类名称');
});
```

### 4.3 批量操作与行操作

```php
$grid->batchActions([new \Dcat\Admin\Grid\Tools\BatchDelete()]);

$grid->actions(function (Grid\Displayers\Actions $actions) {
    $actions->disableView();
    $actions->append('<a href="/admin/articles/'.$actions->row->id.'/copy">复制</a>');
});

$grid->actions([new \Dcat\Admin\Grid\Actions\QuickEditButton()]);
```

### 4.4 导出与导入

```php
$grid->export(['id' => 'ID', 'title' => '标题', 'status' => '状态']);
$grid->import();
```

---

## 五、Form（表单）详解

### 5.1 常用字段类型

```php
// 基础字段
$form->text('name', '名称')->required();
$form->textarea('description', '描述');
$form->number('sort', '排序')->default(0);
$form->email('email', '邮箱');
$form->password('password', '密码');
$form->url('website', '网址');
$form->mobile('phone', '手机号');
$form->color('color', '颜色');

// 选择类
$form->select('category_id', '分类')->options(Category::pluck('name', 'id'));
$form->multipleSelect('tags', '标签')->options(Tag::pluck('name', 'id'));
$form->radio('gender', '性别')->options([0 => '女', 1 => '男'])->default(1);
$form->checkbox('features', '特性')->options([...]);
$form->switch('is_active', '启用');

// 时间类
$form->date('birthday', '生日');
$form->datetime('published_at', '发布时间');
$form->dateRange('start_date', 'end_date', '日期范围');

// 编辑器
$form->editor('content', '内容');          // TinyMCE
$form->markdown('content', '内容');         // Editor.md
$form->wangEditor('content', '内容');       // WangEditor (新增)

// 文件上传
$form->image('cover', '封面')->disk('public')->dir('covers');
$form->multipleImage('images', '图片集');
$form->file('attachment', '附件');

// 其他
$form->display('created_at', '创建时间');
$form->hidden('token');
$form->divider('分割线');
$form->html('<div>自定义HTML</div>', '标题');
```

### 5.2 表单布局

```php
// Tab 布局
$form->tab('基本信息', function (Form $form) {
    $form->text('name');
})->tab('详情', function (Form $form) {
    $form->editor('content');
});

// Row 布局
$form->row(function (Form\Row $form) {
    $form->width(6)->text('name');
    $form->width(6)->text('slug');
});

// 分步骤表单
$form->multipleSteps(function (Form\Step $step) {
    $step->title('基本信息')->input('name')->input('email');
})->form(function (Form $form) {
    $form->editor('content');
});
```

### 5.3 表单事件与回调

```php
$form->saving(function (Form $form) {
    if ($form->password) {
        $form->password = bcrypt($form->password);
    } else {
        $form->deleteInput('password');
    }
});

$form->saved(function (Form $form) {
    // $form->model() / $form->key()
});

$form->deleting(function (Form $form) {
    // 返回非空字符串可阻止删除
});
```

### 5.4 关联关系

```php
// 一对一
$form->display('profile.phone', '手机号');

// 一对多 (hasMany)
$form->hasMany('skus', 'SKU列表', function (Form\NestedForm $table) {
    $table->text('name', '名称');
    $table->number('price', '价格');
    $table->number('stock', '库存');
});

// 多对多
$form->multipleSelect('roles', '角色')->options(Role::all()->pluck('name', 'id'));
```

---

## 六、dcat-plus 新增表单扩展

### 6.1 SKU 规格字段

```php
$form->sku('sku_data', '商品规格');
// 迁移自动创建 sku_attribute 表
// 字段: attr_name, attr_type(radio/checkbox), attr_value(JSON), sort
```

### 6.2 动态表单 (DiyForm)

```php
$form->diyForm('form_config', '表单配置');
// 支持组件: input, textarea, radio, checkbox, select, upload-image, upload-video
// 可自定义组件类型、主题颜色
```

### 6.3 媒体字段 (FormMedia)

```php
$form->iconimg('icon', '图标');
$form->photo('avatar', '头像');
$form->photos('images', '图片集');
$form->video('video_url', '视频');
$form->audio('audio_url', '音频');
$form->files('attachments', '附件集');
```

### 6.4 省市区选择 (Distpicker)

```php
$form->distpicker('province', 'city', 'district', '地区');
$grid->column('province')->distpicker();
$filter->distpicker('province', 'city', 'district', '地区');
```

---

## 七、Show（详情页）

```php
protected function detail($id)
{
    return Show::make($id, new Article(), function (Show $show) {
        $show->field('id');
        $show->field('title', '标题');
        $show->field('content', '内容')->unescape();
        $show->field('status', '状态')->using([0 => '禁用', 1 => '启用']);

        // 关联
        $show->relation('comments', function ($model) {
            return Grid::make($model->comments(), function (Grid $grid) {
                $grid->column('content');
                $grid->column('created_at');
            });
        });
    });
}
```

---

## 八、Widget（小组件）

```php
// 统计卡片
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Widgets\Stat;
$stat = Stat::make(Card::make('用户数', User::count())->icon('feather icon-users'));

// 数据表格卡片
use Dcat\Admin\Widgets\Table;

// 新增 Widget
use Dcat\Admin\Widgets\CoverCard;       // 封面卡片
use Dcat\Admin\Widgets\MediaList;       // 媒体对象列表
use Dcat\Admin\Widgets\Linkbox;         // 分组链接卡片
use Dcat\Admin\Widgets\MiniProgramBox;  // 小程序展示盒

// 自定义页面
public function index(Content $content)
{
    return $content->title('标题')->description('描述')->body(Card::make('Hello World'));
}
```

---

## 九、JWT API 系统（新增）

### 9.1 API 路由

- Admin API: `admin-api/` 前缀, AdminApiAuth 中间件
- Member API: `member-api/` 前缀, MemberApiAuth 中间件

### 9.2 JWT 认证流程

```bash
# 登录
POST /admin-api/auth/login
Body: { "username": "admin", "password": "admin" }
Response: { "token": "eyJ..." }

# 带 token 访问
GET /admin-api/user
Header: Authorization: Bearer eyJ...

# 刷新 token
POST /admin-api/auth/refresh

# 会员 API 同理
POST /member-api/auth/login
POST /member-api/auth/register
```

### 9.3 自定义 API 控制器

```php
class ProductApiController extends BaseApiController
{
    public function index()
    {
        $products = \App\Models\Product::paginate(15);
        return $this->response->success($products);
    }
}
```

### 9.4 OpenAPI 文档

- Admin API: `/docs/admin-api`
- Member API: `/docs/member-api`

---

## 十、会员系统（新增）

- `member_users` 表: username, phone, email, password, avatar(3种尺寸), vip, balance, freeze_price, parent_id, group_id
- `member_oauth` 表: type, open_id, nick, avatar (第三方登录绑定)
- 模型: `App\Models\MemberUser`, `App\Models\MemberOauth`

---

## 十一、扩展开发

```bash
# 基础扩展
php artisan admin:ext-make-pro vendor/package-name

# 带 API 的扩展
php artisan admin:ext-make-pro vendor/package-name --api

# 主题扩展
php artisan admin:ext-make-pro vendor/package-name --theme

# 附带元数据
php artisan admin:ext-make-pro vendor/package-name \
    --plugin_name="我的插件" \
    --plugin_desc="插件描述" \
    --authors_name="作者名" \
    --authors_email="email@example.com"
```

### 注册自定义字段

```php
Admin::booting(function () {
    Form::extend('myField', \MyExt\Form\Field\MyField::class);
});

\Dcat\Admin\Grid\Column::extend('myDisplayer', \MyExt\Grid\Displayer\MyDisplayer::class);
```

---

## 十二、配置系统

### 设置读写

```php
admin_setting('site.name');                          // 读取
admin_setting('site.name', '我的网站');               // 写入
admin_setting_group('sys_setting', ['key' => 'val']); // 分组写入
admin_setting_group('sys_setting');                   // 分组读取
admin_setting_multiple(['site.name', 'site.logo']);   // 多键读取
```

### 关键配置 (config/admin.php)

```php
'route'       => ['prefix' => 'admin'],
'database'    => ['connection' => '', 'users_table' => 'admin_users', ...],
'upload'      => ['disk' => 'public'],
'layout'      => ['color' => 'blue', 'sidebar_collapsed' => false],
'multi_app'   => [],
```

---

## 十三、Artisan 命令

```bash
php artisan admin:install              # 安装
php artisan admin:publish              # 发布资源
php artisan admin:create               # 创建控制器+模型
php artisan admin:menu                 # 生成菜单缓存
php artisan admin:app {name}           # 创建子应用
php artisan admin:ext-make-pro {name}  # 创建扩展(--api/--theme)
php artisan admin:export-seed          # 导出种子
php artisan admin:import-seed          # 导入种子
php artisan admin:minify               # 压缩前端资源
```

---

## 十四、与原版 dcat-admin 的主要差异

1. 包名: `dcat-plus/laravel-admin` (原: `dcat/laravel-admin`)
2. 命名空间: 仍为 `Dcat\Admin\`，完全兼容
3. 新增依赖: tymon/jwt-auth, dedoc/scramble, maatwebsite/excel
4. JWT API: 内置 admin-api + member-api 双认证体系
5. 会员系统: 全新 member_users / member_oauth
6. 设置分组: admin_setting_group() 带 group_name
7. 内置扩展: Distpicker, FormMedia, DiyForm, SKU 直接打包
8. WebConfig: 预构建配置页面(站点/短信/邮件/微信/OSS)
9. OpenAPI: 自动注册 /docs/admin-api 和 /docs/member-api
10. Octane 支持 + Laravel 8~12 兼容
11. 文档过时，以代码为准

---

## 十五、常见问题与陷阱

1. 命名空间不变(`Dcat\Admin\`)是有意的向后兼容设计
2. `admin:install` 自动生成 JWT API 脚手架，不需要可删除
3. Scramble 文档 404 请检查 `config/scramble.php`
4. 确保已执行 `php artisan storage:link`
5. 权限缓存: `php artisan cache:clear`
6. 扩展目录默认 `app/Admin/Extensions/`
7. 支持多数据库: MySQL/MariaDB/SQLite/PostgreSQL/SQL Server
8. Octane 需确保 `FlushAdminState` 监听器已注册
9. PJAX 异常时可 `Dcat.disablePjax()`
10. Laravel 11+ 安装自动检测并兼容处理

---

## 深度参考文件

本技能包含 3 个深度参考文件，覆盖完整 API 目录:

- **references/form-fields.md** -- 63 个表单字段完整目录(方法签名/关键方法/分类/级联/联动/事件/步骤表单)
- **references/grid-system.md** -- Grid 完整 API(列管理/30+显示器/21种筛选器/动作/工具/事件/导出导入)
- **references/infrastructure.md** -- 基础设施(23个Artisan命令/50+Helper函数/9个中间件/40+资源别名/4个主题/30+Widget/扩展系统/Action系统/Repository/模型/配置全表/已知Bug)
- **references/extension-development.md** -- 扩展开发完整指南(创建/生命周期/版本管理/菜单/路由/中间件/视图/资源/配置/自定义字段/自定义动作/完整示例/发布/陷阱)