<?php

namespace Dcat\Admin\Tests\Unit\Support\Authorization;

use Dcat\Admin\Application;
use Dcat\Admin\Support\Authorization\RouteCatalog;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;
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

        return [$router, $application];
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
