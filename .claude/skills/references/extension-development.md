# dcat-plus-admin 扩展/插件开发完整指南

## 一、快速创建扩展

### 1.1 基础扩展

```bash
php artisan admin:ext-make vendor-name/extension-name
```

自动生成:
```
dcat-admin-extensions/vendor-name/extension-name/
├── composer.json
├── version.php
├── logo.png
├── README.md
├── .gitignore
├── src/
│   ├── ExtensionNameServiceProvider.php
│   ├── Setting.php
│   └── Http/
│       ├── routes.php
│       ├── Middleware/
│       └── Controllers/
│           └── ExtensionNameController.php
├── updates/
├── resources/
│   ├── assets/
│   │   ├── css/index.css
│   │   └── js/index.js
│   ├── views/index.blade.php
│   └── lang/
```

### 1.2 Pro 扩展 (带 API + 元数据)

```bash
php artisan admin:ext-make-pro vendor-name/extension-name \
    --api \
    --plugin_name="我的插件" \
    --plugin_desc="插件描述" \
    --authors_name="作者名" \
    --authors_email="email@example.com"
```

`--api` 额外生成:
```
src/Http/Api/
├── routes.php              # member-api 路由
└── Controllers/
    └── IndexController.php  # 带 #[Group] Scramble 注解
src/Http/AdminApi/
├── routes.php              # admin-api 路由
└── Controllers/
    └── IndexController.php
```

### 1.3 主题扩展

```bash
php artisan admin:ext-make vendor-name/theme-name --theme
```

主题扩展只生成 CSS/视图/ServiceProvider(无控制器/路由/JS)，ServiceProvider 中设置 `$type = self::TYPE_THEME`。

---

## 二、ServiceProvider 生命周期

ServiceProvider 是扩展的核心，所有功能通过它注册。

### 2.1 生命周期顺序

```
register() → boot() → init()
                        ├── loadViewsFrom()    注册视图命名空间
                        ├── loadTranslationsFrom() 注册翻译
                        ├── registerRoutes()   注册后台路由
                        ├── addMiddleware()    注册中间件
                        ├── addExceptRoutes()  注册路由白名单
                        └── aliasAssets()      注册JS/CSS资源别名
```

**关键**: `boot()` 方法是 final 不可覆写，业务逻辑放 `init()` 中。
**API路由必须在 `register()` 中加载**（因为 `boot()`/`init()` 对 API 请求会跳过）。

### 2.2 ServiceProvider 属性

```php
class MyExtensionServiceProvider extends ServiceProvider
{
    protected $js = ['js/index.js'];       // JS资源(相对resources/assets)
    protected $css = ['css/index.css'];    // CSS资源
    protected $type;                       // self::TYPE_THEME 或 null
    protected $middleware = [
        'before' => [],    // admin中间件之前
        'middle' => [],    // 混入admin中间件组
        'after'  => [],    // admin中间件之后
    ];
    protected $exceptRoutes = [
        'permission' => ['route/uri'],  // 跳过权限检查
        'auth'       => ['route/uri'],  // 跳过认证检查
    ];
    protected $menu = [
        ['title' => '插件菜单', 'uri' => 'my-ext', 'icon' => 'feather icon-box'],
    ];

    public function register()
    {
        // API路由必须在这里加载
        $this->loadApiRoutes();
        $this->loadAdminApiRoutes();
    }

    public function init()
    {
        parent::init();  // 必须调用！自动注册视图/路由/中间件/资源
        // 自定义初始化逻辑
    }

    public function settingForm()
    {
        return new Setting($this);  // 扩展设置页面
    }
}
```

### 2.3 安装/卸载/更新钩子

```php
// 安装时调用（首次 admin:ext-install）
public function install()
{
    // 创建数据表、导入菜单等
}

// 卸载时调用（admin:ext-uninstall）
public function uninstall()
{
    // 默认: flushMenu() 删除菜单
    // 可覆写添加清理逻辑
}

// 更新时调用（admin:ext-update）
public function update($currentVersion, $stopOnVersion)
{
    // 默认: refreshMenu() 刷新菜单
    // 可覆写添加迁移逻辑
}
```

