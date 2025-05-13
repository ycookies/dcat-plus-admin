<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use App\Models\Traits\HasPermissions;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminUser extends Authenticatable implements JWTSubject,AuthenticatableContract,Authorizable
{
    use HasPermissions, HasFactory, Notifiable,HasDateTimeFormatter;

    protected $table = 'admin_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'username',
        'name',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Create a new Eloquent model instance.
     *
     * @param  array  $attributes
     */
    public function __construct(array $attributes = [])
    {

        parent::__construct($attributes);
    }

    /**
     * A user has and belongs to many roles.
     *
     * @return BelongsToMany
     */
    public function roles(): BelongsToMany
    {
        $pivotTable = config('admin.database.role_users_table');

        $relatedModel = config('admin.database.roles_model');

        return $this->belongsToMany($relatedModel, $pivotTable, 'user_id', 'role_id')->withTimestamps();
    }



    /**
     * 判断是否允许查看菜单.
     *
     * @param  array|Menu  $menu
     * @return bool
     */
    public function canSeeMenu($menu)
    {
        return true;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * Get all menus that the user can access through their roles.
     *
     * @return array
     */
    public function getMenus($parent_id = 0)
    {
        $menuModel = config('admin.database.menu_model');
        $where = [];
        if(!empty($parent_id)){
            $where = [['parent_id','=',$parent_id]];
        }
        if (!$this->isAdministrator()) {
            $roleIds = $this->roles()->pluck('id')->toArray();

            $menuIds = DB::table(config('admin.database.role_menu_table'))
                ->whereIn('role_id', $roleIds)
                ->pluck('menu_id')
                ->unique()
                ->toArray();
            $menus = $menuModel::whereIn('id', $menuIds)
                ->where($where)
                ->orderBy('order')
                ->get()
                ->toArray();
        } else {
            $menus = $menuModel::where($where)->orderBy('order')->get()->toArray();
        }

        return $this->buildMenuTree($menus,$parent_id);
    }

    /**
     * Build menu tree from flat array
     *
     * @param array $menus
     * @param int $parentId
     * @return array
     */
    protected function buildMenuTree(array $menus, int $parentId = 0): array
    {
        $branch = [];

        foreach ($menus as $menu) {
            if ($menu['parent_id'] == $parentId) {
                $children = $this->buildMenuTree($menus, $menu['id']);

                if ($children) {
                    $menu['children'] = $children;
                }

                $branch[] = $menu;
            }
        }

        return $branch;
    }

    /**
     * Determine if current user is administrator.
     *
     * @return bool
     */
    public function isAdministrator(): bool
    {
        return $this->isRole('administrator');
    }

    /**
     * Check if user has specific role.
     *
     * @param string $role
     * @return bool
     */
    public function isRole(string $role): bool
    {
        return $this->roles()->where('slug', $role)->exists();
    }
}
