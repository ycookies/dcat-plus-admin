<style>
    .timeline-box .timeline>div>.timeline-item{
        box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
    }
    .timeline-box ul{
        padding-left: 20px;
    }
    .timeline-box li{
        list-style-type: disc;
    }
</style>
<div class="timeline-box" id="{{$id}}">
    <div class="timeline">
        @foreach($items as $key => $item)
            @if(!empty($item['time_label']))
                <div class="time-label">
                    <span class="bg-green">{{$item['time_label']}}</span>
                </div>
            @endif
        <div>
            <i class="{{$item['icon']}}"></i>
            <div class="timeline-item">
                <span class="time"><i class="fas fa-clock"></i>{{$item['time']}}</span>
                <h3 class="timeline-header">{{$item['title']}}</h3>
                <div class="timeline-body">
                    {!! $item['content'] !!}
                </div>
                {{--<div class="timeline-footer">
                    <a class="btn btn-primary btn-sm">已读</a>
                    <a class="btn btn-danger btn-sm">删除</a>
                </div>--}}
            </div>
        </div>
        @endforeach
        <div>
            <i class="fa fa-clock-o"></i>
        </div>
    </div>
</div>