---

## 三、版本管理

### 3.1 version.php 格式

```php
<?php
return [
    '1.0.0' => [
        'Initialize extension.',       // 注释(记录为 HISTORY_TYPE_COMMENT)
    ],
    '1.1.0' => [
        'Add new feature.',            // 注释
        'update_add_column.php',       // 迁移脚本(从 updates/ 目录执行)
    ],
    '1.2.0' => [
        'Bug fix.',
        'update_fix_bug.php',
    ],
];
```

### 3.2 迁移脚本 (updates/ 目录)

```php
<?php
// updates/update_add_column.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('my_table', function (Blueprint $table) {
            $table->string('new_column')->nullable();
        });
    }

    public function down()
    {
        Schema::table('my_table', function (Blueprint $table) {
            $table->dropColumn('new_column');
        });
    }
};
```

也支持 Seeder 格式:
```php
<?php
// updates/update_seed_data.php
use Illuminate\Database\Seeder;

return new class extends Seeder
{
    public function run()
    {
        DB::table('my_table')->insert([...]);
    }
};
```

### 3.3 版本相关数据表

- `admin_extensions`: name, version, is_enabled, options(JSON)
- `admin_extension_histories`: name, type(1=注释/2=脚本), version, detail

---

## 四、菜单系统

### 4.1 定义菜单

```php
protected $menu = [
    [
        'title'  => '订单管理',           // 必填
        'uri'    => 'orders',             // 可选
        'icon'   => 'feather icon-box',   // 可选(FontAwesome)
    ],
    [
        'title'  => '子菜单',
        'uri'    => 'sub-page',
        'icon'   => 'feather icon-file',
        'parent' => '订单管理',           // 父菜单标题(字符串)或ID(数字)
    ],
];
```

### 4.2 菜单操作

安装时自动调用 `addMenu()`，卸载时自动调用 `flushMenu()`。菜单记录中 `extension` 列标记来源扩展。

手动操作:
```php
$this->addMenu($menuArray);     // 添加菜单
$this->flushMenu();              // 删除本扩展所有菜单
$this->refreshMenu();            // 先删后加
```

---

## 五、路由注册

### 5.1 后台路由 (自动加载)

`src/Http/routes.php` 在 `init()` 阶段自动加载，自动包裹 admin 路由前缀和中间件:

```php
// src/Http/routes.php
use MyVendor\MyExt\Http\Controllers;
use Illuminate\Support\Facades\Route;

Route::get('my-ext', Controllers\MyExtController::class.'@index');
Route::resource('my-ext/items', Controllers\ItemController::class);
```

### 5.2 API 路由 (手动加载)

```php
// src/Http/Api/routes.php (member-api)
use MyVendor\MyExt\Http\Api\Controllers;
use Illuminate\Support\Facades\Route;

Route::prefix('my-ext')->group(function () {
    Route::get('index', Controllers\IndexController::class.'@index');
    // 需认证的路由
    Route::middleware('member.apiAuth')->group(function () {
        Route::get('profile', Controllers\IndexController::class.'@profile');
    });
});

// src/Http/AdminApi/routes.php (admin-api)
Route::prefix('my-ext')->group(function () {
    Route::get('index', Controllers\IndexController::class.'@index');
    Route::middleware('admin.apiAuth')->group(function () {
        Route::get('admin-data', Controllers\IndexController::class.'@adminData');
    });
});
```

### 5.3 路由白名单

跳过认证或权限检查:
```php
protected $exceptRoutes = [
    'auth'       => ['my-ext/public', 'my-ext/callback'],
    'permission' => ['my-ext/dashboard'],
];
```

---

## 六、中间件注册

```php
protected $middleware = [
    'before' => [LogOperation::class],  // admin中间件组之前
    'middle' => [CheckLimit::class],    // 混入admin中间件组中间
    'after'  => [CleanupCache::class],  // admin中间件组之后
];
```

---

## 七、视图系统

### 7.1 视图命名空间

自动注册为 `{name}::`，其中 name 是扩展名(`vendor.name` 格式):

