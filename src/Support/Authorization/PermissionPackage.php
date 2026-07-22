<?php

namespace Dcat\Admin\Support\Authorization;

use Dcat\Admin\Application;

/**
 * Read-only, panel-scoped permission capability catalog.
 *
 * This catalog deliberately does not resolve or create Permission models. It
 * is intended for product-plan configuration, where route capability keys are
 * safer and more portable than database permission IDs.
 */
class PermissionPackage
{
    protected PanelContext $panels;

    public function __construct(?PanelContext $panels = null)
    {
        $this->panels = $panels ?: new PanelContext();
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function get(string $panel = Application::DEFAULT, array $options = []): array
    {
        return $this->panels->run($panel, function () use ($panel, $options) {
            $includeSystem = (bool) ($options['include_system'] ?? false);
            $catalogOptions = (array) ($options['catalog'] ?? []);
            $catalog = new RouteCatalog(null, null, $catalogOptions);
            $grouped = $catalog->grouped($includeSystem);

            $resources = array_map(function (array $resource) use ($panel) {
                return $this->resource($panel, $resource);
            }, $grouped['resources']);

            $singles = array_map(function (array $route) use ($panel) {
                return $this->single($panel, $route);
            }, $grouped['singles']);

            $system = array_map(function (array $route) use ($panel) {
                return $this->single($panel, $route, 'system');
            }, $grouped['system']);

            $items = array_merge($resources, $singles);
            if ($includeSystem) {
                $items = array_merge($items, $system);
            }

            return [
                'panel'     => $panel,
                'version'   => $this->version($items),
                'resources' => $resources,
                'singles'   => $singles,
                'system'    => $system,
                'items'     => $items,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $resource
     * @return array<string, mixed>
     */
    protected function resource(string $panel, array $resource): array
    {
        $resourceKey = (string) ($resource['key'] ?? 'resource');
        $abilities = [];

        foreach (ResourcePermissionGroups::all() as $ability => $definition) {
            $routes = array_values(array_filter($resource['actions'] ?? [], function (array $route) use ($definition) {
                return in_array($route['resource_action'] ?? null, $definition['actions'], true);
            }));

            if (! $routes) {
                continue;
            }

            $abilities[] = [
                'key'        => "resource:{$panel}:{$resourceKey}:{$ability}",
                'name'       => $ability,
                'title'      => $this->translate($definition['label'], $ability),
                'type'       => $definition['type'],
                'actions'    => $definition['actions'],
                'route_keys' => array_values(array_column($routes, 'key')),
                'routes'     => array_map([$this, 'route'], $routes),
            ];
        }

        return [
            'type'        => 'resource',
            'key'         => "resource:{$panel}:{$resourceKey}",
            'resource'    => $resourceKey,
            'title'       => (string) ($resource['title'] ?? $resourceKey),
            'description' => (string) ($resource['description'] ?? ''),
            'group'       => (string) ($resource['group'] ?? ''),
            'uri'         => (string) ($resource['uri'] ?? ''),
            'abilities'   => $abilities,
        ];
    }

    /**
     * @param  array<string, mixed>  $route
     * @return array<string, mixed>
     */
    protected function single(string $panel, array $route, string $type = 'single'): array
    {
        $slug = (string) ($route['permission_slug'] ?? $route['key'] ?? 'route');

        return [
            'type'        => $type,
            'key'         => "{$type}:{$panel}:{$slug}",
            'title'       => (string) ($route['permission_title'] ?? $route['label'] ?? $slug),
            'description' => (string) ($route['description'] ?? ''),
            'group'       => (string) ($route['permission_group'] ?? ''),
            'route_key'   => (string) ($route['key'] ?? ''),
            'route'       => $this->route($route),
        ];
    }

    /**
     * @param  array<string, mixed>  $route
     * @return array<string, mixed>
     */
    protected function route(array $route): array
    {
        return [
            'key'             => (string) ($route['key'] ?? ''),
            'permission_slug' => (string) ($route['permission_slug'] ?? ''),
            'route_name'      => (string) ($route['route_name'] ?? ''),
            'uri'             => (string) ($route['uri'] ?? ''),
            'http_path'       => (string) ($route['http_path'] ?? ''),
            'http_methods'    => array_values((array) ($route['http_methods'] ?? [])),
            'action'          => (string) ($route['resource_action'] ?? $route['action'] ?? ''),
            'title'           => (string) ($route['permission_title'] ?? $route['label'] ?? ''),
            'description'     => (string) ($route['description'] ?? ''),
            'group'           => (string) ($route['permission_group'] ?? ''),
            'label_source'    => (string) ($route['label_source'] ?? ''),
        ];
    }

    protected function translate(string $key, string $fallback): string
    {
        if (! app()->bound('translator')) {
            return $fallback;
        }

        $value = (string) trans($key);

        return $value === $key ? $fallback : $value;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    protected function version(array $items): string
    {
        $keys = [];

        foreach ($items as $item) {
            $keys[] = (string) ($item['key'] ?? '');
            foreach ($item['abilities'] ?? [] as $ability) {
                $keys[] = (string) ($ability['key'] ?? '');
                $keys = array_merge($keys, (array) ($ability['route_keys'] ?? []));
            }
            if (! empty($item['route_key'])) {
                $keys[] = (string) $item['route_key'];
            }
        }

        sort($keys);

        return substr(sha1(implode('|', $keys)), 0, 16);
    }
}
