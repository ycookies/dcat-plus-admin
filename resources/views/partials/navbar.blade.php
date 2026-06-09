{!! admin_section(Dcat\Admin\Admin::SECTION['NAVBAR_BEFORE']) !!}

{{-- 布局配置变量（所有模式共用） --}}
@php
    $savedLayout = [];
    try { $savedLayout = admin_setting_group('layout_config') ?: []; } catch (\Throwable) {}
    $lcColor = $savedLayout['color'] ?? config('admin.layout.color', 'default');
    $lcSidebarStyle = $savedLayout['sidebar_style'] ?? config('admin.layout.sidebar_style', 'light');
    $lcNavbarColor = $savedLayout['navbar_color'] ?? config('admin.layout.navbar_color', '');
    // 菜单模式：默认菜单、水平菜单、双栏菜单（互斥）
    $menu_style = $configData['menu_style'] ?? ($configData['horizontal_menu'] ?? false ? 'horizontal_menu' : 'default_menu');
    
    $lcSidebarCollapsed = isset($savedLayout['sidebar_collapsed']) ? (bool)$savedLayout['sidebar_collapsed'] : config('admin.layout.sidebar_collapsed', false);
    $lcDarkModeSwitch = isset($savedLayout['dark_mode_switch']) ? (bool)$savedLayout['dark_mode_switch'] : config('admin.layout.dark_mode_switch', false);
    $lcFullScreen = isset($savedLayout['full_screen']) ? (bool)$savedLayout['full_screen'] : config('admin.layout.full_screen', true);
    $lcHomeUrl = $savedLayout['home_url'] ?? config('admin.layout.home_url', '');
    $lcLocale = $savedLayout['locale'] ?? config('app.locale', 'zh_CN');
    $lcShowLocaleSwitch = isset($savedLayout['show_locale_switch']) ? (bool)$savedLayout['show_locale_switch'] : true;
    $lcShowHelp = isset($savedLayout['show_help']) ? (bool)$savedLayout['show_help'] : true;
    $lcShowNotification = isset($savedLayout['show_notification']) ? (bool)$savedLayout['show_notification'] : true;
    $lciframeabs = isset($savedLayout['iframe_tabs']) ? (bool)$savedLayout['iframe_tabs'] : config('admin.layout.iframe_tabs', false);
    $iframe_tabs_url = '/admin/iframe-tabs';
    $url_path = request()->path();
    $iframe_tabs_tips = '切换 iframe-tabs';
    if($url_path === 'admin/iframe-tabs'){
        $iframe_tabs_url = '/admin';
        $iframe_tabs_tips = '切回默认';
    }
    // 获取帮助数据（分类+内容）
    $helpCategories = [];
    $helplist = collect();
    try {
        $helplist = \Dcat\Admin\Models\Help::where('is_active', true)
        ->with('category')->orderBy('id','desc')->paginate(5);
    } catch (\Throwable) {}

    // 获取当前用户通知
    $notifications = collect();
    $unreadCount = 0;
    try {
        $adminUser = \Dcat\Admin\Admin::user();
        if ($adminUser) {
            $notifications = \Dcat\Admin\Models\Notification::getAllForUser($adminUser->id);
            $unreadCount = \Dcat\Admin\Models\Notification::getUnreadCountForUser($adminUser->id);
        }
    } catch (\Throwable) {}
@endphp

