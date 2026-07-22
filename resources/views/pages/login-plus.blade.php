@php
    $captchaEnabled = (bool) config('admin.auth.captcha.enable', false);
    $backgroundImage = admin_asset(config('admin.login_background_image') ?: '@admin/images/login_32-bg.jpg');
    $boxImage = admin_asset('@admin/images/login_32-box.png');
    $loginError = $errors->first('username') ?: $errors->first('password') ?: $errors->first('captcha');
    $genericLoginError = trans('admin.auth_failed');

    if ($genericLoginError === 'admin.auth_failed') {
        $genericLoginError = '登录失败，请检查账号和密码。';
    }
@endphp

<style>
    /*
     * 此布局按 login_32.html 原始尺寸、定位和背景资源还原。
     * 仅将 Vue/Element 表单替换为 Laravel 登录表单。
     */
    html {
        font-size: 10vw;
    }

    body.dcat-admin-body.full-page,
    body.dcat-admin-body.full-page .app-content.content,
    body.dcat-admin-body.full-page .wrapper,
    body.dcat-admin-body.full-page .content-body,
    body.dcat-admin-body.full-page .content {
        width: 100%;
        min-height: 100vh;
        margin: 0;
        padding: 0;
        overflow: hidden;
    }

    .login32-page,
    .login32-page * {
        box-sizing: border-box;
    }

    .login32-page {
        --login-form-top: 16%;
        position: fixed;
        inset: 0;
        z-index: 10;
        width: 100%;
        height: 100%;
        overflow: auto;
        background: url('{{ $backgroundImage }}') center center / 100% 100% no-repeat;
    }

    .login32-page .loginBodyCenter {
        position: relative;
        display: flex;
        width: calc(100% - 1.45833rem);
        height: 100%;
        margin: 0 auto;
        flex-direction: column;
        align-items: center;
        justify-content: flex-start;
        z-index: 10;
    }

    .login32-page .loginTitle {
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .login32-page .loginTitle p {
        padding-top: .10417rem;
        color: #fff;
        font-family: PingFang SC, PingFang, sans-serif;
        font-size: .25rem;
        font-weight: 700;
        text-align: center;
    }

    .login32-page .loginBodyMain {
        position: absolute;
        top: var(--login-form-top);
        right: 1%;
        left: 7%;
        display: flex;
        width: auto;
        margin: 0;
        align-items: center;
        justify-content: flex-end;
    }

    .login32-page .loginBody2 {
        width: 3.125rem;
        height: 3.125rem;
        background: url('{{ $boxImage }}') center center / 100% 100% no-repeat;
    }

    /* 验证码开启时仍保持登录框为正方形。 */
    .login32-page .loginBody2.has-captcha {
        height: 3.125rem;
    }

    .login32-page .loginBody2Title {
        display: flex;
        padding-top: .3125rem;
        padding-bottom: .20833rem;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-family: PingFang SC, PingFang, sans-serif;
        font-size: .16667rem;
        font-weight: 800;
    }

    .login32-page .loginInput {
        display: flex;
        width: 2.08333rem;
        height: .28125rem;
        margin: 0 auto .10417rem;
        align-items: center;
        border: 1px solid #00c0ff;
        background: rgba(0, 168, 255, .1);
    }

    .login32-page .loginInput:focus-within {
        border-color: #64dbff;
        box-shadow: 0 0 .05208rem rgba(0, 192, 255, .42);
    }

    .login32-page .loginInput.is-invalid {
        border-color: #ff8097;
        box-shadow: 0 0 .05208rem rgba(255, 91, 122, .35);
    }

    .login32-page .loginInput .icon {
        display: flex;
        width: .29688rem;
        flex: 0 0 .29688rem;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: .10417rem;
        opacity: .92;
    }

    .login32-page .loginInput input {
        width: 100%;
        height: 100%;
        min-width: 0;
        padding: 0 .05208rem 0 0;
        border: 0;
        outline: 0;
        color: #fff;
        font-family: PingFang SC, PingFang, sans-serif;
        font-size: .08333rem;
        font-weight: 500;
        background: none;
    }

    .login32-page .loginInput input::placeholder {
        color: #fff;
        opacity: 1;
    }

    .login32-page .loginCaptcha {
        display: flex;
        width: 2.08333rem;
        height: .28125rem;
        margin: 0 auto .10417rem;
        gap: .05208rem;
    }

    .login32-page .loginCaptcha .loginInput {
        width: auto;
        min-width: 0;
        margin: 0;
        flex: 1;
    }

    .login32-page .loginCaptcha img {
        width: .79167rem;
        height: .28125rem;
        cursor: pointer;
        border: 1px solid #00c0ff;
        background: #fff;
        object-fit: cover;
    }

    .login32-page .loginCaptcha img:hover,
    .login32-page .loginCaptcha img:focus {
        outline: 0;
        border-color: #72e0ff;
        box-shadow: 0 0 .05208rem rgba(0, 192, 255, .42);
    }

    .login32-page .info {
        display: flex;
        width: 2.08333rem;
        min-height: .10417rem;
        margin: 0 auto .10417rem;
        align-items: center;
        justify-content: space-between;
    }

    .login32-page .rememberPwd {
        display: inline-flex;
        align-items: center;
        color: #1badf1;
        font-family: PingFang SC, PingFang, sans-serif;
        font-size: .07292rem;
        font-weight: 500;
        cursor: pointer;
        gap: .04167rem;
    }

    .login32-page .rememberPwd input {
        width: .08333rem;
        height: .08333rem;
        margin: 0;
        accent-color: #0ca0ed;
    }

    .login32-page .forget,
    .login32-page .captcha-refresh {
        padding: 0;
        border: 0;
        color: #00b8ff;
        font-family: PingFang SC, PingFang, sans-serif;
        font-size: .07292rem;
        font-weight: 500;
        background: transparent;
    }

    .login32-page .captcha-refresh {
        cursor: pointer;
    }

    .login32-page .captcha-refresh:hover {
        color: #fff;
    }

    .login32-page .loginBut {
        display: flex;
        width: 2.08333rem;
        height: .26042rem;
        margin: .20833rem auto 0;
        align-items: center;
        justify-content: center;
        border: 0;
        color: #fff;
        cursor: pointer;
        font-family: PingFang SC, PingFang, sans-serif;
        font-size: .10417rem;
        font-weight: 800;
        background-image: linear-gradient(#04c8ee, #0493ee);
    }

    .login32-page .loginBut:hover,
    .login32-page .loginBut:focus {
        outline: 0;
        filter: brightness(1.08);
    }

    .login32-page .loginBut[disabled] {
        cursor: wait;
        opacity: .72;
    }

    .login32-page .loginError {
        width: 2.08333rem;
        min-height: .10417rem;
        margin: .05208rem auto -.15625rem;
        color: #ffb2c0;
        font-family: PingFang SC, PingFang, sans-serif;
        font-size: .06771rem;
        line-height: .10417rem;
        text-align: center;
    }

    .login32-page .loginError:empty {
        display: none;
    }

    @media (max-width: 768px) {
        html {
            font-size: 76.8px;
        }

        .login32-page {
            min-width: 768px;
        }
    }
</style>

<main class="login32-page">
    <div class="loginBodyCenter">
        <div class="loginTitle">
            <p>{{ config('admin.name') }}</p>
        </div>

        <div class="loginBodyMain">
            <section class="loginBody2 {{ $captchaEnabled ? 'has-captcha' : '' }}" aria-label="{{ trans('admin.login') }}">
                <div class="loginBody2Title">{{ trans('admin.login') }}</div>

                <form id="login-form" method="POST" action="{{ admin_url('auth/login') }}" novalidate>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">

                    <div class="loginInput {{ $errors->has('username') ? 'is-invalid' : '' }}" data-field="username">
                        <span class="icon"><i class="feather icon-user"></i></span>
                        <input
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            placeholder="{{ trans('admin.username') }}"
                            aria-label="{{ trans('admin.username') }}"
                            autocomplete="username"
                            required
                            autofocus
                        >
                    </div>

                    <div class="loginInput {{ $errors->has('password') ? 'is-invalid' : '' }}" data-field="password">
                        <span class="icon"><i class="feather icon-lock"></i></span>
                        <input
                            type="password"
                            name="password"
                            placeholder="{{ trans('admin.password') }}"
                            aria-label="{{ trans('admin.password') }}"
                            autocomplete="current-password"
                            required
                        >
                    </div>

                    @if($captchaEnabled)
                        <div class="loginCaptcha">
                            <div class="loginInput {{ $errors->has('captcha') ? 'is-invalid' : '' }}" data-field="captcha">
                                <span class="icon"><i class="feather icon-shield"></i></span>
                                <input
                                    type="text"
                                    name="captcha"
                                    placeholder="{{ trans('admin.captcha') }}"
                                    aria-label="{{ trans('admin.captcha') }}"
                                    autocomplete="off"
                                    maxlength="8"
                                    spellcheck="false"
                                    required
                                >
                            </div>
                            <img
                                id="login-captcha-image"
                                src="{{ admin_url('auth/captcha') }}"
                                data-url="{{ admin_url('auth/captcha') }}"
                                alt="{{ trans('admin.captcha') }}"
                                title="{{ trans('admin.captcha') }}"
                                role="button"
                                tabindex="0"
                            >
                        </div>
                    @endif

                    <div class="info">
                        @if(config('admin.auth.remember'))
                            <label class="rememberPwd">
                                <input id="remember" name="remember" value="1" type="checkbox" {{ old('remember') ? 'checked' : '' }}>
                                <span>{{ trans('admin.remember_me') }}</span>
                            </label>
                        @else
                            <span></span>
                        @endif

                        @if($captchaEnabled)
                            <button id="refresh-login-captcha" class="captcha-refresh" type="button">{{ trans('admin.refresh') }}</button>
                        @else
                            <span class="forget"></span>
                        @endif
                    </div>

                    <div id="login-error" class="loginError" role="alert">{{ $loginError }}</div>

                    <button id="login-submit" class="loginBut" type="submit">{{ trans('admin.login') }}</button>
                </form>
            </section>
        </div>
    </div>
</main>

<script>
    (function ($) {
        function initializeLogin() {
            var $form = $('#login-form');

            if (! $form.length || $form.data('login32-bound')) {
                return;
            }

            $form.data('login32-bound', true);

            var $captcha = $('#login-captcha-image');
            var $error = $('#login-error');
            var $submit = $('#login-submit');
            var genericError = @json($genericLoginError);

            function refreshCaptcha() {
                if (! $captcha.length) {
                    return;
                }

                var url = $captcha.data('url');
                $captcha.attr('src', url + (url.indexOf('?') === -1 ? '?' : '&') + '_=' + Date.now());
            }

            function clearError() {
                $error.empty();
                $form.find('.loginInput').removeClass('is-invalid');
            }

            function firstError(errors, fallback) {
                var field;

                for (field in errors) {
                    if (! Object.prototype.hasOwnProperty.call(errors, field)) {
                        continue;
                    }

                    var message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];

                    if (message) {
                        return message;
                    }
                }

                return fallback || genericError;
            }

            function showError(errors, fallback) {
                errors = errors || {};

                $.each(errors, function (field) {
                    $form.find('[data-field="' + field + '"]').addClass('is-invalid');
                });

                $error.text(firstError(errors, fallback));
            }

            $('#refresh-login-captcha').on('click', refreshCaptcha);
            $captcha.on('click keydown', function (event) {
                if (event.type === 'click' || event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    refreshCaptcha();
                }
            });

            $form.on('submit', function (event) {
                event.preventDefault();
                clearError();

                if ($submit.prop('disabled')) {
                    return;
                }

                if (this.checkValidity && ! this.checkValidity()) {
                    this.reportValidity();
                    return;
                }

                $submit.prop('disabled', true);

                $.ajax({
                    url: $form.attr('action'),
                    type: 'POST',
                    data: $form.serialize(),
                    dataType: 'json',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }).done(function (response) {
                    if (response && response.status) {
                        var data = response.data || {};
                        var redirect = data.then && data.then.value ? data.then.value : data.redirect;

                        window.location.assign(redirect || '{{ admin_url('/') }}');

                        return;
                    }

                    showError((response || {}).errors, (response || {}).message);
                }).fail(function (xhr) {
                    var response = xhr.responseJSON || {};
                    var data = response.data || {};

                    showError(response.errors, data.message || response.message);
                }).always(function () {
                    $submit.prop('disabled', false);
                    refreshCaptcha();
                });
            });
        }

        if (window.Dcat && typeof window.Dcat.ready === 'function') {
            window.Dcat.ready(initializeLogin);
        } else {
            $(initializeLogin);
        }
    })(window.jQuery);
</script>
