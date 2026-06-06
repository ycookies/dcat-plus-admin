{!! admin_section(Dcat\Admin\Admin::SECTION['NAVBAR_BEFORE']) !!}

{{-- 布局配置变量（所有模式共用） --}}
@php
    $savedLayout = [];
    try { $savedLayout = admin_setting_group('layout_config') ?: []; } catch (\Throwable) {}
    $lcColor = $savedLayout['color'] ?? config('admin.layout.color', 'default');
    $lcSidebarStyle = $savedLayout['sidebar_style'] ?? config('admin.layout.sidebar_style', 'light');
    $lcNavbarColor = $savedLayout['navbar_color'] ?? config('admin.layout.navbar_color', '');
    $lcHorizontalMenu = isset($savedLayout['horizontal_menu']) ? (bool)$savedLayout['horizontal_menu'] : config('admin.layout.horizontal_menu', false);
    $lcSidebarCollapsed = isset($savedLayout['sidebar_collapsed']) ? (bool)$savedLayout['sidebar_collapsed'] : config('admin.layout.sidebar_collapsed', false);
    $lcDarkModeSwitch = isset($savedLayout['dark_mode_switch']) ? (bool)$savedLayout['dark_mode_switch'] : config('admin.layout.dark_mode_switch', false);
    $lcFullScreen = isset($savedLayout['full_screen']) ? (bool)$savedLayout['full_screen'] : config('admin.layout.full_screen', true);
    $lcHomeUrl = $savedLayout['home_url'] ?? config('admin.layout.home_url', '');
@endphp

@if(!$configData['horizontal_menu'])
<nav class="header-navbar navbar-expand-lg navbar
    navbar-with-menu {{ $configData['navbar_class'] }}
    {{ $configData['navbar_color'] }}
        navbar-light navbar-shadow " style="top: 0;">

    <div class="navbar-wrapper">
        <div class="navbar-container content">
            @if(! $configData['horizontal_menu'])
            <div class="mr-auto float-left bookmark-wrapper d-flex align-items-center">
                <ul class="nav navbar-nav">
                    <li class="nav-item mr-auto">
                        <a class="nav-link menu-toggle" data-widget="pushmenu" style="cursor: pointer;">
                            <i class="fa fa-bars font-md-2"></i>
                        </a>
                    </li>
                </ul>
            </div>
            @endif

            <div class="navbar-collapse d-flex justify-content-between">
                <div class="navbar-left d-flex align-items-center">
                    {!! Dcat\Admin\Admin::navbar()->render('left') !!}
                </div>

                @if($configData['horizontal_menu'])
                <div class="d-md-block horizontal-navbar-brand justify-content-center text-center">
                    <ul class="nav navbar-nav flex-row">
                        <li class="nav-item mr-auto">
                            <a href="{{ admin_url('/') }}" class="waves-effect waves-light">
                                <span class="logo-lg">{!! config('admin.logo') !!}</span>
                            </a>
                        </li>
                    </ul>
                </div>
                @endif

                <div class="navbar-right d-flex align-items-center">
                    {!! Dcat\Admin\Admin::navbar()->render() !!}
                    @if(!empty($configData['home_url']))
                        <a href="{{$configData['home_url']}}" target="_blank" class="nav-link"><i class="fa fa-home f18"></i></a>
                    @endif

                    @if(isset($configData['full_screen']) && $configData['full_screen'])
                    <a href="javascript:;"  data-check-screen="full" class="nav-link"><i class="feather icon-maximize f16"></i></a>
                    @endif

                    {{-- 布局配置按钮（导航栏内） --}}
                    <a href="javascript:void(0);" class="nav-link lc-open-trigger" title="布局配置">
                        <i class="feather icon-settings f16"></i>
                    </a>

                    <ul class="nav navbar-nav">
                        {{--User Account Menu--}}
                        {!! admin_section(Dcat\Admin\Admin::SECTION['NAVBAR_USER_PANEL']) !!}

                        {!! admin_section(Dcat\Admin\Admin::SECTION['NAVBAR_AFTER_USER_PANEL']) !!}
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>
@endif