@if($menu_style !== 'horizontal_menu')
<nav class="header-navbar navbar-expand-lg navbar
    navbar-with-menu {{ $configData['navbar_class'] }}
    {{ $configData['navbar_color'] }}
        navbar-light navbar-shadow " style="top: 0;">

    <div class="navbar-wrapper">
        <div class="navbar-container content">
            @if($menu_style !== 'horizontal_menu')
            <div class="mr-auto float-left bookmark-wrapper d-flex align-items-center">
                <ul class="nav navbar-nav">
                    <li class="nav-item mr-auto">
                        <a class="nav-link menu-toggle" data-widget="pushmenu" style="cursor: pointer;">
                            <i class="fa fa-bars font-md-2"></i>
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            <div class="navbar-collapse d-flex justify-content-between">
                <div class="navbar-left d-flex align-items-center">
                    {!! Dcat\Admin\Admin::navbar()->render('left') !!}
                </div>

                @if($menu_style === 'horizontal_menu')
                <div class="d-md-block horizontal-navbar-brand justify-content-center text-center">
                    <ul class="nav navbar-nav flex-row">
                        <li class="nav-item mr-auto">
                            <a href="{{ admin_url('/') }}" class="waves-effect waves-light">
                                <span class="logo-lg">{!! config('admin.logo') !!}</span>
                            </a>
                        </li>
                    </ul>
                </div>
                @endif

                <div class="navbar-right d-flex align-items-center">
                    {!! Dcat\Admin\Admin::navbar()->render() !!}
                    
                    <ul class="nav navbar-nav">
                        @if(!empty($configData['home_url']))
                        <li class="nav-item">
                                <a href="{{$configData['home_url']}}"  target="_blank" class="nav-link tips" data-title="首页"><i class="fa fa-home fs18"></i></a> 
                        </li>
                        @endif
                        {{--help 帮助信息--}}
                        @if($lcShowHelp && $helplist->isNotEmpty())
                        <li class="dropdown dropdown-notification nav-item">
                            <a class="nav-link nav-link-label" href="#" data-toggle="dropdown" aria-expanded="true">
                                <i class="feather icon-help-circle fs18"></i>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right shadow-200">
                                
                                    @foreach($helplist as $help)
                                    <li class="scrollable-container media-list">
                                        @if($help->link)
                                        <a class="media d-flex justify-content-between" href="{{ $help->link }}" target="{{ $help->link_target }}">
                                        @else
                                        <a class="media d-flex justify-content-between" href="javascript:void(0)" onclick="openModal('h',{{ $help->id }})" data-id="{{ $help->id }}">
                                        @endif
                                            <div class="d-flex align-items-start">
                                                <div class="media-body">
                                                    <h6 class="primary media-heading h-title-{{ $help->id }}">{{ $help->title }}</h6>
                                                    @if($help->content)
                                                    <small class="notification-text">{{ Str::limit($help->content, 60) }}</small>
                                                    <div style="display: none;" class="h-content-{{ $help->id }}"> {{ $help->content }}</div>
                                                    @endif
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    @endforeach
                                
                            </ul>
                        </li>
                        @endif

                        {{--notification 通知--}}
                        @if($lcShowNotification)
                        <li class="dropdown dropdown-notification nav-item" style="text-align: center">
                            <a class="nav-link nav-link-label" href="#" data-toggle="dropdown" aria-expanded="true">
                                <i class="feather icon-bell fs18"></i>
                                @if($unreadCount > 0)
                                    <span class="badge badge-pill badge-primary badge-up" style="top:12px;right:-6px;font-size:8px">
                                    {{ $unreadCount }}
                                    </span>
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right shadow-200">
                                <li class="dropdown-menu-header">
                                    <div class="dropdown-header m-0 p-2">
                                        <h3 class="white">{{ $unreadCount }}</h3>
                                        <span class="grey darken-2">未读通知</span>
                                    </div>
                                </li>
                                @if($notifications->isNotEmpty())
                                    <li class="scrollable-container media-list ps ps--active-y" style="max-height:300px;">
                                        @foreach($notifications as $notification)
                                            <a class="media d-flex justify-content-between lc-notification-item {{ $notification->is_read ? '' : 'font-weight-bold' }}"
                                               href="javascript:void(0)" data-id="{{ $notification->id }}" data-type="n">
                                                <div class="d-flex align-items-start">
                                                    <div class="media-body">
                                                        <h6 class="primary media-heading n-title-{{ $notification->id }}">{{ $notification->title }}</h6>
                                                        @if($notification->content)
                                                        <small class="notification-text">{{ Str::limit($notification->content, 60) }}</small>
                                                        <div style="display: none;" class="n-content-{{ $notification->id }}"> {{ $notification->content }}</div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </li>
                                    @if($unreadCount > 0)
                                    <li class="dropdown-menu-footer">
                                        <a class="dropdown-item p-1 text-center lc-read-all" href="javascript:void(0)">全部已读</a>
                                    </li>
                                    @endif
                                @else
                                    <li class="text-center text-muted p-3">暂无通知</li>
                                @endif
                            </ul>
                        </li>
                        @endif
                        <li class="nav-item">
                            @if(isset($configData['full_screen']) && $configData['full_screen'])
                            <a href="javascript:;"  data-check-screen="full" class="nav-link">
                                <i class="feather icon-maximize fs18"></i>
                            </a>
                            @endif
                        </li>
                        {{-- 暗黑模式切换 --}}
                        <li class="nav-item">
                            <a href="javascript:void(0);" class="dark-mode-switcher nav-link" style="padding:1.5rem 0rem 1.35rem .5rem !important;" title="切换暗黑模式">
                                  <i class="feather {{ config('admin.layout.dark_mode_switch') ? 'icon-moon' : 'icon-sun' }} fs18"></i>
                            </a>

                            <script>
                            Dcat.darkMode.initSwitcher('.dark-mode-switcher');
                            </script>
                        </li>
                        @if(isset($configData['show_locale_switch']) && $configData['show_locale_switch'])
                        <li class="dropdown nav-item">
                            <a class="nav-link nav-link-label" href="#" data-toggle="dropdown" style="padding:1.5rem 0rem 1.35rem .5rem !important;">
                                <i class="flag-icon @if($lcLocale === 'zh_CN') flag-icon-cn @elseif($lcLocale === 'zh_TW') flag-icon-tw @else flag-icon-us @endif" style="margin-right:4px"></i>
                                <span class="d-none d-sm-inline">
                                    <i class="fa fa-language fs18"></i>
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a style="padding:10px 8px;" class="dropdown-item lc-locale-switch {{ $lcLocale === 'zh_CN' ? 'active' : '' }}" href="javascript:void(0)" data-locale="zh_CN">
                                    <i class="flag-icon flag-icon-cn" style="margin-right:6px"></i> 简体中文
                                </a>
                                <a style="padding:10px 8px;" class="dropdown-item lc-locale-switch {{ $lcLocale === 'zh_TW' ? 'active' : '' }}" href="javascript:void(0)" data-locale="zh_TW">
                                    <i class="flag-icon flag-icon-tw" style="margin-right:6px"></i> 繁體中文
                                </a>
                                <a style="padding:10px 8px;" class="dropdown-item lc-locale-switch {{ $lcLocale === 'en' ? 'active' : '' }}" href="javascript:void(0)" data-locale="en">
                                    <i class="flag-icon flag-icon-us" style="margin-right:6px"></i> English
                                </a>
                            </div>
                        </li>
                        @endif
                        {{-- iframe-tabs 切换 --}}
                        @if($lciframeabs)
                        <li class="nav-item">
                            <a href="{{ $iframe_tabs_url }}" target="_blank" class="nav-link lc-iframe-tabs-switch" data-target-mode="{{ $url_path === 'admin/iframe-tabs' ? 'default' : 'iframe-tabs' }}" style="padding:1.5rem 0rem 1.35rem 1rem !important;">
                                  <img data-tips="tooltip" data-title="{{ $iframe_tabs_tips }}" data-placement="bottom"  src="/vendor/dcat-admin/images/tabs.png" style="width:20px;height:20px;"/>
                            </a>
                        </li>
                        @endif
                        @if(config('admin.layout.layout_config_tool_in_navbar'))
                        <li class="nav-item">
                            {{-- 布局配置按钮（导航栏内） --}}
                            <a href="javascript:void(0);" class="nav-link lc-open-trigger" title="布局配置">
                                <i class="feather icon-settings fs18"></i>
                            </a>
                        </li>
                        @endif
                        {{-- <li class="nav-item">
                        </li>
                        <li class="nav-item">
                        </li> --}}
                    </ul>
                    




                    

                    <ul class="nav navbar-nav">
                        {{--User Account Menu--}}
                        {!! admin_section(Dcat\Admin\Admin::SECTION['NAVBAR_USER_PANEL']) !!}

                        {!! admin_section(Dcat\Admin\Admin::SECTION['NAVBAR_AFTER_USER_PANEL']) !!}
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
@endif

