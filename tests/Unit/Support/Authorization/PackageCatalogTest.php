<?php

namespace Dcat\Admin\Tests\Unit\Support\Authorization;

use Dcat\Admin\Admin;
use Dcat\Admin\Application;
use Dcat\Admin\Support\Authorization\MenuPackage;
use Dcat\Admin\Support\Authorization\RoutePermissionMetadata;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use PHPUnit\Framework\TestCase;

class PackageCatalogTest extends TestCase
{
    protected ?Container $previousContainer = null;

    protected $previousFacadeApplication;

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    public function test_permission_package_returns_resource_and_single_capabilities_for_requested_panel(): void
    {
        [$router, $application, $container] = $this->environment();
        RoutePermissionMetadata::registerMacro();

        $router->group(['prefix' => 'admin', 'as' => 'dcat.admin.', 'middleware' => 'admin.app:admin'], function (Router $router) {
            $router->resource('users', PackageCatalogController::class)->only(['index', 'show', 'store']);
            $router->get('reports', [PackageCatalogController::class, 'reports'])
                ->name('reports')
                ->permissionLabel('经营报表', '查看经营统计报表', '数据中心');
        });
        $router->group(['prefix' => 'seller', 'as' => 'dcat.seller.', 'middleware' => 'admin.app:seller'], function (Router $router) {
            $router->get('orders', [PackageCatalogController::class, 'index'])->name('orders');
        });

        $container->instance('router', $router);
        $package = Admin::permissionPackage('admin');

        $this->assertSame('admin', $package['panel']);
        $this->assertCount(1, $package['resources']);
        $this->assertSame('resource:admin:users', $package['resources'][0]['key']);
        $this->assertSame(['preview', 'create'], array_column($package['resources'][0]['abilities'], 'name'));
        $this->assertSame('经营报表', $package['singles'][0]['title']);
        $this->assertSame('single:admin:reports', $package['singles'][0]['key']);

        $seller = Admin::permissionPackage('seller');
        $this->assertSame('seller', $seller['panel']);
        $this->assertCount(1, $seller['singles']);
        $this->assertSame('dcat.seller.orders', $seller['singles'][0]['route']['route_name']);
        $this->assertSame('admin', $application->getName());
    }

    public function test_menu_package_returns_safe_tree_without_orphan_nodes(): void
    {
        [, , $container] = $this->environment();
        $loader = new ArrayLoader();
        $loader->addMessages('zh_CN', 'menu', [
            'titles' => [
                'business_management' => '业务管理',
                'order_management' => '订单管理',
            ],
        ]);
        $container->instance('translator', new Translator($loader, 'zh_CN'));
        PackageCatalogMenu::$nodes = [
            ['id' => 1, 'parent_id' => 0, 'title' => 'Business Management', 'uri' => '', 'order' => 1],
            ['id' => 2, 'parent_id' => 1, 'title' => 'Order Management', 'uri' => '', 'order' => 2],
            ['id' => 3, 'parent_id' => 99, 'title' => '孤儿菜单', 'uri' => '', 'order' => 3],
        ];
        $container->make('config')->set('admin.database.menu_model', PackageCatalogMenu::class);

        $package = (new MenuPackage())->get('admin');

        $this->assertSame('admin', $package['panel']);
        $this->assertCount(1, $package['tree']);
        $this->assertSame('menu:admin:1', $package['tree'][0]['key']);
        $this->assertSame('订单管理', $package['tree'][0]['children'][0]['title']);
        $this->assertSame('Order Management', $package['tree'][0]['children'][0]['original_title']);
    }

    /**
     * @return array{0: Router, 1: Application, 2: Container}
     */
    protected function environment(): array
    {
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $container = new Container();
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        $container->instance('config', new Repository([
            'admin' => [
                'route' => ['prefix' => 'admin', 'domain' => null],
                'permission' => ['except' => []],
                'multi_app' => ['seller' => true],
            ],
            'seller' => [
                'route' => ['prefix' => 'seller', 'domain' => null],
                'permission' => ['except' => []],
            ],
        ]));

        $router = new Router(new Dispatcher($container), $container);
        $application = new Application($container);
        $application->withName('admin');
        $container->instance('router', $router);
        $container->instance('admin.app', $application);

        return [$router, $application, $container];
    }
}

class PackageCatalogController
{
    public function index() {}

    public function show() {}

    public function store() {}

    public function reports() {}
}

class PackageCatalogMenu
{
    public static array $nodes = [];

    public function allNodes(bool $force = false): Collection
    {
        return new Collection(static::$nodes);
    }

    public function getKeyName(): string
    {
        return 'id';
    }
}
