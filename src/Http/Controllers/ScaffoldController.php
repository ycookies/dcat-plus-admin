<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Http\Auth\Permission;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Scaffold\ControllerCreator;
use Dcat\Admin\Scaffold\ApiControllerCreator;
use Dcat\Admin\Scaffold\LangCreator;
use Dcat\Admin\Scaffold\MigrationCreator;
use Dcat\Admin\Scaffold\ModelCreator;
use Dcat\Admin\Scaffold\RepositoryCreator;
use Dcat\Admin\Support\Helper;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Dcat\Admin\Layout\Menu;

class ScaffoldController extends Controller {
    public static $dbTypes = [
        'string', 'integer', 'text', 'float', 'double', 'decimal', 'boolean', 'date', 'time',
        'dateTime', 'timestamp', 'char', 'mediumText', 'longText', 'tinyInteger', 'smallInteger',
        'mediumInteger', 'bigInteger', 'unsignedTinyInteger', 'unsignedSmallInteger', 'unsignedMediumInteger',
        'unsignedInteger', 'unsignedBigInteger', 'enum', 'json', 'jsonb', 'dateTimeTz', 'timeTz',
        'timestampTz', 'nullableTimestamps', 'binary', 'ipAddress', 'macAddress',
    ];

    public static $dataTypeMap = [
        'int'                => 'integer',
        'int@unsigned'       => 'unsignedInteger',
        'tinyint'            => 'tinyInteger',
        'tinyint@unsigned'   => 'unsignedTinyInteger',
        'smallint'           => 'smallInteger',
        'smallint@unsigned'  => 'unsignedSmallInteger',
        'mediumint'          => 'mediumInteger',
        'mediumint@unsigned' => 'unsignedMediumInteger',
        'bigint'             => 'bigInteger',
        'bigint@unsigned'    => 'unsignedBigInteger',

        'date'      => 'date',
        'time'      => 'time',
        'datetime'  => 'dateTime',
        'timestamp' => 'timestamp',

        'enum'   => 'enum',
        'json'   => 'json',
        'binary' => 'binary',

        'float'   => 'float',
        'double'  => 'double',
        'decimal' => 'decimal',

        'varchar'    => 'string',
        'char'       => 'char',
        'text'       => 'text',
        'mediumtext' => 'mediumText',
        'longtext'   => 'longText',
    ];

    public function index(Content $content) {
        if (!config('app.debug')) {
            Permission::error();
        }

        if ($tableName = request('singular')) {
            return $this->singular($tableName);
        }

        Admin::requireAssets('select2');
        Admin::requireAssets('sortable');

        $dbTypes       = static::$dbTypes;
        $dataTypeMap   = static::$dataTypeMap;
        $action        = URL::current();
        $namespaceBase = 'App\\' . implode('\\', array_map(function ($name) {
                return Str::studly($name);
            }, explode(DIRECTORY_SEPARATOR, substr(config('admin.directory'), strlen(app_path() . DIRECTORY_SEPARATOR)))));
        $tables        = collect($this->getDatabaseColumns())->map(function ($v) {
            return array_keys($v);
        })->toArray();
        
        $menu_parent_selectOptions = \Dcat\Admin\Models\Menu::selectOptions();
        return $content
            ->title(trans('admin.scaffold.header'))
            ->description(' ')
            ->body(view(
                'admin::helpers.scaffold',
                compact('dbTypes', 'action', 'tables', 'dataTypeMap', 'namespaceBase','menu_parent_selectOptions')
            ));
    }


    
    protected function singular($tableName) {
        return [
            'status' => 1,
            'value'  => Str::singular($tableName),
        ];
    }