{{-- 布局配置滑出面板（所有模式共用） --}}
<div id="lc-overlay" class="lc-overlay" style="display:none;"></div>
<div id="lc-config-panel" class="lc-config-panel">
    <div class="lc-panel-header">
        <span class="lc-panel-title"><i class="feather icon-settings"></i> 布局配置</span>
        <button type="button" class="lc-panel-close" id="lc-close-panel">&times;</button>
    </div>
    <form id="lc-config-form" method="POST" action="{{ admin_url('layout-config/save') }}">
        <div class="lc-panel-body">

            <div class="lc-section">
                <div class="lc-section-title">主题色</div>
                <div class="lc-radio-group">
                    <label class="lc-radio-label {{ $lcColor === 'default' ? 'active' : '' }}">
                        <input type="radio" name="color" value="default" {{ $lcColor === 'default' ? 'checked' : '' }}> 默认
                    </label>
                    <label class="lc-radio-label {{ $lcColor === 'blue' ? 'active' : '' }}">
                        <input type="radio" name="color" value="blue" {{ $lcColor === 'blue' ? 'checked' : '' }}> 蓝色
                    </label>
                    <label class="lc-radio-label {{ $lcColor === 'blue-light' ? 'active' : '' }}">
                        <input type="radio" name="color" value="blue-light" {{ $lcColor === 'blue-light' ? 'checked' : '' }}> 浅蓝
                    </label>
                    <label class="lc-radio-label {{ $lcColor === 'green' ? 'active' : '' }}">
                        <input type="radio" name="color" value="green" {{ $lcColor === 'green' ? 'checked' : '' }}> 绿色
                    </label>
                </div>
            </div>

            <div class="lc-section">
                <div class="lc-section-title">侧边栏样式</div>
                <div class="lc-radio-group">
                    <label class="lc-radio-label {{ $lcSidebarStyle === 'light' ? 'active' : '' }}">
                        <input type="radio" name="sidebar_style" value="light" {{ $lcSidebarStyle === 'light' ? 'checked' : '' }}> 浅色
                    </label>
                    <label class="lc-radio-label {{ $lcSidebarStyle === 'primary' ? 'active' : '' }}">
                        <input type="radio" name="sidebar_style" value="primary" {{ $lcSidebarStyle === 'primary' ? 'checked' : '' }}> 主题色
                    </label>
                    <label class="lc-radio-label {{ $lcSidebarStyle === 'dark' ? 'active' : '' }}">
                        <input type="radio" name="sidebar_style" value="dark" {{ $lcSidebarStyle === 'dark' ? 'checked' : '' }}> 深色
                    </label>
                </div>
            </div>

            <div class="lc-section">
                <div class="lc-section-title">导航栏颜色</div>
                <div class="lc-radio-group">
                    <label class="lc-radio-label {{ $lcNavbarColor === '' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="" {{ $lcNavbarColor === '' ? 'checked' : '' }}> 默认
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-primary' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-primary" {{ $lcNavbarColor === 'bg-primary' ? 'checked' : '' }}> 主题色
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-info' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-info" {{ $lcNavbarColor === 'bg-info' ? 'checked' : '' }}> 信息色
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-warning' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-warning" {{ $lcNavbarColor === 'bg-warning' ? 'checked' : '' }}> 警告色
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-success' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-success" {{ $lcNavbarColor === 'bg-success' ? 'checked' : '' }}> 成功色
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-danger' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-danger" {{ $lcNavbarColor === 'bg-danger' ? 'checked' : '' }}> 危险色
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-dark' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-dark" {{ $lcNavbarColor === 'bg-dark' ? 'checked' : '' }}> 深色
                    </label>
                </div>
            </div>
            <div class="lc-section">
                <div class="lc-section-title">菜单</div>
                <div class="lc-switch-group">
                    <label class="lc-switch-label">
                        <input type="radio" name="menu_style" value="default_menu" {{ $menu_style === 'default_menu' ? 'checked' : '' }}>
                        <span>默认菜单</span>
                    </label>
                    <label class="lc-switch-label">
                        <input type="radio" name="menu_style" value="horizontal_menu" {{ $menu_style === 'horizontal_menu' ? 'checked' : '' }}>
                        <span>水平菜单</span>
                    </label>
                    <label class="lc-switch-label">
                        <input type="radio" name="menu_style" value="two_col_menu" {{ $menu_style === 'two_col_menu' ? 'checked' : '' }}>
                        <span>双栏菜单</span>
                    </label>
                </div>
            </div>

            <div class="lc-section">
                <div class="lc-section-title">功能开关</div>
                <div class="lc-switch-group">
                    <label class="lc-switch-label">
                        <input type="checkbox" name="iframe_tabs" value="1" {{ $lciframeabs ? 'checked' : '' }}>
                        <span>iframe-tabs</span>
                    </label>
                    <label class="lc-switch-label">
                        <input type="checkbox" name="sidebar_collapsed" value="1" {{ $lcSidebarCollapsed ? 'checked' : '' }}>
                        <span>侧边栏折叠</span>
                    </label>
                    <label class="lc-switch-label">
                        <input type="checkbox" name="dark_mode_switch" value="1" {{ $lcDarkModeSwitch ? 'checked' : '' }}>
                        <span>暗黑模式切换</span>
                    </label>
                    <label class="lc-switch-label">
                        <input type="checkbox" name="full_screen" value="1" {{ $lcFullScreen ? 'checked' : '' }}>
                        <span>全屏按钮</span>
                    </label>
                    <label class="lc-switch-label">
                        <input type="checkbox" name="show_locale_switch" value="1" {{ $lcShowLocaleSwitch ? 'checked' : '' }}>
                        <span>语言切换</span>
                    </label>
                    <label class="lc-switch-label">
                        <input type="checkbox" name="show_help" value="1" {{ $lcShowHelp ? 'checked' : '' }}>
                        <span>帮助按钮</span>
                    </label>
                    <label class="lc-switch-label">
                        <input type="checkbox" name="show_notification" value="1" {{ $lcShowNotification ? 'checked' : '' }}>
                        <span>通知按钮</span>
                    </label>
                </div>
            </div>

            <div class="lc-section">
                <div class="lc-section-title">默认语言</div>
                <div class="lc-radio-group">
                    <label class="lc-radio-label {{ $lcLocale === 'zh_CN' ? 'active' : '' }}">
                        <input type="radio" name="locale" value="zh_CN" {{ $lcLocale === 'zh_CN' ? 'checked' : '' }}> 简体中文
                    </label>
                    <label class="lc-radio-label {{ $lcLocale === 'zh_TW' ? 'active' : '' }}">
                        <input type="radio" name="locale" value="zh_TW" {{ $lcLocale === 'zh_TW' ? 'checked' : '' }}> 繁體中文
                    </label>
                    <label class="lc-radio-label {{ $lcLocale === 'en' ? 'active' : '' }}">
                        <input type="radio" name="locale" value="en" {{ $lcLocale === 'en' ? 'checked' : '' }}> English
                    </label>
                </div>
            </div>

            <div class="lc-section">
                <div class="lc-section-title">官网链接</div>
                <input type="text" name="home_url" value="{{ $lcHomeUrl }}"
                    class="lc-input" placeholder="留空则不显示官网入口">
            </div>

        </div>
        <div class="lc-panel-footer">
            <button type="submit" class="btn btn-primary btn-sm btn-block">
                <i class="feather icon-save"></i> 保存配置
            </button>
        </div>
    </form>
