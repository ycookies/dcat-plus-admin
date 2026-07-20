<?php

return [
    'labels' => [
        'Help' => '帮助内容',
        'help' => '帮助内容',
    ],
    'permissions' => [
        'resource' => ['group' => '帮助中心'],
        'description' => '管理帮助中心文章和使用说明',
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