    public function store(Request $request) {
        if (!config('app.debug')) {
            Permission::error();
        }
        $paths   = [];
        $message = '';

        $creates    = (array)$request->get('create');
        $table      = Helper::slug($request->get('table_name'), '_');
        $controller = $request->get('controller_name');
        $model      = $request->get('model_name');
        $repository = $request->get('repository_name');
        $route_path = $request->get('route_path');
        $is_add_admin_api = $request->has('is_add_admin_api');
        $is_add_member_api = $request->has('is_add_member_api');
        
        try {
            // 1. Create model.
            if (in_array('model', $creates)) {
                $modelCreator = new ModelCreator($table, $model);

                $paths['model'] = $modelCreator->create(
                    $request->get('primary_key'),
                    $request->get('timestamps') == 1,
                    $request->get('soft_deletes') == 1
                );
            }

            // 2. Create controller.
            if (in_array('controller', $creates)) {
                $paths['controller'] = (new ControllerCreator($controller))
                    ->create(in_array('repository', $creates) ? $repository : $model);
            }

            // 3. Create migration.
            if (in_array('migration', $creates)) {
                $migrationName = 'create_' . $table . '_table';

                $paths['migration'] = (new MigrationCreator(app('files')))->buildBluePrint(
                    $request->get('fields'),
                    $request->get('primary_key', 'id'),
                    $request->get('timestamps') == 1,
                    $request->get('soft_deletes') == 1
                )->create($migrationName, database_path('migrations'), $table);
            }

            if (in_array('lang', $creates)) {
                $paths['lang'] = (new LangCreator($request->get('fields')))
                    ->create($controller, $request->get('translate_title'));
            }

            if (in_array('repository', $creates)) {
                $paths['repository'] = (new RepositoryCreator())
                    ->create($model, $repository);
            }

            // Run migrate.
            if (in_array('migrate', $creates)) {
                Artisan::call('migrate');
                $message = Artisan::output();
            }

            // Make ide helper file.
            if (in_array('migrate', $creates) || in_array('controller', $creates)) {
                try {
                    Artisan::call('admin:ide-helper', ['-c' => $controller]);

                    $paths['ide-helper'] = 'dcat_admin_ide_helper.php';
                } catch (\Throwable $e) {
                }
            }

            // 生成资源路由
            if (!empty($route_path)) {
                $controller_con  = explode('\\', $controller);
                $controller_name = array_slice($controller_con, -1)[0];
                $newRoutes       = "\$router->resource('/" . $route_path . "'," . $controller_name . "::class)";
                $this->addResourceRouteToAdminRoutes($newRoutes);
            }
            // 添加 api
            if($is_add_admin_api){
                $this->ApiControllerCreator($controller,$model);
            }

            // 添加 member api
            if($is_add_member_api){
                $this->MemberApiControllerCreator($controller,$model);
            }

            // 添加权限
            $this->appendPermissions();
            // 添加菜单
            $this->appendMenu();

        } catch (\Exception $exception) {
            // Delete generated files if exception thrown.
            app('files')->delete($paths);

            return $this->backWithException($exception);
        }

        return $this->backWithSuccess($paths, $message);
    }

    // 添加菜单
    public function appendMenu() {
        $request = request();
        $route_path = $request->get('route_path');
        $menu_name   = $request->get('menu_name');
        $is_add_menu = $request->has('is_add_menu');

        if ($is_add_menu && !empty($menu_name) && !empty($route_path)) {
            $menu_icon   = $request->get('menu_icon');
            $parent_menu_id = $request->get('parent_menu',0);

            $parent_menu_id = !empty($parent_menu_id) ?  $parent_menu_id:0;
            $menu_icon = !empty($menu_icon) ? $menu_icon:'fa-file-text-o';

            $menuModel = config('admin.database.menu_model');
            $lastOrder = $menuModel::max('order');
            $menu_data = [
              'parent_id' => $parent_menu_id,
              'order'     => $lastOrder + 1,
              'title' =>   $menu_name,
              'icon' =>   $menu_icon,
              'uri' =>   $route_path,
            ];
            $menu = $menuModel::create($menu_data);

            $roleModel = config('admin.database.roles_model');
            // 获取角色ID为1的角色
            $role = $roleModel::find(1);
            if ($role) {
                // 将菜单附加到角色（通过中间表关联）
                $role->menus()->attach($menu->id);
            }
        }
    }

