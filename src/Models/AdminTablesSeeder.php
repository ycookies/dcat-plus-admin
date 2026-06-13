<?php

namespace Dcat\Admin\Models;

use Illuminate\Database\Seeder;

class AdminTablesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $createdAt = date('Y-m-d H:i:s');

        // create a user.
        Administrator::truncate();
        Administrator::create([
            'username'   => 'admin',
            'password'   => bcrypt('admin'),
            'name'       => 'Administrator',
            'created_at' => $createdAt,
        ]);

        // create a role.
        Role::truncate();
        Role::create([
            'name'       => 'Administrator',
            'slug'       => Role::ADMINISTRATOR,
            'created_at' => $createdAt,
        ]);

        // add role to user.
        Administrator::first()->roles()->save(Role::first());

        //create a permission
        Permission::truncate();
        Permission::insert([
            [
                'id'          => 1,
                'name'        => 'Auth management',
                'slug'        => 'auth-management',
                'http_method' => '',
                'http_path'   => '',
                'parent_id'   => 0,
                'order'       => 1,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 2,
                'name'        => 'Users',
                'slug'        => 'users',
                'http_method' => '',
                'http_path'   => '/auth/users*',
                'parent_id'   => 1,
                'order'       => 2,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 3,
                'name'        => 'Roles',
                'slug'        => 'roles',
                'http_method' => '',
                'http_path'   => '/auth/roles*',
                'parent_id'   => 1,
                'order'       => 3,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 4,
                'name'        => 'Permissions',
                'slug'        => 'permissions',
                'http_method' => '',
                'http_path'   => '/auth/permissions*',
                'parent_id'   => 1,
                'order'       => 4,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 5,
                'name'        => 'Menu',
                'slug'        => 'menu',
                'http_method' => '',
                'http_path'   => '/auth/menu*',
                'parent_id'   => 1,
                'order'       => 5,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 6,
                'name'        => 'Extension',
                'slug'        => 'extension',
                'http_method' => '',
                'http_path'   => '/auth/extensions*',
                'parent_id'   => 1,
                'order'       => 6,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 7,
                'name'        => 'Member',
                'slug'        => 'member',
                'http_method' => '',
                'http_path'   => '/member-user*',
                'parent_id'   => 0,
                'order'       => 7,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 8,
                'name'        => 'Web config',
                'slug'        => 'web-config',
                'http_method' => '',
                'http_path'   => '/web-config*',
                'parent_id'   => 0,
                'order'       => 8,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 9,
                'name'        => 'Openapi docs',
                'slug'        => 'openapi-docs',
                'http_method' => '',
                'http_path'   => '/openapi-docs*',
                'parent_id'   => 0,
                'order'       => 9,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 10,
                'name'        => 'Notifications',
                'slug'        => 'notifications',
                'http_method' => '',
                'http_path'   => '/notifications*',
                'parent_id'   => 0,
                'order'       => 10,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 11,
                'name'        => 'Helps',
                'slug'        => 'helps',
                'http_method' => '',
                'http_path'   => '',
                'parent_id'   => 0,
                'order'       => 11,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 12,
                'name'        => 'Help categories',
                'slug'        => 'help-categories',
                'http_method' => '',
                'http_path'   => '/help-categories*',
                'parent_id'   => 11,
                'order'       => 12,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 13,
                'name'        => 'Help contents',
                'slug'        => 'help-contents',
                'http_method' => '',
                'http_path'   => '/helps*',
                'parent_id'   => 11,
                'order'       => 13,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 14,
                'name'        => 'Operation Log',
                'slug'        => 'operation-log',
                'http_method' => '',
                'http_path'   => '/auth/operation-logs*',
                'parent_id'   => 0,
                'order'       => 14,
                'created_at'  => $createdAt,
            ],
            [
                'id'          => 15,
                'name'        => 'System Log',
                'slug'        => 'system-log',
                'http_method' => '',
                'http_path'   => '/auth/system-log-viewer*',
                'parent_id'   => 0,
                'order'       => 15,
                'created_at'  => $createdAt,
            ],

        ]);

