<?php

namespace Dcat\Admin\Tests\Unit\Support\Authorization;

use Dcat\Admin\Support\Authorization\RoleAuthorizationService;
use Dcat\Admin\Support\Authorization\RouteCatalog;
use Dcat\Admin\Support\Authorization\RoutePermissionResolver;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

class RoleAuthorizationServiceTest extends TestCase
{
    protected ?Container $previousContainer = null;

    protected $previousConnectionResolver;

    protected $previousEventDispatcher;

    protected Capsule $database;

    protected function setUp(): void
    {
        parent::setUp();

        $this->previousContainer = Container::getInstance();
        $this->previousConnectionResolver = Model::getConnectionResolver();
        $this->previousEventDispatcher = Model::getEventDispatcher();

        $container = new Container();
        Container::setInstance($container);
        $container->instance('config', new Repository([
            'database' => ['default' => 'testing'],
            'admin' => [
                'database' => [
                    'connection' => 'testing',
                    'permissions_model' => AuthorizationPermission::class,
                    'role_permissions_table' => 'role_permissions',
                ],
                'permission' => [
                    'role_editor' => ['auto_create' => false],
                ],
                'menu' => ['role_bind_menu' => false],
            ],
        ]));

        $this->database = new Capsule($container);
        $this->database->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ], 'testing');
        $this->database->setEventDispatcher(new Dispatcher($container));
        $this->database->setAsGlobal();
        $this->database->bootEloquent();
        $container->instance('db', $this->database->getDatabaseManager());

        $schema = $this->database->getConnection('testing')->getSchemaBuilder();
        $schema->create('roles', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        $schema->create('permissions', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
        $schema->create('role_permissions', function ($table) {
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('permission_id');
            $table->timestamps();
            $table->unique(['role_id', 'permission_id']);
        });
    }

    protected function tearDown(): void
    {
        if ($this->previousConnectionResolver) {
            Model::setConnectionResolver($this->previousConnectionResolver);
        } else {
            Model::unsetConnectionResolver();
        }

        if ($this->previousEventDispatcher) {
            Model::setEventDispatcher($this->previousEventDispatcher);
        } else {
            Model::unsetEventDispatcher();
        }

        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    public function test_unmapped_existing_permissions_are_preserved_without_being_submitted(): void
    {
        $connection = $this->database->getConnection('testing');
        $connection->table('roles')->insert(['id' => 1, 'name' => 'Editor']);
        $connection->table('permissions')->insert([
            ['id' => 1, 'name' => 'Old mapped route'],
            ['id' => 2, 'name' => 'Legacy wildcard'],
            ['id' => 3, 'name' => 'Logical ability'],
            ['id' => 4, 'name' => 'Selected route'],
        ]);
        $connection->table('role_permissions')->insert([
            ['role_id' => 1, 'permission_id' => 1],
            ['role_id' => 1, 'permission_id' => 2],
            ['role_id' => 1, 'permission_id' => 3],
        ]);

        $permissions = AuthorizationPermission::query()->get()->keyBy('id');
        $descriptors = [
            'route-old' => ['key' => 'route-old'],
            'route-selected' => ['key' => 'route-selected'],
        ];
        $resolver = new AuthorizationResolver([
            'route-old' => $permissions[1],
            'route-selected' => $permissions[4],
        ]);
        $service = new RoleAuthorizationService(new AuthorizationCatalog($descriptors), $resolver);
        $role = AuthorizationRole::query()->findOrFail(1);

        $payload = $service->payload([
            'role_authorization_present' => 1,
            'route_permissions' => ['route-selected'],
            // Old cached pages may still submit this field; it must not grant
            // arbitrary permissions after the simplified editor is deployed.
            'custom_permissions' => [1, 4],
        ]);
        $result = $service->sync($role, $payload);

        $this->assertArrayNotHasKey('custom_permissions', $payload);
        $this->assertSame([2, 3], $result['preserved_permission_ids']);
        $this->assertSame([2, 3, 4], $result['permission_ids']);
        $this->assertSame([2, 3, 4], $role->permissions()->orderBy('permissions.id')->pluck('permissions.id')->map(fn ($id) => (int) $id)->all());
    }
}

class AuthorizationCatalog extends RouteCatalog
{
    public function __construct(protected array $descriptors) {}

    public function all(?bool $includeSystem = null): array
    {
        return $this->descriptors;
    }
}

class AuthorizationResolver extends RoutePermissionResolver
{
    public function __construct(protected array $mapped) {}

    public function map(array $descriptors, ?Collection $permissions = null): array
    {
        return array_intersect_key($this->mapped, $descriptors);
    }

    public function resolve(array $descriptor, bool $create = true): ?Model
    {
        return $this->mapped[$descriptor['key']] ?? null;
    }
}

class AuthorizationRole extends Model
{
    protected $connection = 'testing';

    protected $table = 'roles';

    protected $guarded = [];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AuthorizationPermission::class, 'role_permissions', 'role_id', 'permission_id')
            ->withTimestamps();
    }
}

class AuthorizationPermission extends Model
{
    protected $connection = 'testing';

    protected $table = 'permissions';

    protected $guarded = [];
}
