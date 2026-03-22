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

<style>
    .link-box .info-box{
        margin-bottom:15px;
        min-height: 50px;
    }
    .link-box .info-box-icon{
        width: 50px;
        height: 50px;
    }
    .link-box .info-box-title{
        font-size: 16px;
        color: #1d2129;
    }
    .link-box .info-box-content{
        display: flex;
        align-items: center;
    }
    .link-box .link-box-title{
        font-size: 18px;
        font-weight: bold;
        padding-left: 15px;
        border-left: 3px solid green;
        margin: 20px 10px;
    }
    .link-box .info-box-number{
        font-size: 16px;
        color: #1d2129;
    }
    .link-box .info-box-text{
        color: #c1c1c1;
    }
</style>
<div class="link-box {{ $id }}">
    <div class="link-box-title">{!! $group_title !!}</div>
    <div class="row">
        @foreach($items as $key => $item)
        <div class="col-md-3 col-sm-6 col-12">
            <div class="info-box shadow-x">
                @if(isset($item['hot']) && $item['hot'] === true)
                <div class="ribbon ribbon-top ribbon-bookmark bg-green">
                    <!-- Download SVG icon from http://tabler-icons.io/i/star -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-filled" width="24" height="24"
                         viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z"></path>
                    </svg>
                </div>
                @endif
            <span class="info-box-icon {{$item['bg_value'] ?? 'bg-info'}}">
                <i class="{{$item['icon']}}"></i></span>
                <a class="info-box-content" target="{{$target}}"  @if(isset($item['sub_title'])) style="align-items:normal;flex-direction:column;justify-content:center" @endif href="{{$item['link']}}">
                    @if(isset($item['sub_title']))
                        <span class="info-box-number">{{$item['title']}}</span>
                        <span class="info-box-text">{{$item['sub_title']}}</span>
                    @else
                        <span class="info-box-title">{{$item['title']}}</span>
                    @endif
                </a>
            </div>
        </div>
        @endforeach
    </div>
</div>