<?php

namespace Dcat\Admin\Tests\Unit\Support\Authorization;

use Dcat\Admin\Application;
use Dcat\Admin\Admin;
use Dcat\Admin\Support\Authorization\RouteCatalog;
use Dcat\Admin\Support\Authorization\RoutePermissionMetadata;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;

class RouteCatalogTest extends TestCase
{
    protected ?Container $previousContainer = null;

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function test_it_uses_real_resource_actions_and_http_methods(): void
    {
        [$router, $application] = $this->environment();

        $router->group([
            'prefix' => 'admin',
            'as' => 'dcat.admin.',
            'middleware' => 'admin.app:admin',
        ], function (Router $router) {
            $router->resource('users', RouteCatalogResourceController::class)->only(['index', 'update']);
            $router->post('users/import', [RouteCatalogResourceController::class, 'import'])->name('users.import');
            $router->get('users/export', [RouteCatalogResourceController::class, 'export'])->name('users.export');
            $router->post('reports/{report}/publish', [RouteCatalogResourceController::class, 'publish'])
                ->name('reports.publish');
        });

        $catalog = new RouteCatalog($router, $application, ['include_unnamed_routes' => true]);
        $grouped = $catalog->grouped(false);

        $this->assertCount(1, $grouped['resources']);
        $this->assertSame(['index', 'update', 'import', 'export'], array_column($grouped['resources'][0]['actions'], 'resource_action'));

        $update = collect($grouped['resources'][0]['actions'])->firstWhere('resource_action', 'update');
        $this->assertSame(['PUT', 'PATCH'], $update['http_methods']);
        $this->assertSame('/users/*', $update['http_path']);

        $import = collect($grouped['resources'][0]['actions'])->firstWhere('resource_action', 'import');
        $export = collect($grouped['resources'][0]['actions'])->firstWhere('resource_action', 'export');
        $this->assertSame(['POST'], $import['http_methods']);
        $this->assertSame('/users/import', $import['http_path']);
        $this->assertSame(['GET'], $export['http_methods']);
        $this->assertSame('/users/export', $export['http_path']);

        $this->assertCount(1, $grouped['singles']);
        $this->assertSame(['POST'], $grouped['singles'][0]['http_methods']);
        $this->assertSame('/reports/*/publish', $grouped['singles'][0]['http_path']);
    }

    public function test_it_excludes_other_panels_and_system_routes(): void
    {
        [$router, $application] = $this->environment();

        $router->group(['prefix' => 'admin', 'as' => 'dcat.admin.', 'middleware' => 'admin.app:admin'], function (Router $router) {
            $router->get('dashboard', [RouteCatalogResourceController::class, 'index'])->name('dashboard');
            $router->get('auth/login', [RouteCatalogResourceController::class, 'login'])->name('login');
            $router->post('protected', [RouteCatalogResourceController::class, 'publish'])
                ->middleware('admin.permission:check')
                ->name('protected');
        });
        $router->group(['prefix' => 'seller', 'as' => 'dcat.seller.', 'middleware' => 'admin.app:seller'], function (Router $router) {
            $router->get('dashboard', [RouteCatalogResourceController::class, 'index'])->name('dashboard');
        });

        $catalog = new RouteCatalog($router, $application, ['include_unnamed_routes' => false]);
        $visible = $catalog->all(false);
        $withSystem = $catalog->all(true);

        $this->assertCount(1, $visible);
        $this->assertSame('dcat.admin.dashboard', reset($visible)['route_name']);
        $this->assertCount(3, $withSystem);
        $this->assertFalse((bool) collect($withSystem)->firstWhere('route_name', 'dcat.seller.dashboard'));
    }

    public function test_it_excludes_internal_route_metadata_from_role_permissions(): void
    {
        [$router, $application] = $this->environment();

        $router->group(['prefix' => 'admin', 'as' => 'dcat.admin.', 'middleware' => 'admin.app:admin'], function (Router $router) {
            $router->get('articles', [RouteCatalogResourceController::class, 'index'])->name('articles.index');
            $router->get('dcat-sys/ping', [RouteCatalogResourceController::class, 'index'])
                ->defaults('dcat_route_type', 'internal')
                ->name('dcat-sys.ping');
            $router->post('legacy-upload', [RouteCatalogResourceController::class, 'publish'])
                ->defaults('dcat_route_type', 'internal_legacy')
                ->name('legacy-upload');
        });

        $catalog = new RouteCatalog($router, $application, ['include_unnamed_routes' => true]);
        $visible = $catalog->all(false);
        $withSystem = $catalog->all(true);

        $this->assertCount(1, $visible);
        $this->assertSame('dcat.admin.articles.index', reset($visible)['route_name']);

        $internal = collect($withSystem)->firstWhere('route_name', 'dcat.admin.dcat-sys.ping');
        $this->assertNotNull($internal);
        $this->assertTrue($internal['internal']);
        $this->assertTrue($internal['system']);

        $legacy = collect($withSystem)->firstWhere('route_name', 'dcat.admin.legacy-upload');
        $this->assertNotNull($legacy);
        $this->assertTrue($legacy['internal']);
        $this->assertTrue($legacy['system']);
    }

