<?php

namespace Dcat\Admin\Form\Extend\FormMedia\Controllers;

use Illuminate\Routing\Controller;

use Dcat\Admin\Form\Extend\FormMedia\MediaManager;

class FormMedia extends Controller
{
    /**
     * 清理路径参数，防止路径穿越
     */
    protected function sanitizePath(?string $path): string
    {
        $path = $path ?: '/';

        // 移除路径穿越字符和空字节
        $path = str_replace(['../', '..\\', '..', "\0"], '', $path);

        // 移除首尾空格
        $path = trim($path);

        return $path;
    }

    /**
     * 清理文件夹名称，防止路径穿越和特殊字符
     */
    protected function sanitizeFolderName(?string $name): string
    {
        $name = $name ?: '';

        // 移除路径穿越字符、斜杠和空字节
        $name = str_replace(['../', '..\\', '..', '/', '\\', "\0"], '', $name);

        // 只允许字母、数字、连字符、下划线、点和中文
        $name = preg_replace('/[^a-zA-Z0-9\-_\.\x{4e00}-\x{9fa5}]/u', '', $name);

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

        // 驱动磁盘 - 使用配置默认值，不再接受用户参数
        $disk = request()->input('disk', '');
        $configDisk = config('admin.upload.disk');
        if (! empty($disk) && $disk === $configDisk) {
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
        $path = $this->sanitizePath(request()->input('path', '/'));

        $type = request()->input('type');
        $nametype = request()->input('nametype', 'uniqid');

        // 限制 nametype 只允许预定义的值
        if (!in_array($nametype, ['uniqid', 'sequence', 'original'])) {
            $nametype = 'uniqid';
        }

        // 裁剪
        $resize = request()->input('resize', '');

        $manager = MediaManager::create()
            ->defaultDisk()
            ->setPath($path)
            ->setNametype($nametype);

        // 驱动磁盘 - 使用配置默认值，不再接受用户参数
        $disk = request()->input('disk', '');
        $configDisk = config('admin.upload.disk');
        if (! empty($disk) && $disk === $configDisk) {
            $manager = $manager->withDisk($disk);
        }

        if ($type != 'blend') {
            if (! $manager->checkType($files, $type)) {
                return $this->renderJson(admin_trans('form-media.upload_file_ext_error'), -1);
            }
        }

        // 图片裁剪操作 - 安全验证 resize 参数
        $resizes = explode(",", $resize);
        if (
            $type == 'image'
            && !empty($resize)
            && count($resizes) == 2
            && is_numeric($resizes[0])
            && is_numeric($resizes[1])
        ) {
            try {
                foreach ($files as $file) {
                    $manager->prepareFile([
                        [
                            'method' => 'resize',
                            'arguments' => [(int)$resizes[0], (int)$resizes[1]],
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
        $dir = $this->sanitizePath(request()->input('dir'));
        $name = $this->sanitizeFolderName(request()->input('name'));

        if (empty($dir)) {
            return $this->renderJson(admin_trans('form-media.create_dirname_empty'), -1);
        }

        if (empty($name)) {
            return $this->renderJson(admin_trans('form-media.create_error'), -1);
        }

        $manager = MediaManager::create()
            ->defaultDisk()
            ->setPath($dir);

        // 驱动磁盘 - 使用配置默认值，不再接受用户参数
        $disk = request()->input('disk', '');
        $configDisk = config('admin.upload.disk');
        if (! empty($disk) && $disk === $configDisk) {
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