</div>

{{-- 布局配置 CSS --}}
<style>
.fs18{
    font-size: 18px !important;
}
.lc-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.3);
    z-index: 1030;
    transition: opacity .25s;
}
.lc-config-panel {
    position: fixed;
    top: 0; right: -380px;
    width: 360px;
    height: 100vh;
    background: #fff;
    box-shadow: -2px 0 12px rgba(0,0,0,0.1);
    z-index: 1031;
    transition: right .3s ease;
    display: flex;
    flex-direction: column;
    font-size: 14px;
}
.lc-config-panel.open {
    right: 0;
}
.lc-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
}
.lc-panel-title {
    font-weight: 600;
    font-size: 16px;
    color: #333;
}
.lc-panel-title i {
    margin-right: 6px;
}
.lc-panel-close {
    background: none;
    border: none;
    font-size: 22px;
    color: #999;
    cursor: pointer;
    padding: 0 4px;
    line-height: 1;
}
.lc-panel-close:hover {
    color: #333;
}
.lc-config-panel form {
    flex: 1;
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}
.lc-panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
    min-height: 0;
}
.lc-section {
    margin-bottom: 20px;
}
.lc-section-title {
    font-weight: 600;
    font-size: 13px;
    color: #666;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.lc-radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.lc-radio-label {
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    padding: 4px 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-size: 13px;
    transition: all .15s;
}
.lc-radio-label:hover {
    border-color: #5c6bc0;
    background: #eef0ff;
}
.lc-radio-label.active,
.lc-radio-label:has(input:checked) {
    border-color: #5c6bc0;
    background: #eef0ff;
}
.lc-switch-group {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}
.lc-switch-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 13px;
    padding: 6px 0;
}
.lc-switch-label input[type="checkbox"] {
    width: 16px;
    height: 16px;
}
.lc-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    outline: none;
    transition: border-color .15s;
}
.lc-input:focus {
    border-color: #5c6bc0;
}
.lc-panel-footer {
    flex-shrink: 0;
    padding: 12px 20px;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}
