@section('content')
    @include('admin::partials.alerts')
    @include('admin::partials.exception')

    {!! $content !!}

    @include('admin::partials.toastr')
@endsection

@section('app')
    {!! Dcat\Admin\Admin::asset()->styleToHtml() !!}

    <div class="admin-iframe-tab-shell" id="admin-iframe-tab-shell">
        <div class="admin-iframe-tab-chrome">
            <div class="admin-iframe-tab-tabs" id="admin-iframe-tab-tabs" role="tablist"></div>

            <div class="admin-iframe-tab-toolbar" aria-label="标签页操作">
                <button type="button" class="admin-iframe-tab-tool" data-iframe-tab-action="reload" title="刷新当前标签">
                    <i class="feather icon-refresh-cw"></i>
                </button>
                <button type="button" class="admin-iframe-tab-tool" data-iframe-tab-action="close-other" title="关闭其他标签">
                    <i class="feather icon-copy"></i>
                </button>
                <button type="button" class="admin-iframe-tab-tool" data-iframe-tab-action="close-all" title="关闭全部标签">
                    <i class="feather icon-x-square"></i>
                </button>
                <button type="button" class="admin-iframe-tab-tool" data-iframe-tab-action="open-new" title="新窗口打开当前页">
                    <i class="feather icon-external-link"></i>
                </button>
            </div>
        </div>

        <div class="admin-iframe-tab-panels" id="admin-iframe-tab-panels"></div>
    </div>

    {!! Dcat\Admin\Admin::asset()->scriptToHtml() !!}
    <div class="extra-html">{!! Dcat\Admin\Admin::html() !!}</div>
@endsection

@if(! request()->pjax())
    @include('admin::partials.iframe-tab.shell-page', ['header' => $header])
@else
    <title>{{ Dcat\Admin\Admin::title() }} @if($header) | {{ $header }}@endif</title>

    <script>Dcat.wait();</script>

    {!! Dcat\Admin\Admin::asset()->cssToHtml() !!}
    {!! Dcat\Admin\Admin::asset()->jsToHtml() !!}

    @yield('app')
@endif