{{-- 布局配置滑出面板（所有模式共用） --}}
<div id="lc-overlay" class="lc-overlay" style="display:none;"></div>
<div id="lc-config-panel" class="lc-config-panel">
    <div class="lc-panel-header">
        <span class="lc-panel-title"><i class="feather icon-settings"></i> 布局配置</span>
        <button type="button" class="lc-panel-close" id="lc-close-panel">&times;</button>
    </div>
    <form id="lc-config-form" method="POST" action="{{ admin_url('layout-config/save') }}">
        <div class="lc-panel-body">

            <div class="lc-section">
                <div class="lc-section-title">主题色</div>
                <div class="lc-radio-group">
                    <label class="lc-radio-label {{ $lcColor === 'default' ? 'active' : '' }}">
                        <input type="radio" name="color" value="default" {{ $lcColor === 'default' ? 'checked' : '' }}> 默认
                    </label>
                    <label class="lc-radio-label {{ $lcColor === 'blue' ? 'active' : '' }}">
                        <input type="radio" name="color" value="blue" {{ $lcColor === 'blue' ? 'checked' : '' }}> 蓝色
                    </label>
                    <label class="lc-radio-label {{ $lcColor === 'blue-light' ? 'active' : '' }}">
                        <input type="radio" name="color" value="blue-light" {{ $lcColor === 'blue-light' ? 'checked' : '' }}> 浅蓝
                    </label>
                    <label class="lc-radio-label {{ $lcColor === 'green' ? 'active' : '' }}">
                        <input type="radio" name="color" value="green" {{ $lcColor === 'green' ? 'checked' : '' }}> 绿色
                    </label>
                </div>
            </div>

            <div class="lc-section">
                <div class="lc-section-title">侧边栏样式</div>
                <div class="lc-radio-group">
                    <label class="lc-radio-label {{ $lcSidebarStyle === 'light' ? 'active' : '' }}">
                        <input type="radio" name="sidebar_style" value="light" {{ $lcSidebarStyle === 'light' ? 'checked' : '' }}> 浅色
                    </label>
                    <label class="lc-radio-label {{ $lcSidebarStyle === 'primary' ? 'active' : '' }}">
                        <input type="radio" name="sidebar_style" value="primary" {{ $lcSidebarStyle === 'primary' ? 'checked' : '' }}> 主题色
                    </label>
                    <label class="lc-radio-label {{ $lcSidebarStyle === 'dark' ? 'active' : '' }}">
                        <input type="radio" name="sidebar_style" value="dark" {{ $lcSidebarStyle === 'dark' ? 'checked' : '' }}> 深色
                    </label>
                </div>
            </div>

            <div class="lc-section">
                <div class="lc-section-title">导航栏颜色</div>
                <div class="lc-radio-group">
                    <label class="lc-radio-label {{ $lcNavbarColor === '' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="" {{ $lcNavbarColor === '' ? 'checked' : '' }}> 默认
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-primary' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-primary" {{ $lcNavbarColor === 'bg-primary' ? 'checked' : '' }}> 主题色
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-info' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-info" {{ $lcNavbarColor === 'bg-info' ? 'checked' : '' }}> 信息色
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-warning' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-warning" {{ $lcNavbarColor === 'bg-warning' ? 'checked' : '' }}> 警告色
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-success' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-success" {{ $lcNavbarColor === 'bg-success' ? 'checked' : '' }}> 成功色
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-danger' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-danger" {{ $lcNavbarColor === 'bg-danger' ? 'checked' : '' }}> 危险色
                    </label>
                    <label class="lc-radio-label {{ $lcNavbarColor === 'bg-dark' ? 'active' : '' }}">
                        <input type="radio" name="navbar_color" value="bg-dark" {{ $lcNavbarColor === 'bg-dark' ? 'checked' : '' }}> 深色
                    </label>
                </div>
            </div>

            <div class="lc-section">
                <div class="lc-section-title">功能开关</div>
                <div class="lc-switch-group">
                    <label class="lc-switch-label">
                        <input type="checkbox" name="horizontal_menu" value="1" {{ $lcHorizontalMenu ? 'checked' : '' }}>
                        <span>水平菜单</span>
                    </label>
                    <label class="lc-switch-label">
                        <input type="checkbox" name="sidebar_collapsed" value="1" {{ $lcSidebarCollapsed ? 'checked' : '' }}>
                        <span>侧边栏折叠</span>
                    </label>
                    <label class="lc-switch-label">
                        <input type="checkbox" name="dark_mode_switch" value="1" {{ $lcDarkModeSwitch ? 'checked' : '' }}>
                        <span>暗黑模式切换</span>
                    </label>
                    <label class="lc-switch-label">
                        <input type="checkbox" name="full_screen" value="1" {{ $lcFullScreen ? 'checked' : '' }}>
                        <span>全屏按钮</span>
                    </label>
                </div>
            </div>

            <div class="lc-section">
                <div class="lc-section-title">官网链接</div>
                <input type="text" name="home_url" value="{{ $lcHomeUrl }}"
                    class="lc-input" placeholder="留空则不显示官网入口">
            </div>

        </div>
        <div class="lc-panel-footer">
            <button type="submit" class="btn btn-primary btn-sm btn-block">
                <i class="feather icon-save"></i> 保存配置
            </button>
        </div>
    </form>