</style>

{{-- 主题色覆盖 --}}
@if($lcColor !== 'default')
@php
    $themeColors = [
        'blue' => [
            'main' => '#6d8be6', 'dark' => '#5570cc', 'darker' => '#4560b8', 'darkest' => '#3a50a5',
            'light' => '#8da4ec', 'lighter' => '#adbef2',
            'rgba8' => 'rgba(109,139,230,.8)', 'rgba7' => 'rgba(109,139,230,.7)',
            'rgba5' => 'rgba(109,139,230,.5)', 'rgba2' => 'rgba(109,139,230,.2)',
            'rgba05' => 'rgba(109,139,230,.05)',
            'bg-tint' => '#e8edf8', 'bg-light' => '#eef2fb',
        ],
        'blue-light' => [
            'main' => '#62a8ea', 'dark' => '#4e92d4', 'darker' => '#3e80c0', 'darkest' => '#2e70ac',
            'light' => '#82bef0', 'lighter' => '#a2d4f6',
            'rgba8' => 'rgba(98,168,234,.8)', 'rgba7' => 'rgba(98,168,234,.7)',
            'rgba5' => 'rgba(98,168,234,.5)', 'rgba2' => 'rgba(98,168,234,.2)',
            'rgba05' => 'rgba(98,168,234,.05)',
            'bg-tint' => '#e4f0fa', 'bg-light' => '#edf5fd',
        ],
        'green' => [
            'main' => '#4e9876', 'dark' => '#3e8462', 'darker' => '#2e704e', 'darkest' => '#1e5c3a',
            'light' => '#6eb896', 'lighter' => '#8ed8b6',
            'rgba8' => 'rgba(78,152,118,.8)', 'rgba7' => 'rgba(78,152,118,.7)',
            'rgba5' => 'rgba(78,152,118,.5)', 'rgba2' => 'rgba(78,152,118,.2)',
            'rgba05' => 'rgba(78,152,118,.05)',
            'bg-tint' => '#e2f0ea', 'bg-light' => '#edf7f2',
        ],
    ];
    $tc = $themeColors[$lcColor] ?? null;
