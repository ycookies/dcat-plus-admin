{{--<div {!! $attributes !!}>
    @foreach($items as $key => $item)
    <div class="panel card card-@color" style="margin-bottom: 0px">
        <div class="card-header">
            <h4 class="card-title">
                <a data-toggle="collapse" data-parent="#{{$id}}" href="#collapse{{ $key }}">
                    {{ $item['title'] }}
                </a>
            </h4>
        </div>
        <div id="collapse{{ $key }}" class="panel-collapse collapse {{ $key == 0 ? 'in' : '' }}">
            <div class="card-body">
                {!! $item['content'] !!}
            </div>
        </div>
    </div>
    @endforeach
</div>--}}
<div {!! $attributes !!} style="margin-bottom: 20px">
    <ul class="list-group">
        @foreach($items as $key => $item)
        <li class="list-group-item d-flex justify-content-between align-items-center">
            {{ $item['title'] }}
            <span class="badge badge-primary badge-pill">{{ $item['content'] }}</span>
        </li>
        @endforeach
    </ul>
</div>
