<?php

namespace Dcat\Admin\Console;

use Dcat\Admin\Scaffold\ControllerCreator;
use Dcat\Admin\Scaffold\LangCreator;
use Dcat\Admin\Scaffold\ModelCreator;
use Dcat\Admin\Scaffold\Support\TableColumnInspector;
use Dcat\Admin\Support\Helper;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class ScaffoldCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:scaffold
        {--connection= : Database connection name}
        {--controller-namespace= : Controller namespace}
        {--model-namespace=App\\Models : Model namespace}
        {--table= : Table names to generate, comma separated}
        {--force : Overwrite existing model, controller and lang files}
        {--menu-parent=0 : Parent menu ID}
        {--menu-icon=fa-file-text-o : Menu icon}
        {--role=1 : Role ID attached to generated menu and permission, use 0 to skip}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate admin model, controller, lang, route, permission and menu from database tables';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $connection = $this->option('connection') ?: config('database.default');
        if (! $this->connectionExists($connection)) {
            $this->error("Database connection [{$connection}] is not configured.");

            return 1;
        }

        $controllerNamespace = $this->normalizeNamespace(
            $this->option('controller-namespace') ?: config('admin.route.namespace', 'App\\Admin\\Controllers')
        );
        $modelNamespace = $this->normalizeNamespace($this->option('model-namespace') ?: 'App\\Models');
        $tables = $this->resolveTables($connection);
        $writeConnection = $this->option('connection') ? $connection : null;

        if (empty($tables)) {
            $this->warn('No tables found.');

            return 0;
        }

        $this->info(sprintf(
            'Generating scaffold for %d table(s) on connection [%s].',
            count($tables),
            $connection
        ));

        $failed = false;
        $rows = [];

        foreach ($tables as $table) {
            try {
                $result = $this->generateForTable($table, $connection, $writeConnection, $controllerNamespace, $modelNamespace);
                $rows[] = [
                    $table,
                    'OK',
                    implode(PHP_EOL, array_filter([
                        Arr::get($result, 'model'),
                        Arr::get($result, 'controller'),
                        Arr::get($result, 'lang'),
                        'route: '.Arr::get($result, 'route'),
                        'permission: '.Arr::get($result, 'permission_id'),
                        'menu: '.Arr::get($result, 'menu_id'),
                    ])),
                ];
            } catch (Throwable $e) {
                $failed = true;
                $rows[] = [$table, 'FAILED', $e->getMessage()];
            }
        }

        $this->table(['Table', 'Status', 'Result'], $rows);

        return $failed ? 1 : 0;
    }

    /**
     * Generate scaffold files and database records for one table.
     *
     * @param string $table
     * @param string $connection
     * @param string|null $writeConnection
     * @param string $controllerNamespace
     * @param string $modelNamespace
     * @return array<string, mixed>
     */
    protected function generateForTable(string $table, string $connection, ?string $writeConnection, string $controllerNamespace, string $modelNamespace): array
    {
        $columns = TableColumnInspector::getNormalizedColumns($table, $connection);
        if (empty($columns)) {
            throw new \RuntimeException('Table columns are empty or table does not exist.');
        }

        $scaffoldTable = $this->scaffoldTableName($table, $connection);
        $class = Str::studly(Str::singular($scaffoldTable));
        $model = $modelNamespace.'\\'.$class;
        $controller = $controllerNamespace.'\\'.$class.'Controller';
        $primaryKey = $this->primaryKey($columns);
        $timestamps = isset($columns['created_at'], $columns['updated_at']);
        $softDeletes = isset($columns['deleted_at']);
        $fields = $this->fieldsForCreators($columns, $primaryKey);
        $title = $this->titleForTable($scaffoldTable);
        $routePath = $this->routePathForTable($scaffoldTable);

        if ($this->option('force')) {
            $this->deleteClassFile($model);
            $this->deleteClassFile($controller);
        }

        $paths = [];
        $paths['model'] = (new ModelCreator($table, $model, null, $writeConnection, $columns))
            ->create($primaryKey, $timestamps, $softDeletes);

        $paths['controller'] = (new ControllerCreator($controller))
            ->create($model, $primaryKey, $fields, $timestamps);

        $paths['lang'] = (new LangCreator($fields))
            ->create($controller, $title, (bool) $this->option('force'));

        $paths['route'] = $this->registerResourceRoute($routePath, $controller);

        $registered = $this->registerPermissionAndMenu($routePath, $title);

        return array_merge($paths, $registered);
    }

    /**
     * Get table name used for generated class, menu and permission names.
     *
     * @param string $table
     * @param string $connection
     * @return string
     */
    protected function scaffoldTableName(string $table, string $connection): string
    {
        $name = Str::afterLast($table, '.');
        $prefix = DB::connection($connection)->getTablePrefix();

        if ($prefix && Str::startsWith($name, $prefix)) {
            return Str::replaceFirst($prefix, '', $name);
        }

        return $name;
    }

    /**
     * Resolve table names from options or current connection.
     *
     * @param string $connection
     * @return array<int, string>
     */
    protected function resolveTables(string $connection): array
    {
        $tables = [];

        foreach ((array) $this->option('table') as $table) {
            foreach (explode(',', (string) $table) as $name) {
                $name = trim($name);
                if ($name !== '') {
                    $tables[] = $name;
                }
            }
        }

        if (empty($tables)) {
            return TableColumnInspector::getTableNames($connection);
        }

        $prefix = DB::connection($connection)->getTablePrefix();
        $tables = array_map(function ($table) use ($prefix) {
            if ($prefix && Str::startsWith($table, $prefix)) {
                return Str::replaceFirst($prefix, '', $table);
            }

            return $table;
        }, $tables);

        return array_values(array_unique($tables));
    }

    /**
     * Normalize a namespace string.
     *
     * @param string $namespace
     * @return string
     */
    protected function normalizeNamespace(string $namespace): string
    {
        return trim(str_replace('/', '\\', $namespace), '\\');
    }

    /**
     * Check configured database connection.
     *
     * @param string|null $connection
     * @return bool
     */
    protected function connectionExists(?string $connection): bool
    {
        return $connection && Arr::has(config('database.connections', []), $connection);
    }

    /**
     * Get primary key from normalized columns.
     *
     * @param array $columns
     * @return string
     */
    protected function primaryKey(array $columns): string
    {
        foreach ($columns as $name => $column) {
            if (($column['id'] ?? false) || ($column['key'] ?? '') === 'PRI') {
                return $name;
            }
        }

        return array_key_first($columns) ?: 'id';
    }

    /**
     * Convert normalized columns to creator fields.
     *
     * @param array $columns
     * @param string $primaryKey
     * @return array<int, array<string, mixed>>
     */
    protected function fieldsForCreators(array $columns, string $primaryKey): array
    {
        $excluded = [$primaryKey, 'created_at', 'updated_at', 'deleted_at'];
        $fields = [];

        foreach ($columns as $name => $column) {
            if (in_array($name, $excluded)) {
                continue;
            }

            $fields[] = [
                'name' => $name,
                'translation' => $column['comment'] ?: $name,
                'type' => $column['type'] ?? 'string',
                'nullable' => ! empty($column['nullable']) ? 'on' : '',
                'key' => $column['key'] ?? '',
                'default' => $column['default'] ?? '',
                'comment' => $column['comment'] ?? '',
            ];
        }

        return $fields;
    }

    /**
     * Generate a readable title for menu and lang labels.
     *
     * @param string $table
     * @return string
     */
    protected function titleForTable(string $table): string
    {
        return Str::title(str_replace(['_', '-'], ' ', Str::singular($table)));
    }

    /**
     * Generate menu URI and permission slug from table name.
     *
     * @param string $table
     * @return string
     */
    protected function routePathForTable(string $table): string
    {
        return Helper::slug(Str::singular($table));
    }

    /**
     * Register the generated controller in the application's admin routes file.
     *
     * The route uses the controller's fully-qualified class name so a custom
     * --controller-namespace works independently of the namespace configured
     * on the surrounding route group.
     *
     * @param  string  $routePath
     * @param  string  $controller
     * @return string
     */
    protected function registerResourceRoute(string $routePath, string $controller): string
    {
        $routeFile = admin_path('routes.php');

        if (! is_file($routeFile) || ! is_readable($routeFile) || ! is_writable($routeFile)) {
            throw new \RuntimeException("Admin routes file [{$routeFile}] does not exist or is not writable.");
        }

        $contents = file_get_contents($routeFile);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read admin routes file [{$routeFile}].");
        }

        $routePattern = '/\$router\s*->\s*resource\s*\(\s*[\'\"]\/?'.preg_quote($routePath, '/').'[\'\"]\s*,/';
        if (preg_match($routePattern, $contents)) {
            return $routePath.' (existing)';
        }

        $position = strrpos($contents, '});');
        if ($position === false) {
            throw new \RuntimeException("Unable to locate the admin route group in [{$routeFile}].");
        }

        $controller = ltrim($controller, '\\');
        $route = sprintf("    \$router->resource('%s', \\%s::class);", $routePath, $controller);
        $contents = substr_replace($contents, "\n\n{$route}\n", $position, 0);

        if (file_put_contents($routeFile, $contents, LOCK_EX) === false) {
            throw new \RuntimeException("Unable to write admin routes file [{$routeFile}].");
        }

        return $routePath;
    }

    /**
     * Delete generated class file when force option is enabled.
     *
     * @param string $class
     * @return void
     */
    protected function deleteClassFile(string $class): void
    {
        $path = Helper::guessClassFileName($class);

        if (is_file($path)) {
            app('files')->delete($path);
        }
    }

    /**
     * Register permission and menu records.
     *
     * @param string $routePath
     * @param string $title
     * @return array<string, mixed>
     */
    protected function registerPermissionAndMenu(string $routePath, string $title): array
    {
        $permission = $this->registerPermission($routePath, $title);
        $menu = $this->registerMenu($routePath, $title);

        return [
            'permission_id' => $permission ? $permission->getKey() : null,
            'menu_id' => $menu ? $menu->getKey() : null,
        ];
    }

    /**
     * Register permission record and attach it to role.
     *
     * @param string $routePath
     * @param string $title
     * @return mixed
     */
    protected function registerPermission(string $routePath, string $title)
    {
        $permissionsModel = config('admin.database.permissions_model');
        $slug = str_replace('_', '-', $routePath);
        $httpPath = '/'.$routePath.'/*';

        $this->syncPostgresModelSequence(new $permissionsModel());

        $permission = $permissionsModel::updateOrCreate(
            ['slug' => $slug, 'http_path' => $httpPath],
            [
                'parent_id' => 0,
                'name' => $title,
                'slug' => $slug,
                'http_path' => $httpPath,
            ]
        );

        $this->attachPermissionToRole($permission);

        return $permission;
    }

    /**
     * Register menu record and attach it to role.
     *
     * @param string $routePath
     * @param string $title
     * @return mixed
     */
    protected function registerMenu(string $routePath, string $title)
    {
        $menuModel = config('admin.database.menu_model');
        $menu = $menuModel::firstOrNew(['uri' => $routePath]);

        if (! $menu->exists) {
            $menu->order = ((int) $menuModel::max('order')) + 1;
        }

        $menu->parent_id = (int) $this->option('menu-parent');
        $menu->title = $title;
        $menu->icon = $this->option('menu-icon') ?: 'fa-file-text-o';
        $menu->uri = $routePath;
        $menu->save();

        $this->attachMenuToRole($menu);

        return $menu;
    }

    /**
     * Attach permission to configured role.
     *
     * @param mixed $permission
     * @return void
     */
    protected function attachPermissionToRole($permission): void
    {
        $role = $this->role();

        if ($role && method_exists($role, 'permissions')) {
            $role->permissions()->syncWithoutDetaching([$permission->getKey()]);
        }
    }

    /**
     * Attach menu to configured role.
     *
     * @param mixed $menu
     * @return void
     */
    protected function attachMenuToRole($menu): void
    {
        $role = $this->role();

        if ($role && method_exists($role, 'menus')) {
            $role->menus()->syncWithoutDetaching([$menu->getKey()]);
        }
    }

    /**
     * Get configured role model instance.
     *
     * @return mixed|null
     */
    protected function role()
    {
        $roleId = (int) $this->option('role');
        if ($roleId <= 0) {
            return null;
        }

        $roleModel = config('admin.database.roles_model');

        return $roleModel::find($roleId);
    }

    /**
     * Sync PostgreSQL sequence for admin tables with manually seeded IDs.
     *
     * @param mixed $model
     * @return void
     */
    protected function syncPostgresModelSequence($model): void
    {
        if (! $model || $model->getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        try {
            $connection = $model->getConnection();
            $table = $model->getTable();
            $key = method_exists($model, 'getKeyName') ? $model->getKeyName() : 'id';

            $connection->statement(
                "SELECT setval(pg_get_serial_sequence(?, ?), COALESCE(MAX(\"{$key}\"), 0) + 1, false) FROM \"{$table}\"",
                [$table, $key]
            );
        } catch (Throwable $e) {
            //
        }
    }
}
