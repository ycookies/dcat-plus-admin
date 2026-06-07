<?php

namespace Dcat\Admin\Extend;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Dcat\Admin\Http\Controllers\AssetController;
use Dcat\Admin\Http\Controllers\IframeTabController;

class IframeTabServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 配置已合并至 config/admin.php 的 iframe_tab 键下，无需单独加载
    }

    public function boot(): void
    {
        // 视图已通过 AdminServiceProvider 的 admin:: 命名空间注册，
        // 可直接用 admin::partials.iframe-tab.* 引用，无需单独注册
        // $this->publishes([
        //     __DIR__.'/../config/admin_iframe_tab.php' => config_path('admin_iframe_tab.php'),
        // ], 'admin-iframe-tab-config');
        // $this->publishes([
        //     __DIR__.'/../public' => public_path('/vendor/dcat-admin/dcat/plugins/admin-iframe-tab'),
        // ], 'admin-iframe-tab-assets');

        if (! config('admin.iframe_tab.enabled', true)) {
            return;
        }

        $this->registerAssetRoute();
        $this->registerShellRoute();

        if (! $this->shouldRegisterDcatHooks()) {
            return;
        }

        $this->registerIframeContentResolver();
        $this->registerDialogDefaults();
    }

    /**
     * 插件资源默认由路由提供，避免主项目 public 目录保存一份重复源码。
     */
    protected function registerAssetRoute(): void
    {
        Route::get('vendor/admin-iframe-tab/{path}', [AssetController::class, 'show'])
            ->where('path', '.*')
            ->name('admin-iframe-tab.asset');
    }

    /**
     * 注册独立入口，避免覆盖现有 /admin 首页和菜单权限链路。
     */
    protected function registerShellRoute(): void
    {
        Route::group([
            'prefix' => config('admin.route.prefix'),
            'middleware' => config('admin.route.middleware'),
            'domain' => config('admin.route.domain'),
        ], function (): void {
            Route::get($this->shellPath(), [IframeTabController::class, 'index'])
                ->name('admin.iframe-tab.index');
        });
    }

    /**
     * 只有 iframe 子页面才切换无侧栏视图。
     *
     * 直接访问后台业务页面仍保持 Dcat-plus 原始布局，方便出现兼容问题时快速回退；
     * iframe 内的 GET 跳转由前端统一补齐 query_key，保证子页面不会套娃出完整后台。
     */
    protected function registerIframeContentResolver(): void
    {
        Content::resolving(function (Content $content): void {
            $request = request();

            if (! $this->shouldRenderIframeChild($request)) {
                return;
            }

            $content->view('admin::partials.iframe-tab.child-content');

            if ($this->isLoginPath($request)) {
                session()->forget('url.intended');

                Admin::script(<<<'JS'
if (window !== window.top) {
    window.top.location.href = window.location.href;
}
JS);
            }
        });
    }

    /**
     * iframe 模式下弹窗表单留出更多宽度，避免在子页面里二次压缩。
     */
    protected function registerDialogDefaults(): void
    {
        Grid::resolving(function (Grid $grid): void {
            if (! $this->shouldRenderIframeChild(request())) {
                return;
            }

            if (! method_exists($grid, 'setDialogFormDimensions')) {
                return;
            }

            $grid->setDialogFormDimensions(
                (string) config('admin.iframe_tab.dialog_area_width', '70%'),
                (string) config('admin.iframe_tab.dialog_area_height', '90vh')
            );
        });
    }

    protected function shouldRenderIframeChild(Request $request): bool
    {
        $queryKey = (string) config('admin.iframe_tab.query_key', 'iframe_tab');

        if (! $request->query->has($queryKey)) {
            return false;
        }

        if (! $this->isAdminPath($request)) {
            return false;
        }

        return ! $this->isShellPath($request);
    }

    /**
     * Dcat 的 builder 事件会立即访问 admin.context。
     *
     * API 请求不会加载完整后台上下文，如果全局注册 Content/Grid hook，会导致公开接口
     * 在应用启动阶段就抛出 BindingResolutionException；因此只有后台路径才注册这些 hook。
     */
    protected function shouldRegisterDcatHooks(): bool
    {
        if ($this->app->runningInConsole()) {
            return false;
        }

        return $this->isAdminPath(request());
    }

    protected function isAdminPath(Request $request): bool
    {
        $prefix = trim((string) config('admin.route.prefix', 'admin'), '/');

        if ($prefix === '') {
            return true;
        }

        $path = trim($request->path(), '/');

        return $path === $prefix || str_starts_with($path, $prefix.'/');
    }

    protected function isShellPath(Request $request): bool
    {
        $prefix = trim((string) config('admin.route.prefix', 'admin'), '/');
        $shellPath = trim($this->shellPath(), '/');
        $path = trim($request->path(), '/');
        $expected = trim($prefix.'/'.$shellPath, '/');

        return $path === $expected;
    }

    protected function isLoginPath(Request $request): bool
    {
        $path = trim($request->path(), '/');
        $prefix = trim((string) config('admin.route.prefix', 'admin'), '/');
        $loginPath = trim($prefix.'/auth/login', '/');

        return $path === $loginPath;
    }

    protected function shellPath(): string
    {
        return trim((string) config('admin.iframe_tab.shell_path', 'iframe-tabs'), '/') ?: 'iframe-tabs';
    }
}
