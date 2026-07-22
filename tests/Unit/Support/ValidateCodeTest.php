<?php

namespace Dcat\Admin\Tests\Unit\Support;

use Dcat\Admin\Admin;
use Dcat\Admin\Exception\InvalidArgumentException;
use Dcat\Admin\Support\ValidateCode;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\Store;
use PHPUnit\Framework\TestCase;

class ValidateCodeTest extends TestCase
{
    protected ?Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $container = new Container();
        $container->instance('config', new Repository([
            'app' => ['key' => 'base64:'.base64_encode(str_repeat('k', 32))],
        ]));
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function test_it_generates_a_png_and_a_non_ambiguous_code(): void
    {
        $captcha = new ValidateCode(['length' => 6, 'width' => 160, 'height' => 50]);

        $this->assertMatchesRegularExpression('/^[23456789ABCDEFGHJKMNPQRSTUVWXYZ]{6}$/', $captcha->code());
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $captcha->render());
        $this->assertStringStartsWith('data:image/png;base64,', $captcha->dataUri());
    }

    public function test_it_stores_only_a_hash_and_validates_once(): void
    {
        $session = new Store('validate-code-test', new ArraySessionHandler(120));
        $session->start();
        $captcha = new ValidateCode(['session_key' => 'captcha.test']);
        $code = $captcha->code();

        $captcha->store($session);

        $this->assertNotSame($code, $session->get('captcha.test.hash'));
        $this->assertTrue($captcha->check(strtolower($code), $session));
        $this->assertFalse($captcha->check($code, $session));
    }

    public function test_it_rejects_unsafe_or_invalid_options(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ValidateCode(['width' => 10]);
    }

    public function test_admin_factory_returns_a_fresh_configured_instance(): void
    {
        app()->bind(ValidateCode::class, fn () => new ValidateCode(['length' => 4]));

        $captcha = Admin::validateCode(['length' => 5]);

        $this->assertInstanceOf(ValidateCode::class, $captcha);
        $this->assertSame(5, $captcha->getOptions()['length']);
        $this->assertNotSame($captcha, Admin::validateCode());
    }
}
