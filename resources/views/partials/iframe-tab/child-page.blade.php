@php
    $adminPrefix = trim((string) config('admin.route.prefix', 'admin'), '/');
    $adminPrefixPath = $adminPrefix === '' ? '/' : '/'.$adminPrefix;
    $shellPath = trim((string) config('admin.iframe_tab.shell_path', 'iframe-tabs'), '/');
    $childConfig = [
        'adminPrefix' => $adminPrefixPath,
        'queryKey' => (string) config('admin.iframe_tab.query_key', 'iframe_tab'),
        'shellPath' => '/'.trim($adminPrefix.'/'.$shellPath, '/'),
        'forceTopPathKeywords' => ['auth/login', 'auth/logout', 'mobile/benefit/redeem'],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="chrome=1,IE=edge">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">

    <title>@if(! empty($header)){{ $header }} | @endif {{ Dcat\Admin\Admin::title() }}</title>

    @if(! config('admin.disable_no_referrer_meta'))
        <meta name="referrer" content="no-referrer"/>
    @endif

    @if(! empty($favicon = Dcat\Admin\Admin::favicon()))
        <link rel="shortcut icon" href="{{ $favicon }}">
    @endif

    {!! admin_section(Dcat\Admin\Admin::SECTION['HEAD']) !!}

    {!! Dcat\Admin\Admin::asset()->headerJsToHtml() !!}

    {!! Dcat\Admin\Admin::asset()->cssToHtml() !!}
    <link rel="stylesheet" href="{{ asset('/vendor/dcat-admin/dcat/plugins/admin-iframe-tab/css/admin-iframe-tab.css') }}?v={{ config('admin.iframe_tab.asset_version', '1.0.0') }}">
</head>

<body class="dcat-admin-body full-page admin-iframe-tab-child {{ $configData['body_class'] }}">

<script>
    var Dcat = CreateDcat({!! Dcat\Admin\Admin::jsVariables() !!});
</script>

{!! admin_section(Dcat\Admin\Admin::SECTION['BODY_INNER_BEFORE']) !!}

<div class="app-content content">
    <div class="wrapper" id="{{ $pjaxContainerId }}">
        @yield('app')
    </div>
</div>

{!! admin_section(Dcat\Admin\Admin::SECTION['BODY_INNER_AFTER']) !!}

{!! Dcat\Admin\Admin::asset()->jsToHtml() !!}

<script>Dcat.boot();</script>
<script src="{{ asset('/vendor/dcat-admin/dcat/plugins/admin-iframe-tab/js/admin-iframe-tab-child.js') }}?v={{ config('admin.iframe_tab.asset_version', '1.0.0') }}"></script>
<script>
    Dcat.ready(function () {
        window.AdminIframeTabChild && window.AdminIframeTabChild.init(@json($childConfig));
    });
</script>

</body>
</html>
