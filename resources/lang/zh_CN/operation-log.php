<?php

return [
    'title' => '操作日志',
    'setting_title' => '操作日志',
    'labels' => [
        'OperationLog' => '操作日志',
        'operation-log' => '操作日志',
    ],
    'permissions' => [
        'resource' => ['group' => '系统管理'],
        'description' => '查看和维护管理员在后台执行的操作记录',
        'actions' => [
            'index'   => '列表',
            'show'    => '查看',
            'destroy' => '删除',
        ],
        'routes' => [],
    ],
];
