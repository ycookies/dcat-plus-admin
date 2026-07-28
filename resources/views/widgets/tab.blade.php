<div {!! $attributes !!}>
    <ul class="nav nav-tabs {{ $tabStyle }}" role="tablist">
        @foreach($tabs as $index => $tab)
            @if($tab['type'] == \Dcat\Admin\Widgets\Tab::TYPE_CONTENT)
                <li class="nav-item" >
                    <a href="#tab_{{ $tab['id'] }}" class=" nav-link  {{ $index == $active ? 'active' : '' }}" data-toggle="tab">{!! $tab['icon'] ?? '' !!} {!! $tab['title'] !!}</a>
                </li>
            @elseif($tab['type'] == \Dcat\Admin\Widgets\Tab::TYPE_LINK)
                <li class="nav-item" >
                    <a href="{{ $tab['href'] }}" class=" nav-link  {{ $index == $active ? 'active' : '' }}"> {!! $tab['icon'] ?? '' !!} {!! $tab['title'] !!}</a>
                </li>
            @endif
        @endforeach

        @if (!empty($dropDown))
            <li class="dropdown nav-item">
                <a class="dropdown-toggle nav-link" data-toggle="dropdown" href="#">
                    Dropdown <span class="caret"></span>
                </a>
                <ul class="dropdown-menu">
                    @foreach($dropDown as $link)
                        <li role="presentation" class="dropdown-item"><a role="menuitem" tabindex="-1" href="{{ $link['href'] }}">{!! $link['name'] !!}</a></li>
                    @endforeach
                </ul>
            </li>
        @endif
        <li class="nav-item pull-right header">{!! $title !!}</li>
    </ul>

    <div class="tab-content" style="{!! $padding !!}">
        @foreach($tabs as $index => $tab)
            <div class="tab-pane {{ $index == $active ? 'active' : '' }}" id="tab_{{ $tab['id'] }}">
                {!! $tab['content'] ?? '' !!}
            </div>
        @endforeach

    </div>
</div>