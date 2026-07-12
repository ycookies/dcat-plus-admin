<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Support\UeditorUploadSignature;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * UEditor 编辑器后端统一入口。
 *
 * UEditor 通过单一 serverUrl 同时处理「上传」请求，统一返回
 * {"state":"SUCCESS","url":"...","title":"...","original":"...","type":".ext","size":n}
 * 失败时返回 {"state":"<错误信息>"}。
 */
class UeditorController
{
    /**
     * 各 action 对应的扩展名白名单。
     */
    protected function getAllowedExtensions(string $action): array
    {
        return array_values(array_filter(array_map(function ($extension) {
            return ltrim(strtolower((string) $extension), '.');
        }, $this->uploadOption($action, 'allow_files', []))));
    }

    /**
     * 各 action 对应的 MIME 白名单（为空表示不校验 MIME，仅按扩展名判断）。
     */
    protected function getAllowedMimeTypes(string $action): array
    {
        return $this->uploadOption($action, 'mime_types', []);
    }

    /**
     * 各 action 对应的存储子目录。
     */
    protected function getDirectory(string $action): string
    {
        return trim((string) config('admin.ueditor.directory.'.$this->uploadType($action), 'ueditor/files'), '/');
    }

    /**
     * UEditor 统一入口。
     */
    public function handle(Request $request)
    {
        $action = $request->query('action', '');

        switch ($action) {
            case 'config':
                // 为上传插件提供 action、字段名、大小与扩展名白名单。
                return $this->config();
            case 'uploadimage':
            case 'uploadscrawl':
            case 'uploadvideo':
            case 'uploadfile':
                return $this->upload($request, $action);
            default:
                return ['state' => '不支持的请求 action'];
        }
    }

    /**
     * 上传处理。
     */
    protected function upload(Request $request, string $action)
    {
        $file = $request->file('upfile');

        if (! $file || ! $file->isValid()) {
            return ['state' => '未接收到有效上传文件'];
        }

        try {
            $this->validateFile($file, $action);
        } catch (\Exception $e) {
            return ['state' => $e->getMessage()];
        }

        try {
            [$diskName, $dir] = $this->uploadTarget($request, $action);
        } catch (\Exception $e) {
            return ['state' => $e->getMessage()];
        }

        $disk = $this->disk($diskName);

        $newName = $this->generateNewName($file);

        try {
            $disk->putFileAs($dir, $file, $newName);
        } catch (\Exception $e) {
            return ['state' => '文件保存失败：'.$e->getMessage()];
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');

        return [
            'state'    => 'SUCCESS',
            'url'      => $disk->url("{$dir}/{$newName}"),
            'title'    => $newName,
            'original' => $file->getClientOriginalName(),
            'type'     => '.'.$ext,
            'size'     => $file->getSize(),
        ];
    }

    /**
     * UEditor 前端配置。
     */
    protected function config(): array
    {
        return [
            'imageActionName'    => 'uploadimage',
            'imageFieldName'     => 'upfile',
            // 上传接口已返回绝对 URL，默认保持为空，避免前端拼出 "undefined" 前缀。
            'imageUrlPrefix'     => config('admin.ueditor.url_prefix', ''),
            'imageMaxSize'       => $this->uploadOption('uploadimage', 'max_size', 2048000),
            'imageAllowFiles'    => $this->configExtensions('uploadimage'),
            'videoActionName'    => 'uploadvideo',
            'videoFieldName'     => 'upfile',
            'videoMaxSize'       => $this->uploadOption('uploadvideo', 'max_size', 102400000),
            'videoAllowFiles'    => $this->configExtensions('uploadvideo'),
            'fileActionName'     => 'uploadfile',
            'fileFieldName'      => 'upfile',
            'fileMaxSize'        => $this->uploadOption('uploadfile', 'max_size', 51200000),
            'fileAllowFiles'     => $this->configExtensions('uploadfile'),
        ];
    }

    /**
     * 验证上传文件类型。
     *
     * @throws \Exception
     */
    protected function validateFile(UploadedFile $file, string $action): void
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: '');

