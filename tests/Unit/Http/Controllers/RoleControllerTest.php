<?php

namespace Dcat\Admin\Tests\Unit\Http\Controllers;

use Dcat\Admin\Http\Controllers\RoleController;
use PHPUnit\Framework\TestCase;

class RoleControllerTest extends TestCase
{
    public function test_menu_nodes_are_normalized_before_being_sent_to_jstree(): void
    {
        $nodes = [
            ['id' => 1, 'parent_id' => 0, 'title' => 'Root'],
            ['id' => 2, 'parent_id' => 1, 'title' => 'Valid child'],
            ['id' => 3, 'parent_id' => 99, 'title' => 'Orphan'],
            ['id' => 4, 'parent_id' => 4, 'title' => 'Self cycle'],
            ['id' => 5, 'parent_id' => 6, 'title' => 'Cycle A'],
            ['id' => 6, 'parent_id' => 5, 'title' => 'Cycle B'],
            ['id' => 7, 'parent_id' => 3, 'title' => 'Orphan child'],
            ['id' => 8, 'parent_id' => 2, 'title' => 'Valid grandchild'],
            ['id' => 2, 'parent_id' => 0, 'title' => 'Duplicate'],
            ['id' => 0, 'parent_id' => 0, 'title' => 'Invalid'],
        ];

        $normalized = (new TestableRoleController())->normalizeMenus($nodes, 'id', [2, 3, 4, 5, 6, 7, 8]);
        $byId = collect($normalized)->keyBy('id');

        $this->assertCount(3, $normalized);
        $this->assertSame('#', $byId['1']['parent']);
        $this->assertSame('1', $byId['2']['parent']);
        $this->assertSame('2', $byId['8']['parent']);
        $this->assertFalse($byId->has('3'));
        $this->assertFalse($byId->has('4'));
        $this->assertFalse($byId->has('5'));
        $this->assertFalse($byId->has('6'));
        $this->assertFalse($byId->has('7'));
        $this->assertSame('Valid child', $byId['2']['text']);
        $this->assertTrue($byId['2']['state']['selected']);
        $this->assertTrue($byId['8']['state']['selected']);
        $this->assertFalse($byId['1']['state']['selected']);
    }

    public function test_single_routes_are_grouped_for_permission_assignment(): void
    {
        $routes = [
            ['key' => 'route:1', 'permission_group' => '系统设置'],
            ['key' => 'route:2', 'permission_group' => '开发工具'],
            ['key' => 'route:3', 'permission_group' => '系统设置'],
            ['key' => 'route:4', 'permission_group' => ''],
        ];

        $groups = (new TestableRoleController())->groupSingles($routes);

        $this->assertSame(['系统设置', '开发工具', '其他路由'], array_column($groups, 'title'));
        $this->assertSame(['route:1', 'route:3'], array_column($groups[0]['routes'], 'key'));
        $this->assertSame(['route:2'], array_column($groups[1]['routes'], 'key'));
        $this->assertSame(['route:4'], array_column($groups[2]['routes'], 'key'));
        $this->assertSame(substr(sha1('系统设置'), 0, 12), $groups[0]['key']);
    }

    public function test_resource_routes_are_grouped_for_permission_assignment(): void
    {
        $resources = [
            ['key' => 'users', 'group' => '用户中心'],
            ['key' => 'roles', 'group' => '权限管理'],
            ['key' => 'members', 'group' => '用户中心'],
            ['key' => 'reports', 'group' => ''],
        ];

        $groups = (new TestableRoleController())->groupResources($resources);

        $this->assertSame(['用户中心', '权限管理', '其他资源'], array_column($groups, 'title'));
        $this->assertSame(['users', 'members'], array_column($groups[0]['resources'], 'key'));
        $this->assertSame(['roles'], array_column($groups[1]['resources'], 'key'));
        $this->assertSame(['reports'], array_column($groups[2]['resources'], 'key'));
        $this->assertSame(substr(sha1('用户中心'), 0, 12), $groups[0]['key']);
    }
}

class TestableRoleController extends RoleController
{
    public function normalizeMenus(array $nodes, string $keyName, array $checkedIds): array
    {
        return $this->normalizeMenuTreeNodes($nodes, $keyName, $checkedIds);
    }

    public function groupSingles(array $routes): array
    {
        return $this->groupSingleRoutes($routes, '其他路由');
    }

    public function groupResources(array $resources): array
    {
        return $this->groupResourceRoutes($resources, '其他资源');
    }
}