```php
// 在控制器中使用
Admin::view('vendor.extension-name::index');

// 在 ServiceProvider 中
Admin::view('{name}::view_name', ['data' => $value]);

// Blade 中
@include('{name}::partial')
```

### 7.2 视图中使用主题色

```html
<style>
.element { color: @primary; }
.element-secondary { color: @info; }
</style>
```

---

## 八、资源管理 (JS/CSS)

### 8.1 声明资源

```php
protected $js = ['js/index.js', 'js/another.js'];
protected $css = ['css/index.css'];
```

资源路径相对于 `resources/assets/`，自动注册为别名 `{extension-name}`。

### 8.2 使用资源

```html
<!-- 在视图中通过 require 加载 -->
<script require="@{extension-name}">
    $('.element').myPlugin();
</script>
```

```php
// 在 PHP 中
Admin::js('@extension/vendor-name/extension-name/js/special.js');
Admin::css('@extension/vendor-name/extension-name/css/special.css');
Admin::script('console.log("inline js")');
Admin::style('.class { color: red; }');
```

### 8.3 资源发布

```bash
php artisan vendor:publish --tag={extension.name} --force
```

将 `resources/assets/` 发布到 `public/vendor/{vendor/name}/`。

---

## 九、配置/设置系统

### 9.1 配置读写

```php
// 读取
$this->config('api_key');              // 单键
$this->config('api_key', 'default');   // 带默认值
$this->config();                        // 全部配置

// 写入
$this->config(['api_key' => 'xxx']);   // 传数组=保存
$this->saveConfig(['key1' => 'val1']); // 显式保存

// 静态方式(在扩展内任意位置)
MyExtServiceProvider::setting('key');
MyExtServiceProvider::setting('key', 'value');
```

存储机制: 通过 `Admin::setting()` 保存为键值对，键名为 `vendor:name` 格式。

### 9.2 设置表单

```php
// src/Setting.php
class Setting extends \Dcat\Admin\Extend\Setting
{
    public function form()
    {
        $this->text('api_key', 'API密钥')->required();
        $this->textarea('description', '描述');
        $this->switch('enabled', '启用')->default(1);
        $this->select('mode', '模式')->options(['a' => '模式A', 'b' => '模式B']);
        $this->number('timeout', '超时(秒)')->min(1)->max(300)->default(30);
    }
    
    // 可选: 转换保存前的输入
    protected function formatInput(array $input)
    {
        $input['api_key'] = trim($input['api_key']);
        return $input;
    }
}
```

---

## 十、自定义表单字段扩展

### 10.1 注册字段

```php
// ServiceProvider::boot() 或 init()
Admin::booting(function () {
    Form::extend('myField', \MyExt\Form\Field\MyCustomField::class);
    \Dcat\Admin\Show\Field::extend('myField', \MyExt\Show\Field\MyCustomShowField::class);
    \Dcat\Admin\Grid\Column::extend('myField', \MyExt\Grid\Displayer\MyCustomDisplayer::class);
    \Dcat\Admin\Grid\Filter::extend('myField', \MyExt\Grid\Filter\MyCustomFilter::class);
});
```

### 10.2 自定义 Form 字段类

```php
<?php
namespace MyExt\Form\Field;

use Dcat\Admin\Form\Field;

class ColorPicker extends Field
{
    // 视图模板 (可放 resources/views/ 下)
    protected $view = '{name}::form.color-picker';
    
    // 依赖的JS/CSS资源
    protected static $js = [
        '@extension/vendor-name/extension-name/js/color-picker.js',
    ];
    protected static $css = [
        '@extension/vendor-name/extension-name/css/color-picker.css',
    ];
    
    // 自定义方法
    public function format(string $format): self
    {
        return $this->attribute('data-format', $format);
    }
    
    public function alpha(bool $alpha = true): self
    {
        return $this->attribute('data-alpha', $alpha);
    }
    
    // 渲染逻辑
    public function render()
    {
        $this->attribute('data-color', $this->value());
        $this->format('hex')->alpha(false);
        
        $this->script = <<<JS
            $('#{$this->id()}').colorPicker({
                format: $(this).data('format'),
                alpha: $(this).data('alpha'),
            });
JS;
        return parent::render();
    }
}
```

