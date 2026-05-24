<?php

namespace Dcat\Admin\Http\Controllers;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class EditorMDController
{
    /**
     * 允许的文件扩展名白名单
     */
    protected function getAllowedExtensions(): array
    {
        return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
    }

    /**
     * 允许的 MIME 类型白名单
     */
    protected function getAllowedMimeTypes(): array
    {
        return ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml'];
    }

    public function upload(Request $request)
    {
        $file = $request->file('editormd-image-file');

        $this->validateFile($file);

        $dir = $this->sanitizeDir($request->input('dir'));
        $disk = $this->disk();

        $newName = $this->generateNewName($file);

        $disk->putFileAs($dir, $file, $newName);

        return ['success' => 1, 'url' => $disk->url("{$dir}/$newName")];
    }

    /**
     * 验证上传文件类型
     *
     * @param UploadedFile $file
     * @throws \Exception
     */
    protected function validateFile(UploadedFile $file): void
    {
        $ext = strtolower($file->getClientOriginalExtension());
        $mime = $file->getMimeType();

        if (!in_array($ext, $this->getAllowedExtensions()) || !in_array($mime, $this->getAllowedMimeTypes())) {
            throw new \Exception('不支持的文件类型，仅允许图片文件');
        }
    }

    /**
     * 清理上传目录路径，防止路径穿越
     */
    protected function sanitizeDir(?string $dir): string
    {
        $dir = trim($dir ?: '', '/');

        // 移除路径穿越字符
        $dir = str_replace(['../', '..\\', '..', "\0"], '', $dir);

        // 只允许字母、数字、斜杠、连字符、下划线
        $dir = preg_replace('/[^a-zA-Z0-9\/\-_]/', '', $dir);

        return $dir ?: 'images';
    }

    protected function generateNewName(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, $this->getAllowedExtensions())) {
            $ext = 'bin';
        }

        return bin2hex(random_bytes(16)) . '.' . $ext;
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem|FilesystemAdapter
     */
    protected function disk()
    {
        return Storage::disk(config('admin.upload.disk'));
    }
}