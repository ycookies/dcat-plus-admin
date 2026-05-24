<?php

namespace Dcat\Admin\Form\Extend\Sku\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadController extends AdminController
{
    /**
     * 允许的图片MIME类型
     */
    protected $allowedImageMimes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp',
    ];

    /**
     * 上传图片.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function store(): JsonResponse
    {
        if (request()->hasFile('file')) {
            $file = request()->file('file');

            // 安全检查：验证文件类型为图片
            $mime = $file->getMimeType();
            if (!in_array($mime, $this->allowedImageMimes)) {
                return response()->json(['code' => 422, 'message' => '只允许上传图片文件']);
            }

            $disk = config('admin.upload.disk');
            $path = Storage::disk($disk)->put('sku', $file);
            $response = ['full_url' => Storage::disk($disk)->url($path), 'short_url' => $path];

            return response()->json($response);
        }
    }

    /**
     * 删除图片.
     *
     * @return JsonResponse
     */
    public function delete(): JsonResponse
    {
        $disk = config('admin.upload.disk');
        $path = request()->input('path');

        // 安全检查：路径穿越防护
        if (empty($path) || strpos($path, '..') !== false || strpos($path, "\0") !== false) {
            return response()->json(['code' => 403, 'message' => '非法路径']);
        }

        // 安全检查：只允许删除sku目录下的文件
        if (strpos($path, 'sku/') !== 0) {
            return response()->json(['code' => 403, 'message' => '只能删除sku目录下的文件']);
        }

        if (!Storage::disk($disk)->exists($path)) {
            return response()->json(['code' => 404, 'message' => '未找到相关图片']);
        }

        try {
            Storage::disk($disk)->delete($path);

            return response()->json(['code' => 200, 'message' => '删除成功']);
        } catch (\Exception $exception) {
            return response()->json(['code' => $exception->getCode(), 'message' => $exception->getMessage()]);
        }
    }
}