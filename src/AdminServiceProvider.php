<?php

namespace Dcat\Admin;

use Dcat\Admin\Contracts\ExceptionHandler;
use Dcat\Admin\Exception\Handler;
use Dcat\Admin\Extend\Manager;
use Dcat\Admin\Extend\UpdateManager;
use Dcat\Admin\Extend\VersionManager;
use Dcat\Admin\Form\Extend;
use Dcat\Admin\Form\Extend\Distpicker\DcatDistpickerServiceProvider;
use Dcat\Admin\Form\Extend\Diyforms\DcatDiyFormServiceProvider;
use Dcat\Admin\Form\Extend\FormMedia\FormMediaServiceProvider;
use Dcat\Admin\Form\Steps\FormStepServiceProvider;
use Dcat\Admin\Layout\Asset;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Menu;
use Dcat\Admin\Layout\Navbar;
use Dcat\Admin\Layout\SectionManager;
use Dcat\Admin\Support\Context;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Support\Setting;
use Dcat\Admin\Support\Translator;
use Dcat\Admin\Support\WebUploader;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

class AdminServiceProvider extends ServiceProvider {
    /**
     * @var array
     */
    protected $commands = [
        Console\AdminCommand::class,
        Console\InstallCommand::class,
        Console\PublishCommand::class,
        Console\UninstallCommand::class,
        Console\CreateUserCommand::class,
        Console\ResetPasswordCommand::class,
        Console\ExportSeedCommand::class,
        Console\IdeHelperCommand::class,
        Console\FormCommand::class,
        Console\ActionCommand::class,
        Console\MenuCacheCommand::class,
        Console\MinifyCommand::class,
        Console\AppCommand::class,
        Console\ExtensionMakeCommand::class,
        Console\ExtensionMakeProCommand::class,
        Console\ExtensionInstallCommand::class,
        Console\ExtensionUninstallCommand::class,
        Console\ExtensionRefreshCommand::class,
        Console\ExtensionRollbackCommand::class,
        Console\ExtensionEnableCommand::class,
        Console\ExtensionDiableCommand::class,
        Console\ExtensionUpdateCommand::class,
        Console\UpdateCommand::class,
    ];

    /**
     * 开发环境命令.
     *
     * @var array
     */
    protected $devCommands = [
        Console\Development\LinkCommand::class,
    ];

    /**
     * @var array
     */
    protected $routeMiddleware = [
        'admin.auth'       => Http\Middleware\Authenticate::class,
        'admin.pjax'       => Http\Middleware\Pjax::class,
        'admin.permission' => Http\Middleware\Permission::class,
        'admin.bootstrap'  => Http\Middleware\Bootstrap::class,
        'admin.session'    => Http\Middleware\Session::class,
        'admin.upload'     => Http\Middleware\WebUploader::class,
        'admin.app'        => Http\Middleware\Application::class,
        'admin.apiAuth'     => \App\Http\Middleware\AdminApiAuth::class,
        'member.apiAuth'     => \App\Http\Middleware\MemberApiAuth::class,
    ];

    /**
     * @var array
     */
    protected $middlewareGroups = [
        'admin' => [
            'admin.auth',
            'admin.pjax',
            'admin.bootstrap',
            'admin.permission',
            'admin.session',
            'admin.upload',
        ],
    ];

    public function register() {
        $this->registerRouteMiddleware();
        // 更早执行，避免 boot() 的延迟
        $this->app->afterResolving('view', function ($view) {
            if (file_exists(base_path('config/scramble.php'))) {
                View::composer('scramble::docs', function ($view) {
                    // 重新指定视图路径
                    $view->setPath(base_path('vendor/dcat-plus/laravel-admin/resources/views/apidoc/docs.blade.php'));
                });
            }
        });
        // 如果是 API 请求，跳过 Admin 初始化
        if ($this->isApiRequest()) {
            return;
        }
        $this->aliasAdmin();
        $this->loadAdminAuthConfig();
        
        $this->registerServices();
        $this->registerExtensions();

        $this->commands($this->commands);

        if (config('app.debug')) {
            $this->commands($this->devCommands);
        }
        
    }

