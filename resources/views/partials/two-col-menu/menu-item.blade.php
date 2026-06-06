@if($builder->visible($item))
    <li class="detail-nav-item">
        <a href="{{ $builder->getUrl($item['uri'] ?? '') }}"
           @if(mb_strpos($item['uri'] ?? '', '://') !== false) target="_blank" @endif
           class="detail-nav-link {!! $builder->isActive($item) ? 'active' : '' !!}"
           data-id="{{ $item['id'] ?? '' }}">
            <span class="link-content">
                <i class="link-icon fa fa-fw {{ $item['icon'] ?: ($defaultIcon ?? 'feather icon-circle') }}"></i>
                <span class="link-text">{!! $builder->translate($item['title']) !!}</span>
            </span>
        </a>
    </li>
@endif