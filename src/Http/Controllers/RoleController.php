<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Grid;
use Dcat\Admin\Http\Auth\Permission;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Http\Repositories\Role as RoleRepository;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Show;
use Dcat\Admin\Support\Authorization\RoleAuthorizationService;
use Dcat\Admin\Support\Authorization\RouteCatalog;
use Dcat\Admin\Support\Authorization\RoutePermissionResolver;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\Tree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Throwable;

class RoleController extends AdminController
{
    public function title()
    {
        return trans('admin.roles');
    }

    protected function grid()
    {
        return new Grid(new RoleRepository(), function (Grid $grid) {
            $grid->column('id', 'ID')->sortable();
            $grid->column('slug')->label('primary');
            $grid->column('name');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();

            $grid->quickSearch(['id', 'name', 'slug']);

            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $roleModel = config('admin.database.roles_model');
                if ($roleModel::isAdministrator($actions->row->slug)) {
                    $actions->disableDelete();
                }
            });
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new RoleRepository('permissions'), function (Show $show) {
            $show->field('id');
            $show->field('slug');
            $show->field('name');

            $show->field('permissions')->unescape()->as(function ($permission) {
                $permissionModel = config('admin.database.permissions_model');
                $permissionModel = new $permissionModel();
                $nodes = $permissionModel->allNodes();
                $tree = Tree::make($nodes);
                $tree->check(array_column(Helper::array($permission), $permissionModel->getKeyName()));

                return $tree->render();
            });

            $show->field('created_at');
            $show->field('updated_at');

            $roleModel = config('admin.database.roles_model');
            if ($show->getKey() == $roleModel::ADMINISTRATOR_ID) {
                $show->disableDeleteButton();
            }
        });
    }

    /**
     * Laravel Blade role creation page. No Dcat Form instance is involved.
     */
    public function create(Content $content)
    {
        $roleModel = config('admin.database.roles_model');

        return $this->editor($content, new $roleModel(), false);
    }

    /**
     * Laravel Blade role editing page. No Dcat Form instance is involved.
     */
    public function edit($id, Content $content)
    {
        return $this->editor($content, $this->findRole($id), true);
    }

    public function store()
    {
        /** @var Request $request */
        $request = request();
        $roleModel = config('admin.database.roles_model');
        $role = new $roleModel();

        return $this->persist($request, $role, false);
    }

    public function update($id)
    {
        /** @var Request $request */
        $request = request();

        return $this->persist($request, $this->findRole($id), true);
    }

    public function destroy($id)
    {
        $roleModel = config('admin.database.roles_model');
        $ids = array_values(array_unique(array_filter(array_map('intval', Helper::array($id)))));

        if (in_array((int) $roleModel::ADMINISTRATOR_ID, $ids, true)) {
            Permission::error();
        }

        $model = new $roleModel();
        $model->getConnection()->transaction(function () use ($roleModel, $ids) {
            $roleModel::query()->whereKey($ids)->get()->each(function (Model $role) {
                $role->delete();
            });
        });

        $menuModel = config('admin.database.menu_model');
        $menu = new $menuModel();
        method_exists($menu, 'flushAllCache') ? $menu->flushAllCache() : $menu->flushCache();

        return JsonResponse::make()
            ->success(trans('admin.delete_succeeded'))
            ->refresh()
            ->send();
    }

    protected function editor(Content $content, Model $role, bool $editing)
    {
        admin_require_assets('@jstree');

        $oldInput = session()->hasOldInput('role_authorization_present')
            ? session()->getOldInput()
            : [];
        $bindMenu = (bool) config('admin.menu.role_bind_menu', true);

        return $content
            ->title($this->title())
            ->description($editing ? trans('admin.edit') : trans('admin.create'))
            ->body(view('admin::auth.role-form', [
                'role'             => $role,
                'editing'          => $editing,
                'permissionEditor' => $this->permissionEditorData($role, $oldInput),
                'menuEditor'       => $bindMenu ? $this->menuEditorData($role, $oldInput) : [],
                'bindMenu'         => $bindMenu,
                'isAdministrator'  => $role->exists && (int) $role->getKey() === (int) $role::ADMINISTRATOR_ID,
            ]));
    }

    protected function persist(Request $request, Model $role, bool $editing)
    {
        $validated = $this->validateRole($request, $role, $editing);
        $service = new RoleAuthorizationService();
        $payload = $service->payload($request->all());

        try {
            $role->getConnection()->transaction(function () use ($role, $validated, $payload, $service) {
                $role->name = $validated['name'];

                // The administrator slug controls the runtime bypass and must not
                // be changed through the role editor.
                if (! ($role->exists && (int) $role->getKey() === (int) $role::ADMINISTRATOR_ID)) {
                    $role->slug = $validated['slug'];
                }

                $role->save();
                $service->sync($role, $payload);
            });
        } catch (Throwable $exception) {
            report($exception);

            $message = $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : trans($editing ? 'admin.update_failed' : 'admin.save_failed');

            return back()->withInput()->withErrors(['role_authorization' => $message]);
        }

        admin_success(trans($editing ? 'admin.update_succeeded' : 'admin.save_succeeded'));

        return redirect(admin_url('auth/roles'));
    }

    protected function validateRole(Request $request, Model $role, bool $editing): array
    {
        $connection = $role->getConnectionName();
        $table = $role->getTable();
        $qualifiedTable = $connection ? $connection.'.'.$table : $table;
        $slugRule = Rule::unique($qualifiedTable, 'slug');

        if ($editing) {
            $slugRule->ignore($role->getKey(), $role->getKeyName());
        }

        $rules = [
            'name'                       => ['required', 'string', 'max:50'],
            'slug'                       => ['required', 'string', 'max:50', $slugRule],
            'role_authorization_present' => ['required', 'accepted'],
            'route_permissions'          => ['nullable', 'array'],
            'route_permissions.*'        => ['string'],
            'custom_permissions'         => ['nullable', 'array'],
            'custom_permissions.*'       => ['integer', 'min:1'],
            'role_menus'                 => ['nullable', 'string'],
        ];

        if ($editing && (int) $role->getKey() === (int) $role::ADMINISTRATOR_ID) {
            $rules['slug'] = ['required', Rule::in([(string) $role->slug])];
        }

        return $request->validate($rules);
    }

    protected function findRole($id): Model
    {
        $roleModel = config('admin.database.roles_model');
        $relations = ['permissions'];
        if (config('admin.menu.role_bind_menu', true)) {
            $relations[] = 'menus';
        }

        return $roleModel::query()->with($relations)->findOrFail($id);
    }

    protected function permissionEditorData(Model $role, array $oldInput = []): array
    {
        $catalog = new RouteCatalog();
        $resolver = new RoutePermissionResolver();
        $descriptors = $catalog->all();
        $grouped = $catalog->grouped();
        $permissions = $resolver->permissions();
        $matches = $resolver->map($descriptors, $permissions);
        $permissionModel = config('admin.database.permissions_model');
        $permissionKey = (new $permissionModel())->getKeyName();
        $checkedIds = $role->exists
            ? array_map('intval', $role->permissions->pluck($permissionKey)->all())
            : [];
        $hasOldInput = array_key_exists('role_authorization_present', $oldInput);
        $oldPayload = $hasOldInput ? (new RoleAuthorizationService())->payload($oldInput) : [];
        $selectedRouteKeys = $oldPayload['route_permissions'] ?? [];
        $selectedCustomIds = $oldPayload['custom_permissions'] ?? [];

        $decorate = function (array $descriptor) use ($matches, $checkedIds, $hasOldInput, $selectedRouteKeys) {
            $permission = $matches[$descriptor['key']] ?? null;
            $descriptor['permission_id'] = $permission ? (int) $permission->getKey() : null;
            $descriptor['checked'] = $hasOldInput
                ? in_array($descriptor['key'], $selectedRouteKeys, true)
                : ($permission && in_array((int) $permission->getKey(), $checkedIds, true));

            return $descriptor;
        };

        foreach ($grouped['resources'] as &$resource) {
            $resource['actions'] = array_map($decorate, $resource['actions']);
        }
        unset($resource);
        $grouped['singles'] = array_map($decorate, $grouped['singles']);
        $grouped['system'] = array_map($decorate, $grouped['system']);

        $mappedPermissionIds = array_values(array_unique(array_map(function ($permission) {
            return (int) $permission->getKey();
        }, $matches)));
        $customPermissions = [];

        foreach (Helper::array((new $permissionModel())->allNodes()) as $node) {
            $node = Helper::array($node);
            $permissionId = (int) ($node[$permissionKey] ?? $node['id'] ?? 0);
            if (! $permissionId || in_array($permissionId, $mappedPermissionIds, true)) {
                continue;
            }

            $customPermissions[] = [
                'id'          => $permissionId,
                'parent_id'   => (int) ($node['parent_id'] ?? 0),
                'name'        => (string) ($node['name'] ?? ''),
                'slug'        => (string) ($node['slug'] ?? ''),
                'http_method' => array_values(array_filter((array) ($node['http_method'] ?? []))),
                'http_path'   => array_values(array_filter((array) ($node['http_path'] ?? []))),
                'checked'     => $hasOldInput
                    ? in_array($permissionId, $selectedCustomIds, true)
                    : in_array($permissionId, $checkedIds, true),
            ];
        }

        return [
            'resources'         => $grouped['resources'],
            'singles'           => $grouped['singles'],
            'systemRoutes'      => $grouped['system'],
            'customPermissions' => $customPermissions,
            'autoCreate'        => (bool) config('admin.permission.role_editor.auto_create', true),
        ];
    }

    protected function menuEditorData(Model $role, array $oldInput = []): array
    {
        $menuModel = config('admin.database.menu_model');
        $menu = new $menuModel();
        $keyName = $menu->getKeyName();
        $hasOldInput = array_key_exists('role_authorization_present', $oldInput);
        $oldPayload = $hasOldInput ? (new RoleAuthorizationService())->payload($oldInput) : [];
        $checkedIds = $hasOldInput
            ? ($oldPayload['menus'] ?? [])
            : ($role->exists && $role->relationLoaded('menus')
                ? array_map('intval', $role->menus->pluck($keyName)->all())
                : []);
        $treeNodes = [];

        foreach (Helper::array($menu->allNodes()) as $node) {
            $node = Helper::array($node);
            $id = (int) ($node[$keyName] ?? $node['id'] ?? 0);
            if (! $id) {
                continue;
            }

            $treeNodes[] = [
                'id'     => (string) $id,
                'parent' => empty($node['parent_id']) ? '#' : (string) $node['parent_id'],
                'text'   => (string) ($node['title'] ?? ('menu-'.$id)),
                'state'  => ['selected' => in_array($id, $checkedIds, true)],
            ];
        }

        return [
            'nodes'   => $treeNodes,
            'cascade' => (bool) config('admin.permission.role_editor.menu_cascade', true),
        ];
    }
}
