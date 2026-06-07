<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Layout\Content;
use Illuminate\Routing\Controller;

class IframeTabController extends Controller
{
    /**
     * iframe 标签页外壳入口。
     *
     * 外壳只负责承载菜单、导航栏和 iframe 容器，真实业务页面仍走原有后台路由，
     * 通过 iframe_tab 查询参数切换为无侧栏内容页，避免破坏已有 /admin 首页。
     */
    public function index(Content $content): Content
    {
        return $content
            ->title((string) config('admin.iframe_tab.home_title', '首页'))
            ->view('admin::partials.iframe-tab.shell-content');
    }
}
