# dcat-plus-admin 主题开发完整指南

本包没有独立的 `Theme` 类系统，主题 = **四层机制的组合**：内置配色方案（配置级）→ 布局配置（admin.layout）→ 主题扩展（`--theme` 类型扩展，全局 CSS 注入）→ 视图覆盖（Laravel 原生 vendor 目录）。从轻到重按需选用。

## 一、机制总览（选型）

| 层次 | 适用场景 | 改动位置 | 是否需要扩展 |
|------|---------|---------|------------|
| 配色方案 | 换主色调（蓝/绿/浅蓝） | `config/admin.php` → `layout.color` | 否 |
| 布局配置 | 侧栏风格/深色模式/导航栏样式 | `config/admin.php` → `layout.*` 或后台「布局配置」面板 | 否 |
| 主题扩展 | 全新视觉风格（圆角/字体/间距/组件样式） | `admin:ext-make xxx --theme` 生成 | 是 |
| 视图覆盖 | 改 HTML 结构（换布局/增删区块） | 应用 `resources/views/vendor/admin/**` | 否 |

> ⚠ 样式统一原则（见 memory：AdminLTE 样式统一套路）：「统一样式」= 只换视觉令牌（主色/边框/圆角/阴影/控件规格），不重建 HTML 结构。整页重写布局曾被否决。

## 二、内置配色方案（配置级）

### 2.1 内置四套方案

`config/admin.php`:

```php
'layout' => [
    // 可选：default、blue、blue-light、green
    'color' => 'default',
    // ...
],
```

| 方案名 | primary | 说明 |
|--------|---------|------|
| default | `#586cb1` | 默认靛蓝 |
| blue | `#6d8be6` | 蓝 |
| blue-light | `#62a8ea` | 浅蓝 |
| green | `#4e9876` | 绿 |

定义位置 `src/Color.php::$extensions`，每套方案只含 3 个键: `primary` / `primary-darker` / `link`。

### 2.2 生效链路（关键）

```
config('admin.layout.color')
  → Color::getName()                          (admin.color 单例, Admin::color())
  → Asset::setUpTheme()  [src/Layout/Asset.php:353]
      非 default 时把资产别名替换为分色文件:
        @adminlte → adminlte-{color}.css       (public/vendor/dcat-admin/adminlte/)
        @dcat     → dcat-app-{color}.css       (public/vendor/dcat-admin/dcat/css/)
```

- `default` 直接用无后缀的 `adminlte.css` / `dcat-app.css`，**不加载**分色文件
- 分色 CSS 是独立编译产物，改配色必须同步生成对应文件（见 webpack.mix.js）

### 2.3 代码侧取色

**PHP 端**（`Color` 类 Macroable 魔术方法，60+ 语义色全表见 `src/Color.php::$allColors`）:

```php
Admin::color()->primary();          // 当前方案主色
Admin::color()->primaryDarker();
Admin::color()->get('font');        // 语义色 #414750
Admin::color()->getName();          // 当前方案名 'default'
// 注意: 没有 ->primary() 之外的 Color::primary() 静态调用; 写 Widget 取色用 Admin::color()->primary()
```

**JS 端**（`Admin.php` 启动时把 `color()->all()` 注入 `Dcat.config.colors`，`Dcat.color` 由 `resources/assets/dcat/js/extensions/Color.js` 构建）:

```js
Dcat.color.get('primary')     // 取色, 未命中原样返回
Dcat.color.darken('#586cb1', 10)   // 加深
Dcat.color.lighten('primary', 20)  // 提亮（可传语义色名）
Dcat.color.alpha('primary', 0.5)   // rgba
```

### 2.4 注册自定义配色

```php
// ServiceProvider 中（需在 Asset 别名解析前执行, 如 AdminServiceProvider::boot 或扩展 register）
\Dcat\Admin\Color::extend('purple', [
    'primary'        => '#7c3aed',
    'primary-darker' => '#6d28d9',
    'link'           => '#6d28d9',
]);
```

⚠ `setUpTheme()` 会去找 `adminlte-purple.css` / `dcat-app-purple.css` 分色文件——**必须同时编译产出这两套分色 CSS**（仿照 webpack.mix.js 中 blue/green 的产出配置），否则前端 404。若只想让 PHP/JS 侧取到色、不动全局 CSS，保持 `layout.color` 为 default，仅注册语义色。

## 三、布局配置（admin.layout）

