<?php

namespace Dcat\Admin\Tests\Unit\Console;

use Dcat\Admin\Console\InstallCommand;
use Dcat\Admin\Models\Menu;
use Dcat\Admin\Models\Role;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class InstallCommandTest extends TestCase
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
        Facade::setFacadeApplication($container);
        $container->instance('config', new Repository([
            'database' => ['default' => 'testing'],
            'admin' => [
                'database' => [
                    'connection'      => 'testing',
                    'roles_model'     => Role::class,
                    'roles_table'     => 'admin_roles',
                    'menu_model'      => Menu::class,
                    'menu_table'      => 'admin_menu',
                    'role_menu_table' => 'admin_role_menu',
                    'permissions_model' => \Dcat\Admin\Models\Permission::class,
                    'permission_menu_table' => 'admin_permission_menu',
                ],
                'menu' => ['cache' => ['enable' => false]],
                'permission' => ['enable' => true],
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
        $schema->create('admin_roles', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });
        $schema->create('admin_menu', function ($table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->default(0);
            $table->integer('order')->default(0);
            $table->string('title');
            $table->string('icon')->nullable();
            $table->string('uri')->nullable();
            $table->timestamps();
        });
        $schema->create('admin_role_menu', function ($table) {
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('menu_id');
            $table->timestamps();
            $table->unique(['role_id', 'menu_id']);
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
        Facade::setFacadeApplication($this->previousContainer);
        Container::setInstance($this->previousContainer);

        parent::tearDown();
    }

    public function test_it_idempotently_binds_all_menus_to_administrator_role(): void
    {
        $connection = $this->database->getConnection('testing');
        $connection->table('admin_roles')->insert([
            'id' => Role::ADMINISTRATOR_ID,
            'name' => 'Administrator',
            'slug' => Role::ADMINISTRATOR,
        ]);
        $connection->table('admin_menu')->insert([
            ['id' => 1, 'title' => 'Dashboard'],
            ['id' => 2, 'title' => 'Users'],
            ['id' => 3, 'title' => 'Roles'],
        ]);
        $connection->table('admin_role_menu')->insert([
            'role_id' => Role::ADMINISTRATOR_ID,
            'menu_id' => 1,
        ]);

        $command = new TestableInstallCommand();

        $this->assertSame(3, $command->syncMenus());
        $this->assertSame([1, 2, 3], $this->administratorMenuIds());

        $this->assertSame(3, $command->syncMenus());
        $this->assertSame([1, 2, 3], $this->administratorMenuIds());
        $this->assertSame(3, $connection->table('admin_role_menu')->count());
    }

    public function test_it_reports_missing_administrator_role(): void
    {
        $this->assertNull((new TestableInstallCommand())->syncMenus());
    }

    protected function administratorMenuIds(): array
    {
        return $this->database->getConnection('testing')
            ->table('admin_role_menu')
            ->where('role_id', Role::ADMINISTRATOR_ID)
            ->orderBy('menu_id')
            ->pluck('menu_id')
            ->map(static fn ($id) => (int) $id)
            ->all();
    }
}

class TestableInstallCommand extends InstallCommand
{
    public function syncMenus(): ?int
    {
        return $this->syncAdministratorMenus();
    }
}