使用:
```php
$form->colorPicker('bg_color', '背景色')->format('rgb')->alpha(true);
```

### 10.3 自定义 Grid 列显示器

```php
<?php
namespace MyExt\Grid\Displayer;

use Dcat\Admin\Grid\Displayers\AbstractDisplayer;

class StatusBadge extends AbstractDisplayer
{
    public function display(array $statusMap = [], string $defaultStyle = 'default')
    {
        $style = $statusMap[$this->value] ?? $defaultStyle;
        $label = $this->value;
        
        return "<span class=\"badge badge-{$style}\">{$label}</span>";
    }
}
```

使用:
```php
$grid->column('status')->myField(['active' => 'success', 'inactive' => 'danger']);
```

### 10.4 自定义 Grid 筛选器

```php
<?php
namespace MyExt\Grid\Filter;

use Dcat\Admin\Grid\Filter\AbstractFilter;

class DateRangeFilter extends AbstractFilter
{
    protected static $js = ['@datetime'];
    
    public function condition($inputs)
    {
        $value = $inputs[$this->column] ?? null;
        if (!$value) return null;
        
        return $this->where(function ($query) use ($value) {
            $query->whereBetween($this->column, [$value['start'], $value['end']]);
        });
    }
}
```

### 10.5 自定义 Show 字段

```php
<?php
namespace MyExt\Show\Field;

use Dcat\Admin\Show\AbstractField;

class MarkdownRender extends AbstractField
{
    public $escape = false;  // 不转义HTML
    
    public function render()
    {
        return (new \Parsedown())->text($this->value);
    }
}
```

---

## 十一、自定义动作 (Action)

### 11.1 行动作

```php
<?php
namespace MyExt\Actions;

use Dcat\Admin\Grid\RowAction;

class ApproveAction extends RowAction
{
    public function title()
    {
        return '<i class="feather icon-check"></i> 审批';
    }
    
    public function confirm()
    {
        return ['确定审批?', '审批后不可撤回'];
    }
    
    public function handle(Request $request)
    {
        $id = $this->getKey();
        $row = $this->row();
        
        // 业务逻辑
        Order::find($id)->update(['status' => 'approved']);
        
        return $this->response()->success('审批成功')->refresh();
    }
    
    // 权限检查
    public function authorize($user)
    {
        return $user->can('order.approve');
    }
}
```

使用:
```php
$grid->actions([new \MyExt\Actions\ApproveAction]);
```

### 11.2 批量动作

```php
<?php
namespace MyExt\Actions;

use Dcat\Admin\Grid\BatchAction;

class BatchExportAction extends BatchAction
{
    protected $actionClass = 'btn btn-warning';
    
    public function title()
    {
        return '批量导出';
    }
    
    public function handle(Request $request)
    {
        $ids = $this->getKey();  // 选中行ID数组
        
        // 业务逻辑
        $file = ExportService::export($ids);
        
        return $this->response()->success('导出完成')->download($file);
    }
}
```

使用:
```php
$grid->batchActions([new \MyExt\Actions\BatchExportAction]);
```

### 11.3 工具栏按钮

```php
<?php
namespace MyExt\Tools;

use Dcat\Admin\Grid\Tools\AbstractTool;

class ImportButton extends AbstractTool
{
    protected $actionClass = 'btn btn-primary';
    
    public function render()
    {
        $url = admin_url('my-ext/import');
        return "<a href='{$url}' class='btn btn-primary'><i class='feather icon-upload'></i> 导入</a>";
    }
}
```

使用:
```php
$grid->tools(function ($tools) {
    $tools->append(new \MyExt\Tools\ImportButton);
});
```

### 11.4 用 artisan 生成动作类

```bash
php artisan admin:action
# 交互式选择:
#   default      - 通用 Action
#   grid-row     - Grid\RowAction
#   grid-batch   - Grid\BatchAction
#   grid-tool    - Grid\Tools\AbstractTool
#   form-tool    - Form\AbstractTool
#   show-tool    - Show\AbstractTool
#   tree-row     - Tree\RowAction
#   tree-tool    - Tree\AbstractTool
```

