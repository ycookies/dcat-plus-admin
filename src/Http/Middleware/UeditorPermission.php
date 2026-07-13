<?php

namespace Dcat\Admin\Http\Middleware;

use Closure;
use Dcat\Admin\Admin;
use Dcat\Admin\Http\Auth\Permission;
use Dcat\Admin\Support\UeditorConfig;
use Illuminate\Http\Request;

class UeditorPermission
{
    /**
     * Authorize UEditor uploads independently from the API route permission bypass.
     */
    public function handle(Request $request, Closure $next)
    {
        $permission = UeditorConfig::get('permission');

        if ($permission && (! Admin::user() || Admin::user()->cannot($permission))) {
            Permission::error();
        }

        return $next($request);
    }
}
