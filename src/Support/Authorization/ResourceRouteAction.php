<?php

namespace Dcat\Admin\Support\Authorization;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Prevents one generated resource-action permission from granting a sibling
 * action merely because their wildcard URL patterns overlap.
 */
class ResourceRouteAction
{
    public const ACTIONS = [
        'index',
        'show',
        'create',
        'store',
        'edit',
        'update',
        'destroy',
        'import',
        'export',
    ];

    public static function matchesRequest($permission, Request $request): bool
    {
        $permissionAction = static::permissionAction($permission);
        $requestAction = static::requestAction($request);

        return ! $permissionAction || ! $requestAction || $permissionAction === $requestAction;
    }

    public static function matchesGridAction($permission, string $gridAction): bool
    {
        $permissionAction = static::permissionAction($permission);
        $expectedAction = static::gridAction($gridAction);

        return ! $permissionAction || ! $expectedAction || $permissionAction === $expectedAction;
    }

    public static function permissionAction($permission): ?string
    {
        $slug = trim((string) ($permission->slug ?? ''));
        $action = strtolower(Str::afterLast($slug, '.'));

        return in_array($action, static::ACTIONS, true) ? $action : null;
    }

    public static function requestAction(Request $request): ?string
    {
        $route = $request->route();
        if (! $route) {
            return null;
        }

        $name = (string) $route->getName();
        $action = strtolower(Str::afterLast($name, '.'));

        return in_array($action, static::ACTIONS, true) ? $action : null;
    }

    public static function gridAction(string $action): ?string
    {
        $actions = [
            'create'       => 'create',
            'edit'         => 'edit',
            'quick_edit'   => 'edit',
            'delete'       => 'destroy',
            'batch_delete' => 'destroy',
        ];

        return $actions[$action] ?? null;
    }
}
