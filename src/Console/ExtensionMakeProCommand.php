<?php

namespace Dcat\Admin\Console;

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

        $this->info("The extension scaffolding generated successfully. \r\n");
        $this->showTree();
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
            ['{namespace}', '{className}'],
            [$this->namespace, $this->className],
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
            ['{namespace}', '{className}'],
            [$this->namespace, $this->className],
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
            __DIR__.'/stubs/extension/README.md.stub'    => 'README.md',
            __DIR__.'/stubs/extension/version.stub'      => 'version.php',
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
}