---

## 十二、完整扩展示例 — 简易通知插件

### 目录结构

```
dcat-admin-extensions/my-vendor/notice/
├── composer.json
├── version.php
├── logo.png
├── src/
│   ├── NoticeServiceProvider.php
│   ├── Setting.php
│   ├── Models/
│   │   └── Notice.php
│   └── Http/
│       ├── routes.php
│       ├── Controllers/
│       │   └── NoticeController.php
│       ├── AdminApi/
│       │   ├── routes.php
│       │   └── Controllers/
│       │       └── NoticeApiController.php
│       └── Middleware/
│           └── CheckNoticePermission.php
├── updates/
│   └── update_add_priority.php
├── resources/
│   ├── assets/
│   │   ├── css/index.css
│   │   └── js/index.js
│   ├── views/
│   │   ├── index.blade.php
│   │   └── form/
│   │       └── notice-type.blade.php
│   └── lang/
│       └── zh_CN/
│           └── notice.php
```

### composer.json

```json
{
    "name": "my-vendor/notice",
    "alias": "通知管理",
    "description": "dcat-plus-admin 通知管理插件",
    "type": "library",
    "keywords": ["dcat-admin", "extension", "notice"],
    "license": "MIT",
    "authors": [{"name": "作者名", "email": "email@example.com"}],
    "require": {
        "php": ">=7.4.0",
        "dcat-plus/laravel-admin": "~1.0"
    },
    "autoload": {
        "psr-4": {
            "MyVendor\\Notice\\": "src/"
        }
    },
    "extra": {
        "dcat-admin": "MyVendor\\Notice\\NoticeServiceProvider",
        "laravel": {
            "providers": ["MyVendor\\Notice\\NoticeServiceProvider"]
        }
    }
}
```

**关键字段**: `extra.dcat-admin` 必须指向 ServiceProvider 全限定类名(无前导反斜杠)。

### version.php

```php
<?php
return [
    '1.0.0' => [
        'Initialize notice extension.',
    ],
    '1.1.0' => [
        'Add priority field.',
        'update_add_priority.php',
    ],
];
```

### ServiceProvider

```php
<?php
namespace MyVendor\Notice;

use Dcat\Admin\Extend\ServiceProvider;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;

class NoticeServiceProvider extends ServiceProvider
{
    protected $js = ['js/index.js'];
    protected $css = ['css/index.css'];
    
    protected $middleware = [
        'middle' => [Http\Middleware\CheckNoticePermission::class],
    ];
    
    protected $exceptRoutes = [
        'auth' => ['notice/public'],
    ];
    
    protected $menu = [
        ['title' => '通知管理', 'uri' => 'notice', 'icon' => 'feather icon-bell'],
        ['title' => '通知列表', 'uri' => 'notice/list', 'icon' => 'feather icon-list', 'parent' => '通知管理'],
    ];
    
    public function register()
    {
        $this->loadAdminApiRoutes();
    }
    
    public function init()
    {
        parent::init();
        
        // 注册自定义表单字段
        Admin::booting(function () {
            Form::extend('noticeType', \MyVendor\Notice\Form\NoticeTypeField::class);
            Grid\Column::extend('noticeBadge', \MyVendor\Notice\Grid\NoticeBadgeDisplayer::class);
        });
        
        // 注入全局通知徽章到导航栏
        admin_inject_section(Admin::SECTION['NAVBAR_AFTER'], function () {
            $count = \MyVendor\Notice\Models\Notice::unread()->count();
            return view('my-vendor.notice::nav-badge', compact('count'));
        });
    }
    
    public function settingForm()
    {
        return new Setting($this);
    }
    
    public function install()
    {
        // 安装时创建数据表(也可通过 version.php 的迁移脚本)
    }
    
    public function uninstall()
    {
        // 卸载时清理
    }
}
```

### Setting

