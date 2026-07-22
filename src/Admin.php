<?php

namespace Dcat\Admin;

use Closure;
use Dcat\Admin\Contracts\ExceptionHandler;
use Dcat\Admin\Contracts\Repository;
use Dcat\Admin\Exception\InvalidArgumentException;
use Dcat\Admin\Http\Controllers\AuthController;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Layout\Menu;
use Dcat\Admin\Layout\Navbar;
use Dcat\Admin\Layout\SectionManager;
use Dcat\Admin\Repositories\EloquentRepository;
use Dcat\Admin\Support\Composer;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Support\UeditorConfig;
use Dcat\Admin\Support\ValidateCode;
use Dcat\Admin\Traits\HasAssets;
use Dcat\Admin\Traits\HasHtml;
use Dcat\Admin\Traits\HasPermissions;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    use HasAssets;
    use HasHtml;
    const VERSION='1.2.2';
    const SECTION = [
        // 往 <head> 标签内输入内容
        'HEAD' => 'ADMIN_HEAD',

        // 往body标签内部输入内容
        'BODY_INNER_BEFORE' => 'ADMIN_BODY_INNER_BEFORE',
        'BODY_INNER_AFTER' => 'ADMIN_BODY_INNER_AFTER',

        // 往#app内部输入内容
        'APP_INNER_BEFORE' => 'ADMIN_APP_INNER_BEFORE',
        'APP_INNER_AFTER' => 'ADMIN_APP_INNER_AFTER',

        // 顶部导航栏用户面板
        'NAVBAR_USER_PANEL' => 'ADMIN_NAVBAR_USER_PANEL',
        'NAVBAR_AFTER_USER_PANEL' => 'ADMIN_NAVBAR_AFTER_USER_PANEL',
        // 顶部导航栏之前
        'NAVBAR_BEFORE' => 'ADMIN_NAVBAR_BEFORE',
        // 顶部导航栏底下
        'NAVBAR_AFTER' => 'ADMIN_NAVBAR_AFTER',

        // 侧边栏顶部用户信息面板
        'LEFT_SIDEBAR_USER_PANEL' => 'ADMIN_LEFT_SIDEBAR_USER_PANEL',
        // 菜单栏
        'LEFT_SIDEBAR_MENU' => 'ADMIN_LEFT_SIDEBAR_MENU',
        // 菜单栏顶部
        'LEFT_SIDEBAR_MENU_TOP' => 'ADMIN_LEFT_SIDEBAR_MENU_TOP',
        // 菜单栏底部
        'LEFT_SIDEBAR_MENU_BOTTOM' => 'ADMIN_LEFT_SIDEBAR_MENU_BOTTOM',
    ];

    private static $defaultPjaxContainerId = 'pjax-container';

    /**
     * 版本.
     *
     * @return string
     */
    public static function longVersion()
    {
        return sprintf('Dcat Admin <comment>version</comment> <info>%s</info>', \Dcat\Admin\Support\Helper::getPackageVersion('dcat-plus/laravel-admin'));
    }

    /**
     * @return Color
     */
    public static function color()
    {
        return app('admin.color');
    }

    /**
     * 菜单管理.
     *
     * @param  Closure|null  $builder
     * @return Menu
     */
    public static function menu(?Closure $builder = null)
    {
        $menu = app('admin.menu');

        $builder && $builder($menu);

        return $menu;
    }

    /**
     * 获取指定面板的资源路由与单路由能力目录。
     *
     * 该方法只读取当前已注册的路由信息，不会创建或修改权限记录。
     * 可用于 SaaS 套餐、功能开关或外部授权中心配置。
     *
     * @param  string  $panel
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public static function permissionPackage(string $panel = Application::DEFAULT, array $options = []): array
    {
        return app(\Dcat\Admin\Support\Authorization\PermissionPackage::class)->get($panel, $options);
    }

    /**
     * 获取指定面板的菜单树目录。
     *
     * 返回数据保持既有 admin_menu 结构，不会新增字段或修改菜单记录。
     *
     * @param  string  $panel
     * @return array<string, mixed>
     */
    public static function menuPackage(string $panel = Application::DEFAULT): array
    {
        return app(\Dcat\Admin\Support\Authorization\MenuPackage::class)->get($panel);
    }

    /**
     * 创建一个图形验证码实例。
     *
     * 默认参数来自 admin.validate_code，传入的参数只覆盖当前实例。
     *
     * @param  array<string, mixed>  $options
     */
    public static function validateCode(array $options = []): ValidateCode
    {
        return app(ValidateCode::class)->withOptions($options);
    }

    /**
     * 设置 title.
     *
     * @return string|void
     */
    public static function title($title = null)
    {
        if ($title === null) {
            return static::context()->metaTitle ?: config('admin.title');
        }

        static::context()->metaTitle = $title;
    }

    /**
     * @param  null|string  $favicon
     * @return string|void
     */
    public static function favicon($favicon = null)
    {
        if ($favicon === null) {
            return static::context()->favicon ?: config('admin.favicon');
        }

        static::context()->favicon = $favicon;
    }

    /**
     * 设置翻译文件路径.
     *
     * @param  string|null  $path
     */
    public static function translation(?string $path)
    {
        static::context()->translation = $path;
    }

    /**
     * 获取登录用户模型.
     *
     * @return Model|Authenticatable|HasPermissions
     */
    public static function user()
    {
        return static::guard()->user();
    }

    /**
     * @return \Illuminate\Contracts\Auth\Guard|\Illuminate\Contracts\Auth\StatefulGuard|GuardHelpers
     */
    public static function guard()
    {
        return Auth::guard(config('admin.auth.guard') ?: 'admin');
    }

    /**
     * @param  Closure|null  $builder
     * @return Navbar
     */
    public static function navbar(?Closure $builder = null)
    {
        $navbar = app('admin.navbar');

        $builder && $builder($navbar);

        return $navbar;
    }

    /**
     * 启用或禁用Pjax.
     *
     * @param  bool  $value
     * @return void
     */
    public static function pjax(bool $value = true)
    {
        static::context()->pjaxContainerId = $value ? static::$defaultPjaxContainerId : false;
    }

    /**
     * 禁用pjax.
     *
     * @return void
     */
    public static function disablePjax()
    {
        static::pjax(false);
    }

    /**
     * 获取pjax ID.
     *
     * @return string|void
     */
    public static function getPjaxContainerId()
    {
        $id = static::context()->pjaxContainerId;

        if ($id === false) {
            return;
        }

        return $id ?: static::$defaultPjaxContainerId;
    }

    /**
     * section.
     *
     * @param  Closure|null  $builder
     * @return SectionManager
     */
    public static function section(?Closure $builder = null)
    {
        $manager = app('admin.sections');

        $builder && $builder($manager);

        return $manager;
    }

    /**
     * 配置.
     *
     * @return \Dcat\Admin\Support\Setting
     */
    public static function setting()
    {
        return app('admin.setting');
    }

    /**
     * 创建数据仓库实例.
     *
     * @param  string|Repository|Model|Builder  $value
     * @param  array  $args
     * @return Repository
     */
    public static function repository($repository, array $args = [])
    {
        if (is_string($repository)) {
            $repository = new $repository($args);
        }

        if ($repository instanceof Model || $repository instanceof Builder) {
            $repository = EloquentRepository::make($repository);
        }

        if (! $repository instanceof Repository) {
            $class = is_object($repository) ? get_class($repository) : $repository;

            throw new InvalidArgumentException("The class [{$class}] must be a type of [".Repository::class.'].');
        }

        return $repository;
    }

    /**
     * 应用管理.
     *
     * @return Application
     */
    public static function app()
    {
        return app('admin.app');
    }

    /**
     * 处理异常.
     *
     * @param  \Throwable  $e
     * @return mixed
     */
    public static function handleException(\Throwable $e)
    {
        return app(ExceptionHandler::class)->handle($e);
    }

    /**
     * 上报异常.
     *
     * @param  \Throwable  $e
     * @return mixed
     */
    public static function reportException(\Throwable $e)
    {
        return app(ExceptionHandler::class)->report($e);
    }

    /**
     * 显示异常信息.
     *
     * @param  \Throwable  $e
     * @return mixed
     */
    public static function renderException(\Throwable $e)
    {
        return app(ExceptionHandler::class)->render($e);
    }

    /**
     * @param  callable  $callback
     */
    public static function booting($callback)
    {
        Event::listen('admin:booting', $callback);
    }

    /**
     * @param  callable  $callback
     */
    public static function booted($callback)
    {
        Event::listen('admin:booted', $callback);
    }

    /**
     * @return void
     */
    public static function callBooting()
    {
        Event::dispatch('admin:booting');
    }

    /**
     * @return void
     */
    public static function callBooted()
    {
        Event::dispatch('admin:booted');
    }

    /**
     * 上下文管理.
     *
     * @return \Dcat\Admin\Support\Context
     */
    public static function context()
    {
        return app('admin.context');
    }

    /**
     * 翻译器.
     *
     * @return \Dcat\Admin\Support\Translator
     */
    public static function translator()
    {
        return app('admin.translator');
    }

    /**
     * @param  array|string  $name
     * @return void
     */
    public static function addIgnoreQueryName($name)
    {
        $context = static::context();

        $ignoreQueries = $context->ignoreQueries ?? [];

        $context->ignoreQueries = array_merge($ignoreQueries, (array) $name);
    }

    /**
     * @return array
     */
    public static function getIgnoreQueryNames()
    {
        return static::context()->ignoreQueries ?? [];
    }

    /**
     * 中断默认的渲染逻辑.
     *
     * @param  string|\Illuminate\Contracts\Support\Renderable|\Closure  $value
     */
    public static function prevent($value)
    {
        if ($value !== null) {
            static::context()->add('contents', $value);
        }
    }

    /**
     * @return bool
     */
    public static function shouldPrevent()
    {
        return count(static::context()->getArray('contents')) > 0;
    }

    /**
     * 渲染内容.
     *
     * @return string|void
     */
    public static function renderContents()
    {
        if (! static::shouldPrevent()) {
            return;
        }

        $results = '';

        foreach (static::context()->getArray('contents') as $content) {
            $results .= Helper::render($content);
        }

        // 等待JS脚本加载完成
        static::script('Dcat.wait()', true);

        $asset = static::asset();

        static::baseCss([], false);
        static::baseJs([], false);
        static::headerJs([], false);
        static::fonts([]);

        return $results
            .static::html()
            .$asset->jsToHtml()
            .$asset->cssToHtml()
            .$asset->scriptToHtml()
            .$asset->styleToHtml();
    }

    /**
     * 响应json数据.
     *
     * @param  array  $data
     * @return JsonResponse
     */
    public static function json(array $data = [])
    {
        return JsonResponse::make($data);
    }

    /**
     * 插件管理.
     *
     * @param  string  $name
     * @return \Dcat\Admin\Extend\Manager|\Dcat\Admin\Extend\ServiceProvider|null
     */
    public static function extension(?string $name = null)
    {
        if ($name) {
            return app('admin.extend')->get($name);
        }

        return app('admin.extend');
    }

    /**
     * 响应并中断后续逻辑.
     *
     * @param  Response|string|array  $response
     *
     * @throws HttpResponseException
     */
    public static function exit($response = '')
    {
        if (is_array($response)) {
            $response = response()->json($response);
        } elseif ($response instanceof JsonResponse) {
            $response = $response->send();
        }

        throw new HttpResponseException($response instanceof Response ? $response : response($response));
    }

    /**
     * 类自动加载器.
     *
     * @return \Composer\Autoload\ClassLoader
     */
    public static function classLoader()
    {
        return Composer::loader();
    }

    /**
     * 往分组插入中间件.
     *
     * @param  array  $mix
     */
    public static function mixMiddlewareGroup(array $mix = [])
    {
        $router = app('router');

        $group = $router->getMiddlewareGroups()['admin'] ?? [];

        if ($mix) {
            $finalGroup = [];

            foreach ($group as $i => $mid) {
                $next = $i + 1;

                $finalGroup[] = $mid;

                if (! isset($group[$next]) || $group[$next] !== 'admin.permission') {
                    continue;
                }

                $finalGroup = array_merge($finalGroup, $mix);

                $mix = [];
            }

            if ($mix) {
                $finalGroup = array_merge($finalGroup, $mix);
            }

            $group = $finalGroup;
        }

        $router->middlewareGroup('admin', $group);
    }

    /**
     * 获取js配置.
     *
     * @param  array|null  $variables
     * @return string
     */
    public static function jsVariables(?array $variables = null)
    {
        $jsVariables = static::context()->jsVariables ?: [];

        if ($variables !== null) {
            static::context()->jsVariables = array_merge(
                $jsVariables,
                $variables
            );

            return;
        }

        $sidebarStyle = config('admin.layout.sidebar_style') ?: 'light';

        $pjaxId = static::getPjaxContainerId();

        $jsVariables['pjax_container_selector'] = $pjaxId ? ('#'.$pjaxId) : '';
        $jsVariables['token'] = csrf_token();
        $lang = __('admin.client'); // 获取翻译结果
        $js_lang = !empty($jsVariables['lang']) && is_array($jsVariables['lang']) ? $jsVariables['lang']:[];
        $jsVariables['lang'] = is_array($lang) ? array_merge($lang, $js_lang) : [$lang]; // 确保 $lang 是数组
        //$jsVariables['lang'] = ($lang = __('admin.client')) ? array_merge($lang, $jsVariables['lang'] ?? []) : [];
        $jsVariables['colors'] = static::color()->all();
        $jsVariables['dark_mode'] = static::isDarkMode();
        $jsVariables['sidebar_dark'] = config('admin.layout.sidebar_dark') || ($sidebarStyle === 'dark');
        $jsVariables['sidebar_light_style'] = in_array($sidebarStyle, ['dark', 'light'], true) ? 'sidebar-light-primary' : 'sidebar-primary';

        return admin_javascript_json($jsVariables);
    }

    /**
     * @return bool
     */
    public static function isDarkMode()
    {
        $bodyClass = config('admin.layout.body_class');

        return in_array(
            'dark-mode',
            is_array($bodyClass) ? $bodyClass : explode(' ', $bodyClass),
            true
        );
    }

    /**
     * 注册路由.
     *
     * @return void
     */
    public static function routes()
    {
        $attributes = [
            'prefix'     => config('admin.route.prefix'),
            'middleware' => config('admin.route.middleware'),
        ];

        if (config('admin.auth.enable', true)) {
            app('router')->group($attributes, function ($router) {
                /* @var \Illuminate\Routing\Router $router */
                $router->namespace('Dcat\Admin\Http\Controllers')->group(function ($router) {
                    /* @var \Illuminate\Routing\Router $router */
                    $router->resource('auth/users', 'UserController');
                    $router->resource('auth/menu', 'MenuController', ['except' => ['create', 'show']]);

                    if (config('admin.permission.enable')) {
                        $router->resource('auth/roles', 'RoleController');
                        $router->resource('auth/permissions', 'PermissionController');
                    }
                });

                $router->resource('auth/extensions', 'Dcat\Admin\Http\Controllers\ExtensionController');

                $authController = config('admin.auth.controller', AuthController::class);

                $router->get('auth/login', $authController.'@getLogin')
                    ->permissionLabel('后台登录页', '显示后台账号登录页面', '身份认证');
                $router->post('auth/login', $authController.'@postLogin')
                    ->permissionLabel('后台登录', '验证后台账号并建立登录会话', '身份认证');
                $router->get('auth/captcha', $authController.'@getCaptcha')
                    ->middleware('throttle:30,1')
                    ->defaults('dcat_route_type', 'internal_legacy');
                $router->get('auth/logout', $authController.'@getLogout')
                    ->permissionLabel('退出后台', '结束当前后台登录会话', '身份认证');
                $router->get('auth/setting', $authController.'@getSetting')
                    ->permissionLabel('个人设置', '查看当前管理员的个人资料和安全设置', '个人中心');
                $router->put('auth/setting', $authController.'@putSetting')
                    ->permissionLabel('更新个人设置', '保存当前管理员的个人资料和密码', '个人中心');

                # 布局配置（导航栏可视化配置）
                $router->post('layout-config/save', 'Dcat\Admin\Http\Controllers\LayoutConfigController@save')
                    ->permissionLabel('保存布局配置', '保存导航栏和后台界面布局配置', '系统设置')
                    ->defaults('dcat_route_type', 'internal_legacy');
                $router->post('clear-cache', 'Dcat\Admin\Http\Controllers\LayoutConfigController@clear')
                    ->permissionLabel('清理系统缓存', '清理后台框架运行缓存', '系统维护')
                    ->defaults('dcat_route_type', 'internal_legacy');

                # 通知管理
                $router->resource('notifications', \Dcat\Admin\Http\Controllers\NotificationController::class);
                $router->get('api/notifications', 'Dcat\Admin\Http\Controllers\NotificationApiController@index')
                    ->permissionLabel('通知列表数据', '获取当前管理员的通知列表', '消息通知')
                    ->middleware('admin.internal:authenticated')->defaults('dcat_route_type', 'internal');
                $router->post('api/notifications/{id}/read', 'Dcat\Admin\Http\Controllers\NotificationApiController@read')
                    ->permissionLabel('标记通知已读', '将指定通知标记为已读', '消息通知')
                    ->middleware('admin.internal:authenticated')->defaults('dcat_route_type', 'internal');
                $router->get('api/notifications/first-unread', 'Dcat\Admin\Http\Controllers\NotificationApiController@firstUnread')
                    ->permissionLabel('获取未读通知', '获取当前管理员第一条未读通知', '消息通知')
                    ->middleware('admin.internal:authenticated')->defaults('dcat_route_type', 'internal');
                $router->post('api/notifications/read-all', 'Dcat\Admin\Http\Controllers\NotificationApiController@readAll')
                    ->permissionLabel('全部通知已读', '将当前管理员的全部通知标记为已读', '消息通知')
                    ->middleware('admin.internal:authenticated')->defaults('dcat_route_type', 'internal');

                # 帮助管理
                $router->resource('help-categories', \Dcat\Admin\Http\Controllers\HelpCategoryController::class);
                $router->resource('helps', \Dcat\Admin\Http\Controllers\HelpController::class);

                # 操作日志
                $router->get('auth/operation-logs', \Dcat\Admin\Http\Controllers\OperationLogController::class.'@index')
                    ->name('dcat-admin.operation-log.index')
                    ->permissionLabel('查看操作日志', '查询管理员在后台执行的操作记录', '操作审计');
                $router->delete('auth/operation-logs/{id}', \Dcat\Admin\Http\Controllers\OperationLogController::class.'@destroy')
                    ->name('dcat-admin.operation-log.destroy')
                    ->permissionLabel('删除操作日志', '删除指定的后台操作记录', '操作审计');
                # 系统日志查看
                $router->get('auth/system-log-viewer', \Dcat\Admin\Http\Controllers\SystemLogViewerController::class.'@index')
                    ->name('log-viewer')
                    ->permissionLabel('查看系统日志', '浏览和检索应用运行日志', '系统日志');
                $router->get('auth/system-log-viewer/{file}', \Dcat\Admin\Http\Controllers\SystemLogViewerController::class.'@index')
                    ->name('log-viewer.log-viewer-file')
                    ->permissionLabel('查看日志文件', '读取指定的应用日志文件', '系统日志');
                $router->get('auth/system-log-viewer/download', \Dcat\Admin\Http\Controllers\SystemLogViewerController::class.'@download')
                    ->name('log-viewer.download')
                    ->permissionLabel('下载系统日志', '下载指定的应用日志文件', '系统日志');
                $router->post('auth/system-log-viewer/delete', \Dcat\Admin\Http\Controllers\SystemLogViewerController::class.'@delete')
                    ->name('log-viewer.delete')
                    ->permissionLabel('删除系统日志', '删除指定的应用日志文件', '系统日志');
                $router->post('auth/system-log-viewer/clear', \Dcat\Admin\Http\Controllers\SystemLogViewerController::class.'@clear')
                    ->name('log-viewer.clear')
                    ->permissionLabel('清空系统日志', '清空应用日志目录中的日志文件', '系统日志');
                
                // form-media
                $router->any('lake-form-media/get-files', \Dcat\Admin\Form\Extend\FormMedia\Controllers\FormMedia::class.'@getFiles')
                    ->permissionLabel('浏览媒体文件', '读取媒体库中的目录和文件', '媒体管理')
                    ->defaults('dcat_route_type', 'internal_legacy')->name('admin.lake-form-media.get-files');
                $router->post('lake-form-media/upload', \Dcat\Admin\Form\Extend\FormMedia\Controllers\FormMedia::class.'@upload')
                    ->permissionLabel('上传媒体文件', '向后台媒体库上传文件', '媒体管理')
                    ->defaults('dcat_route_type', 'internal_legacy')->name('admin.lake-form-media.upload');
                $router->post('lake-form-media/create-folder', \Dcat\Admin\Form\Extend\FormMedia\Controllers\FormMedia::class.'@createFolder')
                    ->permissionLabel('创建媒体目录', '在后台媒体库中创建文件夹', '媒体管理')
                    ->defaults('dcat_route_type', 'internal_legacy')->name('admin.lake-form-media.create-folder');
                // sku-image
                $router->resource('sku-action', \Dcat\Admin\Http\Controllers\SkuAttributeController::class);
                $router->post('sku-image-upload', \Dcat\Admin\Form\Extend\Sku\Controllers\UploadController::class.'@store')
                    ->permissionLabel('上传规格图片', '上传商品 SKU 规格使用的图片', '商品规格')
                    ->defaults('dcat_route_type', 'internal_legacy')->name('admin.sku-image-upload');
                $router->post('sku-image-remove', \Dcat\Admin\Form\Extend\Sku\Controllers\UploadController::class.'@delete')
                    ->permissionLabel('删除规格图片', '删除商品 SKU 规格使用的图片', '商品规格')
                    ->defaults('dcat_route_type', 'internal_legacy')->name('admin.sku-image-remove');

                // Framework-internal support routes. These routes are excluded
                // from URL-based role permissions and authorize themselves via
                // the admin.internal middleware.
                $router->prefix('dcat-sys')->as('dcat-sys.')->group(function ($router) {
                    $router->get('notifications', 'Dcat\Admin\Http\Controllers\NotificationApiController@index')
                        ->permissionLabel('通知列表数据', '获取当前管理员的通知列表', '消息通知')
                        ->middleware('admin.internal:authenticated')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('notifications.index');
                    $router->post('notifications/{id}/read', 'Dcat\Admin\Http\Controllers\NotificationApiController@read')
                        ->permissionLabel('标记通知已读', '将指定通知标记为已读', '消息通知')
                        ->middleware('admin.internal:authenticated')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('notifications.read');
                    $router->get('notifications/first-unread', 'Dcat\Admin\Http\Controllers\NotificationApiController@firstUnread')
                        ->permissionLabel('获取未读通知', '获取当前管理员第一条未读通知', '消息通知')
                        ->middleware('admin.internal:authenticated')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('notifications.first-unread');
                    $router->post('notifications/read-all', 'Dcat\Admin\Http\Controllers\NotificationApiController@readAll')
                        ->permissionLabel('全部通知已读', '将当前管理员的全部通知标记为已读', '消息通知')
                        ->middleware('admin.internal:authenticated')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('notifications.read-all');

                    $router->post('preferences/save', 'Dcat\Admin\Http\Controllers\LayoutConfigController@savePreference')
                        ->permissionLabel('保存界面偏好', '保存当前管理员的界面显示偏好', '个人中心')
                        ->middleware('admin.internal:authenticated')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('preferences.save');

                    $router->post('roles/switch', 'Dcat\Admin\Http\Controllers\ActiveRoleController@switchRole')
                        ->permissionLabel('切换当前角色', '在当前登录会话中切换已分配的角色', '个人中心')
                        ->middleware('admin.internal:authenticated')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('roles.switch');

                    $router->match(['GET', 'POST'], 'media/files', \Dcat\Admin\Form\Extend\FormMedia\Controllers\FormMedia::class.'@getFiles')
                        ->permissionLabel('浏览媒体文件', '读取媒体库中的目录和文件', '媒体管理')
                        ->middleware('admin.internal:signed,media.read')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('media.files');
                    $router->post('media/upload', \Dcat\Admin\Form\Extend\FormMedia\Controllers\FormMedia::class.'@upload')
                        ->permissionLabel('上传媒体文件', '向后台媒体库上传文件', '媒体管理')
                        ->middleware('admin.internal:signed,media.write')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('media.upload');
                    $router->post('media/create-folder', \Dcat\Admin\Form\Extend\FormMedia\Controllers\FormMedia::class.'@createFolder')
                        ->permissionLabel('创建媒体目录', '在后台媒体库中创建文件夹', '媒体管理')
                        ->middleware('admin.internal:signed,media.write')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('media.create-folder');

                    $router->post('sku/upload', \Dcat\Admin\Form\Extend\Sku\Controllers\UploadController::class.'@store')
                        ->permissionLabel('上传规格图片', '上传商品 SKU 规格使用的图片', '商品规格')
                        ->middleware('admin.internal:signed,sku.write')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('sku.upload');
                    $router->post('sku/remove', \Dcat\Admin\Form\Extend\Sku\Controllers\UploadController::class.'@delete')
                        ->permissionLabel('删除规格图片', '删除商品 SKU 规格使用的图片', '商品规格')
                        ->middleware('admin.internal:signed,sku.write')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('sku.remove');

                    $router->post('cache/clear', 'Dcat\Admin\Http\Controllers\LayoutConfigController@clear')
                        ->permissionLabel('清理系统缓存', '清理后台框架运行缓存', '系统维护')
                        ->middleware('admin.internal:administrator')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('cache.clear');
                    $router->post('layout/save', 'Dcat\Admin\Http\Controllers\LayoutConfigController@save')
                        ->permissionLabel('保存布局配置', '保存导航栏和后台界面布局配置', '系统设置')
                        ->middleware('admin.internal:administrator')
                        ->defaults('dcat_route_type', 'internal')
                        ->name('layout.save');
                });

            });
        }

        static::registerHelperRoutes();
    }

    /**
     * 注册api路由.
     *
     * @return void
     */
    public static function registerApiRoutes()
    {
        $attributes = [
            'prefix'     => admin_base_path('dcat-api'),
            'middleware' => config('admin.route.middleware'),
            'namespace'  => 'Dcat\Admin\Http\Controllers',
            'as'         => 'dcat-api.',
        ];

        app('router')->group($attributes, function ($router) {
            /* @var \Illuminate\Routing\Router $router */
            $router->post('action', 'HandleActionController@handle')->name('action')
                ->permissionLabel('执行后台动作', '处理 Grid、Form 等组件提交的后台动作', '框架服务');
            $router->post('form', 'HandleFormController@handle')->name('form')
                ->permissionLabel('提交异步表单', '处理弹窗表单和异步表单提交', '框架服务');
            $router->post('form/upload', 'HandleFormController@uploadFile')->name('form.upload')
                ->permissionLabel('上传表单文件', '处理后台表单字段的文件上传', '文件上传');
            $router->post('form/destroy-file', 'HandleFormController@destroyFile')->name('form.destroy-file')
                ->permissionLabel('删除表单文件', '删除后台表单字段已上传的文件', '文件上传');
            $router->post('value', 'ValueController@handle')->name('value')
                ->permissionLabel('更新字段值', '处理表格可编辑字段和快捷状态更新', '框架服务');
            $router->get('render', 'RenderableController@handle')->name('render')
                ->permissionLabel('渲染异步组件', '加载延迟渲染组件和异步内容', '框架服务');
            $router->post('tinymce/upload', 'TinymceController@upload')->name('tinymce.upload')
                ->permissionLabel('上传 TinyMCE 文件', '处理 TinyMCE 编辑器文件上传', '编辑器上传');
            $router->post('editor-md/upload', 'EditorMDController@upload')->name('editor-md.upload')
                ->permissionLabel('上传 Editor.md 文件', '处理 Editor.md 编辑器文件上传', '编辑器上传');
            $router->get('ueditor/server', 'UeditorController@handle')->name('ueditor.server')
                ->permissionLabel('加载 UEditor 配置', '返回 UEditor 后端配置和上传参数', '编辑器上传');
            $router->post('ueditor/server', 'UeditorController@handle')
                ->middleware('throttle:'.UeditorConfig::get('rate_limit', 20).',1')
                ->name('ueditor.server.post')
                ->permissionLabel('处理 UEditor 上传', '处理 UEditor 图片、文件和视频上传', '编辑器上传');
        });
    }

    /**
     * 注册开发工具路由.
     *
     * @return void
     */
    public static function registerHelperRoutes()
    {
        if (! config('admin.helpers.enable', true) || ! config('app.debug')) {
            return;
        }

        $attributes = [
            'prefix'     => config('admin.route.prefix'),
            'middleware' => config('admin.route.middleware'),
        ];

        app('router')->group($attributes, function ($router) {
            /* @var \Illuminate\Routing\Router $router */
            $router->get('helpers/scaffold', 'Dcat\Admin\Http\Controllers\ScaffoldController@index')
                ->permissionLabel('代码生成器', '打开后台 CRUD 代码生成工具', '开发工具');
            $router->post('helpers/scaffold', 'Dcat\Admin\Http\Controllers\ScaffoldController@store')
                ->permissionLabel('执行代码生成', '根据数据表和配置生成后台 CRUD 代码', '开发工具');
            $router->post('helpers/scaffold/table', 'Dcat\Admin\Http\Controllers\ScaffoldController@table')
                ->permissionLabel('读取数据表结构', '读取指定数据表的字段结构供代码生成器使用', '开发工具');
            $router->get('helpers/icons', 'Dcat\Admin\Http\Controllers\IconController@index')
                ->permissionLabel('图标库', '浏览后台框架可用的图标', '开发工具');
        });
    }
}
