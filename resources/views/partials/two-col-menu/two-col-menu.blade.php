@php
    $defaultIcon = config('admin.menu.default_icon', 'feather icon-circle');
    // 将 AdminLTE sidebar 样式类映射为简化的主题名
    $rawSidebarStyle = $configData['sidebar_style'] ?? 'sidebar-dark-white';
    $sidebarThemeMap = [
        'sidebar-light-primary' => 'light',
        'sidebar-primary'       => 'primary',
        'sidebar-dark-white'    => 'dark',
        'sidebar-dark'          => 'dark',
        'light'                 => 'light',
        'primary'               => 'primary',
        'dark'                  => 'dark',
    ];
    $sidebarTheme = $sidebarThemeMap[$rawSidebarStyle] ?? 'dark';
@endphp

<div class="two-col-menu-wrapper {{ $sidebarTheme }}">
    <div class="two-col-container">
        {{-- 左栏：Logo + 图标导航 --}}
        <div class="two-col-icon-bar">
            <div class="two-col-logo">
                <a href="{{ admin_url('/') }}" class="two-col-logo-link">
                    <span class="logo-mini">{!! config('admin.logo-mini') !!}</span>
                </a>
            </div>
            <ul class="icon-nav">
                @foreach($menus as $item)
                    @if($builder->visible($item))
                        <li class="icon-nav-item" data-menu-id="{{ $item['id'] ?? '' }}">
                            <a href="javascript:void(0);"
                               class="icon-nav-link {!! $builder->isActive($item) ? 'active' : '' !!}"
                               data-id="{{ $item['id'] ?? '' }}"
                               title="{!! $builder->translate($item['title']) !!}">
                                <i class="{{ $item['icon'] ?: $defaultIcon }}"></i>
                                <span class="icon-nav-text">{!! $builder->translate($item['title']) !!}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>

        {{-- 右栏：子菜单详情 --}}
        <div class="two-col-detail-bar">
            @foreach($menus as $item)
                @if($builder->visible($item))
                    <div class="detail-panel {{ $builder->isActive($item) ? 'active' : '' }}"
                         data-panel-id="{{ $item['id'] ?? '' }}">
                        <div class="detail-panel-header">
                            <h4>{!! $builder->translate($item['title']) !!}</h4>
                        </div>
                        <ul class="detail-nav">
                            @if(!empty($item['children']))
                                @include('admin::partials.two-col-menu.sub-menu', [
                                    'items' => $item['children'],
                                    'builder' => $builder,
                                    'defaultIcon' => $defaultIcon
                                ])
                            @else
                                @include('admin::partials.two-col-menu.menu-item', [
                                    'item' => $item,
                                    'builder' => $builder,
                                    'defaultIcon' => $defaultIcon
                                ])
                            @endif
                        </ul>
                    </div>
                @endif
            @endforeach

            {{-- LEFT_SIDEBAR_MENU_TOP section --}}
            @if(trim(admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU_TOP'])))
                <div class="detail-panel detail-section" data-panel-id="__section_top__">
                    <div class="detail-panel-header"><h4>快捷操作</h4></div>
                    <div class="detail-section-content">
                        {!! admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU_TOP']) !!}
                    </div>
                </div>
            @endif

            {{-- LEFT_SIDEBAR_MENU_BOTTOM section --}}
            @if(trim(admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU_BOTTOM'])))
                <div class="detail-panel detail-section" data-panel-id="__section_bottom__">
                    <div class="detail-panel-header"><h4>更多</h4></div>
                    <div class="detail-section-content">
                        {!! admin_section(Dcat\Admin\Admin::SECTION['LEFT_SIDEBAR_MENU_BOTTOM']) !!}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>