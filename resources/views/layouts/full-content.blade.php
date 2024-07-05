@section('content')
    <section class="content">
        @include('admin::partials.alerts')
        @include('admin::partials.exception')

        {!! $content !!}

        @include('admin::partials.toastr')
    </section>
@endsection

@section('app')
    {!! Dcatplus\Admin\Admin::asset()->styleToHtml() !!}

    <div class="content-body" id="app">
        {{-- 页面埋点--}}
        {!! admin_section(Dcatplus\Admin\Admin::SECTION['APP_INNER_BEFORE']) !!}

        @yield('content')

        {{-- 页面埋点--}}
        {!! admin_section(Dcatplus\Admin\Admin::SECTION['APP_INNER_AFTER']) !!}
    </div>

    {!! Dcatplus\Admin\Admin::asset()->scriptToHtml() !!}
    <div class="extra-html">{!! Dcatplus\Admin\Admin::html() !!}</div>
@endsection


@if(!request()->pjax())
    @include('admin::layouts.full-page', ['header' => $header])
@else
    <title>{{ Dcatplus\Admin\Admin::title() }} @if($header) | {{ $header }}@endif</title>

    <script>Dcat.wait();</script>

    {!! Dcatplus\Admin\Admin::asset()->cssToHtml() !!}
    {!! Dcatplus\Admin\Admin::asset()->jsToHtml() !!}

    @yield('app')
@endif
