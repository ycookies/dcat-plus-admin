<?php

namespace Dcat\Admin\Support\Authorization;

use Illuminate\Routing\Route;

/**
 * Registers structured, route-cache-safe permission presentation metadata.
 */
class RoutePermissionMetadata
{
    public const DEFAULT_KEY = 'dcat_permission_label';

    public static function registerMacro(): void
    {
        if (Route::hasMacro('permissionLabel')) {
            return;
        }

        Route::macro('permissionLabel', function ($title, $description = null, $group = null) {
            /** @var Route $this */
            $metadata = is_array($title) ? $title : [
                'title'       => $title,
                'description' => $description,
                'group'       => $group,
            ];

            return $this->defaults(RoutePermissionMetadata::DEFAULT_KEY, RoutePermissionMetadata::normalize($metadata));
        });
    }

    public static function normalize($metadata): array
    {
        if (is_string($metadata) || is_numeric($metadata)) {
            $metadata = ['title' => (string) $metadata];
        }

        if (! is_array($metadata)) {
            return [];
        }

        $normalized = [];
        foreach (['title', 'description', 'group'] as $key) {
            $value = $metadata[$key] ?? null;
            if (is_scalar($value) && trim((string) $value) !== '') {
                $normalized[$key] = trim((string) $value);
            }
        }

        return $normalized;
    }
}
