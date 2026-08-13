# dcat-plus-admin 基础设施完整目录

## Artisan 命令

| 命令 | 说明 | 参数/选项 |
|------|------|----------|
| admin | 列出所有管理命令 | - |
| admin:install | 安装后台 | 自动迁移+种子+生成目录 |
| admin:app {name} | 创建子应用 | name=应用名 |
| admin:create-user | 创建管理员 | 交互式 |
| admin:reset-password | 重置密码 | 交互式 |
| admin:publish | 发布资源 | --force |
| admin:uninstall | 卸载 | - |
| admin:update | 更新 | - |
| admin:export-seed | 导出种子 | {classname} {--users} |
| admin:form {name} | 生成表单类 | {--namespace=} |
| admin:action | 生成动作类 | 7种子类型 |
| admin:ide-helper | IDE辅助 | {--controller=} |
| admin:menu-cache | 菜单缓存 | - |
| admin:minify {name} | 压缩资源 | {--type=} |
| admin:ext-make {name} | 创建扩展 | - |
| admin:ext-make-pro {name} | 创建Pro扩展 | --api --theme --plugin_name --plugin_desc --authors_name --authors_email |
| admin:ext-install {name} | 安装扩展 | {--path=} |
| admin:ext-uninstall {name} | 卸载扩展 | - |
| admin:ext-refresh {name} | 刷新扩展 | - |
| admin:ext-rollback {name} | 回滚扩展 | {ver?} |
| admin:ext-enable {name} | 启用扩展 | - |
| admin:ext-disable {name} | 禁用扩展 | - |
| admin:ext-update {name} | 更新扩展 | {--ver=} |

## Helper 函数

### 设置
- admin_setting($key, $default) - 读取/保存设置
- admin_setting_array($key, $default) - 数组设置
- admin_setting_multiple($keys) - 批量读取
- admin_setting_group($name, $data) - 分组读写
- admin_extension_setting($ext, $key, $default) - 扩展设置

### Section注入
- admin_section($section, $default, $options) - 输出section
- admin_has_section($section) - 判断存在
- admin_inject_section($section, $content, $append, $priority) - 注入
- admin_inject_section_if($condition, ...) - 条件注入
- admin_has_default_section($section) - 默认section判断
- admin_inject_default_section($section, $content) - 注入默认

### 翻译
- admin_trans($key, $replace, $locale)
- admin_trans_field($field, $locale)
- admin_trans_label($label, $replace, $locale)
- admin_trans_option($value, $field, $replace, $locale)
- admin_controller_slug() / admin_controller_name()

### URL/路径
- admin_path($path) - 文件系统路径
- admin_url($path, $params, $secure) - 完整URL
- admin_base_path($path) - URL路径
- admin_asset($path) - 静态资源URL
- admin_route($route, $params, $absolute) - 路由URL
- admin_route_name($route) - 完整路由名
- admin_api_route_name($route) - API路由名
- admin_extension_path($path) - 扩展路径

### UI
- admin_toastr($msg, $type, $opts) - toastr通知
- admin_success/error/warning/info($title, $msg) - 闪存消息
- admin_script($js, $direct) - 添加JS代码
- admin_style($style) - 添加CSS代码
- admin_js($js) / admin_css($css) - 添加JS/CSS文件
- admin_require_assets($alias) - 加载资源别名
- admin_color($color) - 获取颜色实例
- admin_view($view, $data) - 渲染视图

### 工具
- admin_javascript($scripts) - JS占位注入
- admin_javascript_json($data) - JSON格式化
- admin_exit($response) - 中断响应
- admin_redirect($to, $code, $request) - 重定向
- format_byte($input, $dec) - 字节格式化
- isTableColumnNullable($table, $col) - 列可空检查
- isLaravel11OrNewer() - 版本检查
- updateEnv(array $values) - 更新.env

## 中间件

| 别名 | 类 | 作用 |
|------|---|------|
| admin.auth | Authenticate | 登录认证 |
| admin.permission | Permission | 权限检查 |
| admin.bootstrap | Bootstrap | 启动引导(CSRF/事件/暗色模式) |
| admin.pjax | Pjax | PJAX导航 |
| admin.session | Session | 会话管理 |
| admin.upload | WebUploader | 分块上传处理 |
| admin.app | Application | 多应用切换 |
| admin.apiAuth | AdminApiAuth | Admin JWT认证(外部) |
| member.apiAuth | MemberApiAuth | 会员JWT认证(外部) |

middleware group `admin`: admin.auth, admin.pjax, admin.bootstrap, admin.permission, admin.session, admin.upload

## 资源别名 (40+)

@adminlte, @dcat, @vendors, @pjax, @toastr, @select2, @tinymce, @wang-editor, @fullcalendar, @webuploader, @chartjs, @apex-charts, @moment, @jstree, @editor-md, @inputmask, @color-picker, @slider, @switchery, @fontawesome-iconpicker, @datamaps, @jquery-nestable, @dropzone, @laravel-file-manager, @simplemde, @poppins, @vapor

