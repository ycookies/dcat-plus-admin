<?php

return [
    'labels' => [
        'Role' => '角色管理',
        'role' => '角色管理',
    ],
    'permissions' => [
        'resource' => ['group' => '权限管理'],
        'description' => '管理后台角色及其路由权限和菜单范围',
        'actions' => [
            'index'   => '查看角色列表',
            'show'    => '查看角色详情',
            'create'  => '新建角色',
            'store'   => '保存角色',
            'edit'    => '编辑角色',
            'update'  => '更新角色',
            'destroy' => '删除角色',
        ],
        'routes' => [],
    ],
];
