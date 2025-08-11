<?php

namespace Dcat\Admin\Console;

use Dcat\Admin\Models\AdminTablesSeeder;
use Illuminate\Console\Command;

class InstallCommand extends Command {
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'admin:install';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install the admin package';

    /**
     * Install directory.
     *
     * @var string
     */
    protected $directory = '';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle() {
        $this->initDatabase();

        $this->initAdminDirectory();

        $this->info('Done.');
    }

    /**
     * Create tables and seed it.
     *
     * @return void
     */
    public function initDatabase() {
        $this->call('migrate');

        $userModel = config('admin.database.users_model');

        if ($userModel::count() == 0) {
            $this->call('db:seed', ['--class' => AdminTablesSeeder::class]);
        }
    }

    /**
     * Set admin directory.
     *
     * @return void
     */
    protected function setDirectory() {
        $this->directory = config('admin.directory');
    }

    /**
     * Initialize the admin directory.
     *
     * @return void
     */
    protected function initAdminDirectory() {
        $this->setDirectory();

        if (is_dir($this->directory)) {
            $this->warn("{$this->directory} directory already exists !");

            return;
        }

        $this->makeDir('/');
        $this->line('<info>Admin directory was created:</info> ' . str_replace(base_path(), '', $this->directory));

        $this->makeDir('Controllers');
        $this->makeDir('Metrics/Examples');

        // start   Api
        $this->makeDir('Api/Controllers');
        // member api
        $this->laravel['files']->makeDirectory(app_path('Api/Controllers'), 0755, true, true);
        $this->addAdminApiGuards();
        $this->createApiModelFile();
        $this->createApiControllerFile();
        $this->createApiRoutesFile();
        $this->createMembmerApiRoutesFile();
        $this->createApiMiddlewareFile();
        $this->createMemberApiMiddlewareFile();
        $this->jwtVendorPublish();
        $this->editLocaleTozh_CN();
        $this->updateScrambleConfig();
        // end  api

        $this->createHomeController();
        $this->createMemberUserController();
        $this->createWebConfigController();
        $this->createOpenapiDocsController();
        $this->createAuthController();
        $this->createMetricCards();

        $this->createBootstrapFile();
        $this->createRoutesFile();
    }

    /**
     * Create HomeController.
     *
     * @return void
     */
    public function createHomeController() {
        $homeController = $this->directory . '/Controllers/HomeController.php';
        $contents       = $this->getStub('HomeController');

        $this->laravel['files']->put(
            $homeController,
            str_replace(
                ['DummyNamespace', 'MetricsNamespace'],
                [$this->namespace('Controllers'), $this->namespace('Metrics\\Examples')],
                $contents
            )
        );
        $this->line('<info>HomeController file was created:</info> ' . str_replace(base_path(), '', $homeController));
    }

    /**
     * Create HomeController.
     *
     * @return void
     */
    public function createMemberUserController() {
        $homeController = $this->directory . '/Controllers/MemberUserController.php';
        $contents       = $this->getStub('MemberUserController');

        $this->laravel['files']->put(
            $homeController,
            str_replace(
                ['DummyNamespace', 'MetricsNamespace'],
                [$this->namespace('Controllers'), $this->namespace('Metrics\\Examples')],
                $contents
            )
        );
        $this->line('<info>MemberUserController file was created:</info> ' . str_replace(base_path(), '', $homeController));
    }

    /**
     * Create HomeController.
     *
     * @return void
     */
    public function createWebConfigController() {
        $homeController = $this->directory . '/Controllers/WebConfigController.php';
        $contents       = $this->getStub('WebConfigController');

        $this->laravel['files']->put(
            $homeController,
            str_replace(
                ['DummyNamespace', 'MetricsNamespace'],
                [$this->namespace('Controllers'), $this->namespace('Metrics\\Examples')],
                $contents
            )
        );
        $this->line('<info>WebConfigController file was created:</info> ' . str_replace(base_path(), '', $homeController));
    }

