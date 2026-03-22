<?php

namespace Dcat\Admin\Form\Extend\FormMedia;

use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image as InterventionImage;
use Intervention\Image\ImageManagerStatic;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * 文件管理
 */
class MediaManager
{
    /**
     * @var string
     */
    protected $path = '/';

    /**
     * @var string
     */
    protected $nametype = 'uniqid';

    /**
     * @var \Illuminate\Filesystem\FilesystemAdapter
     */
    protected $storage;

    /**
     * @var string 权限
     */
    protected $storagePermission = '';

    /**
     * @var array
     */
    protected $fileTypes = [
        'image' => 'jpeg|jpg|bmp|png|svg|wbmp|pic|cgm|djv|djvu|gif|ico|ief|jp2|jpe|mac|pbm|pct|pgm|pict|pnm|pnt|pntg|ppm|qti|qtif|ras|rgb|tif|tiff|xbm|xpm|xwd|avif',
        'video' => 'mkv|avi|mp4|rmvb|rm|flv|wmv|asf|mpeg|mov',
        'audio' => 'mp3|wav|flac|3pg|aa|aac|ape|au|m4a|mpc|ogg',
        'pdf'   => 'pdf',
        'word'  => 'doc|docx|dot|dotx|docm|dotm|wps',
        'ppt'   => 'ppt|pptx|pptm|pot|pps|ppa|pptx|potx|ppsx|ppam|potm|ppsm',
        'xls'   => 'xls|xlt|xla|xlsx|xltx|xlsm|xltm|xlam|xlsb',
        'text'  => 'txt|pac|log|md',
        'code'  => 'html|htm|js|css|vue|json|php|java|go|py|ruby|rb|aspx|asp|c|cpp|sql|m|h|python|ruby|rs|zig|v',
        'zip'   => 'zip|tar|gz|tar\.gz|rar|rpm',
    ];
    
    /**
     * 创建
     */
    public static function create()
    {
        return new static();
    }

    /**
     * 默认驱动
     */
    public function defaultDisk()
    {
        $disk = config('admin.upload.disk');

        return $this->withDisk($disk);
    }

    /**
     * 使用驱动
     */
    public function withDisk($disk)
    {
        $this->storage = Storage::disk($disk);
        
        return $this;
    }
    
    /**
     * 设置目录
     */
    public function setPath($path = '/')
    {
        $this->path = $path;
        return $this;
    }

    /**
     * 列出数据
     */
    public function ls($type = 'image', $order = 'time')
    {
        $files = $this->storage->files($this->path);
        
        $directories = $this->storage->directories($this->path);
        
        $manager = $this;
        $files = $this->formatFiles($files)
            ->map(function($item) use($type, $manager) {
                if ($type == 'blend') {
                    return $item;
                }
                
                $fileType = $manager->detectFileType($item['name']);
                if ($type == $fileType) {
                    return $item;
                }
                
                return null;
            })
            ->filter(function($item) {
                return ! empty($item);
            });
        
        $list = $this->formatDirectories($directories)
            ->merge($files);
        
        if ($order == 'name') {
            $list = $list->sort(function ($item) {
                    return $item['name'];
                });
        } else {
            $list = $list->sortByDesc(function ($item) {
                    return $item['time'];
                });
        }
            
        return $list->all();
    }

    /**
     * 获取完整路径
     *
     * @param string $path
     *
     * @return string
     */
    protected function getFullPath($path)
    {
        return $this->storage->path($path);
    }

    /**
     * 下载
     */
    public function download($file)
    {
        $fullPath = $this->getFullPath($file);

        if (File::isFile($fullPath)) {
            return response()->download($fullPath);
        }

        return response('', 404);
    }

    /**
     * 删除
     */
    public function delete($path)
    {
        $paths = is_array($path) ? $path : func_get_args();

        foreach ($paths as $path) {
            $fullPath = $this->getFullPath($path);

            if (is_file($fullPath)) {
                $this->storage->delete($path);
            } else {
                $this->storage->deleteDirectory($path);
            }
        }

        return true;
    }

    /**
     * 移动
     */
    public function move($new)
    {
        return $this->storage->move($this->path, $new);
    }

    /**
     * 上传
     *
     * @param UploadedFile[] $files
     * @param string         $dir
     *
     * @return mixed
     */
    public function upload($files = [])
    {
        if (empty($files)) {
            return false;
        }
        
        foreach ($files as $file) {
            $this->storage->putFileAs(
                $this->path, 
                $file, 
                $this->getPutFileName($file)
            );
        }

        return true;
    }
    
    /**
     * 检测文件上传类型
     */
    public function checkType($files = [], $type = null)
    {
        if (empty($files)) {
            return false;
        }
        
        foreach ($files as $file) {
            $fileExtension = $file->getClientOriginalExtension();
            $fileType = $this->detectFileType('file.'.$fileExtension);
            if ($fileType != $type) {
                return false;
            }
        }

        return true;
    }
    
    /**
     * 设置命名方式
     */
    public function setNametype($type = 'uniqid')
    {
        $this->nametype = $type;
        
        return $this;
    }
    
