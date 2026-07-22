<?php

return [

    /*
    |--------------------------------------------------------------------------
    | dcat-admin name
    |--------------------------------------------------------------------------
    |
    | This value is the name of dcat-admin, This setting is displayed on the
    | login page.
    |
    */
    'name' => 'Dcat-plus Admin',

    /*
    |--------------------------------------------------------------------------
    | dcat-admin logo
    |--------------------------------------------------------------------------
    |
    | The logo of all admin pages. You can also set it as an image by using a
    | `img` tag, eg '<img src="http://logo-url" alt="Admin logo">'.
    |
    */
    'logo' => '<img src="/vendor/dcat-admin/images/logo.png" width="35"> &nbsp;Dcat-plus Admin',

    /*
    |--------------------------------------------------------------------------
    | dcat-admin mini logo
    |--------------------------------------------------------------------------
    |
    | The logo of all admin pages when the sidebar menu is collapsed. You can
    | also set it as an image by using a `img` tag, eg
    | '<img src="http://logo-url" alt="Admin logo">'.
    |
    */
    'logo-mini' => '<img src="/vendor/dcat-admin/images/logo.png">',

    /*
    |--------------------------------------------------------------------------
    | dcat-admin favicon
    |--------------------------------------------------------------------------
    |
    */
    'favicon' => '@admin/images/favicon.ico',

    /*
     |--------------------------------------------------------------------------
     | User default avatar
     |--------------------------------------------------------------------------
     |
     | Set a default avatar for newly created users.
     |
     */
    'default_avatar' => '@admin/images/default-avatar.jpg',

    /*
    |--------------------------------------------------------------------------
    | Login page background image
    |--------------------------------------------------------------------------
    |
    | This value is used to set the background image of login page.
    |
    */
    'login_background_image' => '@admin/images/login_32-bg.jpg',
    /*
    |--------------------------------------------------------------------------
    | dcat-admin route settings
    |--------------------------------------------------------------------------
    |
    | The routing configuration of the admin page, including the path prefix,
    | the controller namespace, and the default middleware. If you want to
    | access through the root path, just set the prefix to empty string.
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
    | dcat-admin install directory
    |--------------------------------------------------------------------------
    |
    | The installation directory of the controller and routing configuration
    | files of the administration page. The default is `app/Admin`, which must
    | be set before running `artisan admin::install` to take effect.
    |
    */
    'directory' => app_path('Admin'),

    /*
    |--------------------------------------------------------------------------
    | dcat-admin html title
    |--------------------------------------------------------------------------
    |
    | Html title for all pages.
    |
    */
    'title' => 'Admin',

    /*
    |--------------------------------------------------------------------------
    | Assets hostname
    |--------------------------------------------------------------------------
    |
   */
    'assets_server' => env('ADMIN_ASSETS_SERVER'),

    /*
    |--------------------------------------------------------------------------
    | Access via `https`
    |--------------------------------------------------------------------------
    |
    | If your page is going to be accessed via https, set it to `true`.
    |
    */
    'https' => env('ADMIN_HTTPS', false),

    /*
    |--------------------------------------------------------------------------
    | dcat-admin auth setting
    |--------------------------------------------------------------------------
    |
    | Authentication settings for all admin pages. Include an authentication
    | guard and a user provider setting of authentication driver.
    |
    | You can specify a controller for `login` `logout` and other auth routes.
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

        // Add "remember me" to login form
        'remember' => true,

        // Login graphic captcha. When disabled, neither the input nor server-side validation is used.
        'captcha' => [
            // Set true to require a graphic captcha when administrators log in.
            'enable' => false,

            // Kept separate from other ValidateCode usages to prevent session collisions.
            'session_key' => 'dcat.login_captcha',

            // Override only this login captcha's ValidateCode options; empty uses admin.validate_code defaults.
            'options' => [],
        ],

        // All method to path like: auth/users/*/edit
        // or specific method to path like: get:auth/users.
        'except' => [
            'auth/login',
            'auth/captcha',
            'auth/logout',
        ],

        'enable_session_middleware' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Graphic validation code setting
    |--------------------------------------------------------------------------
    |
    | Defaults for Admin::validateCode(). The image uses the bundled
    | Elephant.ttf font by default. Each call may override any value.
    |
    */
    'validate_code' => [
        // Number of characters shown in the image. Allowed range: 3-8.
        'length' => 4,

        // Image dimensions in pixels. Width: 80-600, height: 32-180.
        'width' => 120,
        'height' => 42,

        // Captcha image background color as an RGB array.
        'background' => [248, 250, 252],

        // Snowflake-style visual noise strength: 1 is default; 2-10 increase both types.
        'noise_level' => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | The global Grid setting
    |--------------------------------------------------------------------------
    */
    'grid' => [

        // The global Grid action display class.
        'grid_action_class' => Dcat\Admin\Grid\Displayers\DropdownActions::class,

        // The global Grid batch action display class.
        'batch_action_class' => Dcat\Admin\Grid\Tools\BatchActions::class,

        // The global Grid pagination display class.
        'paginator_class' => Dcat\Admin\Grid\Tools\Paginator::class,

        'actions' => [
            'view' => Dcat\Admin\Grid\Actions\Show::class,
            'edit' => Dcat\Admin\Grid\Actions\Edit::class,
            'quick_edit' => Dcat\Admin\Grid\Actions\QuickEdit::class,
            'delete' => Dcat\Admin\Grid\Actions\Delete::class,
            'batch_delete' => Dcat\Admin\Grid\Tools\BatchDelete::class,
        ],

        // The global Grid column selector setting.
        'column_selector' => [
            'store' => Dcat\Admin\Grid\ColumnSelector\SessionStore::class,
            'store_params' => [
                'driver' => 'file',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | dcat-admin helpers setting.
    |--------------------------------------------------------------------------
    */
    'helpers' => [
        'enable' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | dcat-admin permission setting
    |--------------------------------------------------------------------------
    |
    | Permission settings for all admin pages.
    |
    */
    'permission' => [
        // Whether enable permission.
        'enable' => true,

        // All method to path like: auth/users/*/edit
        // or specific method to path like: get:auth/users.
        'except' => [
            '/',
            'auth/login',
            'auth/logout',
            'auth/setting',
        ],

        // Apply route permissions to the default resource Grid buttons/actions.
        'resource_actions' => [
            // Behavior when the current role has no corresponding route permission:
            // hide = do not render the action; prompt = render it and show a dialog.
            'denied' => 'hide',

            // Enable or disable permission control for individual default actions.
            'actions' => [
                'create'       => true,
                'edit'         => true,
                'quick_edit'   => true,
                'delete'       => true,
                'batch_delete' => true,
            ],
        ],

        // Let a user with multiple roles work under one selected role per
        // session. Disabled by default to preserve historical union behavior.
        'active_role' => [
            // Enable the current-role authorization model.
            'enable' => false,

            // Column on the administrator table that stores the role selected
            // by an administrator as the user's default at next login.
            'default_column' => 'default_role_id',
        ],

        // Unified role editor settings. Existing permission/menu tables and
        // relationships are reused, so enabling this does not require a migration.
        'role_editor' => [
            // Create an exact permission record when a selected route has no match.
            'auto_create' => true,

            // Show exempt/internal routes as a read-only diagnostic list.
            'show_system_routes' => false,

            // Include current-prefix routes without an explicit route name.
            'include_unnamed_routes' => true,

            // Checking a parent menu also checks its descendants and maintains
            // the indeterminate state. Parent IDs are still persisted.
            'menu_cascade' => true,

            // Additional Laravel route-name patterns treated as system routes.
            // Supports Str::is wildcards, for example: dcat.admin.internal.*
            'system_route_names' => [],

            // Additional paths treated as system routes, relative to admin prefix.
            'system_paths' => [
                'dcat-api/*',
                'dcat-sys/*',
                'lake-form-media/*',
                'sku-image-*',
            ],

            // Controller class/basename wildcard patterns treated as system routes.
            'system_controllers' => [],
        ],

        // Framework-internal route settings. Internal routes are excluded from
        // ordinary URL RBAC and protected by their own policy middleware.
        'internal' => [
            // Lifetime in seconds for component capability tokens generated
            // while rendering fields such as FormMedia and SKU.
            'token_ttl' => 3600,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | dcat-admin menu setting
    |--------------------------------------------------------------------------
    |
    */
    'menu' => [
        'cache' => [
            // enable cache or not
            'enable' => false,
            'store'  => 'file',
        ],

        // Whether enable menu bind to a permission.
        'bind_permission' => true,

        // Whether enable role bind to menu.
        'role_bind_menu' => true,

        // Whether enable permission bind to menu.
        'permission_bind_menu' => true,

        'default_icon' => 'feather icon-circle',
    ],

    /*
    |--------------------------------------------------------------------------
    | dcat-admin upload setting
    |--------------------------------------------------------------------------
    |
    | File system configuration for form upload files and images, including
    | disk and upload path.
    |
    */
    'upload' => [

        // Disk in `config/filesystem.php`.
        'disk' => 'public',

        // Image and file upload path under the disk above.
        'directory' => [
            'image' => 'images',
            'file'  => 'files',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | UEditor setting
    |--------------------------------------------------------------------------
    |
    | UEditor upload and frontend defaults. Set `disk` to null to reuse the
    | disk configured in `upload.disk`.
    |
    */
    'ueditor' => [
        // The filesystem disk used for uploads; null reuses upload.disk.
        'disk' => null,

        // Extra disks a field may select with a signed, short-lived upload target; empty denies overrides.
        'allowed_disks' => [],

        // Maximum number of upload requests per account per minute.
        'rate_limit' => 20,

        // Lifetime in seconds for a signed field-specific upload disk or directory.
        'upload_token_ttl' => 3600,

        // Whether to sanitize rich text server-side when the form is saved.
        'sanitize_html' => true,

        // Upload directories under the configured disk.
        'directory' => [
            // Directory for image uploads.
            'image' => 'ueditor/images',
            // Directory for video uploads.
            'video' => 'ueditor/videos',
            // Directory for attachment uploads.
            'file'  => 'ueditor/files',
        ],

        // Image URL prefix; keep empty when the upload controller returns absolute URLs.
        'url_prefix' => '',

        // Whether to load upload actions, field names, limits, and allow lists from the server.
        'load_config_from_server' => true,
        // Initial editor height in pixels.
        'initial_frame_height'    => 400,
        // Whether to display the element path at the bottom of the editor.
        'element_path_enabled'    => false,
        // Whether to grow the editor height as content is added.
        'auto_height_enabled'     => true,
        // Whether to enable AI tools; disabled by default to prevent third-party data disclosure.
        'enable_ai'               => false,
        // Whether to use bundled emoticons instead of the default third-party HTTP endpoint.
        'emotion_localization'    => true,
        // Whether to follow Dcat dark mode for the editor, editing iframe, and dialogs.
        'dark_mode'               => true,

        // Toolbar layout. Remove button names or add supported UEditor buttons; "|" is a separator.
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

        // Image upload rules.
        'image' => [
            // Maximum allowed image size in bytes.
            'max_size'   => 2048000,
            // Allowed image file extensions.
            'allow_files' => ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'],
            // Allowed image MIME types; an empty array disables MIME validation.
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'],
        ],

        // Video upload rules.
        'video' => [
            // Maximum allowed video size in bytes.
            'max_size'   => 102400000,
            // Allowed video file extensions.
            'allow_files' => ['.mp4', '.avi', '.wmv', '.mov', '.flv', '.mkv', '.webm', '.m4v'],
            // Allowed video MIME types; an empty array disables MIME validation.
            'mime_types' => ['video/mp4', 'video/x-msvideo', 'video/x-ms-wmv', 'video/quicktime', 'video/x-flv', 'video/x-matroska', 'video/webm'],
        ],

        // Attachment upload rules.
        'file' => [
            // Maximum allowed attachment size in bytes.
            'max_size'   => 51200000,
            // Allowed attachment file extensions.
            'allow_files' => [
                '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pdf',
                '.zip', '.rar', '.7z', '.gz', '.tar', '.txt', '.csv', '.md',
                '.mp3', '.wav', '.aac', '.flac', '.ogg',
            ],
            // Allowed attachment MIME types; an empty array disables MIME validation.
            'mime_types' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | dcat-admin database settings
    |--------------------------------------------------------------------------
    |
    | Here are database settings for dcat-admin builtin model & tables.
    |
    */
    'database' => [

        // Database connection for following tables.
        'connection' => '',

        // User tables and model.
        'users_table' => 'admin_users',
        'users_model' => Dcat\Admin\Models\Administrator::class,

        // Role table and model.
        'roles_table' => 'admin_roles',
        'roles_model' => Dcat\Admin\Models\Role::class,

        // Permission table and model.
        'permissions_table' => 'admin_permissions',
        'permissions_model' => Dcat\Admin\Models\Permission::class,

        // Menu table and model.
        'menu_table' => 'admin_menu',
        'menu_model' => Dcat\Admin\Models\Menu::class,

        // Pivot table for table above.
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
    | Application layout
    |--------------------------------------------------------------------------
    |
    | This value is the layout of admin pages.
    */
    'layout' => [
        // default, blue, blue-light, green
        'color' => 'default',

        // sidebar-separate
        'body_class' => [],

        'horizontal_menu' => false,

        'sidebar_collapsed' => false,

        // light, primary, dark
        'sidebar_style' => 'light',

        'dark_mode_switch' => false,

        // bg-primary, bg-info, bg-warning, bg-success, bg-danger, bg-dark
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
    | The exception handler class
    |--------------------------------------------------------------------------
    |
    */
    'exception_handler' => Dcat\Admin\Exception\Handler::class,

    /*
    |--------------------------------------------------------------------------
    | Enable default breadcrumb
    |--------------------------------------------------------------------------
    |
    | Whether enable default breadcrumb for every page content.
    */
    'enable_default_breadcrumb' => true,

    /*
    |--------------------------------------------------------------------------
    | Extension
    |--------------------------------------------------------------------------
    */
    'extension' => [
        // When you use command `php artisan admin:ext-make` to generate extensions,
        // the extension files will be generated in this directory.
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
