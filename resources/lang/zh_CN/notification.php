<?php

return [
    'labels' => [
        'Notification' => '通知管理',
        'notification' => '通知管理',
    ],
    'permissions' => [
        'resource' => ['group' => '系统管理'],
        'description' => '发布和维护后台管理员通知',
        'actions' => [
            'index'   => '查看通知列表',
            'show'    => '查看通知详情',
            'create'  => '新建通知',
            'store'   => '保存通知',
            'edit'    => '编辑通知',
            'update'  => '更新通知',
            'destroy' => '删除通知',
        ],
        'routes' => [],
    ],
];