    /**
     * 获取最后的命名
     */
    public function getPutFileName($file)
    {
        switch ($this->nametype) {
            // 时间命名
            case 'datetime':
                return $this->generateDatetimeName($file);
                break;
            
            // 原始命名，不覆盖已传同名文件
            case 'sequence':
                return $this->generateSequenceName($file);
                break;
            
            // 原始命名，将覆盖已传同名文件
            case 'original':
                return $this->generateClientOriginalName($file);
                break;
            
            // 哈希命名
            case 'uniqid':
            default:
                return $this->generateUniqueName($file);
                break;
        }
    }

    /**
     * 时间命名
     */
    public function generateDatetimeName($file)
    {
        $name = date('YmdHis').mt_rand(10000, 99999);
        $extension = $file->getClientOriginalExtension();
        
        if (empty($extension)) {
            return $name;
        }
        
        return $name.'.'.$extension;
    }

    /**
     * 哈希命名
     */
    public function generateUniqueName($file)
    {
        $name = md5(uniqid().microtime());
        $extension = $file->getClientOriginalExtension();
        
        if (empty($extension)) {
            return $name;
        }
        
        return $name.'.'.$extension;
    }

    /**
     * 原始命名，不覆盖已传同名文件
     */
    public function generateSequenceName($file)
    {
        $index = 1;
        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        
        if (! empty($extension)) {
            $new = sprintf('%s_%s.%s', $original, $index, $extension);
        } else {
            $new = sprintf('%s_%s', $original, $index);
        }

        while ($this->storage->exists($this->formatPath($this->path, $new))) {
            $index++;
            
            if (! empty($extension)) {
                $new = sprintf('%s_%s.%s', $original, $index, $extension);
            } else {
                $new = sprintf('%s_%s', $original, $index);
            }
        }

        return $new;
    }
    
    /**
     * 原始命名，将覆盖已传同名文件
     */
    public function generateClientOriginalName($file)
    {
        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        
        if (empty($extension)) {
            return $name;
        }
        
        return $name.'.'.$extension;
    }

    /**
     * 创建文件夹
     */
    public function createFolder($name)
    {
        $path = $this->formatPath($this->path, $name);

        return $this->storage->makeDirectory($path);
    }

    /**
     * 是否存在
     */
    public function exists($name)
    {
        $path = $this->formatPath($this->path, $name);

        return $this->storage->exists($path);
    }

    /**
     * @return array
     */
    public function urls()
    {
        return [
            'path' => $this->path,
        ];
    }
    
    /**
     * 生成url
     */
    public function buildUrl($url = '')
    {
        if (URL::isValidUrl($url)) {
            return $url;
        }
        
        return $this->storage->url($url);
    }

    /**
     * 格式化文件
     */
    public function formatFiles($files = [])
    {
        $files = array_map(function ($file) {
            return [
                'icon'      => '',
                'name'      => $file,
                'namesmall' => pathinfo($file, PATHINFO_BASENAME),
                'preview'   => $this->getFilePreview($file),
                'isDir'     => false,
                'size'      => $this->getFilesize($file),
                'url'       => $this->storage->url($file),
                'time'      => $this->getFileChangeTime($file),
                'type'      => $this->detectFileType($file)
            ];
        }, $files);

        return collect($files);
    }

    /**
     * 格式化文件夹
     */
    public function formatDirectories($dirs = [])
    {
        $preview = '<a href="javascript:;"><span class="file-icon text-aqua"><i class="fa fa-folder"></i></span></a>';

        $dirs = array_map(function ($dir) use ($preview) {
            return [
                'icon'      => '',
                'name'      => $dir,
                'namesmall' => basename($dir),
                'preview'   => str_replace('__path__', $dir, $preview),
                'isDir'     => true,
                'size'      => '-',
                'url'       => $this->storage->url($dir),
                'time'      => $this->getFileChangeTime($dir),
                'type'      => 'dir',
            ];
        }, $dirs);

        return collect($dirs);
    }

    public function navigation()
    {
        $folders = explode('/', $this->path);

        $folders = array_filter($folders);

        $path = '';

        $navigation = [];

        foreach ($folders as $folder) {
            $path = rtrim($path, '/').'/'.$folder;

            $navigation[] = [
                'name'  => $folder,
                'url'   => $path,
            ];
        }

        return $navigation;
    }

