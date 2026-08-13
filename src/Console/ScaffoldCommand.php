<?php

namespace Dcat\Admin\Console;

use Dcat\Admin\Scaffold\ApiControllerCreator;
use Dcat\Admin\Scaffold\ControllerCreator;
use Dcat\Admin\Scaffold\ExtensionApiControllerCreator;
use Dcat\Admin\Scaffold\ExtensionControllerCreator;
use Dcat\Admin\Scaffold\ExtensionResourceCreator;
use Dcat\Admin\Scaffold\LangCreator;
use Dcat\Admin\Scaffold\ModelCreator;
use Dcat\Admin\Scaffold\ResourceCreator;
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
        {--role=1 : Role ID attached to generated menu and permission, use 0 to skip}
        {--api : Generate member-api (C-side) controller + JsonResource, routes into app/Api/routes.php}
        {--admin-api : Generate admin-api controller + JsonResource, routes into app/Admin/Api/routes.php}
        {--resource : Generate a JsonResource class for each model}
        {--extension= : Write code into an extension package (vendor/name); routes into the extension src/Http, namespaces follow the extension}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate model, controller, lang, route, permission, menu (and optional api/admin-api/resource) from database tables, into the main app or an extension package';

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

        $extension = $this->resolveExtensionContext();

        $controllerNamespace = $this->normalizeNamespace(
            $this->resolveControllerNamespace($extension)
        );
        $modelNamespace = $this->normalizeNamespace(
            $this->resolveModelNamespace($extension)
        );
        $tables = $this->resolveTables($connection);
        $writeConnection = $this->option('connection') ? $connection : null;

        if (empty($tables)) {
            $this->warn('No tables found.');

            return 0;
        }

        $this->info(sprintf(
            'Generating scaffold for %d table(s) on connection [%s]%s.',
            count($tables),
            $connection,
            $extension ? " into extension [{$extension['package']}]" : ''
        ));

        $failed = false;
        $rows = [];

        foreach ($tables as $table) {
            try {
                $result = $this->generateForTable($table, $connection, $writeConnection, $controllerNamespace, $modelNamespace, $extension);
                $rows[] = [
                    $table,
                    'OK',
                    implode(PHP_EOL, array_filter([
                        Arr::get($result, 'model'),
                        Arr::get($result, 'controller'),
                        Arr::get($result, 'lang'),
                        Arr::get($result, 'resource'),
                        Arr::get($result, 'api'),
                        Arr::get($result, 'admin_api'),
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
     * @param  string       $table
     * @param  string       $connection
     * @param  string|null  $writeConnection
     * @param  string       $controllerNamespace
     * @param  string       $modelNamespace
     * @param  array|null   $extension
     * @return array<string, mixed>
     */
    protected function generateForTable(string $table, string $connection, ?string $writeConnection, string $controllerNamespace, string $modelNamespace, ?array $extension = null): array
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

        // Resource is generated whenever --api/--admin-api/--resource is requested.
        $wantResource = $this->option('api') || $this->option('admin-api') || $this->option('resource');

        if ($this->option('force')) {
            $this->deleteClassFile($model, $extension);
            $this->deleteClassFile($controller, $extension);
            if ($wantResource) {
                $this->deleteClassFile($this->resourceName($model, $extension), $extension);
            }
            if ($this->option('api')) {
                $this->deleteClassFile($this->apiControllerName($controller, $extension, 'member'), $extension);
            }
            if ($this->option('admin-api')) {
                $this->deleteClassFile($this->apiControllerName($controller, $extension, 'admin'), $extension);
            }
        }

        $paths = [];

        // 1. Model
        $paths['model'] = $this->createModel($table, $model, $writeConnection, $columns, $primaryKey, $timestamps, $softDeletes, $extension);

        // 2. Backend controller
        $paths['controller'] = $this->createBackendController($controller, $model, $primaryKey, $fields, $timestamps, $extension);

        // 3. Lang
        $paths['lang'] = (new LangCreator($fields))
            ->create($controller, $title, (bool) $this->option('force'));

        // 4. JsonResource (optional)
        if ($wantResource) {
            $paths['resource'] = $this->createResource($model, $table, $title, $extension);
        }

        // 5. member-api controller (optional)
        if ($this->option('api')) {
            $paths['api'] = $this->createApiController($controller, $model, $table, $title, $extension, 'member');
        }

        // 6. admin-api controller (optional)
        if ($this->option('admin-api')) {
            $paths['admin_api'] = $this->createApiController($controller, $model, $table, $title, $extension, 'admin');
        }

        // 7. Routes / permission / menu
        $paths['route'] = $this->registerBackendRoute($routePath, $controller, $extension);
        if ($this->option('api')) {
            $this->registerApiRoutes($routePath, $this->apiControllerName($controller, $extension, 'member'), $extension, 'member');
        }
        if ($this->option('admin-api')) {
            $this->registerApiRoutes($routePath, $this->apiControllerName($controller, $extension, 'admin'), $extension, 'admin');
        }

        $registered = $this->registerPermissionAndMenu($routePath, $title);

        return array_merge($paths, $registered);
    }

    /**
     * Create the model file (main app or extension).
     *
     * @param  string       $table
     * @param  string       $model
     * @param  string|null  $writeConnection
     * @param  array        $columns
     * @param  string       $primaryKey
     * @param  bool         $timestamps
     * @param  bool         $softDeletes
     * @param  array|null   $extension
     * @return string
     */
    protected function createModel(string $table, string $model, ?string $writeConnection, array $columns, string $primaryKey, bool $timestamps, bool $softDeletes, ?array $extension): string
    {
        if ($extension) {
            return (new \Dcat\Admin\Scaffold\ExtensionModelCreator(
                $table, $model, $extension['src_path'], $extension['namespace'], $writeConnection, $columns
            ))->create($primaryKey, $timestamps, $softDeletes);
        }

        return (new ModelCreator($table, $model, null, $writeConnection, $columns))
            ->create($primaryKey, $timestamps, $softDeletes);
    }

    /**
     * Create the backend admin controller (main app or extension).
     *
     * @param  string       $controller
     * @param  string       $model
     * @param  string       $primaryKey
     * @param  array        $fields
     * @param  bool         $timestamps
     * @param  array|null   $extension
     * @return string
     */
    protected function createBackendController(string $controller, string $model, string $primaryKey, array $fields, bool $timestamps, ?array $extension): string
    {
        if ($extension) {
            return (new ExtensionControllerCreator($controller, $extension['src_path'], $extension['namespace']))
                ->create($model, $primaryKey, $fields, $timestamps);
        }

        return (new ControllerCreator($controller))
            ->create($model, $primaryKey, $fields, $timestamps);
    }

    /**
     * Create a JsonResource (main app or extension).
     *
     * @param  string       $model
     * @param  string       $table
     * @param  string       $title
     * @param  array|null   $extension
     * @return string
     */
    protected function createResource(string $model, string $table, string $title, ?array $extension): string
    {
        $resourceName = $this->resourceName($model, $extension);

        if ($extension) {
            return (new ExtensionResourceCreator(
                $resourceName, $extension['src_path'], $extension['namespace'], $table, $title
            ))->create($model);
        }

        return (new ResourceCreator($resourceName, $table, $title))->create($model);
    }

    /**
     * Create an API controller (member-api or admin-api).
     *
     * @param  string       $controller  Backend controller FQN (used to derive the API controller name)
     * @param  string       $model
     * @param  string       $table
     * @param  string       $title
     * @param  array|null   $extension
     * @param  string       $kind        member|admin
     * @return string
     */
    protected function createApiController(string $controller, string $model, string $table, string $title, ?array $extension, string $kind): string
    {
        $apiController = $this->apiControllerName($controller, $extension, $kind);
        $resourceFqn = $this->resourceName($model, $extension);

        if ($extension) {
            $subNs = $kind === 'admin' ? 'Http\\AdminApi\\Controllers' : 'Http\\Api\\Controllers';

            return (new ExtensionApiControllerCreator(
                $apiController,
                $extension['src_path'],
                $extension['namespace'],
                $kind,
                $extension['namespace'].'\\'.$subNs,
                $resourceFqn,
                $title
            ))->create($model, $table);
        }

        $creator = (new ApiControllerCreator($apiController));
        if ($kind === 'member') {
            $creator->setMemberApiStub();
        }

        return $creator->create($model, $table);
    }

    /**
     * Resource class FQN for a model (main app or extension Http\Resources).
     *
     * @param  string       $model
     * @param  array|null   $extension
     * @return string
     */
    protected function resourceName(string $model, ?array $extension = null): string
    {
        $ns = $extension
            ? $extension['namespace'].'\\Http\\Resources'
            : 'App\\Http\\Resources';

        return $ns.'\\'.class_basename($model).'Resource';
    }

    /**
     * API controller FQN derived from the backend controller.
     *
     * Main app convention mirrors ScaffoldController:
     *  - admin-api:  App\Admin\Controllers\FooController  -> App\Admin\Api\Controllers\FooController
     *  - member-api: App\Admin\Controllers\FooController  -> App\Api\Controllers\FooController
     * Extension convention:
     *  - admin-api:  {NS}\Http\Controllers\FooController  -> {NS}\Http\AdminApi\Controllers\FooController
     *  - member-api: {NS}\Http\Controllers\FooController  -> {NS}\Http\Api\Controllers\FooController
     *
     * @param  string       $controller
     * @param  array|null   $extension
     * @param  string       $kind  member|admin
     * @return string
     */
    protected function apiControllerName(string $controller, ?array $extension, string $kind): string
    {
        if ($extension) {
            $base = $extension['namespace'].'\\Http\\';
            $tail = $kind === 'admin' ? 'AdminApi\\Controllers' : 'Api\\Controllers';
            $short = class_basename($controller);

            return $base.$tail.'\\'.$short;
        }

        if ($kind === 'admin') {
            return str_replace('Admin\\C', 'Admin\\Api\\C', $controller);
        }

        return str_replace('Admin\\C', 'Api\\C', $controller);
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
     * Resolve the extension context from --extension=vendor/name.
     *
     * Reads the extension's composer.json psr-4 to discover the real root namespace
     * and src/ path. Returns null when --extension is not given (main app mode).
     *
     * @return array{package:string,namespace:string,base_path:string,src_path:string}|null
     */
    protected function resolveExtensionContext(): ?array
    {
        $package = trim((string) $this->option('extension'));
        if ($package === '') {
            return null;
        }

        // Accept both vendor/name and vendor.name.
        $package = str_replace('.', '/', $package);
        $basePath = rtrim(admin_extension_path(), '/').'/'.ltrim($package, '/');

        if (! is_dir($basePath)) {
            throw new \RuntimeException("Extension directory [{$basePath}] does not exist. Create it first with admin:ext-make-pro.");
        }

        $composer = $basePath.'/composer.json';
        $namespace = $this->guessExtensionNamespace($package);

        if (is_file($composer)) {
            $json = json_decode((string) file_get_contents($composer), true);
            $psr4 = $json['autoload']['psr-4'] ?? [];
            foreach ($psr4 as $ns => $dir) {
                $namespace = trim($ns, '\\');
                $srcPath = rtrim($dir, '/');
                break;
            }
        }

        $srcPath = isset($srcPath) && $srcPath !== '' ? $srcPath : 'src';
        $srcAbs = $srcPath && $srcPath[0] === '/' ? $srcPath : $basePath.'/'.ltrim($srcPath, '/');

        return [
            'package' => $package,
            'namespace' => $namespace,
            'base_path' => $basePath,
            'src_path' => $srcAbs,
        ];
    }

    /**
     * Guess the extension root namespace from vendor/name (fallback when composer.json lacks psr-4).
     *
     * @param  string $package  vendor/name
     * @return string
     */
    protected function guessExtensionNamespace(string $package): string
    {
        [$vendor, $name] = array_pad(explode('/', $package, 2), 2, '');

        return str_replace(['-'], '', Str::title($vendor)).'\\'.str_replace(['-'], '', Str::title($name));
    }

    /**
     * Resolve the controller namespace (main app or extension).
     *
     * @param  array|null $extension
     * @return string
     */
    protected function resolveControllerNamespace(?array $extension): string
    {
        if ($extension) {
            if ($this->option('controller-namespace')) {
                return $this->option('controller-namespace');
            }

            return $extension['namespace'].'\\Http\\Controllers';
        }

        return $this->option('controller-namespace') ?: config('admin.route.namespace', 'App\\Admin\\Controllers');
    }

    /**
     * Resolve the model namespace (main app or extension).
     *
     * @param  array|null $extension
     * @return string
     */
    protected function resolveModelNamespace(?array $extension): string
    {
        if ($extension) {
            if ($this->option('model-namespace') && $this->option('model-namespace') !== 'App\\Models') {
                return $this->option('model-namespace');
            }

            return $extension['namespace'].'\\Models';
        }

        return $this->option('model-namespace') ?: 'App\\Models';
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
     * Register the generated backend controller's resource route.
     *
     * Main app: app/Admin/routes.php (inside the admin route group).
     * Extension: src/Http/routes.php (uses $router->resource with FQ controller).
     *
     * @param  string       $routePath
     * @param  string       $controller
     * @param  array|null   $extension
     * @return string
     */
    protected function registerBackendRoute(string $routePath, string $controller, ?array $extension): string
    {
        $routeFile = $extension
            ? $extension['src_path'].'/Http/routes.php'
            : admin_path('routes.php');

        if (! is_file($routeFile) || ! is_readable($routeFile) || ! is_writable($routeFile)) {
            throw new \RuntimeException("Admin routes file [{$routeFile}] does not exist or is not writable.");
        }

        $contents = file_get_contents($routeFile);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read admin routes file [{$routeFile}].");
        }

        $controller = ltrim($controller, '\\');

        if ($extension) {
            // Extension routes are flat Route::resource(...) calls (no group wrapper).
            // Match either the short-group form or the FQ form.
            $existsPattern = '/(Route|\\$router)\s*->\s*resource\s*\(\s*[\'\"]\/?'.preg_quote($routePath, '/').'[\'\"]\s*,/';
            if (preg_match($existsPattern, $contents)) {
                return $routePath.' (existing)';
            }
            $route = sprintf("Route::resource('%s', \\%s::class);", $routePath, $controller);
            $contents = rtrim($contents)."\n".$route."\n";
        } else {
            // Main app: inject before the closing group brace.
            $routePattern = '/\$router\s*->\s*resource\s*\(\s*[\'\"]\/?'.preg_quote($routePath, '/').'[\'\"]\s*,/';
            if (preg_match($routePattern, $contents)) {
                return $routePath.' (existing)';
            }
            $position = strrpos($contents, '});');
            if ($position === false) {
                throw new \RuntimeException("Unable to locate the route group closing in [{$routeFile}].");
            }
            $route = sprintf("    \$router->resource('%s', \\%s::class);", $routePath, $controller);
            $contents = substr_replace($contents, "\n\n{$route}\n", $position, 0);
        }

        if (file_put_contents($routeFile, $contents, LOCK_EX) === false) {
            throw new \RuntimeException("Unable to write admin routes file [{$routeFile}].");
        }

        return $routePath;
    }

    /**
     * Register API routes for an API controller into the matching API routes file.
     *
     * Main app:
     *  - member: app/Api/routes.php
     *  - admin:  app/Admin/Api/routes.php
     * Extension:
     *  - member: src/Http/Api/routes.php
     *  - admin:  src/Http/AdminApi/routes.php
     *
     * Mirrors ScaffoldController::ApiControllerCreator(): registers apiResource plus
     * the auxiliary batch/import/export/field endpoints, using FQ controller names.
     *
     * @param  string       $routePath
     * @param  string       $controller  API controller FQN
     * @param  array|null   $extension
     * @param  string       $kind        member|admin
     * @return string
     */
    protected function registerApiRoutes(string $routePath, string $controller, ?array $extension, string $kind): string
    {
        if ($extension) {
            $relative = $kind === 'admin' ? 'src/Http/AdminApi/routes.php' : 'src/Http/Api/routes.php';
            $routeFile = $extension['base_path'].'/'.$relative;
        } else {
            $routeFile = $kind === 'admin' ? app_path('Admin/Api/routes.php') : app_path('Api/routes.php');
        }

        if (! is_file($routeFile) || ! is_readable($routeFile) || ! is_writable($routeFile)) {
            throw new \RuntimeException("API routes file [{$routeFile}] does not exist or is not writable.");
        }

        $contents = file_get_contents($routeFile);
        if ($contents === false) {
            throw new \RuntimeException("Unable to read API routes file [{$routeFile}].");
        }

        $controller = ltrim($controller, '\\');

        // Extension routes use the Route facade (flat, no $router variable), main app uses
        // $router inside an api-auth group. Member-api omits import/export/field.
        if ($extension) {
            $lines = [
                sprintf("Route::apiResource('%s', \\%s::class);", $routePath, $controller),
                sprintf("Route::patch('%s-batchUpdate', [\\%s::class, 'batchUpdate']);", $routePath, $controller),
                sprintf("Route::post('%s-batchDestroy', [\\%s::class, 'batchDelete']);", $routePath, $controller),
            ];
            if ($kind === 'admin') {
                $lines[] = sprintf("Route::get('%s-downImportTplFile', [\\%s::class, 'downImportTplFile']);", $routePath, $controller);
                $lines[] = sprintf("Route::post('%s-import', [\\%s::class, 'import']);", $routePath, $controller);
                $lines[] = sprintf("Route::get('%s-export', [\\%s::class, 'export']);", $routePath, $controller);
                $lines[] = sprintf("Route::get('%s-field', [\\%s::class, 'field']);", $routePath, $controller);
            }

            $appended = [];
            foreach ($lines as $line) {
                if (strpos($contents, $line) !== false) {
                    continue;
                }
                $appended[] = $line;
            }
            if (empty($appended)) {
                return $routePath.' (existing)';
            }

            $contents = rtrim($contents)."\n".implode("\n", $appended)."\n";
        } else {
            $lines = [
                sprintf("\$router->apiResource('/%s', \\%s::class);", $routePath, $controller),
                sprintf("\$router->patch('/%s-batchUpdate', '\\%s@batchUpdate');", $routePath, $controller),
                sprintf("\$router->post('/%s-batchDestroy', '\\%s@batchDelete');", $routePath, $controller),
            ];
            if ($kind === 'admin') {
                $lines[] = sprintf("\$router->get('/%s-downImportTplFile', '\\%s@downImportTplFile');", $routePath, $controller);
                $lines[] = sprintf("\$router->post('/%s-import', '\\%s@import');", $routePath, $controller);
                $lines[] = sprintf("\$router->get('/%s-export', '\\%s@export');", $routePath, $controller);
                $lines[] = sprintf("\$router->get('/%s-field', '\\%s@field');", $routePath, $controller);
            }

            $appended = [];
            foreach ($lines as $line) {
                if (strpos($contents, $line) !== false) {
                    continue;
                }
                $appended[] = '    '.$line;
            }
            if (empty($appended)) {
                return $routePath.' (existing)';
            }

            $position = strrpos($contents, '});');
            if ($position === false) {
                throw new \RuntimeException("Unable to locate the route group closing in [{$routeFile}].");
            }

            $contents = substr_replace($contents, "\n".implode("\n", $appended)."\n", $position, 0);
        }

        if (file_put_contents($routeFile, $contents, LOCK_EX) === false) {
            throw new \RuntimeException("Unable to write API routes file [{$routeFile}].");
        }

        return $routePath;
    }

    /**
     * Delete generated class file when force option is enabled.
     *
     * @param  string       $class
     * @param  array|null   $extension
     * @return void
     */
    protected function deleteClassFile(string $class, ?array $extension = null): void
    {
        $path = $extension
            ? $this->extensionClassPath($class, $extension)
            : Helper::guessClassFileName($class);

        if (is_file($path)) {
            app('files')->delete($path);
        }
    }

    /**
     * Map a class FQN to its file path inside an extension src/ tree.
     *
     * @param  string  $class
     * @param  array   $extension
     * @return string
     */
    protected function extensionClassPath(string $class, array $extension): string
    {
        $relative = ltrim(str_replace($extension['namespace'], '', $class), '\\');

        return $extension['src_path'].'/'.str_replace('\\', '/', $relative).'.php';
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
