<?php

return [
    'labels' => [
        'SkuAttribute' => 'SKU 规格',
        'sku-attribute' => 'SKU 规格',
    ],
    'permissions' => [
        'resource' => ['group' => '商品管理'],
        'description' => '管理商品 SKU 规格名称、类型和可选值',
        'actions' => [
            'index'   => '查看',
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
