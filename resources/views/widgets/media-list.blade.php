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
{{--<div {!! $attributes !!}>
    <ul class="list-group">
        @foreach($items as $key => $item)
        <li class="list-group-item">
            {{ $item['title'] }}
            <span class="badge badge-primary badge-pill">14</span>
        </li>
        @endforeach
    </ul>
</div>--}}
<ul class="list-unstyled media-list">
    @foreach($items as $key => $item)
        <li class="media">
            <a href="{{$item['link'] ?? 'javascript:void(0);'}}}}" target="{{$target}}">
                <img src="{{$item['media_img']}}" style="max-width: {{$max_width}};" class="{{$img_center}} mr-3"
                     alt="...">
            </a>
            <div class="media-body">
                <a href="{{$item['link'] ?? 'javascript:void(0);'}}}}" target="{{$target}}">
                    <h5 class="mt-0">{{$item['title']}}</h5>
                </a>
                <p class="text-line" style="-webkit-line-clamp:{{$row_num ?? 3}}">{{$item['content']}}</p>

            </div>
        </li>
    @endforeach
</ul>

