<?php

namespace Dcat\Admin\Tests\Unit\Support\Authorization;

use Dcat\Admin\Support\Authorization\RoutePermissionResolver;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class RoutePermissionResolverTest extends TestCase
{
    public function test_exact_match_requires_the_same_path_and_methods(): void
    {
        $resolver = new RoutePermissionResolver(RoutePermissionTestModel::class);
        $permission = new RoutePermissionTestModel([
            'http_method' => ['PATCH', 'PUT', 'HEAD'],
            'http_path' => ['/users/{user}'],
        ]);
        $descriptor = [
            'http_methods' => ['PUT', 'PATCH'],
            'http_path' => '/users/*',
        ];

        $this->assertTrue($resolver->isExactMatch($permission, $descriptor));

        $permission->http_method = ['GET'];
        $this->assertFalse($resolver->isExactMatch($permission, $descriptor));

        $permission->http_method = ['PUT', 'PATCH'];
        $permission->http_path = ['/users/*', '/users/export'];
        $this->assertFalse($resolver->isExactMatch($permission, $descriptor));
    }

}

class RoutePermissionTestModel extends Model
{
    protected $guarded = [];

    protected $casts = [
        'http_method' => 'array',
        'http_path' => 'array',
    ];
}
