<?php

namespace Dcat\Admin\Support\Authorization;

use Dcat\Admin\Admin;
use Dcat\Admin\Application;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;

/**
 * Builds a stable, current-panel-only catalog from Laravel's runtime routes.
 */
class RouteCatalog
{
    protected Router $router;

    protected Application $application;

    protected array $options;

    public function __construct(?Router $router = null, ?Application $application = null, ?array $options = null)
    {
        $this->router = $router ?: app('router');
        $this->application = $application ?: Admin::app();
        $this->options = array_replace_recursive($this->defaultOptions(), $options ?? (array) config('admin.permission.role_editor', []));
    }

    /**
     * Return route descriptors keyed by a tamper-resistant route key.
     */
    public function all(?bool $includeSystem = null): array
    {
        $includeSystem = $includeSystem ?? (bool) ($this->options['show_system_routes'] ?? false);
        $descriptors = [];

        foreach ($this->router->getRoutes() as $route) {
            if (! $route instanceof Route || ! $this->belongsToCurrentPanel($route)) {
                continue;
            }

            $descriptor = $this->describe($route);
            if (! $descriptor || (! $includeSystem && $descriptor['system'])) {
                continue;
            }

            $descriptors[$descriptor['key']] = $descriptor;
        }

        uasort($descriptors, function (array $left, array $right) {
            return [$left['resource'] ?: '~', $left['sort'], $left['label']]
                <=> [$right['resource'] ?: '~', $right['sort'], $right['label']];
        });

        return $descriptors;
    }

    /**
     * Split descriptors into actual resource actions and standalone routes.
     */
    public function grouped(?bool $includeSystem = null): array
    {
        $resources = [];
        $singles = [];
        $system = [];

        foreach ($this->all($includeSystem) as $descriptor) {
            if ($descriptor['system']) {
                $system[] = $descriptor;
                continue;
            }

            if ($descriptor['resource']) {
                $resource = $descriptor['resource'];
                if (! isset($resources[$resource])) {
                    $resources[$resource] = [
                        'key'        => $resource,
                        'title'      => $descriptor['resource_title'],
                        'controller' => $descriptor['controller_name'],
                        'uri'        => $this->resourceUri($descriptor),
                        'actions'    => [],
                    ];
                }

                $resources[$resource]['actions'][] = $descriptor;
            } else {
                $singles[] = $descriptor;
            }
        }

        foreach ($resources as &$resource) {
            usort($resource['actions'], function (array $left, array $right) {
                return [$left['sort'], $left['label']] <=> [$right['sort'], $right['label']];
            });
        }
        unset($resource);

        uasort($resources, function (array $left, array $right) {
            return [$left['title'], $left['uri']] <=> [$right['title'], $right['uri']];
        });

        return [
            'resources' => array_values($resources),
            'singles'   => array_values($singles),
            'system'    => array_values($system),
        ];
    }

