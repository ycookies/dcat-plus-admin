@php
    $adminPrefix = trim((string) config('admin.route.prefix', 'admin'), '/');
    $adminPrefixPath = $adminPrefix === '' ? '/' : '/'.$adminPrefix;
    $shellPath = trim((string) config('admin.iframe_tab.shell_path', 'dcat-sys/iframe-tabs'), '/');
    $homePath = trim((string) config('admin.iframe_tab.home_path', '/'), '/');
    $homeUrl = admin_url($homePath);
    $footer = config('admin.iframe_tab.footer', []);
    $iframeTabConfig = [
        'adminPrefix' => $adminPrefixPath,
        'shellUrl' => admin_url($shellPath),
        'homeUrl' => $homeUrl,
        'homeTitle' => (string) config('admin.iframe_tab.home_title', '首页'),
        'queryKey' => (string) config('admin.iframe_tab.query_key', 'iframe_tab'),
        'cache' => (bool) config('admin.iframe_tab.cache', false),
        'lazyLoad' => (bool) config('admin.iframe_tab.lazy_load', true),
        'userId' => optional(Dcat\Admin\Admin::user())->id ?: 0,
        'forceTopPathKeywords' => ['auth/login', 'auth/logout', 'mobile/benefit/redeem'],
    ];
@endphp

<body
        class="dcat-admin-body sidebar-mini layout-fixed admin-iframe-tab-host {{ $configData['body_class']}} {{ $configData['sidebar_class'] }}
        {{ $configData['navbar_class'] === 'fixed-top' ? 'navbar-fixed-top' : '' }} " >

<script>
    var Dcat = CreateDcat({!! Dcat\Admin\Admin::jsVariables() !!});
</script>

{!! admin_section(Dcat\Admin\Admin::SECTION['BODY_INNER_BEFORE']) !!}

<div class="wrapper">
    @include('admin::partials.sidebar')

    @include('admin::partials.navbar')

    <div class="app-content content">
        <div class="content-wrapper admin-iframe-tab-host-wrapper" id="{{ $pjaxContainerId }}">
            @yield('app')
        </div>
    </div>

    {{-- <footer class="main-footer pt-1">
        <p class="clearfix blue-grey lighten-2 mb-0 text-center">
            <span class="text-center d-block d-md-inline-block mt-25">
                © {{ date('Y') }} {{ $footer['copyright'] ?? '悦享生活物业管理系统' }}
                @if(! empty($footer['version']))
                    <span>&nbsp;·&nbsp;</span>{{ $footer['version'] }}
                @endif
            </span>

            <button class="btn btn-primary btn-icon scroll-top pull-right" style="position: fixed;bottom: 2%; right: 10px;display: none">
                <i class="feather icon-arrow-up"></i>
            </button>
        </p>
    </footer> --}}
</div>

{!! admin_section(Dcat\Admin\Admin::SECTION['BODY_INNER_AFTER']) !!}

{!! Dcat\Admin\Admin::asset()->jsToHtml() !!}

<script>
    Dcat.ready(function () {
        $('body').on('click', '[data-check-screen]', function () {
            var check = $(this).attr('data-check-screen');
            if (check == 'full') {
                openFullscreen();
                $(this).attr('data-check-screen', 'exit');
                $(this).html('<i class="feather icon-minimize"></i>');
            } else {
                closeFullscreen();
                $(this).attr('data-check-screen', 'full');
                $(this).html('<i class="feather icon-maximize"></i>');
            }
        });

        function openFullscreen() {
            var elem = document.documentElement;
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.mozRequestFullScreen) {
                elem.mozRequestFullScreen();
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
        }

        function closeFullscreen() {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        }
    });

    Dcat.boot();
</script>
<script src="{{ asset('/vendor/dcat-admin/dcat/plugins/admin-iframe-tab/js/admin-iframe-tab.js') }}?v={{ config('admin.iframe_tab.asset_version', '1.0.0') }}"></script>
<script>
    window.AdminIframeTab && window.AdminIframeTab.init(@json($iframeTabConfig));
</script>

</body>

</html>
