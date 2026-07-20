<?php

namespace Dcat\Admin\Support\Authorization;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Applies the existing URL permissions to the default resource Grid actions.
 *
 * Decisions are cached on the current request, while the user's permission
 * collection is reused from HasPermissions::allPermissions(). This avoids
 * querying the permission table or scanning Laravel routes while rendering
 * every row action.
 */
class GridActionPermission
{
    public const MODE_HIDE = 'hide';

    public const MODE_PROMPT = 'prompt';

    protected const CACHE_ATTRIBUTE = '_dcat_grid_action_permissions';

    protected const SCRIPT_ATTRIBUTE = '_dcat_grid_action_permission_script';

    /**
     * Determine whether the current user may use a default Grid action.
     */
    public static function allows(Grid $grid, string $action): bool
    {
        return static::allowsResource($grid->resource(), $action);
    }

    /**
     * Determine permission from the actual HTTP method/path used by the action.
     */
    public static function allowsResource(string $resource, string $action, $user = null): bool
    {
        if (! static::enabled($action) || ! config('admin.permission.enable')) {
            return true;
        }

        $user = $user ?: Admin::user();

        if (! $user || $user->isAdministrator()) {
            return true;
        }

        [$method, $url] = static::target($resource, $action);
        $target = Request::create($url, $method);

        if (static::isExcepted($target)) {
            return true;
        }

        $request = request();
        $cache = (array) $request->attributes->get(static::CACHE_ATTRIBUTE, []);
        $userKey = method_exists($user, 'getAuthIdentifier') ? $user->getAuthIdentifier() : spl_object_hash($user);
        $cacheKey = sha1($userKey.'|'.$target->method().'|'.$target->decodedPath());

        if (array_key_exists($cacheKey, $cache)) {
            return $cache[$cacheKey];
        }

        $allowed = (bool) $user->allPermissions()->first(function ($permission) use ($target, $action) {
            return ResourceRouteAction::matchesGridAction($permission, $action)
                && method_exists($permission, 'shouldPassThrough')
                && $permission->shouldPassThrough($target);
        });

        $cache[$cacheKey] = $allowed;
        $request->attributes->set(static::CACHE_ATTRIBUTE, $cache);

        return $allowed;
    }

    public static function mode(): string
    {
        $mode = strtolower((string) config('admin.permission.resource_actions.denied', static::MODE_HIDE));

        return in_array($mode, [static::MODE_HIDE, static::MODE_PROMPT], true)
            ? $mode
            : static::MODE_HIDE;
    }

    public static function shouldHide(Grid $grid, string $action): bool
    {
        return ! static::allows($grid, $action) && static::mode() === static::MODE_HIDE;
    }

    /**
     * Render a non-navigating control that explains the denied action.
     */
    public static function deniedControl(string $content, string $action, string $classes = '', string $tag = 'a'): string
    {
        static::registerPromptScript();

        $tag = $tag === 'button' ? 'button' : 'a';
        $label = static::actionLabel($action);
        $title = trans('admin.permission_denied');
        $message = trans('admin.resource_action_permission_denied', ['action' => $label]);
        $classes = trim($classes.' dcat-grid-action-permission-denied');
        $attributes = [
            'class'                   => $classes,
            'aria-disabled'           => 'true',
            'data-permission-title'   => $title,
            'data-permission-message' => $message,
        ];

        if ($tag === 'button') {
            $attributes['type'] = 'button';
        } else {
            $attributes['href'] = 'javascript:void(0)';
        }

        $htmlAttributes = collect($attributes)->map(function ($value, $key) {
            return sprintf(
                '%s="%s"',
                htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8')
            );
        })->implode(' ');

        return "<{$tag} {$htmlAttributes}>{$content}</{$tag}>";
    }

    protected static function enabled(string $action): bool
    {
        return (bool) config('admin.permission.resource_actions.actions.'.$action, true);
    }

    protected static function target(string $resource, string $action): array
    {
        $resource = rtrim(Str::before($resource, '?'), '/');
        $placeholder = '__dcat_permission__';

        switch ($action) {
            case 'create':
                return ['GET', $resource.'/create'];
            case 'edit':
            case 'quick_edit':
                return ['GET', $resource.'/'.$placeholder.'/edit'];
            case 'delete':
            case 'batch_delete':
                return ['DELETE', $resource.'/'.$placeholder];
            default:
                return ['GET', $resource];
        }
    }

    protected static function isExcepted(Request $target): bool
    {
        $excepts = array_merge(
            (array) config('admin.permission.except', []),
            Admin::context()->getArray('permission.except')
        );
        $current = trim($target->decodedPath(), '/');

        foreach ($excepts as $except) {
            $except = trim((string) $except);
            if ($except === '') {
                continue;
            }

            if (Str::contains($except, ':')) {
                [$methods, $except] = explode(':', $except, 2);
                $methods = array_filter(array_map('strtoupper', array_map('trim', explode(',', $methods))));

                if ($methods && ! in_array($target->method(), $methods, true)) {
                    continue;
                }
            }

            $pattern = trim(admin_base_path($except), '/');
            if (($pattern === '' && $current === '') || Str::is($pattern, $current)) {
                return true;
            }
        }

        return false;
    }

    protected static function actionLabel(string $action): string
    {
        $keys = [
            'create'       => 'admin.new',
            'edit'         => 'admin.edit',
            'quick_edit'   => 'admin.edit',
            'delete'       => 'admin.delete',
            'batch_delete' => 'admin.batch_delete',
        ];

        return trans($keys[$action] ?? 'admin.action');
    }

    protected static function registerPromptScript(): void
    {
        $request = request();
        if ($request->attributes->get(static::SCRIPT_ATTRIBUTE)) {
            return;
        }

        $request->attributes->set(static::SCRIPT_ATTRIBUTE, true);

        Admin::script(<<<'JS'
$(document)
    .off('click.dcatGridActionPermission', '.dcat-grid-action-permission-denied')
    .on('click.dcatGridActionPermission', '.dcat-grid-action-permission-denied', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();

        var $control = $(this);
        Dcat.swal.warning(
            $control.data('permission-title'),
            $control.data('permission-message'),
            {showCancelButton: false}
        );

        return false;
    });
JS
        );
    }
}