    protected function describe(Route $route): ?array
    {
        $methods = array_values(array_diff(array_map('strtoupper', $route->methods()), ['HEAD', 'OPTIONS']));
        if (! $methods) {
            return null;
        }

        $routeName = (string) $route->getName();
        $relativeName = $this->relativeRouteName($routeName);
        $relativeUri = $this->relativeUri($route->uri());
        [$controller, $action] = $this->controllerAction($route);
        $resourceAction = $this->resourceAction($relativeName);
        $resource = $resourceAction ? Str::beforeLast($relativeName, '.') : null;
        $middleware = array_values(array_filter(array_map(function ($value) {
            return is_string($value) ? $value : null;
        }, $route->middleware())));
        $manual = (bool) collect($middleware)->first(function ($value) {
            return Str::startsWith($value, 'admin.permission:');
        });
        $internal = in_array(($route->defaults['dcat_route_type'] ?? null), ['internal', 'internal_legacy'], true)
            || Str::startsWith($relativeUri, 'dcat-sys/');
        $exempt = $this->isExempt($routeName, $relativeUri);
        $api = $this->isApiRoute($routeName, $relativeUri);
        $system = $internal || $api || $exempt || $manual || $this->matchesSystemRule($routeName, $relativeUri, $controller);
        $identity = implode('|', [
            $this->application->getName(),
            $routeName,
            $relativeUri,
            implode(',', $methods),
            $controller,
            $action,
        ]);

        return [
            'key'              => 'route:'.sha1($identity),
            'app'              => $this->application->getName(),
            'route_name'       => $routeName,
            'relative_name'    => $relativeName,
            'uri'              => $relativeUri,
            'http_path'        => $this->permissionPath($relativeUri),
            'http_methods'     => $methods,
            'controller'       => $controller,
            'controller_name'  => $controller === 'Closure' ? 'Closure' : class_basename($controller),
            'action'           => $action,
            'label'            => $this->routeLabel($relativeName, $relativeUri, $controller, $action),
            'resource'         => $resource,
            'resource_title'   => $this->resourceTitle($resource, $controller),
            'resource_action'  => $resourceAction,
            'permission_slug'  => $this->permissionSlug($relativeName, $relativeUri, $action, $identity),
            'manual'           => $manual,
            'internal'         => $internal,
            'exempt'           => $exempt,
            'system'           => $system,
            'sort'             => $this->actionSort($resourceAction ?: $action),
        ];
    }

    protected function belongsToCurrentPanel(Route $route): bool
    {
        $name = (string) $route->getName();
        $routePrefix = $this->application->getRoutePrefix();
        if ($name !== '' && Str::startsWith($name, $routePrefix)) {
            return true;
        }

        $applicationMiddleware = 'admin.app:'.$this->application->getName();
        if (in_array($applicationMiddleware, $route->middleware(), true)) {
            return true;
        }

        if (! ($this->options['include_unnamed_routes'] ?? true)) {
            return false;
        }

        $prefix = trim((string) config('admin.route.prefix'), '/');
        $uri = trim($route->uri(), '/');
        if ($prefix !== '' && $uri !== $prefix && ! Str::startsWith($uri, $prefix.'/')) {
            return false;
        }

        $domain = config('admin.route.domain');

        return ! $domain || $route->getDomain() === $domain;
    }

    protected function relativeRouteName(string $routeName): string
    {
        $prefix = $this->application->getRoutePrefix();

        return Str::startsWith($routeName, $prefix)
            ? ltrim(Str::after($routeName, $prefix), '.')
            : trim($routeName, '.');
    }

    protected function relativeUri(string $uri): string
    {
        $uri = trim($uri, '/');
        $prefix = trim((string) config('admin.route.prefix'), '/');

        if ($prefix !== '' && ($uri === $prefix || Str::startsWith($uri, $prefix.'/'))) {
            $uri = ltrim(substr($uri, strlen($prefix)), '/');
        }

        return $uri;
    }

    protected function permissionPath(string $relativeUri): string
    {
        $path = preg_replace('/\{[^}]+\}/', '*', trim($relativeUri, '/'));

        return '/'.ltrim((string) $path, '/');
    }

    protected function controllerAction(Route $route): array
    {
        $actionName = $route->getActionName();
        if ($actionName === 'Closure') {
            return ['Closure', $this->fallbackAction($route)];
        }

        if (Str::contains($actionName, '@')) {
            return [Str::before($actionName, '@'), Str::after($actionName, '@')];
        }

        return [$actionName, '__invoke'];
    }

    protected function fallbackAction(Route $route): string
    {
        $name = $this->relativeRouteName((string) $route->getName());
        if ($name !== '') {
            return Str::afterLast($name, '.');
        }

        return strtolower($route->methods()[0] ?? 'route');
    }

