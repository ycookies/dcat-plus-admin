<?php

namespace Dcat\Admin\Support\Authorization;

use Dcat\Admin\Application;
use Dcat\Admin\Layout\Menu as MenuLayout;
use Dcat\Admin\Support\Helper;
use IlluminateDatabaseEloquentModel;

/**
 * Read-only, panel-scoped menu tree catalog.
 *
 * Menu IDs are preserved because this first version intentionally keeps the
 * existing database structure unchanged. Invalid orphan/cyclic branches are
 * omitted so consumers can safely render the returned tree.
 */
class MenuPackage
{
    protected PanelContext $panels;

    public function __construct(?PanelContext $panels = null)
    {
        $this->panels = $panels ?: new PanelContext();
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $panel = Application::DEFAULT): array
    {
        return $this->panels->run($panel, function () use ($panel) {
            $menuModel = config('admin.database.menu_model');
            $menu = new $menuModel();
            $nodes = $menu->allNodes(true);
            $tree = $this->tree(
                Helper::array($nodes, false),
                $panel,
                $menu->getKeyName(),
                new MenuLayout()
            );

            return [
                'panel'   => $panel,
                'version' => $this->version($tree),
                'tree'    => $tree,
            ];
        });
    }

    /**
     * @param  array<int, mixed>  $nodes
     * @return array<int, array<string, mixed>>
     */
    protected function tree(array $nodes, string $panel, string $keyName, MenuLayout $menu): array
    {
        $records = [];

        foreach ($nodes as $node) {
            $node = $node instanceof Model ? $node->toArray() : Helper::array($node, false);
            $id = (int) ($node[$keyName] ?? $node['id'] ?? 0);

            if (! $id || isset($records[$id])) {
                continue;
            }

            $uri = trim((string) ($node['uri'] ?? ''), '/');
            $rawTitle = (string) ($node['title'] ?? "menu-{$id}");
            $records[$id] = [
                'id'        => $id,
                'key'       => "menu:{$panel}:{$id}",
                'parent_id' => (int) ($node['parent_id'] ?? 0),
                // Keep the original value for configuration and return the
                // same localized title as the rendered sidebar menu.
                'title'          => $this->translateTitle($menu, $rawTitle),
                'original_title' => $rawTitle,
                'icon'      => (string) ($node['icon'] ?? ''),
                'uri'       => $uri,
                'url'       => $uri === '' ? null : (url()->isValidUrl($uri) ? $uri : admin_url($uri)),
                'external'  => $uri !== '' && url()->isValidUrl($uri),
                'extension' => (string) ($node['extension'] ?? ''),
                'show'      => ! array_key_exists('show', $node) || $node['show'] === null ? true : (bool) $node['show'],
                'order'     => (int) ($node['order'] ?? 0),
                'children'  => [],
            ];
        }

        $parentMap = array_column($records, 'parent_id', 'id');
        $validIds = [];
        foreach (array_keys($records) as $id) {
            if ($this->hasValidParentChain((int) $id, $parentMap)) {
                $validIds[$id] = true;
            }
        }

        $children = [];
        foreach ($records as $id => $record) {
            if (! isset($validIds[$id])) {
                continue;
            }

            $children[$record['parent_id']][] = $record;
        }

        return $this->buildBranch($children, 0);
    }

    protected function translateTitle(MenuLayout $menu, string $title): string
    {
        if (! app()->bound('translator')) {
            return $title;
        }

        return $menu->translate($title);
    }

    /**
     * @param  array<int, int>  $parentMap
     */
    protected function hasValidParentChain(int $id, array $parentMap): bool
    {
        $visited = [];
        $current = $id;

        while (isset($parentMap[$current])) {
            if (isset($visited[$current])) {
                return false;
            }

            $visited[$current] = true;
            $parentId = (int) $parentMap[$current];
            if ($parentId === 0) {
                return true;
            }

            if ($parentId < 0 || ! isset($parentMap[$parentId])) {
                return false;
            }

            $current = $parentId;
        }

        return false;
    }

    /**
     * @param  array<int, array<int, array<string, mixed>>>  $children
     * @return array<int, array<string, mixed>>
     */
    protected function buildBranch(array $children, int $parentId): array
    {
        $branch = $children[$parentId] ?? [];

        foreach ($branch as &$item) {
            $item['children'] = $this->buildBranch($children, (int) $item['id']);
        }
        unset($item);

        return $branch;
    }

    /**
     * @param  array<int, array<string, mixed>>  $tree
     */
    protected function version(array $tree): string
    {
        $keys = [];
        $walk = function (array $nodes) use (&$walk, &$keys) {
            foreach ($nodes as $node) {
                $keys[] = (string) ($node['key'] ?? '');
                $keys[] = (string) ($node['uri'] ?? '');
                $walk((array) ($node['children'] ?? []));
            }
        };
        $walk($tree);

        return substr(sha1(implode('|', $keys)), 0, 16);
    }
}
