<?php

namespace Dcat\Admin\Traits;

use Dcat\Admin\Support\Authorization\ActiveRole;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

trait HasPermissions
{
    protected $allPermissions;

    /**
     * Get all permissions of user.
     *
     * @return mixed
     */
    public function allPermissions(): Collection
    {
        if ($this->allPermissions !== null) {
            return $this->allPermissions;
        }

        $activeRole = app(ActiveRole::class);

        // Preserve the legacy eager-loading behavior when the opt-in active
        // role feature is disabled, so a user with many roles does not cause
        // one query per role.
        if (! $activeRole->enabled() && method_exists($this, 'loadMissing')) {
            $this->loadMissing('roles.permissions');
        }

        $roles = $this->authorizationRoles();

        // Load only the effective role's permissions in active-role mode;
        // legacy mode continues to aggregate every assigned role.
        $roles->each(function ($role) {
            if (method_exists($role, 'loadMissing')) {
                $role->loadMissing('permissions');
            }
        });

        return $this->allPermissions =
            $roles
            ->pluck('permissions')
            ->flatten()
            ->keyBy($this->getKeyName());
    }

    /**
     * All roles assigned to the user remain available for management/UI.
     * Authorization itself uses this one-role collection when active roles
     * are enabled.
     */
    public function authorizationRoles(): Collection
    {
        return app(ActiveRole::class)->authorizationRoles($this);
    }

    /**
     * Get the role currently active in this administrator session.
     */
    public function activeRole()
    {
        return app(ActiveRole::class)->current($this);
    }

    /**
     * Reset request-local permission data after a role switch.
     */
    public function forgetAuthorizationCache(): void
    {
        $this->allPermissions = null;
    }

    /**
     * Check if user has permission.
     *
     * @param $ability
     * @param  array|mixed  $arguments
     * @return bool
     */
    public function can($ability, $paramters = []): bool
    {
        if (! $ability) {
            return false;
        }

        if ($this->isAdministrator()) {
            return true;
        }

        $permissions = $this->allPermissions();

        return $permissions->pluck('slug')->contains($ability) ?:
            $permissions
            ->pluck('id')
            ->contains($ability);
    }

    /**
     * Check if user has no permission.
     *
     * @param $permission
     * @return bool
     */
    public function cannot(string $permission): bool
    {
        return ! $this->can($permission);
    }

    /**
     * Check if user is administrator.
     *
     * @return mixed
     */
    public function isAdministrator(): bool
    {
        $roleModel = config('admin.database.roles_model');

        return $this->isRole($roleModel::ADMINISTRATOR);
    }

    /**
     * Check if user is $role.
     *
     * @param  string  $role
     * @return mixed
     */
    public function isRole(string $role): bool
    {
        /* @var Collection $roles */
        $roles = $this->authorizationRoles();

        return $roles->pluck('slug')->contains($role) ?:
            $roles->pluck('id')->contains($role);
    }

    /**
     * Check if user in $roles.
     *
     * @param  string|array|Arrayable  $roles
     * @return mixed
     */
    public function inRoles($roles = []): bool
    {
        /* @var Collection $all */
        $all = $this->authorizationRoles();

        $roles = Helper::array($roles);

        return $all->pluck('slug')->intersect($roles)->isNotEmpty() ?:
            $all->pluck('id')->intersect($roles)->isNotEmpty();
    }

    /**
     * If visible for roles.
     *
     * @param $roles
     * @return bool
     */
    public function visible($roles = []): bool
    {
        if (empty($roles)) {
            return false;
        }

        if ($this->isAdministrator()) {
            return true;
        }

        return $this->inRoles($roles);
    }

    /**
     * Detach models from the relationship.
     *
     * @return void
     */
    protected static function bootHasPermissions()
    {
        static::deleting(function ($model) {
            $model->roles()->detach();
        });
    }
}
