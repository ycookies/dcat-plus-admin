<?php

namespace Dcat\Admin\Tests\Unit\Support\Authorization;

use Dcat\Admin\Application;
use Dcat\Admin\Models\Permission;
use Dcat\Admin\Show as DetailShow;
use Dcat\Admin\Show\Panel as ShowPanel;
use Dcat\Admin\Show\Tools as ShowTools;
use Dcat\Admin\Support\Authorization\GridActionPermission;
use Dcat\Admin\Support\Authorization\ResourceRouteAction;
use Dcat\Admin\Support\Context;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;

class GridActionPermissionTest extends TestCase
{
    protected ?Container $previousContainer = null;

    protected $previousFacadeApplication;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();
        $container = new Container();
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
        Facade::clearResolvedInstances();
        $container->instance('config', new Repository([
            'admin' => [
                'route' => ['prefix' => 'admin'],
                'database' => [
                    'connection' => null,
                    'permissions_table' => 'admin_permissions',
                    'role_permissions_table' => 'admin_role_permissions',
                    'roles_model' => \Dcat\Admin\Models\Role::class,
                    'menu_model' => \Dcat\Admin\Models\Menu::class,
                    'permission_menu_table' => 'admin_permission_menu',
                ],
                'permission' => [
                    'enable' => true,
                    'except' => [],
                    'resource_actions' => [
                        'denied' => 'hide',
                        'actions' => [
                            'create' => true,
                            'edit' => true,
                            'quick_edit' => true,
                            'delete' => true,
                            'batch_delete' => true,
                        ],
                    ],
                ],
            ],
        ]));
        $container->instance('request', Request::create('/admin/products', 'GET'));
        $container->instance('admin.context', new Context());
        $container->instance('translator', new class {
            public function get($key, ...$arguments)
            {
                return $key;
            }
        });

        $admin = new Application($container);
        $admin->withName('admin');
        $container->instance('admin.app', $admin);
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function test_it_matches_the_real_resource_action_method_and_path(): void
    {
        $permission = new CountingGridPermission('GET', 'admin/products/create');
        $user = new GridPermissionUser([$permission]);

        $this->assertTrue(GridActionPermission::allowsResource('/admin/products', 'create', $user));
        $this->assertFalse(GridActionPermission::allowsResource('/admin/products', 'edit', $user));
        $this->assertFalse(GridActionPermission::allowsResource('/admin/products', 'delete', $user));
    }

    public function test_it_caches_repeated_row_action_decisions_on_the_request(): void
    {
        $permission = new CountingGridPermission('GET', 'admin/products');
        $user = new GridPermissionUser([$permission]);

        $this->assertFalse(GridActionPermission::allowsResource('/admin/products', 'edit', $user));
        $checks = $permission->checks;

        for ($i = 0; $i < 50; $i++) {
            $this->assertFalse(GridActionPermission::allowsResource('/admin/products', 'edit', $user));
        }

        $this->assertSame($checks, $permission->checks);
        $this->assertSame(1, $user->permissionLoads);
    }

    public function test_it_supports_hide_and_prompt_modes_and_individual_switches(): void
    {
        $config = Container::getInstance()->make('config');

        $this->assertSame(GridActionPermission::MODE_HIDE, GridActionPermission::mode());

        $config->set('admin.permission.resource_actions.denied', 'prompt');
        $this->assertSame(GridActionPermission::MODE_PROMPT, GridActionPermission::mode());

        $config->set('admin.permission.resource_actions.actions.create', false);
        $this->assertTrue(GridActionPermission::allowsResource('/admin/products', 'create', new GridPermissionUser([])));
    }

    public function test_show_permission_does_not_grant_create_or_edit_actions(): void
    {
        $show = new WildcardGridPermission('member-user.show', 'GET', 'admin/member-user/*');
        $user = new GridPermissionUser([$show]);

        $this->assertFalse(GridActionPermission::allowsResource('/admin/member-user', 'create', $user));
        $this->assertFalse(GridActionPermission::allowsResource('/admin/member-user', 'edit', $user));

        // Historical whole-resource permissions remain intentionally compatible.
        $legacy = new WildcardGridPermission('member-user', 'GET', 'admin/member-user/*');
        $legacyUser = new GridPermissionUser([$legacy], 3);
        $this->assertTrue(GridActionPermission::allowsResource('/admin/member-user', 'create', $legacyUser));
        $this->assertTrue(GridActionPermission::allowsResource('/admin/member-user', 'edit', $legacyUser));
    }