    /**
     * Create HomeController.
     *
     * @return void
     */
    public function createOpenapiDocsController() {
        $homeController = $this->directory . '/Controllers/OpenApiDocsController.php';
        $contents       = $this->getStub('OpenApiDocsController');

        $this->laravel['files']->put(
            $homeController,
            str_replace(
                ['DummyNamespace', 'MetricsNamespace'],
                [$this->namespace('Controllers'), $this->namespace('Metrics\\Examples')],
                $contents
            )
        );
        $this->line('<info>WebConfigController file was created:</info> ' . str_replace(base_path(), '', $homeController));
    }

    /**
     * Create AuthController.
     *
     * @return void
     */
    public function createAuthController() {
        $authController = $this->directory . '/Controllers/AuthController.php';
        $contents       = $this->getStub('AuthController');

        $this->laravel['files']->put(
            $authController,
            str_replace(
                ['DummyNamespace'],
                [$this->namespace('Controllers')],
                $contents
            )
        );
        $this->line('<info>AuthController file was created:</info> ' . str_replace(base_path(), '', $authController));
    }

    /**
     * @return void
     */
    public function createMetricCards() {
        $map = [
            '/Metrics/Examples/NewUsers.php'      => 'metrics/NewUsers',
            '/Metrics/Examples/NewDevices.php'    => 'metrics/NewDevices',
            '/Metrics/Examples/ProductOrders.php' => 'metrics/ProductOrders',
            '/Metrics/Examples/Sessions.php'      => 'metrics/Sessions',
            '/Metrics/Examples/Tickets.php'       => 'metrics/Tickets',
            '/Metrics/Examples/TotalUsers.php'    => 'metrics/TotalUsers',
        ];

        $namespace = $this->namespace('Metrics\\Examples');

        foreach ($map as $path => $stub) {
            $this->laravel['files']->put(
                $this->directory . $path,
                str_replace(
                    'DummyNamespace',
                    $namespace,
                    $this->getStub($stub)
                )
            );
        }
    }

    /**
     * @param  string $name
     * @return string
     */
    protected function namespace($name = null) {
        $base = str_replace('\\Controllers', '\\', config('admin.route.namespace'));

        return trim($base, '\\') . ($name ? "\\{$name}" : '');
    }

    /**
     * Create routes file.
     *
     * @return void
     */
    protected function createBootstrapFile() {
        $file = $this->directory . '/bootstrap.php';

        $contents = $this->getStub('bootstrap');
        $this->laravel['files']->put($file, $contents);
        $this->line('<info>Bootstrap file was created:</info> ' . str_replace(base_path(), '', $file));
    }

    /**
     * Create routes file.
     *
     * @return void
     */
    protected function createRoutesFile() {
        $file = $this->directory . '/routes.php';

        $contents = $this->getStub('routes');
        $this->laravel['files']->put($file, str_replace('DummyNamespace', $this->namespace('Controllers'), $contents));
        $this->line('<info>Routes file was created:</info> ' . str_replace(base_path(), '', $file));
    }

    /**
     * Get stub contents.
     *
     * @param $name
     * @return string
     */
    protected function getStub($name) {
        return $this->laravel['files']->get(__DIR__ . "/stubs/$name.stub");
    }

    /**
     * Make new directory.
     *
     * @param  string $path
     */
    protected function makeDir($path = '') {
        $this->laravel['files']->makeDirectory("{$this->directory}/$path", 0755, true, true);
    }


    public function createApiModelFile() {

        $model_file = app_path('Models/AdminUser.php');
        $this->laravel['files']->makeDirectory(app_path('Models/Traits'), 0755, true, true);

        $model_contents = $this->getStub('api/AdminUserModel');
        $this->laravel['files']->put($model_file, str_replace('DummyNamespace', $this->namespace('Controllers'), $model_contents));

        $traits_file = app_path('Models/Traits/HasPermissions.php');
        $traits_contents = $this->getStub('api/TraitsHasPermissions');
        $this->laravel['files']->put($traits_file, str_replace('DummyNamespace', $this->namespace('Controllers'), $traits_contents));

        $MemberUserModel_contents = $this->getStub('api/member/MemberUserModel');
        $this->laravel['files']->put(app_path('Models/MemberUser.php'), str_replace('DummyNamespace', $this->namespace('Controllers'), $MemberUserModel_contents));

        $MemberOauthModel_contents = $this->getStub('api/member/MemberOauthModel');
        $this->laravel['files']->put(app_path('Models/MemberOauth.php'), str_replace('DummyNamespace', $this->namespace('Controllers'), $MemberOauthModel_contents));


    }
    // admin api route
    public function createApiRoutesFile() {
        $file = $this->directory . '/Api/routes.php';

        $contents = $this->getStub('api/routes');
        $this->laravel['files']->put($file, str_replace('DummyNamespace', $this->namespace('Controllers'), $contents));
        $this->line('<info>Routes file was created:</info> ' . str_replace(base_path(), '', $file));
    }

