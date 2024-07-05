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
<div {!! $attributes !!}>
    <div class="btn-group">
        <button type="button" class="btn btn-secondary">Left</button>
        <button type="button" class="btn btn-secondary">Middle</button>
        <button type="button" class="btn btn-secondary">Right</button>
    </div>

    <div class="btn-group-vertical">
        <button type="button" class="btn btn-secondary">Left</button>
        <button type="button" class="btn btn-secondary">Middle</button>
        <button type="button" class="btn btn-secondary">Right</button>
    </div>
</div>

<div>
    <div class="btn-group-vertical" role="group" aria-label="Vertical button group">
        <button type="button" class="btn btn-secondary">Button</button>
        <button type="button" class="btn btn-secondary">Button</button>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                Dropdown
            </button>
            <div class="dropdown-menu" style="">
                <a class="dropdown-item" href="#">Dropdown link</a>
                <a class="dropdown-item" href="#">Dropdown link</a>
            </div>
        </div>
        <button type="button" class="btn btn-secondary">Button</button>
        <button type="button" class="btn btn-secondary">Button</button>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                Dropdown
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="#">Dropdown link</a>
                <a class="dropdown-item" href="#">Dropdown link</a>
            </div>
        </div>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                Dropdown
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="#">Dropdown link</a>
                <a class="dropdown-item" href="#">Dropdown link</a>
            </div>
        </div>
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                Dropdown
            </button>
            <div class="dropdown-menu">
                <a class="dropdown-item" href="#">Dropdown link</a>
                <a class="dropdown-item" href="#">Dropdown link</a>
            </div>
        </div>
    </div>
</div>

<br/><br/>
<div>
    <button type="button" class="btn btn-primary" id="liveToastBtn">Show success toast</button>
    <button type="button" class="btn btn-primary" id="warningToastBtn">Show warning toast</button>

    <button type="button" class="btn btn-primary" id="infoToastBtn">Show info toast</button>

    <div class="position-fixed bottom-0 right-0 p-3" style="z-index: 5; right: 0; bottom: 0;">
        <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-autohide="false">
            <div class="toast-header">
                <i class="feather icon-alert-octagon"></i>
                <strong class="mr-auto">Bootstrap</strong>
                <small>11 mins ago</small>
                <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="toast-body">
                Hello, world! This is a toast message.
            </div>
        </div>
    </div>
</div>
<script>
    $('#liveToastBtn').on('click',function (e) {
        //$('#liveToast').toast('show');

        Dcat.success('更新成功', 'Hello, world! This is a toast message.', {
            timeOut: 5000, // 5秒后自动消失
        });
    })
    $('#warningToastBtn').on('click',function (e) {
        //$('#liveToast').toast('show');

        Dcat.warning('更新失败', 'Hello, world! This is a toast message.', {
            timeOut: 5000, // 5秒后自动消失
        });
    })
    $('#infoToastBtn').on('click',function (e) {
        //$('#liveToast').toast('show');

        Dcat.info('用户消息', 'Hello, world! This is a toast message.', {
            timeOut: 5000, // 5秒后自动消失
        });
    })

</script>