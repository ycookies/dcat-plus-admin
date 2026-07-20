<?php

namespace Dcat\Admin\Support\Authorization;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;

/**
 * Normalizes and synchronizes all role authorization relations.
 */
class RoleAuthorizationService
{
    protected RouteCatalog $catalog;

    protected RoutePermissionResolver $resolver;

    public function __construct(?RouteCatalog $catalog = null, ?RoutePermissionResolver $resolver = null)
    {
        $this->catalog = $catalog ?: new RouteCatalog();
        $this->resolver = $resolver ?: new RoutePermissionResolver();
    }

    public function payload(array $input): array
    {
        return [
            'present'           => (bool) Arr::get($input, 'role_authorization_present', false),
            'route_permissions' => $this->strings(Arr::get($input, 'route_permissions', [])),
            'menus'             => $this->integers(Arr::get($input, 'role_menus', [])),
        ];
    }

    public function sync(Model $role, array $payload): array
    {
        if (empty($payload['present'])) {
            return ['permission_ids' => [], 'menu_ids' => []];
        }

        $descriptors = $this->catalog->all(false);
        $selectedKeys = $this->strings($payload['route_permissions'] ?? []);
        $invalidKeys = array_values(array_diff($selectedKeys, array_keys($descriptors)));
        if ($invalidKeys) {
            throw new InvalidArgumentException(trans('admin.role_editor_routes_changed'));
        }

        // Permissions that cannot be represented by the current route catalog
        // may be legacy wildcards, logical abilities, or permissions belonging
        // to routes removed by an extension. They are deliberately hidden from
        // the simplified role editor, but must survive an unrelated role edit.
        $mappedPermissionIds = array_values(array_unique(array_map(function (Model $permission) {
            return (int) $permission->getKey();
        }, $this->resolver->map($descriptors))));
        $preservedPermissionIds = $this->preservedPermissionIds($role, $mappedPermissionIds);
        $permissionIds = $preservedPermissionIds;
        $autoCreate = (bool) config('admin.permission.role_editor.auto_create', true);
        foreach ($selectedKeys as $key) {
            $permission = $this->resolver->resolve($descriptors[$key], $autoCreate);
            if (! $permission) {
                throw new InvalidArgumentException(trans('admin.role_editor_permission_missing'));
            }

            $permissionIds[] = (int) $permission->getKey();
        }

        $permissionIds = array_values(array_unique($permissionIds));
        $role->permissions()->sync($permissionIds);

        $menuIds = [];
        if (config('admin.menu.role_bind_menu', true)) {
            $menuModel = config('admin.database.menu_model');
            $requestedMenuIds = $this->integers($payload['menus'] ?? []);
            if ($requestedMenuIds) {
                $menuIds = $menuModel::query()
                    ->whereKey($requestedMenuIds)
                    ->pluck((new $menuModel())->getKeyName())
                    ->map(function ($id) {
                        return (int) $id;
                    })
                    ->all();
            }

            $role->menus()->sync($menuIds);
            $menu = new $menuModel();
            method_exists($menu, 'flushAllCache') ? $menu->flushAllCache() : $menu->flushCache();
        }

        return [
            'permission_ids'           => $permissionIds,
            'preserved_permission_ids' => $preservedPermissionIds,
            'menu_ids'                 => $menuIds,
        ];
    }

    protected function preservedPermissionIds(Model $role, array $mappedPermissionIds): array
    {
        if (! $role->exists) {
            return [];
        }

        $permissionModel = config('admin.database.permissions_model');
        $permissionKey = (new $permissionModel())->getKeyName();
        $currentPermissionIds = $role->permissions()
            ->pluck($permissionKey)
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();

        return array_values(array_diff(
            array_values(array_unique($currentPermissionIds)),
            array_values(array_unique(array_map('intval', $mappedPermissionIds)))
        ));
    }

    protected function strings($values): array
    {
        if (is_string($values)) {
            $values = $values === '' ? [] : explode(',', $values);
        }

        return array_values(array_unique(array_filter(array_map(function ($value) {
            return is_scalar($value) ? trim((string) $value) : '';
        }, (array) $values), 'strlen')));
    }

    protected function integers($values): array
    {
        return array_values(array_unique(array_filter(array_map(function ($value) {
            return filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        }, $this->strings($values)))));
    }
}
