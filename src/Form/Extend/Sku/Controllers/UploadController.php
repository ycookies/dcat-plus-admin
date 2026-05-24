<?php

namespace Dcat\Admin\Form\Extend\Sku\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadController extends AdminController
{
    /**
     * 允许的图片扩展名
     */
    protected function getAllowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    }

    /**
     * 允许的图片 MIME 类型
     */
    protected function getAllowedMimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
    }

    /**
     * 上传图片.
     *
     * @return \Illuminate\Http\JsonResponse|mixed
     */
    public function store(): JsonResponse
    {
        if (request()->hasFile('file')) {
            $file = request()->file('file');

            // 验证文件类型
            $ext = strtolower($file->getClientOriginalExtension());
            $mime = $file->getMimeType();

            if (!in_array($ext, $this->getAllowedExtensions()) || !in_array($mime, $this->getAllowedMimeTypes())) {
                return response()->json(['code' => 400, 'message' => '不支持的文件类型，仅允许图片文件'], 400);
            }

            $disk = config('admin.upload.disk');
            $path = Storage::disk($disk)->put('sku', $file);
            $response = ['full_url' => Storage::disk($disk)->url($path), 'short_url' => $path];

            return response()->json($response);
        }

        return response()->json(['code' => 400, 'message' => '未找到上传文件'], 400);
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

        // 路径安全验证：只允许删除 sku 目录下的文件
        $normalizedPath = ltrim($path, '/');
        if (!str_starts_with($normalizedPath, 'sku/')) {
            return response()->json(['code' => 403, 'message' => '非法路径，只能删除 sku 目录下的文件']);
        }

        // 防止路径穿越
        if (str_contains($normalizedPath, '..') || str_contains($normalizedPath, "\0")) {
            return response()->json(['code' => 403, 'message' => '非法路径']);
        }

        if (!Storage::disk($disk)->exists($path)) {
            return response()->json(['code' => 404, 'message' => '未找到相关图片']);
        }

        try {
            Storage::disk($disk)->delete($path);

            return response()->json(['code' => 200, 'message' => '删除成功']);
        } catch (\Exception $e) {
            return response()->json(['code' => 500, 'message' => '删除失败']);
        }
    }
}