```php
'layout' => [
    'color'             => 'default',   // 配色方案（见上）
    'sidebar_style'     => 'light',     // light|primary|dark → sidebar-light-primary|sidebar-primary|sidebar-dark-white
    'sidebar_collapsed' => false,       // 侧栏默认折叠（汉堡折叠状态可被用户级 layout_config 持久化覆盖）
    'navbar_color'      => '',          // ''|bg-primary|bg-info|bg-warning|bg-success|bg-danger|bg-dark
    'navbar_class'      => 'sticky',    // floating|sticky|hidden → floating-nav|fixed-top|d-none
    'body_class'        => [],          // 追加 body 类, 含 'dark-mode' 即深色模式
    'horizontal_menu'   => false,       // （已被 menu_style 取代, 保留兼容）
    'dark_mode_switch'  => false,       // 导航栏显示深色模式切换按钮
    'full_screen'       => true,        // 全屏按钮
    'home_url'          => env('APP_URL'),
    'layout_config_tool_in_navbar' => true, // 布局配置工具放导航栏（false 放侧栏）
],
// 菜单模式独立于 layout 段:
'menu_style' => 'default_menu',   // default_menu|horizontal_menu|two_col_menu|top_side_menu
```

生效位置 `src/Layout/Content.php::config()`（约 440-545 行）:

- `body_class` 数组含 `dark-mode` 时，`sidebar_style` 强制 `sidebar-dark-white`
- `menu_style` 映射: horizontal_menu→body 加 `horizontal-menu`; two_col_menu→`two-col-menu`; top_side_menu→`top-side-menu`（3.x 新增, 详见 memory top-side-menu-kernel-integration）
- 用户在后台「布局配置」面板保存的偏好写 DB（admin_settings layout 段），**优先级高于 config 文件**（Content 启动时 array_merge 覆盖）

## 四、主题扩展开发（核心玩法）

### 4.1 创建主题骨架

```bash
php artisan admin:ext-make vendor/theme-name --theme
# 或后台: 扩展 → 创建 → 类型选「主题」(ExtensionController type=2 等价传 --theme)
```

生成结构与普通扩展的差异（`src/Console/ExtensionMakeCommand.php::$themeDirs`）:

```
dcat-admin-extensions/vendor/theme-name/
├── composer.json
├── version.php
├── updates/
├── resources/
│   ├── assets/css/index.css     # ← 主题样式主文件（骨架只给 css, 无 js）
│   └── views/                   # ← 可选: 主题自带视图
└── src/
    ├── ThemeNameServiceProvider.php
    └── Setting.php              # 后台配置表单
```

差异点: 无 Controller / routes.php / index.js / index.blade.php 骨架（主题通常不注册页面路由）。

### 4.2 ServiceProvider 关键代码

```php
class ThemeNameServiceProvider extends ServiceProvider
{
    protected $type = self::TYPE_THEME;   // ← 标记为主题类型（扩展管理界面分类显示）

    protected $css = [
        'css/index.css',                  // 相对 resources/assets/ 的路径, 可多文件
        // 'css/dark.css',                // 建议拆分: 深色适配单独一个文件
    ];

    public function init()
    {
        parent::init();

        // 骨架生成的注入语句——把 css 追加到全局基础样式, 所有后台页面生效
        Admin::baseCss($this->formatAssetFiles($this->css));

        // 可选: 动态切配色方案（会触发分色 CSS 加载, 确保文件存在）
        // Admin::color()->setName('purple');

        // 可选: 全局 JS（如动态设置 CSS 变量）
        // Admin::baseJs($this->formatAssetFiles(['js/theme.js']));  // 需自建 js 目录并发布
    }

    public function settingForm()
    {
        return new Setting($this);        // 用户可配置项（如圆角开关）
    }
}
```

### 4.3 资产发布链路

```
扩展 resources/assets/
  → 发布到 public/vendor/dcat-admin-extensions/{vendor}/{theme-name}/
      (@extension 别名 = vendor/dcat-admin-extensions, 定义于 src/Layout/Asset.php:20)

formatAssetFiles('css/index.css')
  → '@{扩展名}.path/css/index.css'
  → https://.../vendor/dcat-admin-extensions/vendor/theme-name/css/index.css
```

父类 `Extend\ServiceProvider::init()` 会自动 `aliasAssets()` 注册 `{扩展名}.path` 路径别名和 `{扩展名}` 资产别名。

**发布/更新资产命令**（`publishable()` 以扩展点分名为 tag 注册 publishes）:

```bash
php artisan vendor:publish --tag={vendor.theme-name} --force
# tag = 扩展名（getName(), 点分形式）, 如 vendor:publish --tag=dcat.theme.xxx --force
```

改了扩展内 CSS 后需重新发布并强刷浏览器（静态资源带缓存）。

### 4.4 主题 CSS 编写要点

