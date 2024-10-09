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
</style>
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
                                        <a href="{{$configData['home_url']}}" target="_blank" class="nav-link"><i class="fa fa-home f18"></i></a>
                                    @endif

                                    @if(isset($configData['full_screen']) && $configData['full_screen'])
                                        <a href="javascript:;"  data-check-screen="full" class="nav-link"><i class="feather icon-maximize f16"></i></a>
                                    @endif
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