@endphp
@if($tc)
<style>
/* 主题色覆盖: {{ $lcColor }} */
:root { --primary: {{ $tc['main'] }} !important; }
/* 按钮 */
.btn-primary { background-color: {{ $tc['main'] }} !important; border-color: {{ $tc['main'] }} !important; }
.btn-primary:hover,.btn-primary:focus { background-color: {{ $tc['dark'] }} !important; border-color: {{ $tc['dark'] }} !important; }
.btn-trans.btn-primary { background-color: {{ $tc['bg-tint'] }} !important; border-color: {{ $tc['bg-tint'] }} !important; color: {{ $tc['main'] }} !important; }
.btn-primary.btn-outline { color: {{ $tc['main'] }} !important; border-color: {{ $tc['main'] }} !important; background: transparent !important; }
.btn-primary.btn-outline:hover { background: {{ $tc['rgba05'] }} !important; color: {{ $tc['main'] }} !important; }
/* 徽章 */
.badge-primary { background-color: {{ $tc['main'] }} !important; }
/* 进度条 */
#nprogress .bar { background: {{ $tc['rgba8'] }} !important; }
#nprogress .peg { box-shadow: 0 0 10px {{ $tc['main'] }}, 0 0 5px {{ $tc['main'] }} !important; }
#nprogress .spinner-icon { border-color: {{ $tc['main'] }} transparent transparent {{ $tc['main'] }} !important; }
/* 背景 */
.bg-primary { background-color: {{ $tc['main'] }} !important; }
.bg-primary-gradient { background-image: linear-gradient(60deg, {{ $tc['main'] }}, 0, {{ $tc['light'] }} 37%, {{ $tc['lighter'] }} 65%, {{ $tc['bg-tint'] }}) !important; }
/* 文字 */
.text-primary { color: {{ $tc['main'] }} !important; }
.text-primary-darker { color: {{ $tc['darker'] }} !important; }
/* 侧边栏 */
.main-sidebar.sidebar-primary { background-color: {{ $tc['main'] }} !important; }
.main-menu.menu-light .navigation>li.active>a { box-shadow: 0 0 4px 1px {{ $tc['rgba7'] }} !important; }
/* 导航标签 */
.nav.nav-tabs .nav-item .nav-link.active:after { box-shadow: 0 0 2px 0 {{ $tc['rgba5'] }} !important; }
.nav-theme-primary .nav.nav-tabs { background: {{ $tc['main'] }} !important; }
/* Select2 */
.select2-container--default .select2-search--dropdown .select2-search__field:focus { border-color: {{ $tc['main'] }} !important; }
.select2-container--default .select2-selection--multiple .select2-selection__choice { background-color: {{ $tc['main'] }} !important; }
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover { background-color: {{ $tc['main'] }} !important; }
/* 弹窗 */
.layui-layer-btn .layui-layer-btn0 { border-color: {{ $tc['main'] }} !important; background-color: {{ $tc['main'] }} !important; }
/* 滑动面板 */
.slider-panel .slider-toggle { background: {{ $tc['main'] }} !important; }
/* 提示框 */
.alert-primary { background: {{ $tc['rgba2'] }} !important; color: {{ $tc['darker'] }} !important; box-shadow: 0 2px 4px 0 rgba(0,0,0,.17) !important; }
.callout.callout-primary { border-left-color: {{ $tc['light'] }} !important; }
/* 暗黑模式适配 */
body.dark-mode a { color: {{ $tc['light'] }} !important; }
body.dark-mode a:hover { color: {{ $tc['main'] }} !important; }
body.dark-mode .grid-selector .select-options a.active { color: {{ $tc['main'] }} !important; }
body.dark-mode .nav-theme-primary .nav.nav-tabs .nav-item .nav-link.active,
body.dark-mode .nav-theme-white .nav.nav-tabs .nav-item .nav-link.active { color: {{ $tc['main'] }} !important; }
body.dark-mode .nav-theme-primary .nav.nav-tabs .nav-item .nav-link.active:after,
body.dark-mode .nav-theme-white .nav.nav-tabs .nav-item .nav-link.active:after { background: linear-gradient(30deg, {{ $tc['main'] }}, {{ $tc['rgba5'] }}) !important; }
body.dark-mode .dcat-step .done .dcat-step-icons { border-color: {{ $tc['main'] }} !important; }
body.dark-mode .dcat-step .done .dcat-step-icons>.dcat-step-icon { color: {{ $tc['main'] }} !important; }
body.dark-mode .grid-column-header a.active,
body.dark-mode .grid-column-header a:hover { color: {{ $tc['light'] }} !important; }
body.dark-mode.horizontal-menu a.nav-link.active p { color: {{ $tc['main'] }} !important; }
/* Logo颜色 */
.main-menu .navbar-header .logo-lg { color: {{ $tc['darker'] }} !important; }
.main-menu .navbar-header .logo-mini { color: {{ $tc['darker'] }} !important; }
/* 水平菜单 */
.horizontal-menu .nav-sidebar>.nav-item .nav-link.active,
.horizontal-menu .nav-sidebar>.nav-item .nav-link.active i { color: {{ $tc['main'] }} !important; }
/* 白色导航主题 */
.nav-theme-white .nav.nav-tabs .nav-item .nav-link.active { color: {{ $tc['main'] }} !important; }
.nav-theme-white .nav.nav-tabs .nav-item .nav-link.active:after { background: {{ $tc['main'] }} !important; }
/* 侧边栏激活项 */
.sidebar-light-primary .nav-item>.nav-link.active { color: {{ $tc['darker'] }} !important; }
[class*=sidebar-light-] .nav-treeview>.nav-item>.nav-link.active,
[class*=sidebar-light-] .nav-treeview>.nav-item>.nav-link.active:hover { color: {{ $tc['darker'] }} !important; }
/* Grid选择器 */
.grid-selector .select-options a.active { color: {{ $tc['darker'] }} !important; }
/* Column header */
.grid-column-header a.active,.grid-column-header a:hover { color: {{ $tc['darker'] }} !important; }
/* DateTime picker */
.bootstrap-datetimepicker-widget table td.active,
.bootstrap-datetimepicker-widget table td.active:hover { color: {{ $tc['darker'] }} !important; }
/* Code */
code { color: {{ $tc['darker'] }} !important; }
/* 布局配置面板跟随主题 */
.lc-radio-label:hover { border-color: {{ $tc['main'] }}; background: {{ $tc['bg-light'] }}; }
.lc-radio-label.active,.lc-radio-label:has(input:checked) { border-color: {{ $tc['main'] }}; background: {{ $tc['bg-light'] }}; }
.lc-input:focus { border-color: {{ $tc['main'] }}; }
.lc-panel-footer .btn-primary { background-color: {{ $tc['main'] }} !important; border-color: {{ $tc['main'] }} !important; }
</style>
@endif
@endif

