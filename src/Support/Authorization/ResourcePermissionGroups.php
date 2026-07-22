<?php

namespace Dcat\Admin\Support\Authorization;

/**
 * Logical resource capabilities shared by the role editor and SaaS packages.
 */
class ResourcePermissionGroups
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            'preview' => [
                'label'   => 'admin.permission_group_preview',
                'type'    => 'view',
                'actions' => ['index', 'show'],
            ],
            'create' => [
                'label'   => 'admin.permission_action_create',
                'type'    => 'change',
                'actions' => ['create', 'store'],
            ],
            'edit' => [
                'label'   => 'admin.permission_action_edit',
                'type'    => 'change',
                'actions' => ['edit', 'update'],
            ],
            'delete' => [
                'label'   => 'admin.permission_action_destroy',
                'type'    => 'change',
                'actions' => ['destroy'],
            ],
            'import' => [
                'label'   => 'admin.permission_action_import',
                'type'    => 'change',
                'actions' => ['import'],
            ],
            'export' => [
                'label'   => 'admin.permission_action_export',
                'type'    => 'view',
                'actions' => ['export'],
            ],
        ];
    }
}
