<?php

namespace Dcat\Admin\Support;

use Illuminate\Support\Facades\Log;
use League\Flysystem\DirectoryAttributes;
use League\Flysystem\FileAttributes;
use Qiniu\Auth;
use Qiniu\Storage\BucketManager;
use zgldh\QiniuStorage\QiniuAdapter;

/**
 * zgldh/qiniu-laravel-storage 的 Flysystem v3 兼容适配器。
 *
 * 本类仅在 zgldh/qiniu-laravel-storage 已安装时才会被加载（见
 * AdminServiceProvider::patchQiniuStorageDriver 的 class_exists 守卫），
 * dcat-plus 不直接依赖该包。
 *
 * 背景：zgldh 的 QiniuAdapter::listContents() 返回 Flysystem v1 风格的
 * 关联数组，而 Laravel 10+ 的 FilesystemAdapter::files()/directories()
 * 内部 filter 对 StorageAttributes 有强类型约束，直接抛 TypeError ——
 * 后台媒体管理器(dcat-sys/media/files)浏览七牛盘即 500。
 *
 * 本类重写 listContents() 返回 StorageAttributes 对象，并顺带修正：
 *  - putTime 为 100 纳秒单位，转为秒级时间戳
 *  - 补齐 marker 分页（原实现只取前 1000 条）
 *  - 浅层列表带 delimiter '/'，用 commonPrefixes 还原子目录（媒体库文件夹导航）
 *  - 域名前缀强制 https（原适配器构造时硬编码 http://，getUrl 拼出的 URL 也是 http）
 */
class QiniuV3Adapter extends QiniuAdapter
{
    /**
     * 原适配器内部状态均为 private，子类无法复用，自行持有查询句柄。
     *
     * @var \Qiniu\Storage\BucketManager
     */
    protected $bucketManager;

    /**
     * @var string
     */
    protected $bucketName;

    public function __construct(
        $accessKey,
        $secretKey,
        $bucket,
        $domains,
        $notifyUrl = null,
        $access = self::ACCESS_PUBLIC,
        $hotlinkPreventionKey = null
    ) {
        parent::__construct($accessKey, $secretKey, $bucket, $domains, $notifyUrl, $access, $hotlinkPreventionKey);

        $this->bucketName = $bucket;
        $this->bucketManager = new BucketManager(new Auth($accessKey, $secretKey));

        // 覆盖父类构造中硬编码的 http:// 前缀，统一走 https
        $httpsDomain = ! empty($domains['https']) ? $domains['https'] : ($domains['default'] ?? '');
        if ($httpsDomain) {
            $this->setPathPrefix('https://' . $httpsDomain);
            $this->setDomainPrefix('https://' . $domains['default'], 'default');
            $this->setDomainPrefix('https://' . $httpsDomain, 'https');
            if (! empty($domains['custom'])) {
                $this->setDomainPrefix('https://' . $domains['custom'], 'custom');
            }
        }
    }

    /**
     * 列出目录内容，返回 Flysystem v3 要求的 StorageAttributes 对象。
     *
     * 浅层($deep=false)带 '/' 分隔符：items 为文件、commonPrefixes 为子目录；
     * 深层($deep=true)不带分隔符，递归穷举前缀下全部文件。
     *
     * @return \League\Flysystem\StorageAttributes[]
     */
    public function listContents(string $path, bool $deep): iterable
    {
        $prefix = trim($path, '/');
        if ($prefix !== '') {
            $prefix .= '/';
        }

        $marker = null;
        $seenMarkers = [];
        $contents = [];

        while (true) {
            /** @var array $ret */
            /** @var \Qiniu\Http\Error|null $error */
            [$ret, $error] = $this->bucketManager->listFiles(
                $this->bucketName,
                $prefix !== '' ? $prefix : null,
                $marker,
                1000,
                $deep ? null : '/'
            );

            if ($error !== null) {
                Log::error('Qiniu listContents failed: ' . $error->message(), [
                    'path' => $path,
                    'bucket' => $this->bucketName,
                ]);

                return [];
            }

            foreach ((array) ($ret['items'] ?? []) as $item) {
                if (empty($item['key'])) {
                    continue;
                }

                // 七牛可能存在以 '/' 结尾的"目录占位"对象
                if (substr($item['key'], -1) === '/') {
                    $contents[] = new DirectoryAttributes(rtrim($item['key'], '/'));
                    continue;
                }

                // putTime 为 100 纳秒级单位，转秒
                $lastModified = isset($item['putTime'])
                    ? (int) floor((int) $item['putTime'] / 10000000)
                    : null;

                $contents[] = new FileAttributes(
                    $item['key'],
                    (int) ($item['fsize'] ?? 0),
                    null,
                    $lastModified,
                    $item['mimeType'] ?? null
                );
            }

            if (! $deep) {
                foreach ((array) ($ret['commonPrefixes'] ?? []) as $dir) {
                    $dir = rtrim((string) $dir, '/');
                    if ($dir !== '') {
                        $contents[] = new DirectoryAttributes($dir);
                    }
                }
            }

            $marker = $ret['marker'] ?? '';
            if ($marker === '' || isset($seenMarkers[$marker])) {
                break;
            }
            $seenMarkers[$marker] = true;
        }

        return $contents;
    }
}
