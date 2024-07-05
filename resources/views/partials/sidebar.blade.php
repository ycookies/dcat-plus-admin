<div class="{{ $configData['horizontal_menu'] ? 'header-navbar navbar-expand-sm navbar navbar-horizontal' : 'main-menu' }}">
    <div class="main-menu-content">
        <aside class="{{ $configData['horizontal_menu'] ? 'main-horizontal-sidebar' : 'main-sidebar shadow' }} {{ $configData['sidebar_style'] }} main-sidebar-custom">

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

            <div class="p-0 {{ $configData['horizontal_menu'] ? 'pl-1 pr-1' : 'sidebar pb-3' }}">
                <ul class="nav nav-pills nav-sidebar {{ $configData['horizontal_menu'] ? '' : 'flex-column' }}"
                    {!! $configData['horizontal_menu'] ? '' : 'data-widget="treeview"' !!}
                     style="padding-top: 10px">
                    {!! admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU_TOP']) !!}

                    {!! admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU']) !!}

                    {!! admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU_BOTTOM']) !!}
                </ul>
                <div class="sidebar-custom">{{-- menu-footer--}}
                    <a class="btn btn-app tips0" data-title="这是一个提示0">
                        <i class="fa fa-edit"></i>
                    </a>
                    <a class="btn btn-app tips1">
                        <span class="badge bg-purple">891</span>
                        <i class="fa fa-chrome"></i>
                    </a>
                    <a class="btn btn-app tips2">
                        <i class="fa fa-television"></i>
                    </a>
                    <a class="btn btn-app tips3" data-title="这是一个提示">
                        <i class="fa fa-book"></i>
                    </a>
                    <a class="btn btn-app">
                        <i class="fa fa-calendar"></i>
                    </a>
                    <a class="btn btn-app">
                        <i class="fa fa-cubes"></i>
                    </a>
                </div>
            </div>
        </aside>

    </div>
</div>