## 主题颜色

4个内置主题:
- default: #586cb1
- blue-light: #62a8ea
- blue: #6d8be6
- green: #4e9876

50+命名色: primary, info, success, danger, warning, indigo, blue, red, orange, green, cyan, purple, pink, dark, white, yellow, font, gray-bg, border, input-border, background, dark-mode各色...

自定义主题: Color::extend('我的主题', ['primary' => '#xxx', ...])

## Widget 完整目录

| 类 | 说明 | 关键方法 |
|----|------|---------|
| Box | 内容盒子 | title(), content(), tools(), padding(), solid(), collapsable() |
| Card | 卡片 | title(), content(), footer(), divider(), outline(), collapse() |
| Tab | 选项卡 | add(), link(), active(), dropDown() |
| Modal | 弹窗 | title(), content(), footer(), button(), size(), center() |
| Collapse | 折叠面板 | - |
| Table | HTML表格 | 构造传入$headers, $rows |
| LazyTable | 异步表格 | simple(), load() |
| Descriptions | 描述列表 | - |
| InfoList | 信息列表 | - |
| Timeline | 时间线 | - |
| Tree | 树视图 | - |
| Form | 独立表单 | 全部Form字段方法, action(), confirm() |
| DialogForm | 弹窗表单 | - |
| DialogTable | 弹窗表格 | - |
| Alert | 告警 | - |
| BtnGroup | 按钮组 | - |
| Calendar | 日历 | FullCalendar |
| Callout | 提示框 | - |
| Carousel | 轮播 | - |
| Code | 代码显示 | - |
| CoverCard | 封面卡片 | 图片+头像+标题 |
| DarkModeSwitcher | 暗色切换 | - |
| Dropdown | 下拉菜单 | - |
| Dump | 变量输出 | - |
| Linkbox | 链接卡片 | 图标+标题+副标题+徽章 |
| ListGroup | 列表组 | - |
| Markdown | Markdown | - |
| MediaList | 媒体列表 | 图片+标题+内容+链接 |
| MiniProgramBox | 小程序盒 | - |
| PricingCard | 定价卡 | - |
| Terminal | 终端显示 | - |
| Tooltip | 提示 | .tips 自动注册, purple |

### 指标卡片 (Widgets\Metrics\)
| 类 | 说明 |
|----|------|
| Card | 指标卡(value, subValue, chart, icon) |
| Bar | 柱状图指标卡 |
| Donut | 环形图指标卡 |
| Line | 折线图指标卡 |
| RadialBar | 径向条指标卡 |
| Round | 圆形指标卡 |
| SingleRound | 单圆指标卡 |

(ApexCharts 引擎)

## 扩展 ServiceProvider

```php
class MyExtServiceProvider extends Dcat\Admin\Extend\ServiceProvider
{
    public function register() {}
    public function boot() {}

    // 设置表单(必实现)
    public function settingForm() { return new Form($this); }

    // 安装/卸载钩子
    public function install() {}
    public function uninstall() {}

    // 菜单导入
    protected function menu(): array
    {
        return [
            ['title' => '插件', 'uri' => 'my-ext', 'icon' => 'feather icon-box'],
        ];
    }

    // 读取扩展配置
    public function config($key, $default = null)
}
```

## Action 动作系统

### 基类
- Action (abstract) - Renderable, 有selector/method/event/disabled/htmlClasses
- GridAction (abstract) - extends Action, 有grid/resource
- RowAction (abstract) - extends GridAction, 有getKey()/row()/setRow()
- BatchAction (abstract) - extends GridAction, 有selectedKeysScript

### 创建自定义行动作
```php
class MyRowAction extends \Dcat\Admin\Grid\RowAction {
    public function title() { return '操作名'; }
    public function handle() {
        // $this->getKey() 获取行ID
        // $this->row() 获取行数据
        return $this->response()->success('成功');
    }
}
```

### 创建自定义批量动作
```php
class MyBatchAction extends \Dcat\Admin\Grid\BatchAction {
    public function title() { return '批量操作'; }
    public function handle() {
        // $this->getKey() 获取选中ID数组
        return $this->response()->success('成功');
    }
}
```

### 创建自定义工具
```php
class MyTool extends \Dcat\Admin\Grid\Tools\AbstractTool {
    public function render() { return '<button>...</button>'; }
}
```

## Repository 数据层

### EloquentRepository
```php
// 使用模型类
$grid = Grid::make(new EloquentRepository(Article::class), ...);
// 或简写
$grid = Grid::make(new Article(), ...);

// 关联预加载
$grid = Grid::make(Article::with('category'), ...);
```

### QueryBuilderRepository
```php
// 使用查询构建器
$repo = new QueryBuilderRepository(DB::table('my_view'));
```

### 自定义Repository
```php
class MyRepo extends \Dcat\Admin\Repositories\EloquentRepository {
    protected $modelClass = MyModel::class;

    // 可覆写 get(), store(), update(), destroy() 等
}
```