</div>

{{-- 布局配置 CSS --}}
<style>
.lc-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.3);
    z-index: 1030;
    transition: opacity .25s;
}
.lc-config-panel {
    position: fixed;
    top: 0; right: -380px;
    width: 360px;
    height: 100vh;
    background: #fff;
    box-shadow: -2px 0 12px rgba(0,0,0,0.1);
    z-index: 1031;
    transition: right .3s ease;
    display: flex;
    flex-direction: column;
    font-size: 14px;
}
.lc-config-panel.open {
    right: 0;
}
.lc-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid #eee;
    background: #f8f9fa;
}
.lc-panel-title {
    font-weight: 600;
    font-size: 16px;
    color: #333;
}
.lc-panel-title i {
    margin-right: 6px;
}
.lc-panel-close {
    background: none;
    border: none;
    font-size: 22px;
    color: #999;
    cursor: pointer;
    padding: 0 4px;
    line-height: 1;
}
.lc-panel-close:hover {
    color: #333;
}
.lc-panel-body {
    flex: 1;
    overflow-y: auto;
    padding: 16px 20px;
}
.lc-section {
    margin-bottom: 20px;
}
.lc-section-title {
    font-weight: 600;
    font-size: 13px;
    color: #666;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.lc-radio-group {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}
.lc-radio-label {
    display: flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    padding: 4px 10px;
    border-radius: 4px;
    border: 1px solid #ddd;
    font-size: 13px;
    transition: all .15s;
}
.lc-radio-label:hover {
    border-color: #5c6bc0;
    background: #eef0ff;
}
.lc-radio-label.active,
.lc-radio-label:has(input:checked) {
    border-color: #5c6bc0;
    background: #eef0ff;
}
.lc-switch-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.lc-switch-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-size: 13px;
    padding: 6px 0;
}
.lc-switch-label input[type="checkbox"] {
    width: 16px;
    height: 16px;
}
.lc-input {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 13px;
    outline: none;
    transition: border-color .15s;
}
.lc-input:focus {
    border-color: #5c6bc0;
}
.lc-panel-footer {
    padding: 12px 20px;
    border-top: 1px solid #eee;
    background: #f8f9fa;
}
</style>

{{-- 布局配置 JS --}}
<script>
(function() {
    var panel = document.getElementById('lc-config-panel');
    var overlay = document.getElementById('lc-overlay');
    var openBtns = document.querySelectorAll('.lc-open-trigger');
    var closeBtn = document.getElementById('lc-close-panel');
    var form = document.getElementById('lc-config-form');

    if (!panel || !form) return;

    function openPanel() {
        panel.classList.add('open');
        overlay.style.display = 'block';
    }
    function closePanel() {
        panel.classList.remove('open');
        overlay.style.display = 'none';
    }

    openBtns.forEach(function(btn) {
        btn.addEventListener('click', openPanel);
    });
    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    if (overlay) overlay.addEventListener('click', closePanel);

    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var checkboxes = form.querySelectorAll('input[type="checkbox"]');
        checkboxes.forEach(function(cb) {
            if (!cb.checked) {
                var hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = cb.name;
                hidden.value = '0';
                form.appendChild(hidden);
            }
        });

        var formData = new FormData(form);
        var token = (typeof Dcat !== 'undefined' && Dcat.token) ? Dcat.token : '';

        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            }
        })
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
            closePanel();
            if (data.status) {
                location.reload();
            } else {
                alert(data.message || '保存失败');
            }
        })
        .catch(function(err) {
            closePanel();
            alert('请求失败，请重试');
        });
    });
})();
</script>
{!! admin_section(Dcat\Admin\Admin::SECTION['NAVBAR_AFTER']) !!}