    // 添加权限
    public function appendPermissions() {
        $request = request();
        $menu_name   = $request->get('menu_name');
        $route_path = $request->get('route_path');
        $permissionsModel = config('admin.database.permissions_model');

        $count = $permissionsModel::where([
            'slug' =>   str_replace('_','-',$route_path),
            'http_path' =>   '/'.$route_path.'/*',])->count();
        if($count > 0){
            return false;
        }
        $permissions_data = [
            'parent_id' => 0,
            'name' =>   $menu_name,
            'slug' =>   str_replace('_','-',$route_path),
            'http_path' =>   '/'.$route_path.'/*',
        ];
        $permissionsinfo =  $permissionsModel::create($permissions_data);

        $roleModel = config('admin.database.roles_model');
        // 获取角色ID为1的角色
        $role = $roleModel::find(1);

        if ($role) {
            // 将权限附加到角色（通过中间表关联）
            $role->permissions()->attach($permissionsinfo->id);
        }
    }

    public function addResourceRouteToAdminRoutes($newRoute) {

        $routesPath = app_path('Admin/routes.php');

        // 验证文件存在且可写
        if (!file_exists($routesPath) || !is_writable($routesPath)) {
            throw new \Exception('路由文件不存在或不可写');
        }

        // 创建备份（放在storage/backups目录）
        $backupDir = storage_path('backups/routes');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $backupPath = $backupDir . '/admin_routes_' . date('Ymd_His') . '.php';
        copy($routesPath, $backupPath);

        // 读取内容
        $content = file_get_contents($routesPath);

        // 标准化换行符
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // 检查路由是否已存在
        $normalizedRoute = trim($newRoute, '; ') . ';';
        if (strpos($content, $normalizedRoute) !== false) {
            throw new \Exception('路由已存在: ' . $normalizedRoute);
        }

        // 查找插入位置（匹配路由组结束位置）
        $pattern = '/(\n\s*\}\);\s*$)/';
        if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            throw new \Exception('无法定位路由组结束位置');
        }

        $endTag      = $matches[1][0];  // 匹配到的结束标记
        $endPosition = $matches[1][1];  // 结束标记的位置

        // 准备要插入的内容（带正确缩进和换行）
        $routeToInsert = "\n    " . trim($newRoute, '; ') . ";" .
            (str_ends_with($endTag, "\n") ? '' : "\n");

        // 构建新内容
        $newContent = substr_replace(
            $content,
            $routeToInsert,
            $endPosition,
            0
        );

        // 验证PHP语法
        try {
            eval('?>' . $newContent);
        } catch (ParseError $e) {
            copy($backupPath, $routesPath);
            throw new \Exception('生成的路由文件有语法错误: ' . $e->getMessage());
        }

        // 写入文件（使用LOCK_EX防止并发写入）
        if (file_put_contents($routesPath, $newContent, LOCK_EX) === false) {
            throw new \Exception('写入路由文件失败');
        }

