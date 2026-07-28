<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 名称
    |--------------------------------------------------------------------------
    |
    | 后台名称，会展示在登录页等位置。
    |
    */
    'name' => 'Dcat-plus Admin',

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 标志
    |--------------------------------------------------------------------------
    |
    | 所有后台页面使用的标志。可使用 img 标签设置图片，例如
    | '<img src="http://logo-url" alt="后台标志">'。
    |
    */
    'logo' => '<img src="/vendor/dcat-admin/images/logo.png" width="35"> &nbsp;Dcat-plus Admin',

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 折叠标志
    |--------------------------------------------------------------------------
    |
    | 侧边栏折叠时展示的标志。可使用 img 标签设置图片，例如
    | '<img src="http://logo-url" alt="后台标志">'。
    |
    */
    'logo-mini' => '<img src="/vendor/dcat-admin/images/logo.png">',

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 浏览器图标
    |--------------------------------------------------------------------------
    |
    */
    'favicon' => '@admin/images/favicon.ico',

    /*
     |--------------------------------------------------------------------------
     | 用户默认头像
     |--------------------------------------------------------------------------
     |
     | 为新创建的用户设置默认头像。
     |
     */
    'default_avatar' => '@admin/images/default-avatar.jpg',

    /*
    |--------------------------------------------------------------------------
    | 登录页背景图片
    |--------------------------------------------------------------------------
    |
    | 用于设置登录页背景图片。
    |
    */
    'login_background_image' => '@admin/images/login_32-bg.jpg',
    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 路由配置
    |--------------------------------------------------------------------------
    |
    | 后台路由配置，包括路径前缀、控制器命名空间和默认中间件。
    | 如需通过根路径访问，将 prefix 设为空字符串即可。
    |
    */
    'route' => [
        'domain' => env('ADMIN_ROUTE_DOMAIN'),

        'prefix' => env('ADMIN_ROUTE_PREFIX', 'admin'),

        'namespace' => 'App\\Admin\\Controllers',

        'middleware' => ['web', 'admin'],

        'enable_session_middleware' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 安装目录
    |--------------------------------------------------------------------------
    |
    | 后台控制器和路由配置文件的安装目录。默认为 app/Admin；
    | 必须在执行 artisan admin::install 前设置才会生效。
    |
    */
    'directory' => app_path('Admin'),

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 页面标题
    |--------------------------------------------------------------------------
    |
    | 所有后台页面的 HTML title。
    |
    */
    'title' => 'Admin',

    /*
    |--------------------------------------------------------------------------
    | 静态资源域名
    |--------------------------------------------------------------------------
    |
   */
    'assets_server' => env('ADMIN_ASSETS_SERVER'),

    /*
    |--------------------------------------------------------------------------
    | 使用 HTTPS 访问
    |--------------------------------------------------------------------------
    |
    | 后台页面通过 HTTPS 访问时，设为 true。
    |
    */
    'https' => env('ADMIN_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 认证配置
    |--------------------------------------------------------------------------
    |
    | 所有后台页面的认证配置，包括认证 Guard 和用户提供者。
    |
    | 可指定登录、退出等认证路由使用的控制器。
    |
    */
    'auth' => [
        'enable' => true,

        'controller' => App\Admin\Controllers\AuthController::class,

        'guard' => 'admin',

        'guards' => [
            'admin' => [
                'driver'   => 'session',
                'provider' => 'admin',
            ],
        ],

        'providers' => [
            'admin' => [
                'driver' => 'eloquent',
                'model'  => Dcat\Admin\Models\Administrator::class,
            ],
        ],

        // 是否在登录表单中显示“记住登录”。
        'remember' => true,

        // 登录图形验证码。关闭后，前端输入框和服务端校验都会跳过。
        'captcha' => [
            // 设为 true 后，管理员登录必须通过图形验证码校验。
            'enable' => false,

            // 与其他 ValidateCode 使用场景隔离，避免 Session 数据冲突。
            'session_key' => 'dcat.login_captcha',

            // 仅覆盖登录验证码的参数；留空则使用 admin.validate_code 默认配置。
            'options' => [],
        ],

        // 所有请求方法可写为 auth/users/*/edit；
        // 指定请求方法可写为 get:auth/users。
        'except' => [
            'auth/login',
            'auth/captcha',
            'auth/logout',
        ],

        'enable_session_middleware' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | 图形验证码配置
    |--------------------------------------------------------------------------
    |
    | Admin::validateCode() 的默认参数。图片默认使用内置 Elephant.ttf
    | 字体；每次调用均可覆盖任意参数。
    |
    */
    'validate_code' => [
        // 验证码字符数量，允许范围：3-8。
        'length' => 4,

        // 图片尺寸（像素）。宽度：80-600，高度：32-180。
        'width' => 120,
        'height' => 42,

        // 验证码图片背景色，使用 RGB 数组。
        'background' => [248, 250, 252],

        // 雪花干扰强度：1 为默认；2-10 会同时增强干扰线和随机像素点。
        'noise_level' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | 全局 Grid 配置
    |--------------------------------------------------------------------------
    */
    'grid' => [

        // 全局 Grid 行操作展示类。
        'grid_action_class' => Dcat\Admin\Grid\Displayers\DropdownActions::class,

        // 全局 Grid 批量操作展示类。
        'batch_action_class' => Dcat\Admin\Grid\Tools\BatchActions::class,

        // 全局 Grid 分页展示类。
        'paginator_class' => Dcat\Admin\Grid\Tools\Paginator::class,

        'actions' => [
            'view' => Dcat\Admin\Grid\Actions\Show::class,
            'edit' => Dcat\Admin\Grid\Actions\Edit::class,
            'quick_edit' => Dcat\Admin\Grid\Actions\QuickEdit::class,
            'delete' => Dcat\Admin\Grid\Actions\Delete::class,
            'batch_delete' => Dcat\Admin\Grid\Tools\BatchDelete::class,
        ],

        // 全局 Grid 列选择器配置。
        'column_selector' => [
            'store' => Dcat\Admin\Grid\ColumnSelector\SessionStore::class,
            'store_params' => [
                'driver' => 'file',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 助手功能配置
    |--------------------------------------------------------------------------
    */
    'helpers' => [
        'enable' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 权限配置
    |--------------------------------------------------------------------------
    |
    | 所有后台页面的权限配置。
    |
    */
    'permission' => [
        // 是否启用权限控制。
        'enable' => true,

        // 所有请求方法可写为 auth/users/*/edit；
        // 指定请求方法可写为 get:auth/users。
        'except' => [
            '/',
            'auth/login',
            'auth/logout',
            'auth/setting',
        ],

        // 将路由权限应用到资源 Grid 的默认按钮和操作。
        'resource_actions' => [
            // 当前角色缺少对应路由权限时的行为：
            // hide = 不渲染操作；prompt = 渲染操作并弹出提示。
            'denied' => 'hide',

            // 是否对各默认操作启用权限控制。
            'actions' => [
                'create'       => true,
                'edit'         => true,
                'quick_edit'   => true,
                'delete'       => true,
                'batch_delete' => true,
            ],
        ],

        // 允许拥有多个角色的用户在一个会话中使用选定的单一角色；
        // 默认关闭，以兼容历史上的多角色权限并集行为。
        'active_role' => [
            // 是否启用当前角色授权模式。
            'enable' => false,

            // 管理员表中的字段，用于保存用户下次登录时默认使用的角色。
            'default_column' => 'default_role_id',
        ],

        // 统一角色编辑器配置。复用现有权限/菜单表及关联关系，
        // 因此启用后无需执行数据库迁移。
        'role_editor' => [
            // 选中的路由未匹配到权限记录时，自动创建精确权限记录。
            'auto_create' => true,

            // 将豁免/内部路由以只读诊断列表展示。
            'show_system_routes' => false,

            // 扫描包含当前前缀且未显式命名的路由。
            'include_unnamed_routes' => true,

            // 勾选父级菜单时一并勾选所有后代，并维护半选状态；
            // 父级菜单 ID 仍会保存。
            'menu_cascade' => true,

            // 额外视为系统路由的 Laravel 路由名称匹配规则。
            // 支持 Str::is 通配符，例如：dcat.admin.internal.*。
            'system_route_names' => [],

            // 额外视为系统路由的路径，路径相对于后台前缀。
            'system_paths' => [
                'dcat-api/*',
                'dcat-sys/*',
                'lake-form-media/*',
                'sku-image-*',
            ],

            // 额外视为系统路由的控制器类名/基类名通配规则。
            'system_controllers' => [],
        ],

        // 框架内部路由配置。内部路由不参与常规 URL RBAC，
        // 由其专用策略中间件保护。
        'internal' => [
            // 渲染 FormMedia、SKU 等字段时生成的组件能力令牌有效期（秒）。
            'token_ttl' => 3600,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 菜单配置
    |--------------------------------------------------------------------------
    |
    */
    'menu' => [
        'cache' => [
            // 是否启用缓存。
            'enable' => false,
            'store'  => 'file',
        ],

        // 是否启用菜单绑定权限。
        'bind_permission' => true,

        // 是否启用角色绑定菜单。
        'role_bind_menu' => true,

        // 是否启用权限绑定菜单。
        'permission_bind_menu' => true,

        'default_icon' => 'feather icon-circle',
    ],

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 上传配置
    |--------------------------------------------------------------------------
    |
    | 表单图片和文件上传的文件系统配置，包括磁盘和上传路径。
    |
    */
    'upload' => [

        // config/filesystem.php 中定义的磁盘。
        'disk' => 'public',

        // 上述磁盘下图片和文件的上传目录。
        'directory' => [
            'image' => 'images',
            'file'  => 'files',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | UEditor 配置
    |--------------------------------------------------------------------------
    |
    | UEditor 上传与前端默认配置。将 disk 设为 null 时，
    | 会复用 upload.disk 配置的磁盘。
    |
    */
    'ueditor' => [
        // 上传使用的文件系统磁盘；null 时复用 upload.disk。
        'disk' => null,

        // 字段可通过带签名、短时有效的上传目标选择的额外磁盘；留空则禁止覆盖。
        'allowed_disks' => [],

        // 每个账号每分钟允许的最大上传请求数。
        'rate_limit' => 20,

        // 字段专属上传磁盘或目录签名的有效期（秒）。
        'upload_token_ttl' => 3600,

        // 表单保存时是否在服务端清理富文本内容。
        'sanitize_html' => true,

        // 配置磁盘下的上传目录。
        'directory' => [
            // 图片上传目录。
            'image' => 'ueditor/images',
            // 视频上传目录。
            'video' => 'ueditor/videos',
            // 附件上传目录。
            'file'  => 'ueditor/files',
        ],

        // 图片 URL 前缀；上传控制器返回绝对 URL 时保持为空。
        'url_prefix' => '',

        // 是否从服务端加载上传动作、字段名称、限制和允许列表。
        'load_config_from_server' => true,
        // 编辑器初始高度（像素）。
        'initial_frame_height'    => 400,
        // 是否在编辑器底部显示元素路径。
        'element_path_enabled'    => false,
        // 是否随内容增加自动增长编辑器高度。
        'auto_height_enabled'     => true,
        // 是否启用 AI 工具；默认关闭，避免向第三方泄露数据。
        'enable_ai'               => false,
        // 是否使用内置表情，替代默认的第三方 HTTP 服务。
        'emotion_localization'    => true,
        // 编辑器、编辑 iframe 和弹窗是否跟随 Dcat 深色模式。
        'dark_mode'               => true,

        // 工具栏布局。可删除按钮名称或加入 UEditor 支持的按钮；“|”表示分隔符。
        'toolbars' => [
            [
                'fullscreen', 'source', '|', 'undo', 'redo', '|',
                'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript',
                'removeformat', 'formatmatch', 'autotypeset', 'blockquote', 'pasteplain', '|',
                'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', 'selectall', 'cleardoc', '|',
                'rowspacingtop', 'rowspacingbottom', 'lineheight', '|',
                'customstyle', 'paragraph', 'fontfamily', 'fontsize', '|',
                'directionalityltr', 'directionalityrtl', 'indent', '|',
                'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|', 'touppercase', 'tolowercase', '|',
                'link', 'unlink', 'anchor', '|', 'imagenone', 'imageleft', 'imageright', 'imagecenter', '|',
                'simpleupload', 'insertimage', 'emotion', 'scrawl', 'insertvideo', 'music', 'attachment', 'map', 'insertcode', 'template',
                'background', '|', 'horizontal', 'date', 'time', 'spechars', '|',
                'inserttable', 'deletetable', 'insertparagraphbeforetable', 'insertrow', 'deleterow',
                'insertcol', 'deletecol', 'mergecells', 'mergeright', 'mergedown', 'splittocells',
                'splittorows', 'splittocols', '|', 'print', 'preview', 'searchreplace',
            ],
        ],

        // 图片上传规则。
        'image' => [
            // 允许的图片最大字节数。
            'max_size'   => 2048000,
            // 允许的图片文件扩展名。
            'allow_files' => ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'],
            // 允许的图片 MIME 类型；空数组表示不校验 MIME 类型。
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'],
        ],

        // 视频上传规则。
        'video' => [
            // 允许的视频最大字节数。
            'max_size'   => 102400000,
            // 允许的视频文件扩展名。
            'allow_files' => ['.mp4', '.avi', '.wmv', '.mov', '.flv', '.mkv', '.webm', '.m4v'],
            // 允许的视频 MIME 类型；空数组表示不校验 MIME 类型。
            'mime_types' => ['video/mp4', 'video/x-msvideo', 'video/x-ms-wmv', 'video/quicktime', 'video/x-flv', 'video/x-matroska', 'video/webm'],
        ],

        // 附件上传规则。
        'file' => [
            // 允许的附件最大字节数。
            'max_size'   => 51200000,
            // 允许的附件文件扩展名。
            'allow_files' => [
                '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pdf',
                '.zip', '.rar', '.7z', '.gz', '.tar', '.txt', '.csv', '.md',
                '.mp3', '.wav', '.aac', '.flac', '.ogg',
            ],
            // 允许的附件 MIME 类型；空数组表示不校验 MIME 类型。
            'mime_types' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dcat Admin 数据库配置
    |--------------------------------------------------------------------------
    |
    | Dcat Admin 内置模型和数据表的数据库配置。
    |
    */
    'database' => [

        // 以下数据表使用的数据库连接。
        'connection' => '',

        // 用户数据表及模型。
        'users_table' => 'admin_users',
        'users_model' => Dcat\Admin\Models\Administrator::class,

        // 角色数据表及模型。
        'roles_table' => 'admin_roles',
        'roles_model' => Dcat\Admin\Models\Role::class,

        // 权限数据表及模型。
        'permissions_table' => 'admin_permissions',
        'permissions_model' => Dcat\Admin\Models\Permission::class,

        // 菜单数据表及模型。
        'menu_table' => 'admin_menu',
        'menu_model' => Dcat\Admin\Models\Menu::class,

        // 上述关联关系使用的中间表。
        'role_users_table'       => 'admin_role_users',
        'role_permissions_table' => 'admin_role_permissions',
        'role_menu_table'        => 'admin_role_menu',
        'permission_menu_table'  => 'admin_permission_menu',
        'settings_table'         => 'admin_settings',
        'extensions_table'       => 'admin_extensions',
        'extension_histories_table' => 'admin_extension_histories',
    ],

    /*
    |--------------------------------------------------------------------------
    | 后台布局
    |--------------------------------------------------------------------------
    |
    | 后台页面的布局配置。
    */
    'layout' => [
        // 可选：default、blue、blue-light、green。
        'color' => 'default',

        // 可选：sidebar-separate。
        'body_class' => [],

        'horizontal_menu' => false,

        'sidebar_collapsed' => false,

        // 可选：light、primary、dark。
        'sidebar_style' => 'light',

        'dark_mode_switch' => false,

        // 可选：bg-primary、bg-info、bg-warning、bg-success、bg-danger、bg-dark。
        'navbar_color' => '',

        'full_screen' => true, // 是否展示全屏按钮

        'home_url'=> env('APP_URL'), // 是否展示官网url

        'layout_config_tool_in_navbar' => true, // 是否将布局配置工具放置在导航栏（默认放置在侧边栏）
    ],
    
    'iframe_tab' => [
        /*
        |--------------------------------------------------------------------------
        | 后台 iframe 标签页入口
        |--------------------------------------------------------------------------
        |
        | 适配版默认不替换 /admin 首页，只新增 /admin/dcat-sys/iframe-tabs 作为标签页入口。
        | 这样即使标签页模式出现兼容问题，也可以直接回到原后台入口排查。
        |
        */
        'enabled' => env('ADMIN_IFRAME_TAB_ENABLED', true),

        'shell_path' => env('ADMIN_IFRAME_TAB_SHELL_PATH', 'dcat-sys/iframe-tabs'),

        'query_key' => env('ADMIN_IFRAME_TAB_QUERY_KEY', 'iframe_tab'),

        'home_path' => env('ADMIN_IFRAME_TAB_HOME_PATH', '/'),

        'home_title' => env('ADMIN_IFRAME_TAB_HOME_TITLE', '首页'),

        /*
        |--------------------------------------------------------------------------
        | 标签页行为
        |--------------------------------------------------------------------------
        */
        'cache' => env('ADMIN_IFRAME_TAB_CACHE', false),

        'lazy_load' => env('ADMIN_IFRAME_TAB_LAZY_LOAD', true),

        'dialog_area_width' => env('ADMIN_IFRAME_TAB_DIALOG_AREA_WIDTH', '70%'),

        'dialog_area_height' => env('ADMIN_IFRAME_TAB_DIALOG_AREA_HEIGHT', '90vh'),
        
        'asset_version' => env('ADMIN_IFRAME_TAB_ASSET_VERSION', '1.0.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 异常处理类
    |--------------------------------------------------------------------------
    |
    */
    'exception_handler' => Dcat\Admin\Exception\Handler::class,

    /*
    |--------------------------------------------------------------------------
    | 启用默认面包屑导航
    |--------------------------------------------------------------------------
    |
    | 是否为每个页面内容启用默认面包屑导航。
    */
    'enable_default_breadcrumb' => true,

    /*
    |--------------------------------------------------------------------------
    | 扩展配置
    |--------------------------------------------------------------------------
    */
    'extension' => [
        // 使用 php artisan admin:ext-make 创建扩展时，
        // 扩展文件会生成到此目录。
        'dir' => base_path('dcat-admin-extensions'),
    ],
    /*
    |--------------------------------------------------------------------------
    | ApexCharts 版本
    |--------------------------------------------------------------------------
    |
    | 后台图表组件 (Dcat\Admin\Widgets\ApexCharts\Chart) 使用的 ApexCharts 版本。
    |
    | 可选值：
    |   - 'v3'：ApexCharts 3.17.1（默认，向后兼容）
    |   - 'v5'：ApexCharts 5.16.0
    |
    | 切换版本后，需重新发布前端资产：
    |     php artisan admin:publish --assets --force
    |
    */
    'apexcharts_version' => env('ADMIN_APEXCHARTS_VERSION', 'v3'),

    /*
    |--------------------------------------------------------------------------
    | 多应用
    |--------------------------------------------------------------------------
    */
    'multi_app'                 => [
        // 'seller'    => true,
        // 'reseller'  => true,
        // 'brand'     => true,
    ],
];
