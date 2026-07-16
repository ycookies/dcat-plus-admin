<?php

namespace Dcat\Admin\Tests\Unit\Http\Middleware;

use Dcat\Admin\Http\Middleware\Permission;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;

class PermissionTest extends TestCase
{
    public function test_internal_routes_require_a_dedicated_policy_before_bypassing_url_rbac(): void
    {
        $middleware = new TestablePermission();

        $protected = (new Route(['GET'], 'dcat-sys/ping', fn () => null))
            ->middleware('admin.internal:authenticated')
            ->defaults('dcat_route_type', 'internal');
        $missingPolicy = (new Route(['GET'], 'dcat-sys/unsafe', fn () => null))
            ->defaults('dcat_route_type', 'internal');
        $legacy = (new Route(['POST'], 'legacy-upload', fn () => null))
            ->middleware('admin.internal:authenticated')
            ->defaults('dcat_route_type', 'internal_legacy');

        $this->assertTrue($middleware->protectedInternalRoute($protected));
        $this->assertFalse($middleware->protectedInternalRoute($missingPolicy));
        $this->assertFalse($middleware->protectedInternalRoute($legacy));
        $this->assertFalse($middleware->protectedInternalRoute(null));
    }
}

class TestablePermission extends Permission
{
    public function protectedInternalRoute($route): bool
    {
        return $this->isProtectedInternalRoute($route);
    }
}
