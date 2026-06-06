@foreach($items as $item)
    @if($builder->visible($item))
        @if(!empty($item['children']))
            <li class="detail-nav-item has-children {{ $builder->isActive($item) ? 'menu-open' : '' }}" data-tree-id="{{ $item['id'] }}">
                <a href="javascript:void(0);"
                   class="detail-nav-link {{ $builder->isActive($item) ? 'active' : '' }}">
                    <span class="link-content">
                        <i class="link-icon {{ $item['icon'] ?: ($defaultIcon ?? 'feather icon-circle') }}"></i>
                        <span class="link-text">{!! $builder->translate($item['title']) !!}</span>
                    </span>
                    <i class="link-arrow fa fa-angle-left"></i>
                </a>
                <ul class="detail-sub-nav" style="{{ $builder->isActive($item) ? 'display:block' : 'display:none' }}">
                    @foreach($item['children'] as $child)
                        @if($builder->visible($child))
                            @if(!empty($child['children']))
                                @include('admin::partials.two-col-menu.sub-menu', [
                                    'items' => [$child],
                                    'builder' => $builder,
                                    'defaultIcon' => $defaultIcon ?? 'feather icon-circle'
                                ])
                            @else
                                @include('admin::partials.two-col-menu.menu-item', [
                                    'item' => $child,
                                    'builder' => $builder,
                                    'defaultIcon' => $defaultIcon ?? 'feather icon-circle'
                                ])
                            @endif
                        @endif
                    @endforeach
                </ul>
            </li>
        @else
            @include('admin::partials.two-col-menu.menu-item', [
                'item' => $item,
                'builder' => $builder,
                'defaultIcon' => $defaultIcon ?? 'feather icon-circle'
            ])
        @endif
    @endif
@endforeach