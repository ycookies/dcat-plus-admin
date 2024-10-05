<body
        class="dcat-admin-body sidebar-mini layout-fixed {{ $configData['body_class']}} {{ $configData['sidebar_class'] }}
        {{ $configData['navbar_class'] === 'fixed-top' ? 'navbar-fixed-top' : '' }} " >

<script>
    var Dcat = CreateDcat({!! Dcat\Admin\Admin::jsVariables() !!});
</script>

{!! admin_section(Dcat\Admin\Admin::SECTION['BODY_INNER_BEFORE']) !!}

<div class="wrapper">
    @include('admin::partials.sidebar')

    @include('admin::partials.navbar')

    <div class="app-content content">
        <div class="content-wrapper" id="{{ $pjaxContainerId }}" style="top: 0;min-height: 900px;">
            @yield('app')
        </div>
    </div>
</div>

<footer class="main-footer pt-1">
    <p class="clearfix blue-grey lighten-2 mb-0 text-center">
            <span class="text-center d-block d-md-inline-block mt-25">
                Powered by
                <a target="_blank" href="https://github.com/ycookies/dcat-plus-admin">Dcat-plus Admin (速码邦)</a>
                <span>&nbsp;·&nbsp;</span>
                {{ \Dcat\Admin\Support\Helper::getPackageVersion('dcat-plus/laravel-admin') }}
            </span>

        <button class="btn btn-primary btn-icon scroll-top pull-right" style="position: fixed;bottom: 2%; right: 10px;display: none">
            <i class="feather icon-arrow-up"></i>
        </button>
    </p>
</footer>

{!! admin_section(Dcat\Admin\Admin::SECTION['BODY_INNER_AFTER']) !!}

{!! Dcat\Admin\Admin::asset()->jsToHtml() !!}

<script>
    Dcat.ready(function () {
        var clipboard = new ClipboardJS('.copy');
        clipboard.on('success', function(e) {
            e.clearSelection();
            layer.msg('已复制');
        });
        clipboard.on('error', function(e) {
            e.clearSelection();
            layer.msg('复制内容失败');
        });
        /**
         * 全屏
         */
        $('body').on('click', '[data-check-screen]', function () {
            var check = $(this).attr('data-check-screen');
            if (check == 'full') {
                openFullscreen();
                $(this).attr('data-check-screen', 'exit');
                $(this).html('<i class="feather icon-minimize"</i>');
            } else {
                closeFullscreen();
                $(this).attr('data-check-screen', 'full');
                $(this).html('<i class="feather icon-maximize"></i>');
            }
    });
    // 进入全屏
    function openFullscreen() {
        var elem = document.documentElement;
        if (elem.requestFullscreen) {
            elem.requestFullscreen();
        } else if (elem.mozRequestFullScreen) { /* Firefox */
            elem.mozRequestFullScreen();
        } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari and Opera */
            elem.webkitRequestFullscreen();
        } else if (elem.msRequestFullscreen) { /* IE/Edge */
            elem.msRequestFullscreen();
        }
    }
    // 退出全屏
    function closeFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.mozCancelFullScreen) { /* Firefox */
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) { /* Chrome, Safari and Opera */
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) { /* IE/Edge */
            document.msExitFullscreen();
        }
    }
    })
    Dcat.boot();
</script>

</body>

</html>