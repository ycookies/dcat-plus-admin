<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Dcat\Admin\Extend\LogViewer;
class SystemLogViewerController extends Controller {


    public function index($file = null)
    {
        $request = app('request');

        $dir      = $request->get('dir') ? trim($request->get('dir')) : '';
        $filename = $request->get('filename') ? trim($request->get('filename')) : '';
        $offset   = $request->get('offset');
        $keyword  = $request->get('keyword') ? trim($request->get('keyword')) : '';
        $lines = $keyword ? (config('dcat-log-viewer.search_page_items') ?: 500) : (config('dcat-log-viewer.page_items') ?: 30);

        $viewer = new LogViewer($this->getDirectory(), $dir, $file);

        $viewer->setKeyword($keyword);
        $viewer->setFilename($filename);
        $content = new Content();
        $content->header('系统日志查看器');
        $content->breadcrumb(['text'=>'日志记录列表','uri'=>'']);
        $content->description('方便快速查看日志');
        return $content->body(view('admin::partials.system-log-viewer', [
            'dir'       => $dir,
            'logs'      => $viewer->fetch($offset, $lines),
            'logFiles'  => $this->formatLogFiles($viewer, $dir),
            'logDirs'   => $viewer->getLogDirectories(),
            'fileName'  => $viewer->file,
            'end'       => $viewer->getFilesize(),
            'prevUrl'   => $viewer->getPrevPageUrl(),
            'nextUrl'   => $viewer->getNextPageUrl(),
            'filePath'  => $viewer->getFilePath(),
            'size'      => static::bytesToHuman($viewer->getFilesize()),
        ]));

    }

    public function download()
    {
        $request = app('request');

        $file = trim($request->get('file'));
        $dir = trim($request->get('dir'));
        $filename = trim($request->get('filename'));
        $keyword = trim($request->get('keyword'));

        $viewer = new LogViewer($this->getDirectory(), $dir, $file);

        $viewer->setKeyword($keyword);
        $viewer->setFilename($filename);

        return response()->download($viewer->getFilePath());
    }

    protected function getDirectory()
    {
        return config('dcat-log-viewer.directory') ?: storage_path('logs');
    }

    protected function formatLogFiles(LogViewer $logViewer, $currentDir)
    {
        return array_map(function ($value) use ($logViewer, $currentDir) {
            $file = $value;
            $dir = $currentDir;

            if (Str::contains($value, '/')) {
                $array = explode('/', $value);
                $file = end($array);

                array_pop($array);
                $dir = implode('/', $array);
            }

            return [
                'file' => $value,
                'url' => url('admin/auth/system-log-viewer').'/'.$file.'?dir='.$dir,//route('dcat-log-viewer.file', ['file' => $file, 'dir' => $dir]),
                'active' => $logViewer->isCurrentFile($value),
            ];
        }, $logViewer->getLogFiles());
    }

    protected static function bytesToHuman($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

}