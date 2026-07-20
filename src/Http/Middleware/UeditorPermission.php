<?php

namespace Dcat\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UeditorPermission
{
    /**
     * Compatibility middleware retained for projects that reference the alias.
     * UEditor is a form component and does not apply role-level authorization;
     * authentication and upload security are handled by the admin route group,
     * CSRF middleware, throttling and the upload controller validation.
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
