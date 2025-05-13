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

        // start  admin Api
        $this->makeDir('Api/Controllers');
        $this->addAdminApiGuards();
        $this->createApiModelFile();
        $this->createApiControllerFile();
        $this->createApiRoutesFile();
        $this->createApiMiddlewareFile();
        $this->jwtVendorPublish();
        $this->editLocaleTozh_CN();
        // end Admin api

        $this->createHomeController();
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
        
    }
    //
    public function createApiRoutesFile() {
        $file = $this->directory . '/Api/routes.php';

        $contents = $this->getStub('api/routes');
        $this->laravel['files']->put($file, str_replace('DummyNamespace', $this->namespace('Controllers'), $contents));
        $this->line('<info>Routes file was created:</info> ' . str_replace(base_path(), '', $file));

    }

    //
    public function createApiMiddlewareFile(){
        $file = app_path('/Http/Middleware/AdminApiAuth.php');

        $contents = $this->getStub('api/AdminApiAuth_Middleware');
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
    }
    
    // 发布jwt配置文件 生成secret
    public function jwtVendorPublish(){
        \Illuminate\Support\Facades\Artisan::call('vendor:publish', [
            '--provider' => 'Tymon\JWTAuth\Providers\LaravelServiceProvider'
        ]);
        \Illuminate\Support\Facades\Artisan::call('jwt:secret');
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
            $content = $this->insertAfter(
                $content,
                "'guards' => [",
                "\n        'adminapi' => [\n            'driver' => 'jwt',\n            'provider' => 'admin_users',\n        ],",
                $guardsPos
            );
        }

        if ($providersPos !== false) {
            $content = $this->insertAfter(
                $content,
                "'providers' => [",
                "\n        'admin_users' => [\n            'driver' => 'eloquent',\n            'model' => App\Models\AdminUser::class,\n        ],",
                $providersPos
            );
        }

        if ($passwordsPos !== false) {
            $content = $this->insertAfter(
                $content,
                "'passwords' => [",
                "\n        'admin_users' => [\n            'provider' => 'admin_users',\n            'table' => 'password_resets',\n            'expire' => 60,\n            'throttle' => 60,\n        ],",
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
        $appPath = config_path('app.php');
        $content = file_get_contents($appPath);
        // edit locale
        $content = str_replace('\'UTC\'','\'Asia/Shanghai\'',$content);
        $content = str_replace('\'en\'','\'zh_CN\'',$content);
        $content = str_replace('\'en_US\'','\'zh_CN\'',$content);

        file_put_contents($appPath, $content);
    }
}