    // member api route
    public function createMembmerApiRoutesFile() {
        $file = app_path() . '/Api/routes.php';

        $contents = $this->getStub('api/member/routes');
        $this->laravel['files']->put($file, str_replace('DummyNamespace', $this->namespace('Controllers'), $contents));
        $this->line('<info>Routes file was created:</info> ' . str_replace(base_path(), '', $file));


    }

    // 检查中间件目录是否存在
    public function ensureMiddlewareDirectoryExists()
    {
        $middlewareDir = app_path('Http/Middleware');

        if (!\Illuminate\Support\Facades\File::exists($middlewareDir)) {
            \Illuminate\Support\Facades\File::makeDirectory($middlewareDir, 0755, true);
            return true; // 目录已创建
        }

        return false; // 目录已存在
    }

    //
    public function createApiMiddlewareFile(){
        $file = app_path('/Http/Middleware/AdminApiAuth.php');
        $this->ensureMiddlewareDirectoryExists();
        $contents = $this->getStub('api/AdminApiAuth_Middleware');
        $this->laravel['files']->put($file, str_replace('DummyNamespace', $this->namespace('Controllers'), $contents));
        $this->line('<info>Routes middleware file was created:</info> ' . str_replace(base_path(), '', $file));

    }

    public function createMemberApiMiddlewareFile(){
        $file = app_path('/Http/Middleware/MemberApiAuth.php');
        $this->ensureMiddlewareDirectoryExists();
        $contents = $this->getStub('api/member/MemberApiAuth_Middleware');
        $this->laravel['files']->put($file, str_replace('DummyNamespace', $this->namespace('Controllers'), $contents));
        $this->line('<info>Routes middleware file was created:</info> ' . str_replace(base_path(), '', $file));

    }
    //
    public function createApiControllerFile() {
        $map = [
            '/Api/Controllers/BaseApiController.php'    => 'api/BaseApiController',
            '/Api/Controllers/AuthController.php'       => 'api/AuthController',
            '/Api/Controllers/UserController.php'       => 'api/UserController',
            '/Api/Controllers/MenuController.php'       => 'api/MenuController',
            '/Api/Controllers/PermissionController.php' => 'api/PermissionController',
            '/Api/Controllers/RoleController.php'       => 'api/RoleController',
            '/Api/Controllers/SettingsController.php'   => 'api/SettingsController',
        ];

        // member api
        $member_map = [
            '/Api/Controllers/BaseApiController.php'    => 'api/member/MemberBaseApiController',
            '/Api/Controllers/AuthController.php'       => 'api/member/MemberAuthController',
            '/Api/Controllers/MemberUserController.php'       => 'api/member/MemberUserController',
        ];

        $namespace = $this->namespace('Api\\Controllers');

        foreach ($map as $path => $stub) {
            $this->laravel['files']->put(
                $this->directory . $path,
                str_replace(
                    'DummyNamespace',
                    $namespace,
                    $this->getStub($stub)
                )
            );
        }

        foreach ($member_map as $path => $stub) {
            $this->laravel['files']->put(
                app_path() . $path,
                str_replace(
                    'DummyNamespace',
                    $namespace,
                    $this->getStub($stub)
                )
            );
        }
    }

    // 发布jwt配置文件 生成secret
    public function jwtVendorPublish(){
        // 创建软链接
        \Illuminate\Support\Facades\Artisan::call('storage:link');

        \Illuminate\Support\Facades\Artisan::call('vendor:publish', [
            '--provider' => 'Tymon\JWTAuth\Providers\LaravelServiceProvider',
        ]);
        if (!defined('STDIN')) {
            define('STDIN', fopen('php://stdin', 'r'));
        }
        \Illuminate\Support\Facades\Artisan::call('jwt:secret');

        \Illuminate\Support\Facades\Artisan::call('vendor:publish', [
            '--provider' => 'Dedoc\Scramble\ScrambleServiceProvider',
            '--tag' => 'scramble-config'
        ]);

    }

