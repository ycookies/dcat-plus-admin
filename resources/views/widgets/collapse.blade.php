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

<div class="accordion" {!! $attributes !!} id="accordionExample">
    @foreach($items as $key => $item)
    <div class="card">
        <div class="card-header" id="heading{{ $key }}">
            <h2 class="mb-0">
                <a class="text-left collapsed" href="javascript:void(0);"  data-toggle="collapse" data-target="#collapse{{ $key }}" aria-expanded="false" aria-controls="collapse{{ $key }}">
                    {{ $item['title'] }}
                </a>
            </h2>
        </div>

        <div id="collapse{{ $key }}" class="collapse" aria-labelledby="headingOne" data-parent="#accordionExample" style="">
            <div class="card-body">
                {!! $item['content'] !!}
            </div>
        </div>
    </div>
    @endforeach
</div>