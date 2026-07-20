<?php

return [
    'labels' => [
        'Permission' => '权限管理',
        'permission' => '权限管理',
    ],
    'permissions' => [
        'resource' => ['group' => '权限管理'],
        'description' => '管理后台已有权限规则和自定义权限',
        'actions' => [
            'index'   => '查看权限列表',
            'show'    => '查看权限详情',
            'create'  => '新建权限',
            'store'   => '保存权限',
            'edit'    => '编辑权限',
            'update'  => '更新权限',
            'destroy' => '删除权限',
        ],
        'routes' => [],
    ],
];