        $allowedExt = $this->getAllowedExtensions($action);
        if (! $ext || ! in_array($ext, $allowedExt)) {
            throw new \Exception('不支持的文件类型，允许扩展名：'.implode(', ', $allowedExt));
        }

        $allowedMime = $this->getAllowedMimeTypes($action);
        if ($allowedMime) {
            $mime = $file->getMimeType();
            if (! in_array($mime, $allowedMime)) {
                throw new \Exception('文件 MIME 类型不被允许');
            }
        }

        $maxSize = (int) $this->uploadOption($action, 'max_size', 0);
        if ($maxSize > 0 && $file->getSize() > $maxSize) {
            throw new \Exception('文件大小不能超过 '.$maxSize.' 字节');
        }
    }

    /**
     * 清理上传目录路径，防止路径穿越。
     */
    protected function sanitizeDir(?string $dir): string
    {
        $dir = trim($dir ?: '', '/');

        $dir = str_replace(['../', '..\\', '..', "\0"], '', $dir);

        $dir = preg_replace('/[^a-zA-Z0-9\/\-_]/', '', $dir);

        return $dir ?: 'ueditor/files';
    }

    protected function generateNewName(UploadedFile $file): string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');

        return bin2hex(random_bytes(16)).'.'.$ext;
    }

    /**
     * @return \Illuminate\Contracts\Filesystem\Filesystem|FilesystemAdapter
     */
    protected function disk(?string $disk = null)
    {
        $disk = $disk ?: config('admin.ueditor.disk');

        return $disk ? Storage::disk($disk) : Storage::disk(config('admin.upload.disk'));
    }

    /**
     * Resolve a server-configured upload target, or a short-lived target signed
     * by an editor field that explicitly called disk() or imageDirectory().
     *
     * @return array{0: ?string, 1: string}
     *
     * @throws \Exception
     */
    protected function uploadTarget(Request $request, string $action): array
    {
        $disk = config('admin.ueditor.disk');
        $directory = $this->getDirectory($action);
        $requestedDisk = $request->input('disk');
        $requestedDirectory = $request->input('dir');

        if ($requestedDisk === null && $requestedDirectory === null) {
            return [$disk, $directory];
        }

        $requestedDisk = $requestedDisk === null ? null : (string) $requestedDisk;
        $requestedDirectory = $requestedDirectory === null ? null : $this->sanitizeDir((string) $requestedDirectory);
        $expires = (int) $request->input('expires', 0);

        if (! UeditorUploadSignature::verify(
            $requestedDisk,
            $requestedDirectory,
            $expires,
            $request->input('signature')
        )) {
            throw new \Exception('无效或已过期的上传授权');
        }

        if ($requestedDisk) {
            $allowedDisks = (array) config('admin.ueditor.allowed_disks', []);
            if (! in_array($requestedDisk, $allowedDisks, true)) {
                throw new \Exception('不允许使用指定的上传磁盘');
            }
            $disk = $requestedDisk;
        }

        if ($requestedDirectory) {
            $directory = $requestedDirectory;
        }

        return [$disk, $directory];
    }

    /**
     * 获取指定上传动作对应的配置类型。
     */
    protected function uploadType(string $action): string
    {
        if (in_array($action, ['uploadimage', 'uploadscrawl'], true)) {
            return 'image';
        }

        return $action === 'uploadvideo' ? 'video' : 'file';
    }

    /**
     * 获取 UEditor 上传配置项。
     *
     * @param  mixed  $default
     * @return mixed
     */
    protected function uploadOption(string $action, string $option, $default)
    {
        return config('admin.ueditor.'.$this->uploadType($action).'.'.$option, $default);
    }

    /**
     * 转换为 UEditor 前端所需的带点扩展名格式。
     */
    protected function configExtensions(string $action): array
    {
        return array_values(array_filter(array_map(function ($extension) {
            return '.'.ltrim(strtolower((string) $extension), '.');
        }, $this->uploadOption($action, 'allow_files', []))));
    }

}