    public function boot() {
        $this->mapAdminApiRoutes();
        $this->mapMemberApiRoutes();
        // 如果是 API 请求，跳过 Admin 启动
        if ($this->isApiRequest()) {
            return;
        }
        $this->registerDefaultSections();
        $this->registerViews();
        $this->ensureHttps();
        $this->bootApplication();
        $this->registerPublishing();
        $this->compatibleBlade();
        $this->bootExtensions();
        $this->registerBladeDirective();

        $this->app->register(FormStepServiceProvider::class);
        $this->app->register(DcatDistpickerServiceProvider::class);
        $this->app->register(DcatDiyFormServiceProvider::class);
        $this->app->register(FormMediaServiceProvider::class);
        \Dcat\Admin\Widgets\Tooltip::make('.tips')->purple();

        // 注册 admin-api 文档
        $api_path = config('admin.openapi.admin-api.api_path','admin-api');

        \Dedoc\Scramble\Scramble::registerApi($api_path, [
            'api_path' => $api_path,
            'info' => [
                'version' => config('admin.openapi.admin-api.info.version','1.0.0'),
                'description' => config('admin.openapi.admin-api.info.description','管理端api文档'),
            ],
            'ui' => [
                'title' => config('admin.openapi.admin-api.ui.title','B端Api文档'),
            ]
        ])->withDocumentTransformers(function (OpenApi $openApi) {
            $openApi->secure(
                SecurityScheme::http('Bearer','JWT')
            );
        });

        \Dedoc\Scramble\Scramble::registerUiRoute('docs/'.$api_path,$api_path);
        \Dedoc\Scramble\Scramble::registerJsonSpecificationRoute('docs/'.$api_path.'json', $api_path);
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('Bearer','JWT')
                );
            });
    }

    protected function aliasAdmin() {
        if (!class_exists(\Admin::class)) {
            class_alias(Admin::class, \Admin::class);
        }
    }

    protected function registerViews() {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'admin');
    }

    /**
     * 是否强制使用https.
     *
     * @return void
     */
    protected function ensureHttps() {
        if (config('admin.https') || config('admin.secure')) {
            \URL::forceScheme('https');
            $this->app['request']->server->set('HTTPS', true);
        }
    }

    /**
     * 路由注册.
     */
    protected function bootApplication() {
        Admin::app()->boot();
    }

    /**
     * 禁止laravel 5.6或更高版本中启用双编码的默认特性.
     *
     * @return void
     */
    protected function compatibleBlade() {
        $bladeReflectionClass = new \ReflectionClass('\Illuminate\View\Compilers\BladeCompiler');
        if ($bladeReflectionClass->hasMethod('withoutDoubleEncoding')) {
            Blade::withoutDoubleEncoding();
        }
    }

    /**
     * 资源发布注册.
     *
     * @return void
     */
    protected function registerPublishing() {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../config' => config_path()], 'dcat-admin-config');
            $this->publishes([__DIR__ . '/../resources/lang' => $this->app->langPath()], 'dcat-admin-lang');
            $this->publishes([__DIR__ . '/../database/migrations' => database_path('migrations')], 'dcat-admin-migrations');
            $this->publishes([__DIR__ . '/../resources/dist' => public_path(Admin::asset()->getRealPath('@admin'))], 'dcat-admin-assets');
        }
    }

    /**
     * 设置 auth 配置.
     *
     * @return void
     */
    protected function loadAdminAuthConfig() {
        config(Arr::dot(config('admin.auth', []), 'auth.'));

        foreach ((array)config('admin.multi_app') as $app => $enable) {
            if ($enable) {
                config(Arr::dot(config($app . '.auth', []), 'auth.'));
            }
        }
    }

    /**
     * 默认 section 注册.
     */
    protected function registerDefaultSections() {
        Content::composing(function () {
            if (!admin_has_default_section(Admin::SECTION['NAVBAR_USER_PANEL'])) {
                admin_inject_default_section(Admin::SECTION['NAVBAR_USER_PANEL'], function () {
                    return view('admin::partials.navbar-user-panel', ['user' => Admin::user()]);
                });
            }

            if (!admin_has_default_section(Admin::SECTION['LEFT_SIDEBAR_USER_PANEL'])) {
                admin_inject_default_section(Admin::SECTION['LEFT_SIDEBAR_USER_PANEL'], function () {
                    return view('admin::partials.sidebar-user-panel', ['user' => Admin::user()]);
                });
            }

            // Register menu
            Admin::menu()->register();
        }, true);
    }

    public function registerServices() {
        $this->app->singleton('admin.app', Application::class);
        $this->app->singleton('admin.asset', Asset::class);
        $this->app->singleton('admin.color', Color::class);
        $this->app->singleton('admin.sections', SectionManager::class);
        $this->app->singleton('admin.extend', Manager::class);
        $this->app->singleton('admin.extend.update', function () {
            return new UpdateManager(app('admin.extend'));
        });
        $this->app->singleton('admin.extend.version', function () {
            return new VersionManager(app('admin.extend'));
        });
        $this->app->singleton('admin.navbar', Navbar::class);
        $this->app->singleton('admin.menu', Menu::class);
        $this->app->singleton('admin.context', Context::class);
        $this->app->singleton('admin.setting', function () {
            return Setting::fromDatabase();
        });
        $this->app->singleton('admin.web-uploader', WebUploader::class);
        $this->app->singleton(ExceptionHandler::class, config('admin.exception_handler') ?: Handler::class);
        $this->app->singleton('admin.translator', Translator::class);
    }

    public function registerExtensions() {
        Admin::extension()->register();
    }

    public function bootExtensions() {
        Admin::extension()->boot();
    }

    protected function registerBladeDirective() {
        Blade::directive('primary', function ($amt = 0) {
            return <<<PHP
<?php echo admin_color()->primary($amt); ?>
PHP;
        });
    }

    /**
     * 路由中间件注册.
     *
     * @return void
     */
    protected function registerRouteMiddleware() {
        $router = $this->app->make('router');

        // register route middleware.
        foreach ($this->routeMiddleware as $key => $middleware) {
            $router->aliasMiddleware($key, $middleware);
        }

        $disablePermission = !config('admin.permission.enable');

        // register middleware group.
        foreach ($this->middlewareGroups as $key => $middleware) {
            if ($disablePermission) {
                Helper::deleteByValue($middleware, 'admin.permission', true);
            }
            $router->middlewareGroup($key, $middleware);
        }
    }

    // Admin api routes
    protected function mapAdminApiRoutes() {
        if(file_exists(base_path('app/Admin/Api/routes.php'))){
            \Illuminate\Support\Facades\Route::prefix('admin-api')
                ->middleware('api')
                ->namespace('App\Admin\Api\Controllers')
                ->group(base_path('app/Admin/Api/routes.php'));
        }
    }

    // Admin api routes
    protected function mapMemberApiRoutes() {
        if(file_exists(base_path('app/Api/routes.php'))){
            \Illuminate\Support\Facades\Route::prefix('member-api')
                ->middleware('api')
                ->namespace('App\Api\Controllers')
                ->group(base_path('app/Api/routes.php'));
        }
    }

    protected function isApiRequest(){
        $request = request();
        // 方法 1：通过路由前缀判断
        if ($request->is('api/*') || $request->is('member-api/*') || $request->is('admin-api/*') || $request->is('seller-api/*') || $request->is('docs/*')) {
            return true;
        }

        // 方法 2：通过自定义头判断
        // if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
        //     return true;
        // }
        
        return false;
    }
}
