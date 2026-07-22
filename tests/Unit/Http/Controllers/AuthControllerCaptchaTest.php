<?php

namespace Dcat\Admin\Tests\Unit\Http\Controllers;

use Dcat\Admin\Http\Controllers\AuthController;
use Dcat\Admin\Support\ValidateCode;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\Validator;
use PHPUnit\Framework\TestCase;

class AuthControllerCaptchaTest extends TestCase
{
    protected ?Container $previousContainer = null;

    protected $previousFacadeApplication;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $this->previousFacadeApplication = Facade::getFacadeApplication();

        $container = new Container();
        $container->instance('config', new Repository([
            'app' => ['key' => 'base64:'.base64_encode(str_repeat('k', 32))],
            'admin' => [
                'auth' => [
                    'captcha' => [
                        'enable' => true,
                        'session_key' => 'dcat.login_captcha',
                        'options' => ['length' => 5],
                    ],
                ],
            ],
        ]));

        $translator = new Translator(new ArrayLoader(), 'en');
        $container->instance('translator', $translator);
        $container->instance('validator', new ValidatorFactory($translator, $container));
        $container->bind(ValidateCode::class, fn () => new ValidateCode());

        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Facade::setFacadeApplication($this->previousFacadeApplication);
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function test_invalid_login_captcha_is_rejected_before_the_password_is_checked(): void
    {
        $session = new Store('login-captcha-test', new ArraySessionHandler(120));
        $session->start();
        (new ValidateCode(['length' => 5, 'session_key' => 'dcat.login_captcha']))->store($session);

        $request = Request::create('/admin/auth/login', 'POST', [
            'username' => 'admin',
            'password' => 'password',
            'captcha' => 'wrong',
        ]);
        $request->setLaravelSession($session);

        $response = (new CaptchaTestAuthController())->postLogin($request);

        $this->assertSame(['captcha' => 'invalid captcha'], $response);
        $this->assertFalse($session->has('dcat.login_captcha'));
    }

    public function test_login_captcha_uses_its_own_session_key_and_options(): void
    {
        $controller = new CaptchaTestAuthController();

        $this->assertTrue($controller->captchaEnabled());
        $this->assertSame([
            'length' => 5,
            'session_key' => 'dcat.login_captcha',
        ], $controller->captchaOptions());
    }
}

class CaptchaTestAuthController extends AuthController
{
    public function captchaEnabled(): bool
    {
        return $this->loginCaptchaEnabled();
    }

    /** @return array<string, mixed> */
    public function captchaOptions(): array
    {
        return $this->loginCaptchaOptions();
    }

    public function validationErrorsResponse($validationMessages)
    {
        if ($validationMessages instanceof Validator) {
            return $validationMessages->errors()->toArray();
        }

        return $validationMessages;
    }

    protected function loginCaptchaFailedMessage(): string
    {
        return 'invalid captcha';
    }
}
