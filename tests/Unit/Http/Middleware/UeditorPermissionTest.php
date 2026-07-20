<?php

namespace Dcat\Admin\Tests\Unit\Http\Middleware;

use Dcat\Admin\Http\Middleware\UeditorPermission;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class UeditorPermissionTest extends TestCase
{
    public function test_config_and_upload_requests_do_not_apply_role_permissions(): void
    {
        $middleware = new UeditorPermission();

        foreach (['config', 'uploadimage', 'uploadvideo', 'uploadfile'] as $action) {
            $request = Request::create('/admin/dcat-api/ueditor/server?action='.$action, 'POST');
            $response = $middleware->handle($request, function () use ($action) {
                return $action;
            });

            $this->assertSame($action, $response);
        }
    }
}
