@php
    // 顶栏一级菜单（仅在 menu_style = top_side_menu 时由 navbar.blade.php 引入）
    // 数据与 sidebar.blade.php 的 top_side 分支同源：Menu::getMergedNodes()（单例内已缓存）
    $tsBuilder = \Dcat\Admin\Admin::menu();
    $tsMenus = \Dcat\Admin\Support\Helper::buildNestedArray($tsBuilder->getMergedNodes());
    $tsActiveId = $tsBuilder->inferActiveItemId($tsMenus);
    $tsDefaultIcon = config('admin.menu.default_icon', 'feather icon-circle');
@endphp
<ul class="nav navbar-nav ts-top-menu">
    @foreach($tsMenus as $item)
        @continue(! $tsBuilder->visible($item))
        {{-- 顶栏只展示 parent_id=0 的一级菜单；所有项均为纯切换链接（与 learnku 示例同款 void(0)，pjax 安全），点击只切换左侧对应子菜单面板 --}}
        <li class="nav-item ts-top-item {{ $item['id'] == $tsActiveId ? 'active' : '' }}" data-id="{{ $item['id'] }}">
            <a class="nav-link ts-top-link" href="javascript:void(0);" data-id="{{ $item['id'] }}">
                <i class="{{ $item['icon'] ?: $tsDefaultIcon }}"></i>
                <span class="ts-top-label" title="{{ $tsBuilder->translate($item['title']) }}">{{ $tsBuilder->translate($item['title']) }}</span>
            </a>
        </li>
    @endforeach
</ul>
