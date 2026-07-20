<?php

namespace Dcat\Admin\Tests\Unit\Support\Authorization;

use Dcat\Admin\Application;
use Dcat\Admin\Support\Authorization\ActiveRole;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\TestCase;

class ActiveRoleTest extends TestCase
{
    protected ?Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $container = new Container();
        Container::setInstance($container);
        $container->instance('config', new Repository([
            'admin' => [
                'permission' => [
                    'active_role' => [
                        'enable' => true,
                        'default_column' => 'default_role_id',
                    ],
                ],
                'database' => [
                    'roles_model' => \Dcat\Admin\Models\Role::class,
                ],
            ],
        ]));

        $session = new Store('active-role-test', new ArraySessionHandler(120));
        $session->start();
        $request = Request::create('/admin', 'GET');
        $request->setLaravelSession($session);
        $container->instance('request', $request);

        $admin = new Application($container);
        $admin->withName('admin');
        $container->instance('admin.app', $admin);
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function test_login_initializes_the_users_default_role_and_switches_only_to_owned_roles(): void
    {
        $viewer = new ActiveRoleRecord(2, 'Viewer', 'viewer', [new ActiveRolePermission(12, 'reports.view')]);
        $editor = new ActiveRoleRecord(3, 'Editor', 'editor', [new ActiveRolePermission(13, 'reports.edit')]);
        $user = new ActiveRoleUser([$viewer, $editor], 3);
        $resolver = new ActiveRole();

        $this->assertSame(3, $resolver->initialize($user)->getKey());
        $this->assertSame([3], $resolver->authorizationRoles($user)->pluck('id')->all());
        $this->assertNull($resolver->switch($user, 99));
        $this->assertSame(3, $resolver->current($user)->getKey());
        $this->assertSame(2, $resolver->switch($user, 2)->getKey());
        $this->assertSame([2], $resolver->authorizationRoles($user)->pluck('id')->all());
        $this->assertSame(2, $user->authorizationCacheForgets);
    }

    public function test_only_the_active_role_contributes_permissions_and_administrator_status(): void
    {
        $operator = new ActiveRoleRecord(2, 'Operator', 'operator', [new ActiveRolePermission(21, 'orders.view')]);
        $administrator = new ActiveRoleRecord(1, 'Administrator', 'administrator', [new ActiveRolePermission(22, 'orders.delete')]);
        $user = new ActiveRolePermissionUser([$operator, $administrator], 2);
        $resolver = new ActiveRole();

        $resolver->initialize($user);

        $this->assertTrue($user->can('orders.view'));
        $this->assertFalse($user->can('orders.delete'));
        $this->assertFalse($user->isAdministrator());
        $this->assertFalse($user->inRoles(['administrator']));

        $resolver->switch($user, 1);

        $this->assertTrue($user->isAdministrator());
        $this->assertTrue($user->can('orders.delete'));
    }

    public function test_legacy_mode_keeps_all_role_permissions(): void
    {
        config()->set('admin.permission.active_role.enable', false);

        $viewer = new ActiveRoleRecord(2, 'Viewer', 'viewer', [new ActiveRolePermission(31, 'reports.view')]);
        $editor = new ActiveRoleRecord(3, 'Editor', 'editor', [new ActiveRolePermission(32, 'reports.edit')]);
        $user = new ActiveRolePermissionUser([$viewer, $editor], 2);

        $this->assertTrue($user->can('reports.view'));
        $this->assertTrue($user->can('reports.edit'));
        $this->assertCount(2, $user->authorizationRoles());
    }
}

class ActiveRoleUser
{
    public array $roles;

    public int $authorizationCacheForgets = 0;

    public function __construct(array $roles, public ?int $default_role_id = null)
    {
        $this->roles = $roles;
    }

    public function roles()
    {
    }

    public function getAuthIdentifier(): int
    {
        return 7;
    }

    public function getAttribute(string $key)
    {
        return $this->{$key};
    }

    public function setAttribute(string $key, $value): void
    {
        $this->{$key} = $value;
    }

    public function forgetAuthorizationCache(): void
    {
        $this->authorizationCacheForgets++;
    }
}

class ActiveRolePermissionUser extends ActiveRoleUser
{
    use HasPermissions;

    public function getKeyName(): string
    {
        return 'id';
    }
}

class ActiveRoleRecord
{
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public array $permissions
    ) {
    }

    public function getKey(): int
    {
        return $this->id;
    }
}

class ActiveRolePermission
{
    public function __construct(public int $id, public string $slug)
    {
    }
}
