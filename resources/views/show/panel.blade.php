@if($title || $tools)
<div class="box-header with-border" style="padding: .65rem 1rem">
    <h3 class="box-title" style="line-height:30px;">{!! $title !!}</h3>
    <div class="pull-right">{!! $tools !!}</div>
</div>
@endif
<div class="box-body">
    <div class="form-horizontal mt-1">
        @if($rows->isEmpty())
            @foreach($fields as $field)
                {!! $field->render() !!}
            @endforeach
        @else
            <div>
                @foreach($rows as $row)
                    {!! $row->render() !!}
                @endforeach
            </div>
        @endif
        @if($tabs->isNotEmpty())
            <ul class="nav nav-tabs show-detail-tabs" role="tablist">
                @foreach($tabs as $index => $tab)
                    <li class="nav-item">
                        <a class="nav-link {{ $index === 0 ? 'active' : '' }}" data-toggle="tab" href="#{{ $tab['id'] }}" role="tab">{{ $tab['title'] }}</a>
                    </li>
                @endforeach
            </ul>
            <div class="tab-content">
                @foreach($tabs as $index => $tab)
                    <div class="tab-pane fade {{ $index === 0 ? 'show active' : '' }}" id="{{ $tab['id'] }}" role="tabpanel">
                        @if($tab['rows']->isEmpty())
                            @foreach($tab['fields'] as $field)
                                {!! $field->render() !!}
                            @endforeach
                        @else
                            @foreach($tab['rows'] as $row)
                                {!! $row->render() !!}
                            @endforeach
                        @endif
                        @foreach($tab['relations'] as $relation)
                            {!! $relation->render() !!}
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
        <div class="clearfix"></div>
    </div>
</div>
