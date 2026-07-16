<?php

namespace Dcat\Admin\Support\Authorization;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Matches route descriptors to existing permissions and creates missing ones
 * without modifying manually maintained permission records.
 */
class RoutePermissionResolver
{
    protected string $permissionModel;

    protected ?Collection $permissions = null;

    public function __construct(?string $permissionModel = null)
    {
        $this->permissionModel = $permissionModel ?: config('admin.database.permissions_model');
    }

    public function map(array $descriptors, ?Collection $permissions = null): array
    {
        $permissions = $permissions ?: $this->permissions();
        $matches = [];

        foreach ($descriptors as $key => $descriptor) {
            if ($permission = $this->findExisting($descriptor, $permissions)) {
                $matches[$key] = $permission;
            }
        }

        return $matches;
    }

    public function resolve(array $descriptor, bool $create = true): ?Model
    {
        if ($permission = $this->findExisting($descriptor, $this->permissions())) {
            return $permission;
        }

        if (! $create) {
            return null;
        }

        $slug = $this->availableSlug($descriptor);
        $attributes = [
            'parent_id'   => 0,
            'name'        => Str::limit($descriptor['label'] ?: $slug, 50, ''),
            'slug'        => $slug,
            'http_method' => $this->normalizeMethods($descriptor['http_methods'] ?? []),
            'http_path'   => [$this->normalizePath($descriptor['http_path'] ?? '/')],
        ];

        try {
            $permission = $this->permissionModel::create($attributes);
        } catch (QueryException $exception) {
            // A concurrent request may have created the same generated slug.
            $permission = $this->permissionModel::where('slug', $slug)->first();
            if (! $permission || ! $this->isExactMatch($permission, $descriptor)) {
                throw $exception;
            }
        }

        if ($this->permissions !== null && ! $this->permissions->contains($permission->getKey())) {
            $this->permissions->push($permission);
        }

        return $permission;
    }

    public function findExisting(array $descriptor, ?Collection $permissions = null): ?Model
    {
        $permissions = $permissions ?: $this->permissions();

        // Exact method/path matching is safer than reusing a same-slug wildcard permission.
        $exact = $permissions->first(function (Model $permission) use ($descriptor) {
            return $this->isExactMatch($permission, $descriptor);
        });
        if ($exact) {
            return $exact;
        }

        $slug = $descriptor['permission_slug'] ?? null;
        if (! $slug) {
            return null;
        }

        return $permissions->first(function (Model $permission) use ($slug, $descriptor) {
            return (string) $permission->slug === (string) $slug
                && $this->isExactMatch($permission, $descriptor);
        });
    }

    public function isExactMatch(Model $permission, array $descriptor): bool
    {
        $paths = array_values(array_unique(array_map(function ($path) {
            return $this->normalizePath($path);
        }, array_filter((array) $permission->http_path, 'strlen'))));
        $methods = $this->normalizeMethods((array) $permission->http_method);
        $expectedPaths = [$this->normalizePath($descriptor['http_path'] ?? '/')];
        $expectedMethods = $this->normalizeMethods($descriptor['http_methods'] ?? []);

        sort($paths);
        sort($expectedPaths);

        return $paths === $expectedPaths && $methods === $expectedMethods;
    }

    public function permissions(): Collection
    {
        if ($this->permissions === null) {
            $this->permissions = $this->permissionModel::query()->get();
        }

        return $this->permissions;
    }

    protected function availableSlug(array $descriptor): string
    {
        $base = (string) ($descriptor['permission_slug'] ?? 'route');
        $existing = $this->permissionModel::where('slug', $base)->first();
        if (! $existing) {
            return $base;
        }

        if ($this->isExactMatch($existing, $descriptor)) {
            return $base;
        }

        $suffix = substr(sha1(implode('|', [
            $descriptor['route_name'] ?? '',
            $descriptor['uri'] ?? '',
            implode(',', $descriptor['http_methods'] ?? []),
        ])), 0, 10);

        return rtrim(substr($base, 0, 39), '.-').'.'.$suffix;
    }

    protected function normalizePath($path): string
    {
        $path = preg_replace('/\{[^}]+\}/', '*', trim((string) $path));

        return '/'.ltrim((string) $path, '/');
    }

    protected function normalizeMethods(array $methods): array
    {
        $methods = array_values(array_unique(array_filter(array_map(function ($method) {
            $method = strtoupper(trim((string) $method));

            return in_array($method, ['HEAD', 'OPTIONS'], true) ? null : $method;
        }, $methods))));
        sort($methods);

        return $methods;
    }
}