        return true;
    }

    public function addApiResourceRouteToAdminRoutes($newRoute) {

        $routesPath = app_path('Admin/Api/routes.php');

        // 验证文件存在且可写
        if (!file_exists($routesPath) || !is_writable($routesPath)) {
            throw new \Exception('路由文件不存在或不可写');
        }

        // 创建备份（放在storage/backups目录）
        $backupDir = storage_path('backups/api_routes');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $backupPath = $backupDir . '/admin_api_routes_' . date('Ymd_His') . '.php';
        copy($routesPath, $backupPath);

        // 读取内容
        $content = file_get_contents($routesPath);

        // 标准化换行符
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // 检查路由是否已存在
        $normalizedRoute = trim($newRoute, '; ') . ';';
        if (strpos($content, $normalizedRoute) !== false) {
            throw new \Exception('路由已存在: ' . $normalizedRoute);
        }

        // 查找插入位置（匹配路由组结束位置）
        $pattern = '/(\n\s*\}\);\s*$)/';
        if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            throw new \Exception('无法定位路由组结束位置');
        }

        $endTag      = $matches[1][0];  // 匹配到的结束标记
        $endPosition = $matches[1][1];  // 结束标记的位置

        // 准备要插入的内容（带正确缩进和换行）
        $routeToInsert = "\n    " . trim($newRoute, '; ') . ";" .
            (str_ends_with($endTag, "\n") ? '' : "\n");

        // 构建新内容
        $newContent = substr_replace(
            $content,
            $routeToInsert,
            $endPosition,
            0
        );

        // 验证PHP语法
        try {
            eval('?>' . $newContent);
        } catch (ParseError $e) {
            copy($backupPath, $routesPath);
            throw new \Exception('生成的路由文件有语法错误: ' . $e->getMessage());
        }

        // 写入文件（使用LOCK_EX防止并发写入）
        if (file_put_contents($routesPath, $newContent, LOCK_EX) === false) {
            throw new \Exception('写入路由文件失败');
        }

        return true;
    }

    public function addMemberApiResourceRouteToAdminRoutes($newRoute) {

        $routesPath = app_path('Api/routes.php');

        // 验证文件存在且可写
        if (!file_exists($routesPath) || !is_writable($routesPath)) {
            throw new \Exception('路由文件不存在或不可写');
        }

        // 创建备份（放在storage/backups目录）
        $backupDir = storage_path('backups/member_api_routes');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $backupPath = $backupDir . '/member_api_routes_' . date('Ymd_His') . '.php';
        copy($routesPath, $backupPath);

        // 读取内容
        $content = file_get_contents($routesPath);

        // 标准化换行符
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        // 检查路由是否已存在
        $normalizedRoute = trim($newRoute, '; ') . ';';
        if (strpos($content, $normalizedRoute) !== false) {
            throw new \Exception('路由已存在: ' . $normalizedRoute);
        }

        // 查找插入位置（匹配路由组结束位置）
        $pattern = '/(\n\s*\}\);\s*$)/';
        if (!preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            throw new \Exception('无法定位路由组结束位置');
        }

        $endTag      = $matches[1][0];  // 匹配到的结束标记
        $endPosition = $matches[1][1];  // 结束标记的位置

        // 准备要插入的内容（带正确缩进和换行）
        $routeToInsert = "\n    " . trim($newRoute, '; ') . ";" .
            (str_ends_with($endTag, "\n") ? '' : "\n");

        // 构建新内容
        $newContent = substr_replace(
            $content,
            $routeToInsert,
            $endPosition,
            0
        );

        // 验证PHP语法
        try {
            eval('?>' . $newContent);
        } catch (ParseError $e) {
            copy($backupPath, $routesPath);
            throw new \Exception('生成的member路由文件有语法错误: ' . $e->getMessage());
        }

        // 写入文件（使用LOCK_EX防止并发写入）
        if (file_put_contents($routesPath, $newContent, LOCK_EX) === false) {
            throw new \Exception('写入member路由文件失败');
        }

        return true;
    }

    // 添加api Controller
    public function ApiControllerCreator($controller,$model) {
        $request = request();
        $api_route_path   = $request->get('api_route_path');
        if(empty($api_route_path)){
            return false;
        }
        $controller = str_replace('Admin\C','Admin\Api\C',$controller);
        $controller_con  = explode('\\', $controller);
        $controller_name = array_slice($controller_con, -1)[0];

        // Create Controller
        $res = (new ApiControllerCreator($controller))->create($model);

        // append api route
        $newRoutes       = "\$router->apiResource('/" . $api_route_path . "'," . $controller_name . "::class)";
        $this->addApiResourceRouteToAdminRoutes($newRoutes);
        return true;
    }

    // 添加 member api Controller
    public function MemberApiControllerCreator($controller,$model) {
        $request = request();
        $member_api_route_path   = $request->get('member_api_route_path');
        if(empty($member_api_route_path)){
            return false;
        }
        $controller = str_replace('Admin\C','Api\C',$controller);
        $controller_con  = explode('\\', $controller);
        $controller_name = array_slice($controller_con, -1)[0];

        // Create Controller
        $res = (new ApiControllerCreator($controller))->setStub(__DIR__.'/stubs/MemberApiController.stub')->create($model);

        // append api route
        $newRoutes       = "\$router->apiResource('/" . $member_api_route_path . "'," . $controller_name . "::class)";
        $this->addMemberApiResourceRouteToAdminRoutes($newRoutes);
        return true;
    }


    /**
     * @return array
     */
    public function table() {
        $db    = addslashes(\request('db'));
        $table = \request('tb');
        if (!$table || !$db) {
            return ['status' => 1, 'list' => []];
        }

        $tables = collect($this->getDatabaseColumns($db, $table))
            ->filter(function ($v, $k) use ($db) {
                return $k == $db;
            })->map(function ($v) use ($table) {
                return Arr::get($v, $table);
            })
            ->filter()
            ->first();

        return ['status' => 1, 'list' => $tables];
    }

    /**
     * @return array
     */
    protected function getDatabaseColumns($db = null, $tb = null) {
        $databases = Arr::where(config('database.connections', []), function ($value) {
            $supports = ['mysql'];

            return in_array(strtolower(Arr::get($value, 'driver')), $supports);
        });

        $data = [];

        try {
            foreach ($databases as $connectName => $value) {
                if ($db && $db != $value['database']) {
                    continue;
                }

                $sql = sprintf('SELECT * FROM information_schema.columns WHERE table_schema = "%s"', $value['database']);

                if ($tb) {
                    $p = Arr::get($value, 'prefix');

                    $sql .= " AND TABLE_NAME = '{$p}{$tb}'";
                }

                $sql .= ' ORDER BY `ORDINAL_POSITION` ASC';

                $tmp = DB::connection($connectName)->select($sql);

                $collection = collect($tmp)->map(function ($v) use ($value) {
                    if (!$p = Arr::get($value, 'prefix')) {
                        return (array)$v;
                    }
                    $v = (array)$v;

                    $v['TABLE_NAME'] = Str::replaceFirst($p, '', $v['TABLE_NAME']);

                    return $v;
                });

                $data[$value['database']] = $collection->groupBy('TABLE_NAME')->map(function ($v) {
                    return collect($v)->keyBy('COLUMN_NAME')->map(function ($v) {
                        $v['COLUMN_TYPE'] = strtolower($v['COLUMN_TYPE']);
                        $v['DATA_TYPE']   = strtolower($v['DATA_TYPE']);

                        if (Str::contains($v['COLUMN_TYPE'], 'unsigned')) {
                            $v['DATA_TYPE'] .= '@unsigned';
                        }

                        return [
                            'type'     => $v['DATA_TYPE'],
                            'default'  => $v['COLUMN_DEFAULT'],
                            'nullable' => $v['IS_NULLABLE'],
                            'key'      => $v['COLUMN_KEY'],
                            'id'       => $v['COLUMN_KEY'] === 'PRI',
                            'comment'  => $v['COLUMN_COMMENT'],
                        ];
                    })->toArray();
                })->toArray();
            }
        } catch (\Throwable $e) {
        }

        return $data;
    }

    protected function backWithException(\Exception $exception) {
        $error = new MessageBag([
            'title'   => 'Error',
            'message' => $exception->getMessage(),
        ]);

        return redirect()->refresh()->withInput()->with(compact('error'));
    }

    protected function backWithSuccess($paths, $message) {
        $messages = [];

        foreach ($paths as $name => $path) {
            $messages[] = ucfirst($name) . ": $path";
        }

        $messages[] = "<br />$message";

        $success = new MessageBag([
            'title'   => 'Success',
            'message' => implode('<br />', $messages),
        ]);

        return redirect()->refresh()->with(compact('success'));
    }
}