{{-- 布局配置 JS --}}
<script>
(function() {
    var panel = document.getElementById('lc-config-panel');
    var overlay = document.getElementById('lc-overlay');
    var openBtns = document.querySelectorAll('.lc-open-trigger');
    var closeBtn = document.getElementById('lc-close-panel');
    var form = document.getElementById('lc-config-form');

    if (!panel || !form) return;

    function openPanel() {
        panel.classList.add('open');
        overlay.style.display = 'block';
    }
    function closePanel() {
        panel.classList.remove('open');
        overlay.style.display = 'none';
    }
    $('[data-tips="tooltip"]').tooltip();
    window.openModal = function(type, id) {
        var titleEl, contentEl, defaultTitle;
        if (type === 'h') {
            titleEl = document.querySelector('.h-title-' + id);
            contentEl = document.querySelector('.h-content-' + id);
            defaultTitle = '帮助信息';
        } else if (type === 'n') {
            titleEl = document.querySelector('.n-title-' + id);
            contentEl = document.querySelector('.n-content-' + id);
            defaultTitle = '通知详情';
        }
        var title = titleEl ? titleEl.textContent : defaultTitle;
        var content = contentEl ? contentEl.textContent : '';
        layer.open({
            type: 1,
            title: title || defaultTitle,
            content: '<div style="padding:20px;max-height:400px;overflow-y:auto;">' + content + '</div>',
            area: ['600px', '60%'],
            btn: ['关闭'],
            shadeClose: true
        });
    };

    openBtns.forEach(function(btn) {
        btn.addEventListener('click', openPanel);
    });
    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    if (overlay) overlay.addEventListener('click', closePanel);

    // 导航栏语言切换（点击即保存并刷新）
    document.querySelectorAll('.lc-locale-switch').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var locale = this.getAttribute('data-locale');
            if (!locale) return;
            var token = (typeof Dcat !== 'undefined' && Dcat.token) ? Dcat.token : '';
            var fd = new FormData();
            fd.append('locale', locale);
            fd.append('_token', token);
            fetch('{{ admin_url("layout-config/save") }}', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(d) { if (d.status) location.reload(); else alert(d.message || '切换失败'); })
            .catch(function() { alert('请求失败'); });
        });
    });

    // 通知项点击 → 弹窗详情 + 标记已读
    document.querySelectorAll('.lc-notification-item').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var id = this.getAttribute('data-id');
            var type = this.getAttribute('data-type') || 'n';
            if (!id) return;
            // 弹窗显示详情
            if (typeof window.openModal === 'function') {
                window.openModal(type, id);
            }
            // 后台静默标记已读
            var token = (typeof Dcat !== 'undefined' && Dcat.token) ? Dcat.token : '';
            var fd = new FormData();
            fd.append('_token', token);
            fetch('{{ admin_url("api/notifications") }}/' + id + '/read', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (!d.status) return;
                // 移除加粗样式
                el.classList.remove('font-weight-bold');
                // 更新未读数角标
                var badge = document.querySelector('.badge-up');
                if (badge) {
                    var count = parseInt(badge.textContent) - 1;
                    if (count > 0) {
                        badge.textContent = count;
                        var headerH3 = document.querySelector('.dropdown-header h3.white');
                        if (headerH3) headerH3.textContent = count;
                    } else {
                        badge.style.display = 'none';
                        var headerH3 = document.querySelector('.dropdown-header h3.white');
                        if (headerH3) headerH3.textContent = '0';
                    }
                }
            })
            .catch(function() {});
        });
    });

    // 全部已读
    document.querySelectorAll('.lc-read-all').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var token = (typeof Dcat !== 'undefined' && Dcat.token) ? Dcat.token : '';
            var fd = new FormData();
            fd.append('_token', token);
            fetch('{{ admin_url("api/notifications/read-all") }}', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(d) { if (d.status) location.reload(); })
            .catch(function() {});
        });
    });

    // 自动弹出未读通知 modal
    @if($lcShowNotification && $unreadCount > 0)
    (function autoPopupNotification() {
        var token = (typeof Dcat !== 'undefined' && Dcat.token) ? Dcat.token : '';
        fetch('{{ admin_url("api/notifications/first-unread") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            //Dcat.swal.warning('标题');return;
            if (!res.status || !res.data) return;
            var n = res.data;
            var typeColors = { info: '#17a2b8', warning: '#ffc107', success: '#28a745', danger: '#dc3545' };
            var modal = document.createElement('div');
            modal.innerHTML = '<div class="lc-noti-modal-overlay" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.4);z-index:9999;display:flex;align-items:center;justify-content:center;">'
                + '<div style="background:#fff;border-radius:8px;width:620px;max-width:90%;box-shadow:0 4px 24px rgba(0,0,0,0.2);overflow:hidden;">'
                + '<div style="padding:16px 20px;background:' + (typeColors[n.type] || '#17a2b8') + ';color:#fff;">'
                + '<h5 style="margin:0;font-size:16px;"><i class="feather icon-bell" style="margin-right:6px"></i>' + n.title + '</h5>'
                + '</div>'
                + '<div style="padding:20px;max-height:300px;overflow-y:auto;">'
                + '<p style="margin:0;color:#333;white-space:pre-wrap;">' + (n.content || '') + '</p>'
                + '<small style="color:#999;margin-top:12px;display:block;">' + n.created_at + '</small>'
                + '</div>'
                + '<div style="padding:12px 20px;border-top:1px solid #eee;text-align:right;">'
                + '<button type="button" class="btn btn-sm btn-primary lc-noti-read-btn" data-id="' + n.id + '"><i class="feather icon-check"></i> 已阅读</button>'
                + '</div>'
                + '</div></div>';
            document.body.appendChild(modal);
            modal.querySelector('.lc-noti-read-btn').addEventListener('click', function() {
                var btnId = this.getAttribute('data-id');
                var fd = new FormData();
                fd.append('_token', token);
                fetch('{{ admin_url("api/notifications") }}/' + btnId + '/read', {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(d) {
                    document.body.removeChild(modal);
                    if (d.status && d.unread_count > 0) {
                        // 还有未读，延迟1秒后再弹
                        setTimeout(autoPopupNotification, 1000);
                    } else if (d.status) {
                        location.reload();
                    }
                })
                .catch(function() { document.body.removeChild(modal); });
            });
            modal.querySelector('.lc-noti-modal-overlay').addEventListener('click', function(e) {
                if (e.target === this) document.body.removeChild(modal);
            });
        })
        .catch(function() {});
    })();
    @endif

    // iframe-tabs 模式切换：点击时先保存 active_mode 再跳转
    document.querySelectorAll('.lc-iframe-tabs-switch').forEach(function(el) {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            var targetMode = this.getAttribute('data-target-mode') || 'iframe-tabs';
            var href = this.getAttribute('href');
            var token = (typeof Dcat !== 'undefined' && Dcat.token) ? Dcat.token : '';
            var fd = new FormData();
            fd.append('active_mode', targetMode);
            fd.append('_token', token);
            fetch('{{ admin_url("layout-config/save") }}', {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function() {
                window.open(href, '_blank');
            }).catch(function() {
                window.open(href, '_blank');
            });
        });
    });

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var checkboxes = form.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(function(cb) {
            if (!cb.checked) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = cb.name;
                hidden.value = '0';
                form.appendChild(hidden);
            }
        });

        var formData = new FormData(form);
        var token = (typeof Dcat !== 'undefined' && Dcat.token) ? Dcat.token : '';

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            }
        })
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
            closePanel();
            if (data.status) {
                location.reload();
            } else {
                alert(data.message || '保存失败');
            }
        })
        .catch(function(err) {
            closePanel();
            alert('请求失败，请重试');
        });
    });
})();
</script>
{!! admin_section(Dcat\Admin\Admin::SECTION['NAVBAR_AFTER']) !!}