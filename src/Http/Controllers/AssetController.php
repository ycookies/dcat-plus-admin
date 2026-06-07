<?php

namespace Dcat\Admin\Http\Controllers;

use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AssetController extends Controller
{
    private const ALLOWED_ASSETS = [
        '/vendor/dcat-admin/dcat/plugins/admin-iframe-tab/css/admin-iframe-tab.css' => 'text/css; charset=UTF-8',
        '/vendor/dcat-admin/dcat/plugins/admin-iframe-tab/js/admin-iframe-tab.js' => 'application/javascript; charset=UTF-8',
        '/vendor/dcat-admin/dcat/plugins/admin-iframe-tab/js/admin-iframe-tab-child.js' => 'application/javascript; charset=UTF-8',
    ];

    public function show(string $path): BinaryFileResponse
    {
        $path = trim($path, '/');

        if (! array_key_exists($path, self::ALLOWED_ASSETS)) {
            abort(404);
        }

        $file = dirname(__DIR__, 3).'/public/'.$path;

        if (! is_file($file)) {
            abort(404);
        }

        return response()->file($file, [
            'Content-Type' => self::ALLOWED_ASSETS[$path],
        ]);
    }
}