## 模型

| 模型 | 表名 | 关键 |
|------|------|------|
| Administrator | admin_users | username, password, name, avatar; roles() |
| Role | admin_roles | name, slug; administrators(), permissions(), menus() |
| Permission | admin_permissions | parent_id, name, slug, http_method, http_path; ModelTree |
| Menu | admin_menu | parent_id, order, title, icon, uri, extension, show; ModelTree |
| Setting | admin_settings | id(non-incrementing), group_name, slug, value |
| Extension | admin_extensions | name, is_enabled, version, options(json) |
| ExtensionHistory | admin_extension_histories | name, type, version, detail |
| SkuAttribute | sku_attribute | attr_name, attr_type, attr_value(json), sort |

## Section 注入点

| 常量 | 位置 |
|------|------|
| Admin::SECTION['NAVBAR_AFTER'] | 导航栏后面 |
| Admin::SECTION['NAVBAR_USER_PANEL'] | 导航栏用户面板 |
| Admin::SECTION['LEFT_SIDEBAR_USER_PANEL'] | 左侧栏用户面板 |
| Admin::SECTION['LEFT_SIDEBAR_MENU_TOP'] | 左侧栏菜单顶部 |
| Admin::SECTION['LEFT_SIDEBAR_MENU_BOTTOM'] | 左侧栏菜单底部 |

## ServiceProvider 注册的单例

| 键 | 类 | 作用 |
|----|---|------|
| admin.app | Application | 多应用管理 |
| admin.asset | Asset | 资源管理 |
| admin.color | Color | 主题颜色 |
| admin.sections | SectionManager | Section注入 |
| admin.extend | Manager | 扩展管理 |
| admin.extend.update | UpdateManager | 扩展更新 |
| admin.extend.version | VersionManager | 版本管理 |
| admin.navbar | Navbar | 导航栏 |
| admin.menu | Menu | 菜单 |
| admin.context | Context | 上下文 |
| admin.setting | Setting | 设置(从DB加载) |
| admin.web-uploader | WebUploader | 上传管理 |
| admin.translator | Translator | 翻译 |

## 已知代码问题

1. Setting::add() 逻辑反转 - $k!==null 时用了数字索引
2. Administrator::canSeeMenu() 永远返回 true - 无菜单权限检查
3. Color dark-mode-font 双井号 '##a8a9bb'
4. AdminTablesSeeder 默认 admin/admin 无强制改密
5. 中间件引用外部类 App\Http\Middleware\AdminApiAuth/MemberApiAuth 若未创建会崩溃

## 完整配置参考 (config/admin.php)

```
name                        'Dcat-plus Admin'
logo                        'Dcat-plus Admin'
logo-mini                   ...
favicon                     '@admin/images/favicon.ico'
default_avatar              '@admin/images/default-avatar.jpg'
login_background_image      '@admin/images/login-bg.jpg'

route.domain                env()
route.prefix                'admin'
route.namespace             'App\Admin\Controllers'
route.middleware            ['web', 'admin']
route.enable_session_middleware  false

directory                   app_path('Admin')
assets_server               env()
https                       false

auth.enable                 true
auth.controller             AuthController::class
auth.guard                  'admin'
auth.guards.admin           session driver + admin provider
auth.providers.admin        eloquent + Administrator
auth.remember               true
auth.except                 ['auth/login', 'auth/logout']

grid.grid_action_class      DropdownActions
grid.batch_action_class     BatchActions
grid.paginator_class        Paginator
grid.column_selector.store  SessionStore(file)

helpers.enable              true

permission.enable           true
permission.except           ['/', 'auth/login', 'auth/logout', 'auth/setting']

menu.cache.enable           false
menu.cache.store            'file'
menu.bind_permission        true
menu.role_bind_menu         true
menu.permission_bind_menu   true
menu.default_icon           'feather icon-circle'

upload.disk                 'public'
upload.directory.image      'images'
upload.directory.file       'files'

database.connection         ''
database.users_table/model  admin_users / Administrator
database.roles_table/model  admin_roles / Role
database.permissions_table  admin_permissions / Permission
database.menu_table/model   admin_menu / Menu
database.role_users_table   admin_role_users
database.role_permissions_table  admin_role_permissions
database.role_menu_table    admin_role_menu
database.permission_menu_table  admin_permission_menu
database.settings_table     admin_settings
database.extensions_table   admin_extensions
database.extension_histories  admin_extension_histories

layout.color                'default'
layout.body_class           []
layout.horizontal_menu      false
layout.sidebar_collapsed    false
layout.sidebar_style        'light'
layout.dark_mode_switch     false
layout.navbar_color         ''
layout.full_screen          true
layout.home_url             env('APP_URL')

exception_handler           Handler::class
enable_default_breadcrumb   true
extension.dir               base_path('dcat-admin-extensions')
multi_app                   []
```