    public function test_it_reads_resource_language_files_and_single_route_metadata(): void
    {
        [$router, $application, $container] = $this->environment();
        $loader = new ArrayLoader();
        $loader->addMessages('zh_CN', 'admin', [
            'permission_action_index' => '列表',
            'permission_action_show' => '查看',
        ]);
        $loader->addMessages('zh_CN', 'member-user', [
            'labels' => [
                'MemberUser' => '用户管理',
                'member-user' => '用户管理',
            ],
            'permissions' => [
                'resource' => [
                    'group' => '用户中心',
                ],
                'description' => '管理平台注册用户',
                'actions' => [
                    'index' => [
                        'title' => '查看用户列表',
                        'description' => '查看和搜索用户',
                    ],
                ],
                'routes' => [
                    'member-user.statistics' => '用户统计',
                ],
            ],
        ]);
        $container->instance('translator', new Translator($loader, 'zh_CN'));
        RoutePermissionMetadata::registerMacro();

        $router->group(['prefix' => 'admin', 'as' => 'dcat.admin.', 'middleware' => 'admin.app:admin'], function (Router $router) {
            $router->resource('member-user', MemberUserController::class)->only(['index', 'show']);
            $router->get('member-user/statistics', [MemberUserController::class, 'statistics'])
                ->name('member-user.statistics');
            $router->get('openapi-docs', function () {})
                ->name('openapi-docs')
                ->permissionLabel('接口文档', '查看后台 OpenAPI 文档', '开发工具');
        });

        $catalog = new RouteCatalog($router, $application, ['include_unnamed_routes' => true]);
        $grouped = $catalog->grouped(false);
        $resource = $grouped['resources'][0];
        $index = collect($resource['actions'])->firstWhere('resource_action', 'index');
        $statistics = collect($grouped['singles'])->firstWhere('relative_name', 'member-user.statistics');
        $openapi = collect($grouped['singles'])->firstWhere('relative_name', 'openapi-docs');

        $this->assertSame('用户管理', $resource['title']);
        $this->assertSame('管理平台注册用户', $resource['description']);
        $this->assertSame('用户中心', $resource['group']);
        $this->assertSame('查看用户列表', $index['permission_title']);
        $this->assertSame('查看和搜索用户', $index['description']);
        $this->assertSame('lang.action', $index['label_source']);
        $this->assertSame('用户统计', $statistics['permission_title']);
        $this->assertSame('lang.route', $statistics['label_source']);
        $this->assertSame('接口文档', $openapi['permission_title']);
        $this->assertSame('查看后台 OpenAPI 文档', $openapi['description']);
        $this->assertSame('开发工具', $openapi['permission_group']);
        $this->assertSame('route', $openapi['label_source']);
    }

    public function test_application_language_overrides_package_resource_defaults(): void
    {
        [$router, $application, $container] = $this->environment();
        $loader = new ArrayLoader();
        $loader->addMessages('zh_CN', 'admin', [
            'permission_action_index' => '列表',
        ]);
        $loader->addMessages('zh_CN', 'user', [
            'labels' => [
                'User' => '项目管理员',
            ],
        ]);
        $container->instance('translator', new Translator($loader, 'zh_CN'));

        $router->group(['prefix' => 'admin', 'as' => 'dcat.admin.', 'middleware' => 'admin.app:admin'], function (Router $router) {
            $router->resource('users', UserController::class)->only(['index']);
        });

        $resource = (new RouteCatalog($router, $application))->grouped(false)['resources'][0];
        $index = $resource['actions'][0];

        $this->assertSame('项目管理员', $resource['title']);
        $this->assertSame('管理后台管理员账号、状态和角色绑定', $resource['description']);
        $this->assertNotSame('users.index', $index['permission_title']);
        $this->assertSame('lang.action', $index['label_source']);
    }

    public function test_package_single_routes_define_permission_metadata(): void
    {
        [$router, , $container] = $this->environment();
        $container->make('config')->set('admin.auth.enable', true);
        $container->make('config')->set('admin.permission.enable', false);
        $container->make('config')->set('admin.route.middleware', []);
        $container->make('config')->set('admin.helpers.enable', false);
        RoutePermissionMetadata::registerMacro();

        Admin::routes();

        $expected = [
            'admin/auth/login' => '后台登录页',
            'admin/auth/logout' => '退出后台',
            'admin/auth/setting' => '个人设置',
            'admin/auth/system-log-viewer' => '查看系统日志',
            'admin/dcat-sys/notifications' => '通知列表数据',
            'admin/dcat-sys/media/upload' => '上传媒体文件',
            'admin/dcat-sys/cache/clear' => '清理系统缓存',
        ];

        foreach ($expected as $uri => $title) {
            $route = collect($router->getRoutes())->first(function ($route) use ($uri) {
                return $route->uri() === $uri;
            });

            $this->assertNotNull($route, $uri);
            $this->assertSame(
                $title,
                $route->defaults[RoutePermissionMetadata::DEFAULT_KEY]['title'] ?? null,
                $uri
            );
        }
    }

    protected function environment(): array
    {
        $this->previousContainer = Container::getInstance();
        $container = new Container();
        Container::setInstance($container);
        $container->instance('config', new Repository([
            'admin' => [
                'route' => ['prefix' => 'admin', 'domain' => null],
                'permission' => ['except' => ['auth/login']],
            ],
        ]));
        $events = new Dispatcher($container);
        $router = new Router($events, $container);
        $application = new Application($container);
        $application->withName('admin');
        $container->instance('router', $router);
        $container->instance('admin.app', $application);

        return [$router, $application, $container];
    }
}

class RouteCatalogResourceController
{
    public function index() {}

    public function update() {}

    public function import() {}

    public function export() {}

    public function publish() {}

    public function login() {}
}

class MemberUserController
{
    public function index() {}

    public function show() {}

    public function statistics() {}
}

class UserController
{
    public function index() {}
}
