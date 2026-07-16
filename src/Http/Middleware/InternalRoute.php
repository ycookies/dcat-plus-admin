<?php

namespace Dcat\Admin\Http\Middleware;

use Closure;
use Dcat\Admin\Admin;
use Dcat\Admin\Http\Auth\Permission;
use Dcat\Admin\Support\InternalRouteToken;
use Illuminate\Http\Request;

/**
 * Authorizes framework-internal routes that are intentionally excluded from
 * ordinary URL-based RBAC.
 */
class InternalRoute
{
    public function handle(Request $request, Closure $next, string $policy = 'authenticated', ?string $argument = null)
    {
        $user = Admin::user();
        if (! $user) {
            Permission::error();
        }

        if ($policy === 'authenticated') {
            return $next($request);
        }

        if ($policy === 'signed') {
            $claims = $argument ? app(InternalRouteToken::class)->verify($request, $argument) : null;
            if ($claims === null) {
                abort(403, trans('admin.deny'));
            }

            $request->attributes->set('dcat_internal_claims', $claims);

            return $next($request);
        }

        if ($policy === 'administrator') {
            if (! $user->isAdministrator()) {
                Permission::error();
            }

            return $next($request);
        }

        if ($policy === 'capability') {
            if (! $argument || $user->cannot($argument)) {
                Permission::error();
            }

            return $next($request);
        }

        abort(500, "Unsupported internal route policy [{$policy}].");
    }
}
