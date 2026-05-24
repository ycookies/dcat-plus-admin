<?php

namespace Dcat\Admin\Form\Extend\FormMedia\Controllers;

use Illuminate\Routing\Controller;

use Dcat\Admin\Form\Extend\FormMedia\MediaManager;

class FormMedia extends Controller
{
    /**
     * 清理路径参数，防止路径穿越
     */
    protected function sanitizePath($path)
    {
        $path = str_replace(['../', '..\\', '..', "\0"], '', $path);
        $path = preg_replace('/[^a-zA-Z0-9\/\-_\.]/', '', $path);
        return '/' . trim($path, '/');
    }

    /**
     * 清理文件夹名，防止路径穿越和特殊字符
     */
    protected function sanitizeFolderName($name)
    {
        $name = str_replace(['../', '..\\', '..', "\0", '/', '\\'], '', $name);
        $name = preg_replace('/[^a-zA-Z0-9_\-\.\x{4e00}-\x{9fa5}]/u', '', $name);
        return $name;
    }

    /**
     * 获取文件列表
     */
    public function getFiles()
    {
        $path = $this->sanitizePath(request()->input('path', '/'));
        
        $currentPage = (int) request()->input('page', 1);
        $perPage = (int) request()->input('pageSize', 120);
        
        $manager = MediaManager::create()
            ->defaultDisk()
            ->setPath($path);
        
        $type = (string) request()->input('type', 'image');
        $order = (string) request()->input('order', 'time');
        
        $files = $manager->ls($type, $order);
        $list = collect($files)
            ->slice(($currentPage - 1) * $perPage, $perPage)
            ->values();
            
        $totalPage = count(collect($files)->chunk($perPage));

        $data = [
            'list' => $list, // 数据
            'total_page' => $totalPage, // 数量
            'current_page' => $currentPage, // 当前页码
            'per_page' => $perPage, // 每页数量
            'nav' => $manager->navigation()  // 导航
        ];
        
        return $this->renderJson(admin_trans('form-media.get_success'), 200, $data);
    }

    /**
     * 上传
     */
    public function upload()
    {
        $files = request()->file('files');
        $path = $this->sanitizePath(request()->get('path', '/'));
        
        $type = request()->get('type');
        
        // Security: nametype whitelist
        $nametype = request()->get('nametype', 'uniqid');
        $allowedNametypes = ['uniqid', 'datetime', 'sequence', 'original'];
        if (!in_array($nametype, $allowedNametypes)) {
            $nametype = 'uniqid';
        }
        
        // 裁剪
        $resize = request()->get('resize', '');
        
        $manager = MediaManager::create()
            ->defaultDisk()
            ->setPath($path)
            ->setNametype($nametype);
        
        if ($type != 'blend') {
            if (! $manager->checkType($files, $type)) {
                return $this->renderJson(admin_trans('form-media.upload_file_ext_error'), -1);
            }
        }
        
        // 图片裁剪操作
        $resizes = explode(",", $resize);
        if (
            $type == 'image'
            && !empty($resize) 
            && count($resizes) == 2
        ) {
            try {
                foreach ($files as $file) {
                    $manager->prepareFile([
                        [
                            'method' => 'resize',
                            'arguments' => $resizes,
                        ],
                    ], $file);
                }
            } catch (\Exception $e) {}
        }
        
        try {
            if ($manager->upload($files)) {
                return $this->renderJson(admin_trans('form-media.upload_success'), 200);
            }
        } catch (\Exception $e) {
            return $this->renderJson($e->getMessage(), -1);
        }
        
        return $this->renderJson(admin_trans('form-media.upload_error'), -1);
    }

    /**
     * 新建文件夹
     */
    public function createFolder()
    {
        $dir = $this->sanitizePath(request()->input('dir', '/'));
        $name = $this->sanitizeFolderName(request()->input('name'));
        
        if (empty($name)) {
            return $this->renderJson(admin_trans('form-media.create_dirname_empty'), -1);
        }

        $manager = MediaManager::create()
            ->defaultDisk()
            ->setPath($dir);

        try {
            if ($manager->createFolder($name)) {
                return $this->renderJson(admin_trans('form-media.create_success'), 200);
            }
        } catch (\Exception $e) {}
        
        return $this->renderJson(admin_trans('form-media.create_error'), -1);
    }
    
    /**
     * 输出json
     */
    protected function renderJson($msg, $code = 200, $data = [])
    {
        return response()->json([
            'code' => $code, 
            'msg' => $msg,
            'data' => $data,
        ]);
    }
}