<?php

namespace Dcat\Admin\Form\Extend\FormMedia\Controllers;

use Illuminate\Routing\Controller;

use Dcat\Admin\Form\Extend\FormMedia\MediaManager;

class FormMedia extends Controller
{
    /**
     * 获取文件列表
     */
    public function getFiles()
    {
        $path = request()->input('path', '/');
        
        $currentPage = (int) request()->input('page', 1);
        $perPage = (int) request()->input('pageSize', 120);
        
        $manager = MediaManager::create()
            ->defaultDisk()
            ->setPath($path);
        
        // 驱动磁盘
        $disk = request()->input('disk', '');
        if (! empty($disk)) {
            $manager = $manager->withDisk($disk);
        }
        
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
        $path = request()->get('path', '/');
        
        $type = request()->get('type');
        $nametype = request()->get('nametype', 'uniqid');
        
        // 裁剪
        $resize = request()->get('resize', '');
        
        $manager = MediaManager::create()
            ->defaultDisk()
            ->setPath($path)
            ->setNametype($nametype);
        
        // 驱动磁盘
        $disk = request()->input('disk', '');
        if (! empty($disk)) {
            $manager = $manager->withDisk($disk);
        }
        
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
        $dir = request()->input('dir');
        $name = request()->input('name');
        
        if (empty($dir)) {
            return $this->renderJson(admin_trans('form-media.create_dirname_empty'), -1);
        }

        $manager = MediaManager::create()
            ->defaultDisk()
            ->setPath($dir);
        
        // 驱动磁盘
        $disk = request()->input('disk', '');
        if (! empty($disk)) {
            $manager = $manager->withDisk($disk);
        }

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