    public function test_backend_resource_action_matching_rejects_sibling_route_names(): void
    {
        $request = Request::create('/admin/member-user/create', 'GET');
        $route = (new Route(['GET'], 'admin/member-user/create', fn () => null))
            ->name('dcat.admin.member-user.create');
        $request->setRouteResolver(fn () => $route);

        $this->assertFalse(ResourceRouteAction::matchesRequest((object) ['slug' => 'member-user.show'], $request));
        $this->assertTrue(ResourceRouteAction::matchesRequest((object) ['slug' => 'member-user.create'], $request));
        $this->assertTrue(ResourceRouteAction::matchesRequest((object) ['slug' => 'member-user'], $request));
    }

    public function test_permission_model_does_not_allow_show_wildcard_on_create_route(): void
    {
        $request = Request::create('/admin/member-user/create', 'GET');
        $route = (new Route(['GET'], 'admin/member-user/create', fn () => null))
            ->name('dcat.admin.member-user.create');
        $request->setRouteResolver(fn () => $route);

        $permission = new Permission();
        $permission->slug = 'member-user.show';
        $permission->http_method = ['GET'];
        $permission->http_path = ['/member-user/*'];

        $this->assertFalse($permission->shouldPassThrough($request));

        $permission->slug = 'member-user.create';
        $this->assertTrue($permission->shouldPassThrough($request));
    }

    public function test_show_page_hides_edit_and_delete_tools_without_permissions(): void
    {
        $showPermission = new WildcardGridPermission('member-user.show', 'GET', 'admin/member-user/*');
        $user = new GridPermissionUser([$showPermission]);

        Container::getInstance()->instance('auth', new GridPermissionAuthManager($user));
        Facade::clearResolvedInstance('auth');

        $show = new DetailShow(1, ['id' => 1]);
        $tools = new TestShowTools(new ShowPanel($show));

        $this->assertSame('', $tools->renderEditForTest());
        $this->assertSame('', $tools->renderDeleteForTest());
    }
}

class GridPermissionUser
{
    public int $permissionLoads = 0;

    protected $permissions;

    protected int $id;

    public function __construct(array $permissions, int $id = 2)
    {
        $this->permissions = collect($permissions);
        $this->id = $id;
    }

    public function isAdministrator(): bool
    {
        return false;
    }

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }

    public function allPermissions()
    {
        $this->permissionLoads++;

        return $this->permissions;
    }
}

class CountingGridPermission
{
    public int $checks = 0;

    protected string $method;

    protected string $path;

    public function __construct(string $method, string $path)
    {
        $this->method = $method;
        $this->path = trim($path, '/');
    }

    public function shouldPassThrough(Request $request): bool
    {
        $this->checks++;

        return $request->method() === $this->method
            && $request->decodedPath() === $this->path;
    }
}

class WildcardGridPermission extends CountingGridPermission
{
    public string $slug;

    public function __construct(string $slug, string $method, string $path)
    {
        parent::__construct($method, $path);
        $this->slug = $slug;
    }

    public function shouldPassThrough(Request $request): bool
    {
        $this->checks++;

        return $request->method() === $this->method
            && Str::is($this->path, $request->decodedPath());
    }
}

class GridPermissionAuthManager
{
    protected GridPermissionGuard $guard;

    public function __construct($user)
    {
        $this->guard = new GridPermissionGuard($user);
    }

    public function guard(?string $name = null): GridPermissionGuard
    {
        return $this->guard;
    }
}

class GridPermissionGuard
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function user()
    {
        return $this->user;
    }
}

class TestShowTools extends ShowTools
{
    public function resource()
    {
        return 'http://localhost/admin/member-user';
    }

    public function renderEditForTest(): string
    {
        return (string) $this->renderEdit();
    }

    public function renderDeleteForTest(): string
    {
        return (string) $this->renderDelete();
    }
}