```css
/* resources/assets/css/index.css */

/* 1. 优先用 CSS 变量收口视觉令牌, 少量高优选择器覆盖 */
:root {
    --theme-border-radius: 8px;
    --theme-card-shadow: 0 2px 8px rgba(0,0,0,.06);
}

/* 2. 覆盖以 dcat-admin-body / AdminLTE 结构类为锚点 */
.dcat-admin-body .card {
    border-radius: var(--theme-border-radius);
    box-shadow: var(--theme-card-shadow);
}

/* 3. 主色不要写死——主包没有全局注入 --primary 等 CSS 变量,
      主题自己在 ServiceProvider::init() 里用 Admin::style() 输出变量,
      色值取 PHP 端当前配色, 与方案联动: */
// Admin::style(':root { --theme-primary: '.Admin::color()->primary().'; }');
.dcat-admin-body .btn-primary {
    background: var(--theme-primary) !important;
    border-color: var(--theme-primary) !important;
}

/* 4. 深色模式作用域 */
.dcat-admin-body.dark-mode .card { ... }
```

要点:

- 页面 body class 固定含 `dcat-admin-body`（container/full-page 两布局都有），是最稳的命名空间锚点
- 布局结构性 class（`sidebar-mini layout-fixed` 等）来自 AdminLTE 3，深改前先看 `resources/views/layouts/container.blade.php`
- 弹窗内容（layer 承载）不在 `dcat-admin-body` 内时注意选择器作用域，必要时双写
- `!important` 用于覆盖 AdminLTE/dcat 已编译样式，克制使用

### 4.5 Setting 配置表单

主题扩展的 `src/Setting.php` 与普通扩展一致（后台「扩展→设置」渲染），常用于开关项:

```php
$form->switch('radius', '启用圆角风格')->default(1);
```

在 CSS 中按配置注入不同变量块，或 `init()` 里按 `$this->getConfig()` 读配置后 `Admin::style()` 注入。

## 五、视图覆盖（改结构时用）

两层视图体系:

1. **主包视图**: `admin::` 命名空间（AdminServiceProvider loadViewsFrom）
2. **扩展视图**: `{扩展名}::` 命名空间（各扩展 init 里 loadViewsFrom, 只增不覆盖）

覆盖主包视图走 **Laravel 原生机制**——在应用创建同名文件即可优先加载:

```
应用/resources/views/vendor/admin/
├── layouts/container.blade.php      # 覆盖整个后台骨架（谨慎!）
├── partials/navbar.blade.php        # 只覆盖导航栏
└── partials/sidebar.blade.php       # 只覆盖侧栏
```

⚠ 主包升级会改这些视图，被覆盖的文件**不会跟着升级**，每次升级需手动 diff 同步——优先用 CSS 方案，视图覆盖作为最后手段。

## 六、运行时注入 API（微调用）

```php
// 单页面注入（当前请求生效）
Admin::style('.card { border-radius: 12px; }');
Admin::script('console.log("theme");');

// 全局注入（所有后台页面, 主题扩展核心 API）
Admin::baseCss(['/path/或别名/theme.css']);
Admin::baseJs([...]);

// 覆盖资产别名（如整体替换 adminlte 样式）
Admin::asset()->alias('@adminlte', [
    'js'  => ['@admin/adminlte/adminlte.js'],
    'css' => ['@extension/vendor/theme-name/css/adminlte-override.css'],
]);

// Widget/控制器内取当前主题色
$primary = Admin::color()->primary();
```

## 七、验证与调试

1. **资产缓存坑**: 静态资源响应带缓存（max-age=3600 量级），改 CSS 看不到效果先强刷（Cmd+Shift+R）或注入版本参数验证
2. **分色 CSS 404**: 切非 default 配色后样式全无 → 检查 `public/vendor/dcat-admin/adminlte/adminlte-{color}.css` 是否存在
3. **扩展资产未发布**: 404 于 `vendor/dcat-admin-extensions/...` → `php artisan vendor:publish --tag={扩展名} --force` 重新发布
4. **用户级布局偏好覆盖**: 改了 config 不生效 → 后台「布局配置」保存过偏好（DB admin_settings），清掉或改面板
5. 主包 dist 资源开发时: 改 `resources/assets` 编译后**必须同步 public/vendor 副本**（见 memory local-test-app）

## 八、陷阱清单

- `Color` 无 `primary()` 静态方法，取色走 `Admin::color()`（Widget 里常踩, 见 memory step-widget-request）
- `Color::extend()` 注册方案名后，`setUpTheme()` 会按方案名拼分色 CSS 文件名——不产出文件就 404
- `layout.color` 非 default 时 `@adminlte`/`@dcat` **整体替换**为分色文件，主题扩展若 alias 覆盖了 `@adminlte` 需考虑与分色机制的兼容
- 深色模式不是独立配色方案：`body_class` 含 `dark-mode` + CSS 侧适配，主题 CSS 必须写深色分支
- 汉堡折叠状态持久化在用户级 layout_config（DB），非 config 文件（memory top-side-menu）
- 视图覆盖文件升级不同步（见 §五）
- `admin:ext-make --theme` 骨架不含 js 目录，需 JS 时自建 `resources/assets/js/` 并在 `$js` 声明 + 手动补发布
