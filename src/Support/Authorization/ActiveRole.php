<?php

namespace Dcat\Admin\Support\Authorization;

use Dcat\Admin\Admin;
use Illuminate\Support\Collection;

/**
 * Resolves the single role that is active for an administrator session.
 *
 * The user's roles relationship is intentionally left untouched: it still
 * represents every role assigned to the user and is used by user management.
 */
class ActiveRole
{
    protected const SESSION_PREFIX = 'dcat_admin_active_role';

    public function enabled(): bool
    {
        return (bool) config('admin.permission.active_role.enable', false);
    }

    /**
     * Return every role assigned to the user, in a stable display order.
     */
    public function roles($user = null): Collection
    {
        $user = $user ?: Admin::user();
        if (! $user || ! method_exists($user, 'roles')) {
            return collect();
        }

        if (method_exists($user, 'loadMissing')) {
            $user->loadMissing('roles');
        }

        return collect($user->roles ?? [])
            ->sortBy(function ($role) {
                $key = method_exists($role, 'getKey') ? $role->getKey() : ($role->id ?? 0);

                return (string) ($role->name ?? '').'|'.str_pad((string) $key, 12, '0', STR_PAD_LEFT);
            })
            ->values();
    }

    /**
     * Return the current role. When the feature is disabled this simply
     * returns the first assigned role for display purposes.
     */
    public function current($user = null)
    {
        $user = $user ?: Admin::user();
        $roles = $this->roles($user);
        if ($roles->isEmpty()) {
            return null;
        }

        if (! $this->enabled()) {
            return $roles->first();
        }

        if ($role = $this->find($roles, $this->sessionRoleId($user))) {
            return $role;
        }

        return $this->set($user, $this->default($user, $roles));
    }

    /**
     * Return roles that are effective for authorization.
     */
    public function authorizationRoles($user = null): Collection
    {
        $roles = $this->roles($user);

        if (! $this->enabled()) {
            return $roles;
        }

        return ($role = $this->current($user)) ? collect([$role]) : collect();
    }

    /**
     * Start a fresh login session from the user's configured default role.
     */
    public function initialize($user)
    {
        if (! $this->enabled()) {
            return null;
        }

        $this->forget($user);

        return $this->set($user, $this->default($user, $this->roles($user)));
    }

    /**
     * Switch the active role after confirming it belongs to the user.
     */
    public function switch($user, $roleId)
    {
        if (! $this->enabled() || ! $user || ! $role = $this->find($this->roles($user), $roleId)) {
            return null;
        }

        return $this->set($user, $role);
    }

    /**
     * Ensure the persisted default role always belongs to the user.
     */
    public function synchronizeDefault($user)
    {
        if (! $user) {
            return null;
        }

        return $this->default($user, $this->roles($user), true);
    }

    public function forget($user): void
    {
        if ($session = $this->session()) {
            $session->forget($this->sessionKey($user));
        }
    }

    protected function default($user, Collection $roles, bool $persist = true)
    {
        if ($roles->isEmpty()) {
            return null;
        }

        $column = (string) config('admin.permission.active_role.default_column', 'default_role_id');
        $default = $this->find($roles, $this->attribute($user, $column));
        $default = $default ?: $roles->first();

        if ($persist && $this->attribute($user, $column) != $this->roleKey($default)) {
            $this->setAttribute($user, $column, $this->roleKey($default));

            if (method_exists($user, 'saveQuietly')) {
                $user->saveQuietly();
            } elseif (method_exists($user, 'save')) {
                $user->save();
            }
        }

        return $default;
    }

    protected function set($user, $role)
    {
        if (! $role) {
            return null;
        }

        if ($session = $this->session()) {
            $session->put($this->sessionKey($user), $this->roleKey($role));
        }

        if (method_exists($user, 'forgetAuthorizationCache')) {
            $user->forgetAuthorizationCache();
        }

        return $role;
    }

    protected function find(Collection $roles, $roleId)
    {
        if (! is_numeric($roleId) || (int) $roleId < 1) {
            return null;
        }

        return $roles->first(function ($role) use ($roleId) {
            return (string) $this->roleKey($role) === (string) $roleId;
        });
    }

    protected function sessionRoleId($user)
    {
        return ($session = $this->session()) ? $session->get($this->sessionKey($user)) : null;
    }

    protected function sessionKey($user): string
    {
        $app = app()->bound('admin.app') ? app('admin.app')->getName() : 'admin';
        $identifier = method_exists($user, 'getAuthIdentifier')
            ? $user->getAuthIdentifier()
            : ($user->getKey() ?? $user->id ?? 'guest');

        return static::SESSION_PREFIX.'.'.preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $app).'.'.$identifier;
    }

    protected function session()
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = app('request');

        return method_exists($request, 'hasSession') && $request->hasSession()
            ? $request->session()
            : null;
    }

    protected function roleKey($role)
    {
        return method_exists($role, 'getKey') ? $role->getKey() : ($role->id ?? null);
    }

    protected function attribute($user, string $key)
    {
        return method_exists($user, 'getAttribute') ? $user->getAttribute($key) : ($user->{$key} ?? null);
    }

    protected function setAttribute($user, string $key, $value): void
    {
        if (method_exists($user, 'setAttribute')) {
            $user->setAttribute($key, $value);

            return;
        }

        $user->{$key} = $value;
    }
}
