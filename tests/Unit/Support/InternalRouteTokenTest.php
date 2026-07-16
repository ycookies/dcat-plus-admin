<?php

namespace Dcat\Admin\Tests\Unit\Support;

use Dcat\Admin\Support\InternalRouteToken;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class InternalRouteTokenTest extends TestCase
{
    protected ?Container $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
        $container = new Container();
        Container::setInstance($container);
        $container->instance('config', new Repository([
            'app' => ['key' => 'base64:'.base64_encode(str_repeat('k', 32))],
            'admin' => ['permission' => ['internal' => ['token_ttl' => 3600]]],
        ]));
    }

    protected function tearDown(): void
    {
        Container::setInstance($this->previousContainer);
        parent::tearDown();
    }

    public function test_token_is_bound_to_scope_user_and_application(): void
    {
        $tokenService = new TestInternalRouteToken('7', 'admin');
        $token = $tokenService->issue('media.read', ['disk' => 'public']);
        $request = Request::create('/admin/dcat-sys/media/files', 'GET', [
            InternalRouteToken::PARAMETER => $token,
        ]);

        $this->assertSame(['disk' => 'public'], $tokenService->verify($request, 'media.read'));
        $this->assertNull($tokenService->verify($request, 'media.write'));
        $this->assertNull((new TestInternalRouteToken('8', 'admin'))->verify($request, 'media.read'));
        $this->assertNull((new TestInternalRouteToken('7', 'seller'))->verify($request, 'media.read'));
    }

    public function test_tampered_token_is_rejected(): void
    {
        $tokenService = new TestInternalRouteToken('7', 'admin');
        $token = $tokenService->issue('sku.write');
        $request = Request::create('/admin/dcat-sys/sku/upload', 'POST', [
            InternalRouteToken::PARAMETER => substr($token, 0, -1).'x',
        ]);

        $this->assertNull($tokenService->verify($request, 'sku.write'));
    }
}

class TestInternalRouteToken extends InternalRouteToken
{
    protected string $testUserId;

    protected string $testApplicationName;

    public function __construct(string $testUserId, string $testApplicationName)
    {
        $this->testUserId = $testUserId;
        $this->testApplicationName = $testApplicationName;
    }

    protected function userIdentifier(): ?string
    {
        return $this->testUserId;
    }

    protected function applicationName(): string
    {
        return $this->testApplicationName;
    }
}
