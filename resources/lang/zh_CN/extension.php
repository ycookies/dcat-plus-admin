<?php

return [
    'labels' => [
        'Extensions' => '扩展管理',
    ],
    'fields' => [
        'alias'       => '别名',
        'description' => '描述',
        'authors'     => '开发者',
        'homepage'    => '主页',
        'require'     => '依赖',
        'require_dev' => '开发环境依赖',
        'name'        => '包名',
        'version'     => '版本',
        'enable'      => '启用',
        'config'      => '配置',
        'imported'    => '导入',
    ],
    'options' => [
    ],
    'permissions' => [
        'resource' => ['group' => '系统管理'],
        'description' => '安装、启用、配置和维护后台扩展',
        'actions' => [
            'index'   => '查看扩展列表',
            'show'    => '查看扩展详情',
            'create'  => '安装扩展',
            'store'   => '保存扩展',
            'edit'    => '配置扩展',
            'update'  => '更新扩展配置',
            'destroy' => '卸载扩展',
        ],
        'routes' => [],
    ],
];