    protected function updateScrambleConfig(){
        $configPath = config_path('scramble.php');
        $content = file_get_contents($configPath);
        // 更精确的匹配模式
        $newContent = preg_replace(
            "/'api_path' =>\s*'api'(?=,)/",
            "'api_path' => 'member-api'",
            $content
        );
        $newContent = preg_replace(
            "/'description' =>\s*''(?=,)/",
            "'description' => '用户端Api文档'",
            $newContent
        );

        $newContent = preg_replace(
            "/'title'\\s*=>\\s*null/",
            "'title' => '用户端Api文档'",
            $newContent
        );
        file_put_contents($configPath, $newContent);
    }

    // 添加api guards
    protected function addAdminApiGuards()
    {
        $configPath = config_path('auth.php');
        $content = file_get_contents($configPath);

        // 查找 guards 和 providers 的位置
        $guardsPos = strpos($content, "'guards' => [");
        $providersPos = strpos($content, "'providers' => [");
        $passwordsPos = strpos($content, "'passwords' => [");
        if ($guardsPos !== false) {
            $inserts = "\n        'memberapi' => [\n            'driver' => 'jwt',\n            'provider' => 'member_users',\n        ],";
            $inserts .= "\n        'adminapi' => [\n            'driver' => 'jwt',\n            'provider' => 'admin_users',\n        ],";
            $content = $this->insertAfter(
                $content,
                "'guards' => [",
                $inserts,
                $guardsPos
            );
        }

        if ($providersPos !== false) {
            $provider_insert = "\n        'member_users' => [\n            'driver' => 'eloquent',\n            'model' => App\Models\MemberUser::class,\n        ],";
            $provider_insert .= "\n        'admin_users' => [\n            'driver' => 'eloquent',\n            'model' => App\Models\AdminUser::class,\n        ],";

            $content = $this->insertAfter(
                $content,
                "'providers' => [",
                $provider_insert,
                $providersPos
            );
        }

        if ($passwordsPos !== false) {
            $password_insert = "\n        'member_users' => [\n            'provider' => 'member_users',\n            'table' => 'password_resets',\n            'expire' => 60,\n            'throttle' => 60,\n        ],";
            $password_insert .= "\n        'admin_users' => [\n            'provider' => 'admin_users',\n            'table' => 'password_resets',\n            'expire' => 60,\n            'throttle' => 60,\n        ],";
            $content = $this->insertAfter(
                $content,
                "'passwords' => [",
                $password_insert,
                $passwordsPos
            );
        }

        file_put_contents($configPath, $content);
    }

    protected function insertAfter($haystack, $search, $insert, $offset = 0)
    {
        $pos = strpos($haystack, $search, $offset);
        if ($pos === false) {
            return $haystack;
        }

        $pos += strlen($search);
        return substr($haystack, 0, $pos) . $insert . substr($haystack, $pos);
    }

    protected function editLocaleTozh_CN(){
        if (isLaravel11OrNewer()) {
            // Laravel 11+ 的处理逻辑
            // 要更新的环境变量
            $updates = [
                'APP_TIMEZONE' => 'Asia/Shanghai',
                'APP_LOCALE' => 'zh_CN',
                'APP_FALLBACK_LOCALE' => 'zh_CN',
                'APP_FAKER_LOCALE' => 'zh_CN',
            ];
            updateEnv($updates);
        }else{
            // Laravel 10 及更早版本的处理逻辑
            $appPath = config_path('app.php');
            $content = file_get_contents($appPath);
            // edit locale
            $content = str_replace('\'UTC\'','\'Asia/Shanghai\'',$content);
            $content = str_replace('\'en\'','\'zh_CN\'',$content);
            $content = str_replace('\'en_US\'','\'zh_CN\'',$content);

            file_put_contents($appPath, $content);
        }

    }
}