```php
<?php
namespace MyVendor\Notice;

use Dcat\Admin\Extend\Setting as Form;

class Setting extends Form
{
    public function form()
    {
        $this->text('push_url', '推送地址')->required();
        $this->switch('auto_push', '自动推送')->default(0);
        $this->number('retention_days', '保留天数')->min(7)->max(365)->default(30);
        $this->select('default_type', '默认类型')
            ->options(['system' => '系统', 'order' => '订单', 'promo' => '营销']);
    }
}
```

### Controller

```php
<?php
namespace MyVendor\Notice\Http\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use MyVendor\Notice\Models\Notice;

class NoticeController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new Notice(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('title', '标题');
            $grid->column('type', '类型')->noticeBadge([
                'system' => 'info', 'order' => 'warning', 'promo' => 'success'
            ]);
            $grid->column('is_read', '已读')->bool();
            $grid->column('created_at');
            
            $grid->filter(function ($filter) {
                $filter->like('title');
                $filter->equal('type')->select(['system' => '系统', 'order' => '订单']);
                $filter->between('created_at')->datetime();
            });
            
            $grid->quickSearch('title');
        });
    }
    
    protected function form()
    {
        return Form::make(new Notice(), function (Form $form) {
            $form->text('title', '标题')->required();
            $form->noticeType('type', '类型');  // 自定义字段!
            $form->editor('content', '内容');
            $form->switch('is_read', '已读');
        });
    }
}
```

---

## 十三、扩展管理命令

| 命令 | 说明 |
|------|------|
| `admin:ext-install {name} {--path=}` | 安装扩展(从市场或本地ZIP) |
| `admin:ext-uninstall {name}` | 卸载扩展(回滚所有版本+删除记录) |
| `admin:ext-enable {name}` | 启用扩展(is_enabled=true) |
| `admin:ext-disable {name}` | 禁用扩展(is_enabled=false) |
| `admin:ext-update {name} {--ver=}` | 更新扩展(执行新版本迁移) |
| `admin:ext-refresh {name}` | 刷新扩展(先回滚再重装) |
| `admin:ext-rollback {name} {ver?}` | 回滚到指定版本 |

---

## 十四、发布扩展

### 14.1 必要文件校验

Manager::checkFiles() 检查:
1. `src/` 目录必须存在
2. `composer.json` 必须存在
3. `version.php` 必须存在
4. `composer.json` 必须含 `name` 和 `extra.dcat-admin`

### 14.2 发布到 Packagist

1. 确保 `composer.json` 完整且正确
2. 推到 GitHub
3. 在 Packagist.org 提交包
4. 用户通过 `composer require vendor/name` 安装

### 14.3 本地安装

```bash
# ZIP包安装
php artisan admin:ext-install my-vendor/notice --path=/path/to/notice.zip

# 手动安装: 解压到 dcat-admin-extensions/ 目录，然后
php artisan admin:ext-update my-vendor/notice
```

### 14.4 扩展目录

默认: `base_path('dcat-admin-extensions/')`
可配置: `config('admin.extension.dir')`

Manager 会递归扫描(最大深度2)该目录下含 `composer.json` 的子目录。

---

## 十五、陷阱与注意事项

1. **API路由必须在 `register()` 加载** — `boot()`/`init()` 对 API 请求会跳过
2. **`boot()` 是 final 方法** — 所有初始化逻辑放 `init()`
3. **`init()` 必须先调 `parent::init()`** — 否则视图/路由/中间件/资源不会自动注册
4. **菜单的 parent 引用** — 字符串=父菜单标题，数字=父菜单ID
5. **资源路径** — `$js`/`$css` 相对于 `resources/assets/`，使用前需 `vendor:publish`
6. **视图命名空间** — 用 `{extension-name}::` (中划线格式，非 `vendor.name`)
7. **设置键名** — 内部存储为 `vendor:name` (冒号分隔)
8. **version.php 版本排序** — 使用 `version_compare` 排序，必须遵循语义化版本
9. **迁移脚本位置** — 放 `updates/` 目录，返回 Migration 或 Seeder 实例
10. **扩展禁用后** — `boot()` 中 `disabled()` 检查会跳过 `init()`，但 `register()` 仍执行
11. **已知 Bug**: ServiceProvider::config() 传数组存储时，Setting::add() 逻辑条件反转