//        Role::first()->permissions()->save(Permission::first());

        // add default menus.
        Menu::truncate();
        Menu::insert([
            [
                'parent_id'     => 0,
                'order'         => 1,
                'title'         => 'Index',
                'icon'          => 'feather icon-bar-chart-2',
                'uri'           => '/',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 0,
                'order'         => 2,
                'title'         => 'Admin',
                'icon'          => 'feather icon-settings',
                'uri'           => '',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 2,
                'order'         => 3,
                'title'         => 'Users',
                'icon'          => '',
                'uri'           => 'auth/users',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 2,
                'order'         => 4,
                'title'         => 'Roles',
                'icon'          => '',
                'uri'           => 'auth/roles',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 2,
                'order'         => 5,
                'title'         => 'Permission',
                'icon'          => '',
                'uri'           => 'auth/permissions',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 2,
                'order'         => 6,
                'title'         => 'Menu',
                'icon'          => '',
                'uri'           => 'auth/menu',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 2,
                'order'         => 7,
                'title'         => 'Extensions',
                'icon'          => '',
                'uri'           => 'auth/extensions',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 0,
                'order'         => 8,
                'title'         => 'Member',
                'icon'          => 'feather icon-users',
                'uri'           => '',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 8,
                'order'         => 9,
                'title'         => 'Member manage',
                'icon'          => 'feather icon-user',
                'uri'           => 'member-user',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 0,
                'order'         => 10,
                'title'         => 'Web config',
                'icon'          => 'feather icon-settings',
                'uri'           => 'web-config',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 0,
                'order'         => 11,
                'title'         => 'Openapi docs',
                'icon'          => 'feather icon-layers',
                'uri'           => 'openapi-docs',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 0,
                'order'         => 12,
                'title'         => 'Notifications',
                'icon'          => 'feather icon-bell',
                'uri'           => 'notifications',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 0,
                'order'         => 13,
                'title'         => 'Helps',
                'icon'          => 'feather icon-help-circle',
                'uri'           => '',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 13,
                'order'         => 14,
                'title'         => 'Help categories',
                'icon'          => '',
                'uri'           => 'help-categories',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 13,
                'order'         => 15,
                'title'         => 'Help contents',
                'icon'          => '',
                'uri'           => 'helps',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 2,
                'order'         => 16,
                'title'         => 'Operation Log',
                'icon'          => 'fa-file-text-o',
                'uri'           => 'auth/operation-logs',
                'created_at'    => $createdAt,
            ],
            [
                'parent_id'     => 2,
                'order'         => 17,
                'title'         => 'System Log',
                'icon'          => 'fa-file-text-o',
                'uri'           => 'auth/system-log-viewer',
                'created_at'    => $createdAt,
            ],
        ]);

        (new Menu())->flushCache();
        $this->seedMemberUsers();
    }

    protected function seedMemberUsers(): void
    {
        \Illuminate\Support\Facades\DB::table('member_users')->updateOrInsert(
            ['id' => 1],
            [
                'id' => 1,
                'username' => '杨光',
                'phone' => '18966172031',
                'email' => '3664839@qq.com',
                'password' => '$2y$12$sAa5XY7SRkOBTuqlEJ9Yn.sAM0MRrODvYp222DlU7YB17Zf36wQsa',
                'last_login_time' => null,
                'last_login_ip' => null,
                'phone_verified' => null,
                'email_verified' => null,
                'avatar' => 'https://www.dcat-admin.com/img/author-avatar.png',
                'avatar_medium' => 'https://www.dcat-admin.com/img/author-avatar.png',
                'avatar_big' => 'https://www.dcat-admin.com/img/author-avatar.png',
                'gender' => null,
                'realname' => null,
                'signature' => null,
                'vip_id' => 1,
                'vip_expire' => null,
                'nickname' => null,
                'status' => null,
                'balance' => 0,
                'freeze_price' => 0,
                'group_id' => null,
                'delete_at_time' => null,
                'is_deleted' => null,
                'message_count' => null,
                'register_ip' => null,
                'is_certified' => 0,
                'parent_id' => null,
                'temp_parent_id' => null,
                'junior_at' => null,
                'created_at' => '2026-03-20 19:19:44',
                'updated_at' => '2026-03-20 19:19:44',
            ]
        );
    }
}
