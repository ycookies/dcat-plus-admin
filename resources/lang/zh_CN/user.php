<?php

return [
    'labels' => [
        'User' => '管理员',
        'user' => '管理员',
    ],
    'permissions' => [
        'resource' => ['group' => '权限管理'],
        'description' => '管理后台管理员账号、状态和角色绑定',
        'actions' => [
            'index'   => '列表',
            'show'    => '查看',
            'create'  => '新建',
            'store'   => '保存',
            'edit'    => '编辑',
            'update'  => '更新',
            'destroy' => '删除',
        ],
        'routes' => [],
    ],
];