    public function getFilePreview($file)
    {
        switch ($this->detectFileType($file)) {
            case 'image':
                $url = $this->storage->url($file);
                if ($url) {
                    $preview = '<span class="file-icon has-img"><img src="'.$url.'" alt="'.$url.'"></span>';
                } else {
                    $preview = '<span class="file-icon"><i class="fa fa-file-image-o"></i></span>';
                }
                break;

            case 'video':
                /**
                if ($this->storage->getDriver()->getConfig()->has('url')) {
                    $url = $this->storage->url($file);
                    $preview = "<span class=\"file-icon has-video\"><video width='30%' src=\"$url\" alt=\"Attachment\"></span>";
                } else {
                    $preview = '<span class="file-icon"><i class="fa fa-file-video-o"></i></span>';
                }
                */
                $preview = '<span class="file-icon"><i class="fa fa-file-video-o"></i></span>';
                break;

            case 'audio':
                $preview = '<span class="file-icon"><i class="fa fa-file-audio-o"></i></span>';
                break;

            case 'pdf':
                $preview = '<span class="file-icon"><i class="fa fa-file-pdf-o"></i></span>';
                break;

            case 'word':
                $preview = '<span class="file-icon"><i class="fa fa-file-word-o"></i></span>';
                break;

            case 'ppt':
                $preview = '<span class="file-icon"><i class="fa fa-file-powerpoint-o"></i></span>';
                break;

            case 'xls':
                $preview = '<span class="file-icon"><i class="fa fa-file-excel-o"></i></span>';
                break;

            case 'text':
                $preview = '<span class="file-icon"><i class="fa fa-file-text-o"></i></span>';
                break;

            case 'code':
                $preview = '<span class="file-icon"><i class="fa fa-code"></i></span>';
                break;

            case 'zip':
                $preview = '<span class="file-icon"><i class="fa fa-file-zip-o"></i></span>';
                break;

            default:
                $preview = '<span class="file-icon"><i class="fa fa-file"></i></span>';
        }

        return $preview;
    }

    protected function detectFileType($file)
    {
        $extension = File::extension($file);

        foreach ($this->fileTypes as $type => $regex) {
            if (preg_match("/^($regex)$/i", $extension) !== 0) {
                return $type;
            }
        }

        return 'other';
    }

    /**
     * 提前完成裁剪等操作
     *
     * @param array  $methods<method, arguments> arguments = []
     * @param UploadedFile $file
     */
    public function prepareFile($methods, UploadedFile $file)
    {
        $this->callInterventionMethods($methods, $file->getRealPath(), $file->getMimeType());
    }
    
    /**
     * 执行裁剪等操作
     *
     * @param array  $methods<method, arguments> arguments = []
     * @param string $target
     * @param string $mime
     *
     * @return mixed
     */
    public function callInterventionMethods($methods, $target, $mime)
    {
        if (! empty($methods)) {
            $image = ImageManagerStatic::make($target);

            $mime = $mime ?: finfo_file(finfo_open(FILEINFO_MIME_TYPE), $target);

            // 常用方法
            // $call['method'] = resize(320, 240)
            // $call['method'] = insert('public/watermark.png')
            // $call['method'] = rotate(-45);
            foreach ($methods as $call) {
                call_user_func_array(
                    [$image, $call['method']],
                    $call['arguments']
                )->save($target, null, $mime);
            }
        }

        return $target;
    }

    /**
     * 删除缩略图
     *
     * @param array $thumbnails
     * @param string|array $file
     *
     * @return void.
     */
    public function destroyThumbnail($thumbnails, $file = null)
    {
        if (! $file) {
            return;
        }

        if (is_array($file)) {
            foreach ($file as $f) {
                $this->destroyThumbnail($f);
            }

            return;
        }

        foreach ($thumbnails as $name => $_) {
            $ext = pathinfo($file, PATHINFO_EXTENSION);

            $path = Str::replaceLast('.'.$ext, '', $file);

            $path = $path.'-'.$name.'.'.$ext;

            if ($this->storage->exists($path)) {
                $this->storage->delete($path);
            }
        }
    }

    /**
     * 上传并删除缩略图
     *
     * @param array $thumbnails
     * @param UploadedFile $file
     * @return $this
     */
    public function uploadAndDeleteOriginalThumbnail($thumbnails, UploadedFile $file)
    {
        foreach ($thumbnails as $name => $size) {
            $ext = pathinfo($this->name, PATHINFO_EXTENSION);

            $path = Str::replaceLast('.'.$ext, '', $this->name);

            $path = $path.'-'.$name.'.'.$ext;

            /** @var \Intervention\Image\Image $image */
            $image = InterventionImage::make($file);

            $action = $size[2] ?? 'resize';
            $image->$action($size[0], $size[1], function (Constraint $constraint) {
                $constraint->aspectRatio();
            });

            if (! is_null($this->storagePermission)) {
                $this->storage->put($this->formatPath($this->path, $path), $image->encode(), $this->storagePermission);
            } else {
                $this->storage->put($this->formatPath($this->path, $path), $image->encode());
            }
        }

        $this->destroyThumbnail($thumbnails);

        return $this;
    }

    /**
     * 获取文件大小
     */
    public function getFilesize($file)
    {
        try {
            $bytes = filesize($this->getFullPath($file));
            $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

            for ($i = 0; $bytes > 1024; $i++) {
                $bytes /= 1024;
            }
            return round($bytes, 2).' '.$units[$i];
        } catch (\ErrorException $e) {
            return '未知';
        }
    }

    public function getFileChangeTime($file)
    {
        try {
            $time = filectime($this->getFullPath($file));
            return date('Y-m-d H:i:s', $time);
        } catch (\ErrorException $e) {
            return '';
        }
    }
    
    /**
     * 格式化路径
     */
    public function formatPath($path, $name)
    {
        return rtrim($path, '/') . '/' . trim($name, '/');
    }

}