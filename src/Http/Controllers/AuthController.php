<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Http\Repositories\Administrator;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Support\Authorization\ActiveRole;
use Dcat\Admin\Traits\HasFormResponse;
use Illuminate\Auth\GuardHelpers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use HasFormResponse;

    /**
     * @var string
     */
    protected $view = 'admin::pages.login-plus';

    /**
     * @var string
     */
    protected $redirectTo;

    /**
     * Show the login page.
     *
     * @return Content|\Illuminate\Http\RedirectResponse
     */
    public function getLogin(Content $content)
    {
        if ($this->guard()->check()) {
            return redirect($this->getRedirectPath());
        }

        return $content->full()->body(view($this->view));
    }

    /**
     * 生成登录页图形验证码。
     */
    public function getCaptcha()
    {
        abort_unless($this->loginCaptchaEnabled(), 404);

        return Admin::validateCode($this->loginCaptchaOptions())->response();
    }

    /**
     * Handle a login request.
     *
     * @param  Request  $request
     * @return mixed
     */
    public function postLogin(Request $request)
    {
        $credentials = $request->only([$this->username(), 'password']);
        $remember = (bool) $request->input('remember', false);

        if ($this->loginCaptchaEnabled()) {
            $credentials['captcha'] = $request->input('captcha');
        }

        /** @var \Illuminate\Validation\Validator $validator */
        $validator = Validator::make($credentials, [
            $this->username()   => 'required',
            'password'          => 'required',
            'captcha'           => $this->loginCaptchaEnabled() ? 'required|string|max:8' : 'nullable',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorsResponse($validator);
        }

        if ($this->loginCaptchaEnabled() && ! Admin::validateCode($this->loginCaptchaOptions())
            ->check((string) $request->input('captcha'), $request->session())) {
            return $this->validationErrorsResponse([
                'captcha' => $this->loginCaptchaFailedMessage(),
            ]);
        }

        unset($credentials['captcha']);

        if ($this->guard()->attempt($credentials, $remember)) {
            return $this->sendLoginResponse($request);
        }

        return $this->validationErrorsResponse([
            $this->username() => $this->getFailedLoginMessage(),
        ]);
    }

    /**
     * User logout.
     *
     * @return Redirect|string
     */
    public function getLogout(Request $request)
    {
        app(ActiveRole::class)->forget($this->guard()->user());
        $this->guard()->logout();

        $request->session()->invalidate();

        $path = admin_url('auth/login');
        if ($request->pjax()) {
            return "<script>location.href = '$path';</script>";
        }

        return redirect($path);
    }

    /**
     * User setting page.
     *
     * @param  Content  $content
     * @return Content
     */
    public function getSetting(Content $content)
    {
        $form = $this->settingForm();
        $form->tools(
            function (Form\Tools $tools) {
                $tools->disableList();
            }
        );

        return $content
            ->title(trans('admin.user_setting'))
            ->body($form->edit(Admin::user()->getKey()));
    }

    /**
     * Update user setting.
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putSetting()
    {
        $form = $this->settingForm();

        if (! $this->validateCredentialsWhenUpdatingPassword()) {
            $form->responseValidationMessages('old_password', trans('admin.old_password_error'));
        }

        return $form->update(Admin::user()->getKey());
    }

    protected function validateCredentialsWhenUpdatingPassword()
    {
        $user = Admin::user();

        $oldPassword = \request('old_password');
        $newPassword = \request('password');

        if (
            (! $newPassword)
            || ($newPassword === $user->getAuthPassword())
        ) {
            return true;
        }

        if (! $oldPassword) {
            return false;
        }

        return $this->guard()
            ->getProvider()
            ->validateCredentials($user, ['password' => $oldPassword]);
    }

    /**
     * Model-form for user setting.
     *
     * @return Form
     */
    protected function settingForm()
    {
        return new Form(new Administrator(), function (Form $form) {
            $form->action(admin_url('auth/setting'));

            $form->disableCreatingCheck();
            $form->disableEditingCheck();
            $form->disableViewCheck();

            $form->tools(function (Form\Tools $tools) {
                $tools->disableView();
                $tools->disableDelete();
            });

            $form->display('username', trans('admin.username'));
            $form->text('name', trans('admin.name'))->required();
            $form->image('avatar', trans('admin.avatar'))->autoUpload();

            $form->password('old_password', trans('admin.old_password'));

            $form->password('password', trans('admin.password'))
                ->minLength(5)
                ->maxLength(20)
                ->customFormat(function ($v) {
                    if ($v == $this->password) {
                        return;
                    }

                    return $v;
                });
            $form->password('password_confirmation', trans('admin.password_confirmation'))->same('password');

            $form->ignore(['password_confirmation', 'old_password']);

            // $form->submitted(function (Form $form) {
            //     return $form->response()->error('兄弟你没有权限改密码！');
            // });
            $form->saving(function (Form $form) {
                if ($form->password && $form->model()->password != $form->password) {
                    $form->password = bcrypt($form->password);
                }

                if (! $form->password) {
                    $form->deleteInput('password');
                }
            });

            $form->saved(function (Form $form) {
                return $form
                    ->response()
                    ->success(trans('admin.update_succeeded'))
                    ->redirect('auth/setting');
            });
        });
    }

    /**
     * @return string|\Symfony\Component\Translation\TranslatorInterface
     */
    protected function getFailedLoginMessage()
    {
        return Lang::has('admin.auth_failed')
            ? trans('admin.auth_failed')
            : 'These credentials do not match our records.';
    }

    /**
     * Get the post login redirect path.
     *
     * @return string
     */
    protected function getRedirectPath()
    {
        if ($this->redirectTo) {
            return $this->redirectTo;
        }

        // 根据 layout_config 中的 active_mode 决定登录后跳转目标
        try {
            $layoutConfig = admin_setting_group('layout_config');
            $userPreferences = (array) session('dcat.admin.preferences', []);
            $activeMode = $userPreferences['active_mode'] ?? (is_array($layoutConfig) ? ($layoutConfig['active_mode'] ?? '') : '');
            if ($activeMode === 'iframe-tabs') {
                $shellPath = trim(config('admin.iframe_tab.shell_path', 'dcat-sys/iframe-tabs'), '/');
                return admin_url($shellPath);
            }
        } catch (\Throwable $e) {
            // 忽略异常，回退默认路径
        }

        return admin_url('/');
    }

    /**
     * Send the response after the user was authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function sendLoginResponse(Request $request)
    {
        $request->session()->regenerate();

        // A login always starts from the user's configured default role rather
        // than retaining a role chosen in a previous browser session.
        app(ActiveRole::class)->initialize($this->guard()->user());

        $path = $this->getRedirectPath();

        return $this->response()
            ->success(trans('admin.login_successful'))
            ->locationToIntended($path)
            ->locationIf(Admin::app()->getEnabledApps(), $path)
            ->send();
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    protected function username()
    {
        return 'username';
    }

    /**
     * 是否启用后台登录图形验证码。
     */
    protected function loginCaptchaEnabled(): bool
    {
        return (bool) config('admin.auth.captcha.enable', false);
    }

    /**
     * @return array<string, mixed>
     */
    protected function loginCaptchaOptions(): array
    {
        $options = (array) config('admin.auth.captcha.options', []);

        // 登录验证码使用独立 Session 键，避免与业务页面的验证码互相覆盖。
        $options['session_key'] = (string) config('admin.auth.captcha.session_key', 'dcat.login_captcha');

        return $options;
    }

    protected function loginCaptchaFailedMessage(): string
    {
        return Lang::has('admin.captcha_invalid')
            ? trans('admin.captcha_invalid')
            : 'The captcha is invalid or has expired.';
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\StatefulGuard|GuardHelpers
     */
    protected function guard()
    {
        return Admin::guard();
    }
}
