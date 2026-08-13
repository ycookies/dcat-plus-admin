<?php

namespace Dcat\Admin\Console;

use Dcat\Admin\Scaffold\DbColumnToSchemaFields;
use Dcat\Admin\Scaffold\ExtensionMigrationBuilder;
use Dcat\Admin\Scaffold\ExtensionModelCreator;
use Dcat\Admin\Scaffold\ExtensionSeederBuilder;
use Dcat\Admin\Scaffold\Support\TableColumnInspector;
use Dcat\Admin\Support\Helper;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ExtensionMakeProCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'admin:ext-make-pro
    {name : The name of the extension. Eg: author-name/extension-name}
    {--namespace= : The namespace of the extension.}
    {--theme}
    {--api : Create API and AdminApi directories with routes and controllers}
    {--plugin_name}
    {--plugin_desc}
    {--authors_name}
    {--authors_email}
    {--models= : Comma-separated table names to reverse-engineer into Model + Migration (e.g. users,orders)}
    {--seed : Generate idempotent seeders for each --models table and register them}
    {--blueprint : Generate docs/ blueprint + delivery documents}
    {--marketplace : Generate docs/MARKETPLACE.md + resources/views/landing.blade.php}
    {--db-connection= : Database connection used for reverse-engineering (defaults to database.default)}
    {--table-prefix= : Prefix for newly created tables (defaults to a slug of the package name, e.g. miniapp_manager -> miniapp_). Only applies to tables that do NOT already exist. Use --table-prefix= to disable}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Build a dcat-admin extension';

    /**
     * @var string
     */
    protected $basePath = '';

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $extensionName;

    /**
     * @var string
     */
    protected $package;

    /**
     * @var string
     */
    protected $extensionDir;

    /**
     * 收集已生成的 migration 文件名（用于登记 version.php）.
     *
     * @var array<string>
     */
    protected $generatedMigrations = [];

    /**
     * 收集已生成的 seeder 文件名（用于登记 version.php）.
     *
     * @var array<string>
     */
    protected $generatedSeeders = [];

    /**
     * 收集每个表的元信息（反推来源 / 字段 / 是否占位），供蓝图文档渲染.
     * 结构：[ tableName => [ 'exists' => bool, 'columns' => array, 'source' => string, 'comment' => string ] ].
     *
     * @var array<string, array>
     */
    protected $tableInfos = [];

    /**
     * 反推用的数据库连接.
     *
     * @var string|null
     */
    protected $dbConnection;

    /**
     * 新建表（占位模式）使用的表名前缀，如 `miniapp_`.
     * 反推已存在的表保持原名，不受此影响.
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * @var array
     */
    protected $dirs = [
        'updates',
        'resources/assets/css',
        'resources/assets/js',
        'resources/views',
        'resources/lang',
        'src/Models',
        'src/Http/Controllers',
        'src/Http/Middleware',
        'docs',
        'docs/database',
        'docs/tasks',
        'docs/delivery',
    ];

    /**
     * @var array
     */
    protected $apiDirs = [
        'src/Http/Api/Controllers',
        'src/Http/AdminApi/Controllers',
    ];

    protected $themeDirs = [
        'updates',
        'resources/assets/css',
        'resources/views',
        'src',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        $this->extensionDir = admin_extension_path();

        if (! file_exists($this->extensionDir)) {
            $this->makeDir();
        }

        $this->package = str_replace('.', '/', $this->argument('name'));
        $this->extensionName = str_replace('/', '.', $this->package);

        $this->basePath = rtrim($this->extensionDir, '/').'/'.ltrim($this->package, '/');

        if (is_dir($this->basePath)) {
            return $this->error(sprintf('The extension [%s] already exists!', $this->package));
        }

        InputExtensionName :
        if (! Helper::validateExtensionName($this->package)) {
            $this->package = $this->ask("[$this->package] is not a valid package name, please input a name like (<vendor>/<name>)");
            goto InputExtensionName;
        }

        $this->makeDirs();
        $this->makeFiles();

        // 反推 / 占位生成 Model + Migration + Seeder，并登记进 version.php
        $this->dbConnection = $this->option('db-connection') ?: config('database.default');
        $this->tablePrefix = $this->resolveTablePrefix();

        $this->makeModelMigrations();
        $this->makeSeeders();
        $this->makeBlueprintDocs();
        $this->makeMarketplace();
        $this->makeReadme();

        // version.php 必须在所有迁移 / seeder 生成后再写入，才能正确登记
        $this->makeVersionFile();

        $this->info("The extension scaffolding generated successfully. \r\n");
        $this->showTree();

        if ($this->generatedMigrations || $this->generatedSeeders) {
            $this->info('Registered in version.php:');
            foreach (array_merge($this->generatedMigrations, $this->generatedSeeders) as $script) {
                $this->line('  - '.$script);
            }
            $this->comment('Run `php artisan admin:ext-update '.$this->extensionName.'` to apply migrations.');
        }
    }

    /**
     * Show extension scaffolding with tree structure.
     */
    protected function showTree()
    {
        if ($this->option('theme')) {
            $tree = <<<TREE
{$this->extensionPath()}
    ├── README.md
    ├── composer.json
    ├── version.php
    ├── updates
    ├── resources
    │   ├── lang
    │   ├── assets
    │   │   └── css
    │   │       └── index.css
    │   └── views
    └── src
        ├── {$this->className}ServiceProvider.php
        └── Setting.php
TREE;
        } else {
            $apiTree = '';
            if ($this->option('api')) {
                $apiTree = <<<TREE

        ├── Api
        │   ├── Controllers
        │   │   └── IndexController.php
        │   └── routes.php
        ├── AdminApi
            ├── Controllers
            │   └── IndexController.php
            └── routes.php
TREE;
            }

            $tree = <<<TREE
{$this->extensionPath()}
    ├── README.md
    ├── composer.json
    ├── version.php
    ├── logo.png
    ├── updates
    ├── resources
    │   ├── lang
    │   ├── assets
    │   │   ├── css
    │   │   │   └── index.css
    │   │   └── js
    │   │       └── index.js
    │   └── views
    │       └── index.blade.php
    └── src
        ├── {$this->className}ServiceProvider.php
        ├── Setting.php
        ├── Models
        └── Http
            ├── routes.php
            ├── Middleware
            ├── Controllers
            │   └── {$this->className}Controller.php{$apiTree}
TREE;
        }

        $this->info($tree);
    }

    /**
     * Make extension files.
     */
    protected function makeFiles()
    {
        $this->namespace = $this->getRootNameSpace();

        $this->className = $this->getClassName();

        // copy files
        $this->copyFiles();

        $plugin_name = $this->option('plugin_name');
        $plugin_desc = $this->option('plugin_desc');
        $authors_name = $this->option('authors_name');
        $authors_email = $this->option('authors_email');
        // make composer.json
        $composerContents = str_replace(
            ['{package}', '{pluginName}', '{namespace}', '{className}', '{pluginDesc}', '{authorsName}', '{authorsEmail}'],
            [$this->package, $plugin_name, str_replace('\\', '\\\\', $this->namespace).'\\\\', $this->className, $plugin_desc, $authors_name, $authors_email],
            file_get_contents(__DIR__.'/stubs/extension/composer.json.stub')
        );
        $this->putFile('composer.json', $composerContents);

        // make setting
        $settingContents = str_replace(
            ['{namespace}'],
            [$this->namespace],
            file_get_contents(__DIR__.'/stubs/extension/setting.stub')
        );
        $this->putFile('src/Setting.php', $settingContents);

        $basePackage = Helper::slug(basename($this->package));

        // make service provider class
        $classContents = str_replace(
            ['{namespace}', '{className}', '{title}', '{path}', '{basePackage}', '{property}', '{registerTheme}', '{apiRegisterCalls}', '{apiMethods}'],
            [
                $this->namespace,
                $this->className,
                Str::title($this->className),
                $basePackage,
                $basePackage,
                $this->makeProviderContent(),
                $this->makeRegisterThemeContent(),
                $this->makeApiRegisterCalls(),
                $this->makeApiMethods(),
            ],
            file_get_contents(__DIR__.'/stubs/extension/extension.stub')
        );
        $this->putFile("src/{$this->className}ServiceProvider.php", $classContents);

        if (! $this->option('theme')) {
            // make controller
            $controllerContent = str_replace(
                ['{namespace}', '{className}', '{name}'],
                [$this->namespace, $this->className, $this->extensionName],
                file_get_contents(__DIR__.'/stubs/extension/controller.stub')
            );
            $this->putFile("src/Http/Controllers/{$this->className}Controller.php", $controllerContent);

            $viewContents = str_replace(
                ['{name}'],
                [$this->extensionName],
                file_get_contents(__DIR__.'/stubs/extension/view.stub')
            );
            $this->putFile('resources/views/index.blade.php', $viewContents);

            // make routes
            $routesContent = str_replace(
                ['{namespace}', '{className}', '{path}'],
                [$this->namespace, $this->className, $basePackage],
                file_get_contents(__DIR__.'/stubs/extension/routes.stub')
            );
            $this->putFile('src/Http/routes.php', $routesContent);

            // make API files if --api option is set
            if ($this->option('api')) {
                $this->makeApiFiles($basePackage);
            }
        }
    }

    /**
     * Make API related files (routes + controllers).
     */
    protected function makeApiFiles(string $basePackage)
    {
        // Api routes
        $apiRoutesContent = str_replace(
            ['{namespace}', '{className}', '{path}'],
            [$this->namespace, $this->className, $basePackage],
            file_get_contents(__DIR__.'/stubs/extension/api_routes.stub')
        );
        $this->putFile('src/Http/Api/routes.php', $apiRoutesContent);

        // Api controller
        $apiControllerContent = str_replace(
            ['{namespace}', '{className}', '{name}'],
            [$this->namespace, $this->className, $this->extensionName],
            file_get_contents(__DIR__.'/stubs/extension/api_controller.stub')
        );
        $this->putFile('src/Http/Api/Controllers/IndexController.php', $apiControllerContent);

        // AdminApi routes
        $adminApiRoutesContent = str_replace(
            ['{namespace}', '{className}', '{path}'],
            [$this->namespace, $this->className, $basePackage],
            file_get_contents(__DIR__.'/stubs/extension/admin_api_routes.stub')
        );
        $this->putFile('src/Http/AdminApi/routes.php', $adminApiRoutesContent);

        // AdminApi controller
        $adminApiControllerContent = str_replace(
            ['{namespace}', '{className}', '{name}'],
            [$this->namespace, $this->className, $this->extensionName],
            file_get_contents(__DIR__.'/stubs/extension/admin_api_controller.stub')
        );
        $this->putFile('src/Http/AdminApi/Controllers/IndexController.php', $adminApiControllerContent);
    }

    /**
     * Generate API register calls for ServiceProvider.
     */
    protected function makeApiRegisterCalls()
    {
        if (! $this->option('api') || $this->option('theme')) {
            return '';
        }

        return <<<'TEXT'
		// API 路由需在 register() 中加载，因为 boot()/init() 会跳过 API 请求
		$this->loadApiRoutes();
		$this->loadAdminApiRoutes();
TEXT;
    }

    /**
     * Generate API methods for ServiceProvider.
     */
    protected function makeApiMethods()
    {
        if (! $this->option('api') || $this->option('theme')) {
            return '';
        }

        $namespace = str_replace('\\', '\\\\', $this->namespace);

        return <<<TEXT

    /**
     * 加载插件API路由（前台会员端）
     */
    protected function loadApiRoutes(): void
    {
        \$apiRouteFile = \$this->path('src/Http/Api/routes.php');
        if (file_exists(\$apiRouteFile)) {
            \\Illuminate\\Support\\Facades\\Route::prefix('member-api')
                ->middleware('api')
                ->namespace('{$namespace}\\Http\\Api\\Controllers')
                ->group(\$apiRouteFile);
        }
    }

    /**
     * 加载插件AdminApi路由（后台管理端）
     */
    protected function loadAdminApiRoutes(): void
    {
        \$apiRouteFile = \$this->path('src/Http/AdminApi/routes.php');
        if (file_exists(\$apiRouteFile)) {
            \\Illuminate\\Support\\Facades\\Route::prefix('admin-api')
                ->middleware('api')
                ->namespace('{$namespace}\\Http\\AdminApi\\Controllers')
                ->group(\$apiRouteFile);
        }
    }
TEXT;
    }

    protected function makeProviderContent()
    {
        if (! $this->option('theme')) {
            return <<<'TEXT'
protected $js = [
        'js/index.js',
    ];
TEXT;
        }

        return <<<'TEXT'
protected $type = self::TYPE_THEME;

TEXT;
    }

    protected function makeRegisterThemeContent()
    {
        if (! $this->option('theme')) {
            return;
        }

        return <<<'TEXT'
Admin::baseCss($this->formatAssetFiles($this->css));
TEXT;
    }

    protected function copyFiles()
    {
        $files = [
            $view = __DIR__.'/stubs/extension/view.stub' => 'resources/views/index.blade.php',
            $js = __DIR__.'/stubs/extension/js.stub'     => 'resources/assets/js/index.js',
            __DIR__.'/stubs/extension/css.stub'          => 'resources/assets/css/index.css',
            __DIR__.'/stubs/extension/dcat-plus-logo.png'      => 'resources/assets/dcat-plus-logo.png',
            __DIR__.'/stubs/extension/.gitignore.stub'   => '.gitignore',
            __DIR__.'/stubs/extension/logo.png'      => 'logo.png',
        ];

        if ($this->option('theme')) {
            unset($files[$view], $files[$js]);
        }

        $this->copy($files);
    }

    /**
     * Get root namespace for this package.
     *
     * @return array|null|string
     */
    protected function getRootNameSpace()
    {
        [$vendor, $name] = explode('/', $this->package);

        $default = str_replace(['-'], '', Str::title($vendor).'\\'.Str::title($name));

        if (! $namespace = $this->option('namespace')) {
            $namespace = $this->ask('Root namespace', $default);
        }

        return $namespace === 'default' ? $default : $namespace;
    }

    /**
     * Get extension class name.
     *
     * @return string
     */
    protected function getClassName()
    {
        return ucfirst(Str::camel(basename($this->package)));
    }

    /**
     * Create package dirs.
     */
    protected function makeDirs()
    {
        $dirs = $this->option('theme') ? $this->themeDirs : $this->dirs;

        // Add API dirs if --api option is set and not a theme
        if (! $this->option('theme') && $this->option('api')) {
            $dirs = array_merge($dirs, $this->apiDirs);
        }

        $this->makeDir($dirs);
    }

    /**
     * Extension path.
     *
     * @param  string  $path
     * @return string
     */
    protected function extensionPath($path = '')
    {
        $path = rtrim($path, '/');

        if (empty($path)) {
            return rtrim($this->basePath, '/');
        }

        return rtrim($this->basePath, '/').'/'.ltrim($path, '/');
    }

    /**
     * Put contents to file.
     *
     * @param  string  $to
     * @param  string  $content
     */
    protected function putFile($to, $content)
    {
        $to = $this->extensionPath($to);

        $this->filesystem->put($to, $content);
    }

    /**
     * Copy files to extension path.
     *
     * @param  string|array  $from
     * @param  string|null  $to
     */
    protected function copy($from, $to = null)
    {
        if (is_array($from) && is_null($to)) {
            foreach ($from as $key => $value) {
                $this->copy($key, $value);
            }

            return;
        }

        if (! file_exists($from)) {
            return;
        }

        $to = $this->extensionPath($to);

        $this->filesystem->copy($from, $to);
    }

    /**
     * Make new directory.
     *
     * @param  array|string  $paths
     */
    protected function makeDir($paths = '')
    {
        foreach ((array) $paths as $path) {
            $path = $this->extensionPath($path);

            $this->filesystem->makeDirectory($path, 0755, true, true);
        }
    }

    /**
     * 解析 --models 选项为数组（去除空值）.
     *
     * @return array<string>
     */
    protected function parseModelsOption(): array
    {
        $raw = (string) $this->option('models');
        if ($raw === '') {
            return [];
        }

        $tables = array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== '');

        return array_values($tables);
    }

    /**
     * 是否需要生成蓝图文档（--blueprint 或有 --models 时自动开启）.
     */
    protected function shouldMakeBlueprint(): bool
    {
        return (bool) $this->option('blueprint') || $this->parseModelsOption() !== [];
    }

    /**
     * 解析新建表的前缀.
     *
     * 规则：
     *   1. --table-prefix 显式指定（含空串=不要前缀），优先级最高；
     *   2. 否则从包名 name 段自动缩写：snake_case → 去业务无关后缀
     *      (manager/system/plugin/extension/chart/charts/module) → 仍超 14 字符则取首段 → 末尾加 _。
     *   例：miniapp_manager → miniapp_；competition_management → competition_；cms → cms_。
     *
     * 仅作用于占位（新建）表；反推已存在表保持原名。
     */
    protected function resolveTablePrefix(): string
    {
        if ($this->option('table-prefix') !== null) {
            $explicit = trim($this->option('table-prefix'));
            // 显式前缀规范化为「小写蛇形 + 结尾下划线」，空串表示明确不要前缀
            if ($explicit === '') {
                return '';
            }

            return rtrim(Str::snake(preg_replace('/[^a-zA-Z0-9_]+/', '_', $explicit)), '_').'_';
        }

        // 从包名 name 段推断
        $nameSegment = basename($this->package);
        $snake = Str::snake(Str::camel($nameSegment));

        // 去常见业务无关后缀（整体或末段）
        $suffixes = ['_manager', '_system', '_plugin', '_extension', '_chart', '_charts', '_module'];
        foreach ($suffixes as $suffix) {
            if (str_ends_with($snake, $suffix)) {
                $snake = substr($snake, 0, -strlen($suffix));
                break;
            }
        }

        // 仍超长（>14）则只取第一段，避免前缀淹没表名
        if (strlen($snake) > 14 && str_contains($snake, '_')) {
            $snake = explode('_', $snake)[0];
        }
        if ($snake === '') {
            return '';
        }

        return $snake.'_';
    }

    /**
     * 为 --models 中的每个表反推生成 Model + Migration（表不存在则占位）.
     */
    protected function makeModelMigrations(): void
    {
        $tables = $this->parseModelsOption();
        if ($tables === []) {
            return;
        }
        if ($this->option('theme')) {
            $this->warn('--models is ignored for theme extensions.');

            return;
        }

        $existing = array_flip(TableColumnInspector::getTableNames($this->dbConnection));

        foreach ($tables as $logicTable) {
            $logicTable = trim($logicTable);
            if ($logicTable === '') {
                continue;
            }

            $exists = isset($existing[$logicTable]);
            // 物理表名：反推的已存在表保持原名；占位（新建）表加扩展前缀，避免多扩展撞名
            $physicalTable = $exists ? $logicTable : $this->tablePrefix.$logicTable;

            $columns = $exists
                ? TableColumnInspector::getNormalizedColumns($logicTable, $this->dbConnection)
                : [];

            $this->tableInfos[$physicalTable] = [
                'exists' => $exists,
                'logic_name' => $logicTable,
                'columns' => $columns,
                'source' => $exists ? 'reverse-engineered' : 'placeholder',
                'comment' => $exists ? $this->guessTableComment($logicTable, $columns) : '',
            ];

            // 先 Model：modelFqn 落在扩展命名空间 src/Models/，类名用逻辑表名（不带前缀）
            $modelGenerated = $this->generateModelForTable($logicTable, $physicalTable, $columns);

            // 后 Migration：Model 冲突时不写孤立 migration
            $migrationGenerated = $this->generateMigrationForTable($logicTable, $physicalTable, $exists, $columns);

            $this->line(sprintf(
                '  [%s%s] %s%s',
                $physicalTable,
                $exists ? '' : " (逻辑名: {$logicTable})",
                $exists ? 'reverse-engineered' : 'placeholder',
                $modelGenerated || $migrationGenerated ? '' : ' (skipped, files exist)'
            ));
        }
    }

    /**
     * 反推生成 Model（复用 ModelCreator）.
     *
     * @param  string  $logicTable     逻辑表名（决定 Model 类名，不带前缀）
     * @param  string  $physicalTable  物理表名（写入 $table 属性；占位表带前缀）
     * @param  array   $columns        normalized columns（占位模式为空数组）
     * @return bool 是否成功生成
     */
    protected function generateModelForTable(string $logicTable, string $physicalTable, array $columns): bool
    {
        $modelFqn = $this->namespace.'\\Models\\'.$this->modelClassName($logicTable);
        $targetFile = $this->extensionPath('src/Models/'.$this->modelClassName($logicTable).'.php');

        if (file_exists($targetFile)) {
            return false;
        }

        try {
            $creator = new ExtensionModelCreator(
                $physicalTable,
                $modelFqn,
                $this->extensionPath('src'),
                $this->namespace,
                $this->dbConnection,
                $columns
            );
            $creator->create('id', true, isset($columns['deleted_at']));
        } catch (\Throwable $e) {
            $this->warn("Model for [{$physicalTable}] skipped: ".$e->getMessage());

            return false;
        }

        return file_exists($targetFile);
    }

    /**
     * 生成 create migration（反推或占位）.
     *
     * @param  string  $logicTable
     * @param  string  $physicalTable  Schema::create 的表名（占位表带前缀）
     * @param  bool    $exists         表是否已存在（决定反推 or 占位）
     * @param  array   $columns
     * @return bool
     */
    protected function generateMigrationForTable(string $logicTable, string $physicalTable, bool $exists, array $columns): bool
    {
        $builder = new ExtensionMigrationBuilder();
        // 文件名用物理表名，便于通过表名定位文件
        $fileName = $builder->fileNameForCreate($physicalTable);
        $targetFile = $this->extensionPath('updates/'.$fileName);

        if (file_exists($targetFile)) {
            return false;
        }

        $comment = $exists ? $this->guessTableComment($logicTable, $columns) : '';
        $source = $exists
            ? $builder->buildCreateFromColumns($physicalTable, $columns, $comment)
            : $builder->buildPlaceholderCreate($physicalTable, $logicTable, $comment);

        $this->putFile('updates/'.$fileName, $source);

        if (! in_array($fileName, $this->generatedMigrations, true)) {
            $this->generatedMigrations[] = $fileName;
        }

        return true;
    }

    /**
     * 生成 Seeder（--seed）.
     */
    protected function makeSeeders(): void
    {
        if (! $this->option('seed')) {
            return;
        }
        $tables = $this->parseModelsOption();
        if ($tables === []) {
            $this->warn('--seed requires --models.');

            return;
        }

        $seederBuilder = new ExtensionSeederBuilder();
        // tableInfos 以物理表名为 key（含前缀），seeder 表名/文件名用物理表名
        foreach ($this->tableInfos as $physicalTable => $info) {
            $columns = $info['columns'] ?? [];
            $fileName = $seederBuilder->fileNameFor($physicalTable);
            $targetFile = $this->extensionPath('updates/'.$fileName);
            if (file_exists($targetFile)) {
                continue;
            }

            $source = $seederBuilder->build($physicalTable, $columns);
            $this->putFile('updates/'.$fileName, $source);

            if (! in_array($fileName, $this->generatedSeeders, true)) {
                $this->generatedSeeders[] = $fileName;
            }
        }
    }

    /**
     * 生成 version.php，登记所有迁移 / seeder.
     */
    protected function makeVersionFile(): void
    {
        $entries = $this->buildVersionEntries();
        $content = str_replace(
            ['{migrationEntries}'],
            [$entries],
            file_get_contents(__DIR__.'/stubs/extension/version.stub')
        );
        $this->putFile('version.php', $content);
    }

    /**
     * 渲染 version.php 中 1.0.0 版本下的脚本条目（8 空格缩进行）.
     * 顺序：先所有 create_*，后所有 seed_*.
     */
    protected function buildVersionEntries(): string
    {
        $migrations = array_unique($this->generatedMigrations);
        $seeders = array_unique($this->generatedSeeders);
        $scripts = array_merge($migrations, $seeders);

        if ($scripts === []) {
            return '';
        }

        $lines = [];
        foreach ($scripts as $script) {
            $lines[] = "        '".addslashes($script)."',";
        }

        return implode("\n", $lines);
    }

    /**
     * 生成 docs/ 蓝图与交付文档.
     */
    protected function makeBlueprintDocs(): void
    {
        if (! $this->shouldMakeBlueprint() || $this->option('theme')) {
            return;
        }

        $stubsDir = __DIR__.'/stubs/extension/docs';
        $mappings = [
            'BLUEPRINT.md.stub'        => 'docs/BLUEPRINT.md',
            'schema.md.stub'           => 'docs/database/schema.md',
            'PLAN.md.stub'             => 'docs/tasks/PLAN.md',
        ];
        if ($this->option('api')) {
            $mappings['api_delivery.md.stub'] = 'docs/delivery/api_delivery.md';
        }

        foreach ($mappings as $stub => $target) {
            if (! file_exists($stubsDir.'/'.$stub)) {
                continue;
            }
            $content = $this->renderDocStub(file_get_contents($stubsDir.'/'.$stub));
            $this->putFile($target, $content);
        }
    }

    /**
     * 生成市场发布文档与落地页（--marketplace）.
     */
    protected function makeMarketplace(): void
    {
        if (! $this->option('marketplace') || $this->option('theme')) {
            return;
        }

        $stubsDir = __DIR__.'/stubs/extension';
        $md = $stubsDir.'/MARKETPLACE.md.stub';
        if (file_exists($md)) {
            $this->putFile('docs/MARKETPLACE.md', $this->renderDocStub(file_get_contents($md)));
        }
        $landing = $stubsDir.'/landing.blade.stub';
        if (file_exists($landing)) {
            $this->putFile('resources/views/landing.blade.php', $this->renderDocStub(file_get_contents($landing)));
        }
    }

    /**
     * 生成 README（占位替换，非直接 copy）.
     */
    protected function makeReadme(): void
    {
        $stub = __DIR__.'/stubs/extension/README.md.stub';
        if (! file_exists($stub)) {
            return;
        }
        $this->putFile('README.md', $this->renderDocStub(file_get_contents($stub)));
    }

    /**
     * 渲染文档 stub 的通用占位替换.
     */
    protected function renderDocStub(string $content): string
    {
        $pluginName = (string) $this->option('plugin_name');
        if ($pluginName === '') {
            $pluginName = $this->className;
        }

        return str_replace(
            ['{pluginName}', '{pluginDesc}', '{extensionName}', '{namespace}', '{className}', '{package}', '{tables}', '{tableSummaries}', '{apiOrNone}', '{tablePrefix}'],
            [
                $pluginName,
                (string) $this->option('plugin_desc'),
                $this->extensionName,
                $this->namespace,
                $this->className,
                $this->package,
                implode(', ', array_keys($this->tableInfos)),
                $this->renderTableSummaries(),
                $this->option('api') ? '' : "\n> 本扩展未启用 API（创建时未带 --api）。\n",
                $this->tablePrefix,
            ],
            $content
        );
    }

    /**
     * 渲染 schema.md 的表清单章节.
     */
    protected function renderTableSummaries(): string
    {
        if ($this->tableInfos === []) {
            return "_暂无已生成表。请在创建扩展时使用 `--models=<表名>` 反推生成，或手动补充本章节。_\n";
        }

        $sections = [];
        foreach ($this->tableInfos as $table => $info) {
            $sections[] = $this->renderOneTableSummary($table, $info);
        }

        return implode("\n\n---\n\n", $sections);
    }

    /**
     * 渲染单张表的 schema 摘要.
     */
    protected function renderOneTableSummary(string $physicalTable, array $info): string
    {
        $exists = $info['exists'];
        $logicTable = $info['logic_name'] ?? $physicalTable;
        $columns = $info['columns'];
        $source = $exists ? '反推自现有数据库表' : '**占位（表尚未创建，需补充字段）**';
        $builder = new ExtensionMigrationBuilder();
        $fileName = $builder->fileNameForCreate($physicalTable);

        $md = "### `{$physicalTable}`\n\n";
        $md .= "- **业务实体**：".($info['comment'] !== '' ? $info['comment'] : '待补充')."\n";
        if ($physicalTable !== $logicTable) {
            $md .= "- **逻辑表名**：`{$logicTable}`（Model 类名据此，物理表名加扩展前缀 `{$this->tablePrefix}`）\n";
        }
        $md .= "- **迁移文件**：`updates/{$fileName}`\n";
        $md .= "- **反推来源**：{$source}\n";
        $md .= "- **Model**：`{$this->namespace}\\Models\\{$this->modelClassName($logicTable)}`\n\n";

        if ($exists && $columns) {
            $converter = new DbColumnToSchemaFields();
            $md .= "| 字段 | 类型 | 默认 | 可空 | 键 | 注释 |\n";
            $md .= "| --- | --- | --- | --- | --- | --- |\n";
            foreach ($columns as $name => $col) {
                $field = $converter->convertColumn($name, (array) $col);
                $md .= sprintf(
                    "| `%s` | %s | %s | %s | %s | %s |\n",
                    $name,
                    $field['method'].($field['args'] ? '('.implode(',', $field['args']).')' : '').($field['unsigned'] ? ' unsigned' : ''),
                    $field['has_default'] ? '`'.str_replace('|', '\\|', (string) $field['default']).'`' : '—',
                    $field['nullable'] ? '是' : '否',
                    $field['unique'] ? 'UNI' : ($field['index'] ? 'MUL' : ''),
                    str_replace('|', '\\|', $field['comment'])
                );
            }
            $md .= "\n> 字段注释中的 `@scaffold:type` / `@scaffold:options` 指令遵循 [database-schema.md](../../../../.claude/skills/dcat-plus-admin/references/database-schema.md)。\n";
        } else {
            $md .= "> **占位表**：字段尚未定义。请参考 [database-schema.md](../../../../.claude/skills/dcat-plus-admin/references/database-schema.md) 补充 `updates/{$fileName}` 的字段，并在 [PLAN.md](../tasks/PLAN.md) 阶段 1 勾选完成。\n";
        }

        return $md;
    }

    /**
     * 推断表级业务实体名.
     * normalized columns 不含表 COMMENT，故优先回退到 Studly 单数表名，
     * 让 schema.md 的「业务实体」语义清晰而非误取首字段注释.
     */
    protected function guessTableComment(string $table, array $columns): string
    {
        return Str::studly(Str::singular($table));
    }

    /**
     * 表名 → Model 类名（Studly 单数）.
     */
    protected function modelClassName(string $table): string
    {
        return Str::studly(Str::singular($table));
    }
}