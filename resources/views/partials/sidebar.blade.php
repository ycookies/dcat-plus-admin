<style>
    .main-horizontal-sidebar .user-nav {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        float: left;
        margin-right: .8rem;
    }

    .main-horizontal-sidebar .dropdown-item {
        padding: 10px !important;
    }
    .main-horizontal-sidebar ul.nav li .badge{
        padding: .42em .6em .25rem;
    }
    .main-horizontal-sidebar li.dropdown .dropdown-menu{
        top:48px
    }
    .main-horizontal-sidebar ul.nav li .badge.badge-up{
        position: absolute;
        top: 12px;
        right: -2px;
    }
    .dropdown-menu-media{
        width: 26rem;
    }
    .fs18{
        font-size: 18px !important;
    }
</style>
{{-- 布局配置变量（所有模式共用） --}}
@php
    $savedLayout = [];
    try { $savedLayout = admin_setting_group('layout_config') ?: []; } catch (\Throwable) {}
    $lcColor = $savedLayout['color'] ?? config('admin.layout.color', 'default');
    $lcSidebarStyle = $savedLayout['sidebar_style'] ?? config('admin.layout.sidebar_style', 'light');
    $lcNavbarColor = $savedLayout['navbar_color'] ?? config('admin.layout.navbar_color', '');
    $lcHorizontalMenu = isset($savedLayout['horizontal_menu']) ? (bool)$savedLayout['horizontal_menu'] : config('admin.layout.horizontal_menu', false);
    $lcSidebarCollapsed = isset($savedLayout['sidebar_collapsed']) ? (bool)$savedLayout['sidebar_collapsed'] : config('admin.layout.sidebar_collapsed', false);
    $lcDarkModeSwitch = isset($savedLayout['dark_mode_switch']) ? (bool)$savedLayout['dark_mode_switch'] : config('admin.layout.dark_mode_switch', false);
    $lcFullScreen = isset($savedLayout['full_screen']) ? (bool)$savedLayout['full_screen'] : config('admin.layout.full_screen', true);
    $lcHomeUrl = $savedLayout['home_url'] ?? config('admin.layout.home_url', '');
    $lcLocale = $savedLayout['locale'] ?? config('app.locale', 'zh_CN');
    $lcShowLocaleSwitch = isset($savedLayout['show_locale_switch']) ? (bool)$savedLayout['show_locale_switch'] : true;
    $lcShowHelp = isset($savedLayout['show_help']) ? (bool)$savedLayout['show_help'] : true;
    $lcShowNotification = isset($savedLayout['show_notification']) ? (bool)$savedLayout['show_notification'] : true;

    // 获取帮助数据（分类+内容）
    $helpCategories = [];
    $helplist = [];
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
<div class="{{ $configData['horizontal_menu'] ? 'header-navbar navbar-expand-sm navbar navbar-horizontal' : 'main-menu' }}">
    <div class="main-menu-content">
        <aside class="{{ $configData['horizontal_menu'] ? 'main-horizontal-sidebar' : 'main-sidebar shadow' }} {{ $configData['sidebar_style'] }}">

            @if(! $configData['horizontal_menu'])
                <div class="navbar-header">
                    <ul class="nav navbar-nav flex-row">
                        <li class="nav-item mr-auto">
                            <a href="{{ admin_url('/') }}" class="navbar-brand waves-effect waves-light">
                                <span class="logo-mini">{!! config('admin.logo-mini') !!}</span>
                                <span class="logo-lg">{!! config('admin.logo') !!}</span>
                            </a>
                        </li>
                    </ul>
                </div>
            @endif

            <div class="p-0 {{ $configData['horizontal_menu'] ? 'pl-2 pr-2' : 'sidebar pb-3' }}">
                @if(!empty($configData['horizontal_menu']))
                    <div class="navbar-wrapper">
                        <div class=" content">
                            <div class="navbar-collapse d-flex justify-content-between">
                                <div class="navbar-left d-flex align-items-center">
                                    <ul class="nav  nav-pills nav-sidebar {{ $configData['horizontal_menu'] ? '' : 'flex-column' }}"
                                        {!! $configData['horizontal_menu'] ? '' : 'data-widget="treeview"' !!}
                                        style="padding-top: 10px">
                                        @if($configData['horizontal_menu'])
                                            <li class="nav-item" style="margin-right: 50px">
                                                <a href="{{ admin_url('/') }}" class="waves-effect waves-light">
                                                    <span class="logo-lg">{!! config('admin.logo') !!}</span>
                                                </a>
                                            </li>
                                        @endif
                                        {!! admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU_TOP']) !!}

                                        {!! admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU']) !!}

                                        {!! admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU_BOTTOM']) !!}
                                    </ul>
                                </div>
                                <div class="navbar-right d-flex align-items-center">
                                    {!! Dcat\Admin\Admin::navbar()->render() !!}
                                    

                                    @if(!empty($configData['home_url']))
                                    <li class="nav-item">
                                            <a href="{{$configData['home_url']}}"  target="_blank" class="nav-link" data-tips="tooltip" data-title="网站首页" data-placement="bottom"><i class="fa fa-home fs18"></i></a> 
                                    </li>
                                    @endif
                                    {{--help 帮助信息--}}
                                    @if($lcShowHelp && $helplist->isNotEmpty())
                                    <li class="dropdown dropdown-notification nav-item">
                                        <a class="nav-link nav-link-label " href="#" data-tips="tooltip" data-title="帮助信息" data-placement="bottom" data-toggle="dropdown" aria-expanded="true">
                                            <i class="feather icon-help-circle fs18"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-media dropdown-menu-right shadow-200">
                                            
                                                @foreach($helplist as $help)
                                                <li class="scrollable-container media-list">
                                                    @if($help->link)
                                                    <a style="padding: 5px 10px !important;" class="media d-flex justify-content-between" href="{{ $help->link }}" target="{{ $help->link_target }}">
                                                    @else
                                                    <a style="padding: 5px 10px !important;" class="media d-flex justify-content-between" href="javascript:void(0)" onclick="openModal('h',{{ $help->id }})" data-id="{{ $help->id }}">
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
                                        <a class="nav-link nav-link-label" href="#" data-tips="tooltip" data-title="通知" data-placement="bottom" data-toggle="dropdown" aria-expanded="true">
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
                                                        <a style="padding: 5px 10px !important;" class="media d-flex justify-content-between lc-notification-item {{ $notification->is_read ? '' : 'font-weight-bold' }}"
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
                                        <a href="javascript:;"  data-check-screen="full" class="nav-link" data-tips="tooltip" data-title="全屏" data-placement="bottom">
                                            <i class="feather icon-maximize fs18"></i>
                                        </a>
                                        @endif
                                    </li>
                                    {{-- 暗黑模式切换 --}}
                                    <li class="nav-item">
                                        <a href="javascript:void(0);"  class="dark-mode-switcher nav-link" style="padding:1rem 0rem 1.35rem .5rem !important;">
                                            <i data-tips="tooltip" data-title="切换暗黑模式" data-placement="bottom" class="feather {{ config('admin.layout.dark_mode_switch') ? 'icon-moon' : 'icon-sun' }} fs18"></i>
                                        </a>

                                        <script>
                                        Dcat.darkMode.initSwitcher('.dark-mode-switcher');
                                        </script>
                                    </li>
                                    @if(isset($configData['show_locale_switch']) && $configData['show_locale_switch'])
                                    <li class="dropdown nav-item">
                                        <a class="nav-link nav-link-label"  href="#" data-toggle="dropdown" style="padding:1.5rem .5rem 1.35rem .5rem !important;">
                                            <i class="flag-icon @if($lcLocale === 'zh_CN') flag-icon-cn @elseif($lcLocale === 'zh_TW') flag-icon-tw @else flag-icon-us @endif" style="margin-right:4px"></i>
                                            <span class="d-none d-sm-inline">
                                                <i data-tips="tooltip" data-title="切换语言" data-placement="bottom" class="fa fa-language fs18"></i>
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
                                    

                                    <li class="nav-item">
                                        {{-- 布局配置按钮（导航栏内） --}}
                                        <a href="javascript:void(0);" class="nav-link lc-open-trigger" >
                                            <i data-tips="tooltip" data-title="布局配置" data-placement="bottom" class="feather icon-settings fs18"></i>
                                        </a>
                                    </li>

                                    <ul class="nav navbar-nav">
                                        {{--User Account Menu--}}
                                        {!! admin_section(Dcat\Admin\Admin::SECTION['NAVBAR_USER_PANEL']) !!}

                                        {!! admin_section(Dcat\Admin\Admin::SECTION['NAVBAR_AFTER_USER_PANEL']) !!}
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <ul class="nav nav-pills nav-sidebar {{ $configData['horizontal_menu'] ? '' : 'flex-column' }}"
                        {!! $configData['horizontal_menu'] ? '' : 'data-widget="treeview"' !!}
                        style="padding-top: 10px">
                        {!! admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU_TOP']) !!}

                        {!! admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU']) !!}

                        {!! admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU_BOTTOM']) !!}
                    </ul>
                @endif
            </div>
        </aside>
    </div>
</div>

<script>
(function() {
    $('[data-tips="tooltip"]').tooltip();
    })();
</script>
