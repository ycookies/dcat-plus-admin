<?php

namespace Dcat\Admin\Extend;

use ZipArchive;

/**
 * 扩展包安装预检.
 *
 * 在解压/安装之前一次性检查全部环境要求（PHP Zip 扩展、压缩包有效性、
 * 临时目录与扩展目录权限、磁盘剩余空间），收集所有错误一次性返回给
 * 前端，避免用户逐个失败、逐个排查。
 */
class InstallPreflight
{
    /**
     * 解压 + 复制到扩展目录大约需要的磁盘空间倍数（zip 体积为基准）.
     */
    const DISK_SPACE_RATIO = 5;

    /**
     * 磁盘空间最低要求.
     */
    const DISK_SPACE_MIN = 10485760; // 10MB

    /**
     * 执行全部检查.
     *
     * @param  string  $filePath  上传的 zip 包绝对路径
     * @return array 错误消息列表；空数组表示全部通过
     */
    public function check(string $filePath): array
    {
        $errors = [];

        $errors = array_merge($errors, $this->checkZipExtension());
        $errors = array_merge($errors, $this->checkPackageFile($filePath));
        $errors = array_merge($errors, $this->checkTempDirectory());
        $errors = array_merge($errors, $this->checkExtensionDirectory());
        $errors = array_merge($errors, $this->checkDiskSpace($filePath));

        return $errors;
    }

    /**
     * PHP Zip 扩展.
     *
     * @return array
     */
    protected function checkZipExtension(): array
    {
        if (! class_exists(ZipArchive::class)) {
            return [
                'PHP Zip 扩展未安装或未启用（ext-zip）：无法解压扩展包。'.
                '请在 php.ini 中开启 extension=zip（并确认 zlib 已启用）后重启 PHP；'.
                '宝塔/面板环境可在「PHP 版本 → 安装扩展」中勾选 zip 后重载。',
            ];
        }

        return [];
    }

    /**
     * 上传的压缩包：存在、可读、非空、是有效 zip.
     *
     * @param  string $filePath
     * @return array
     */
    protected function checkPackageFile(string $filePath): array
    {
        $errors = [];

        if (! is_file($filePath)) {
            $errors[] = sprintf('上传的扩展包文件不存在或已过期（%s），请重新上传后再安装。', $filePath);

            return $errors;
        }

        if (! is_readable($filePath)) {
            $errors[] = sprintf('扩展包文件不可读（权限不足）：%s。请检查文件属主与读取权限。', $filePath);
        }

        $size = (int) @filesize($filePath);

        if ($size === 0) {
            $errors[] = '扩展包文件为空（0 字节）：上传可能被中断或超时，请重新上传。';
        } elseif (class_exists(ZipArchive::class)) {
            $zip = @new ZipArchive();
            $code = $zip->open($filePath, ZipArchive::RDONLY);

            if ($code !== true) {
                $errors[] = sprintf('扩展包压缩文件无效：%s（%s）。', $this->formatOpenError($code), $filePath);
            } elseif ($zip->numFiles === 0) {
                $errors[] = sprintf('扩展包压缩文件内没有任何文件：%s。', $filePath);
                $zip->close();
            } else {
                $zip->close();
            }
        }

        return $errors;
    }

    /**
     * 临时目录（storage/tmp/extensions）可创建、可写.
     *
     * @return array
     */
    protected function checkTempDirectory(): array
    {
        $tempDir = storage_path('tmp/extensions');

        if (is_dir($tempDir)) {
            if (! $this->isWritable($tempDir)) {
                return [
                    sprintf('临时目录不可写：%s。请将目录权限设为 755（或 775）并将属主改为 PHP 运行用户后重试。', $tempDir),
                ];
            }

            return [];
        }

        $parent = dirname($tempDir);

        if (! $this->isWritable($parent)) {
            return [
                sprintf('无法创建临时目录：%s。上级目录 %s 不可写，请将 storage 目录权限设为 755（或 775）并将属主改为 PHP 运行用户（如 www:www）后重试。', $tempDir, $parent),
            ];
        }

        return [];
    }

    /**
     * 扩展安装目录可创建、可写.
     *
     * @return array
     */
    protected function checkExtensionDirectory(): array
    {
        $extensionDir = admin_extension_path();

        if (is_dir($extensionDir)) {
            if (! $this->isWritable($extensionDir)) {
                return [
                    sprintf('扩展安装目录不可写：%s。请将目录权限设为 755（或 775）并将属主改为 PHP 运行用户后重试。', $extensionDir),
                ];
            }

            return [];
        }

        $parent = dirname($extensionDir);

        // 项目根目录（base_path 上一级）不可写时给出目录级指引
        if (! $this->isWritable($parent)) {
            return [
                sprintf('无法创建扩展安装目录：%s。上级目录 %s 不可写，请调整权限与属主后重试。', $extensionDir, $parent),
            ];
        }

        return [];
    }

    /**
     * 磁盘剩余空间（解压 + 复制需要 zip 体积数倍的空间）.
     *
     * @param  string $filePath
     * @return array
     */
    protected function checkDiskSpace(string $filePath): array
    {
        if (! function_exists('disk_free_space') || ! is_file($filePath)) {
            return [];
        }

        $size = (int) @filesize($filePath);

        if ($size <= 0) {
            return [];
        }

        $free = @disk_free_space(storage_path('tmp'));
        $required = max($size * static::DISK_SPACE_RATIO, static::DISK_SPACE_MIN);

        if ($free !== false && $free < $required) {
            return [
                sprintf(
                    '磁盘空间不足：安装约需要 %s 可用空间（解压与复制约为压缩包的 %d 倍），当前剩余 %s。请清理磁盘或扩容后重试。',
                    $this->formatSize($required),
                    static::DISK_SPACE_RATIO,
                    $this->formatSize($free)
                ),
            ];
        }

        return [];
    }

    /**
     * @param  string  $dir
     * @return bool
     */
    protected function isWritable(string $dir): bool
    {
        if (! is_dir($dir)) {
            return false;
        }

        if (is_writable($dir)) {
            return true;
        }

        // open_basedir 等场景下 is_writable 可能误报，用实际写入探测
        $probe = $dir.'/'.uniqid('preflight-', true);

        return @file_put_contents($probe, '') !== false && @unlink($probe);
    }

    /**
     * ZipArchive::open 错误码转可读信息.
     *
     * @param  int  $code
     * @return string
     */
    protected function formatOpenError(int $code): string
    {
        $map = [
            ZipArchive::ER_NOZIP => '不是有效的 zip 压缩文件（请确认上传的是 zip 包，而非损坏或改名的其他文件）',
            ZipArchive::ER_INCONS => 'zip 压缩包不一致或已损坏，请重新导出/下载后再上传',
            ZipArchive::ER_CRC => 'zip 校验失败（文件损坏，可能是上传中断导致），请重新上传',
            ZipArchive::ER_READ => '读取压缩包失败（磁盘或权限问题）',
            ZipArchive::ER_OPEN => '无法打开压缩包文件（权限不足）',
            ZipArchive::ER_MEMORY => '内存不足，无法读取压缩包',
        ];

        return $map[$code] ?? sprintf('无法打开压缩包（错误码 %d）', $code);
    }

    /**
     * @param  int  $bytes
     * @return string
     */
    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 1).$units[$i];
    }
}