    protected function resourceAction(string $relativeName): ?string
    {
        if (! Str::contains($relativeName, '.')) {
            return null;
        }

        $action = Str::afterLast($relativeName, '.');

        return in_array($action, $this->resourceActions(), true) ? $action : null;
    }

    protected function resourceActions(): array
    {
        return ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy', 'import', 'export'];
    }

    protected function actionSort(string $action): int
    {
        $order = array_flip(['index', 'show', 'create', 'store', 'edit', 'update', 'destroy', 'import', 'export']);

        return $order[$action] ?? 100;
    }

    protected function routeLabel(string $relativeName, string $uri, string $controller, string $action): string
    {
        if ($relativeName !== '') {
            return $relativeName;
        }

        $controller = $controller === 'Closure' ? 'Closure' : class_basename($controller);

        return $controller.'@'.$action.' /'.$uri;
    }

    protected function resourceTitle(?string $resource, string $controller): string
    {
        if ($controller && $controller !== 'Closure') {
            return preg_replace('/Controller$/', '', class_basename($controller));
        }

        return $resource ?: 'Resource';
    }

    protected function resourceUri(array $descriptor): string
    {
        if (in_array($descriptor['resource_action'], ['index', 'store'], true)) {
            return $descriptor['uri'];
        }

        return trim(preg_replace('#/(create|import|export|\{[^}]+\}(?:/edit)?)$#', '', $descriptor['uri']), '/');
    }

    protected function permissionSlug(string $relativeName, string $uri, string $action, string $identity): string
    {
        $slug = $relativeName !== ''
            ? $relativeName
            : trim(str_replace(['/', '{', '}', '?'], ['.', '', '', ''], $uri), '.').'.'.$action;

        if ($this->application->getName() !== Application::DEFAULT) {
            $slug = $this->application->getName().'.'.$slug;
        }

        $slug = trim(preg_replace('/[^A-Za-z0-9_.-]+/', '-', $slug), '.-');
        if ($slug === '') {
            $slug = 'route.'.substr(sha1($identity), 0, 12);
        }

        if (strlen($slug) > 50) {
            $slug = rtrim(substr($slug, 0, 37), '.-').'.'.substr(sha1($identity), 0, 12);
        }

        return $slug;
    }

    protected function isApiRoute(string $routeName, string $relativeUri): bool
    {
        return Str::startsWith($routeName, $this->application->getCurrentApiRoutePrefix())
            || Str::startsWith($relativeUri, 'dcat-api/');
    }

    protected function isExempt(string $routeName, string $relativeUri): bool
    {
        foreach ((array) config('admin.permission.except', []) as $except) {
            $except = trim((string) $except);
            if ($except === '') {
                continue;
            }

            if (Str::is($except, $routeName) || Str::is($this->application->getRoutePrefix().$except, $routeName)) {
                return true;
            }

            $path = trim($except, '/');
            $uri = trim($relativeUri, '/');
            if (($path === '' && $uri === '') || Str::is($path, $uri)) {
                return true;
            }
        }

        return false;
    }

    protected function matchesSystemRule(string $routeName, string $uri, string $controller): bool
    {
        foreach ((array) ($this->options['system_route_names'] ?? []) as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        foreach ((array) ($this->options['system_paths'] ?? []) as $pattern) {
            if (Str::is(trim($pattern, '/'), trim($uri, '/'))) {
                return true;
            }
        }

        foreach ((array) ($this->options['system_controllers'] ?? []) as $pattern) {
            if (Str::is($pattern, $controller) || Str::is($pattern, class_basename($controller))) {
                return true;
            }
        }

        return false;
    }

    protected function defaultOptions(): array
    {
        return [
            'show_system_routes'     => false,
            'include_unnamed_routes' => true,
            'system_route_names'     => [],
            'system_paths'           => [
                'dcat-api/*',
                'dcat-sys/*',
                'lake-form-media/*',
                'sku-image-*',
            ],
            'system_controllers'     => [],
        ];
    }
}
