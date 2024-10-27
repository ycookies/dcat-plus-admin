<?php

namespace Dcat\Admin\Form\Extend\FormMedia\Form;

use Illuminate\Support\Facades\Storage;
use Dcat\Admin\Form\Field as BaseField;
use Dcat\Admin\Form\Extend\FormMedia\MediaManager;

/**
 * 表单字段
 *
 * @create 2020-11-25
 * @author deatil
 */
class FieldIconimg extends BaseField
{
    protected $view = 'admin::form.extend.form-media.field-iconimg';

    protected static $css = [
        '@form-media'
    ];

    protected static $js = [
        '@form-media',
    ];
    
    protected $uploadUrl = '';
    protected $listUrl = '';
    protected $createFolderUrl = '';
    protected $rootpath = '';

    protected $disableUpload = false;
    protected $disableCreateFolder = false;
    
    protected $showTitle = false;
    protected $multipleChoice = true;
    protected $showIcon = false;
    
    protected $resize = [];
    
    protected $saveFullUrl = false;
    
    protected $type = '';
    protected $disk = '';

    protected $path = '';
    protected $limit = 1;
    protected $remove = false;
    
    protected $accept = '';
    protected $nametype = 'uniqid';
    protected $pageSize = 120;

    /**
     * 设置上传链接
     *
     * @param string $uploadUrl
     * @return $this
     */
    public function uploadUrl($uploadUrl = null)
    {
        $this->uploadUrl = $uploadUrl;

        return $this;
    }

    /**
     * 设置数据列表链接
     *
     * @param string $listUrl
     * @return $this
     */
    public function listUrl($listUrl = null)
    {
        $this->listUrl = $listUrl;

        return $this;
    }

    /**
     * 设置新建文件夹链接
     *
     * @param string $createFolderUrl
     * @return $this
     */
    public function createFolderUrl($createFolderUrl = null)
    {
        $this->createFolderUrl = $createFolderUrl;

        return $this;
    }

    /**
     * 设置预览前缀
     *
     * @param string $rootpath
     * @return $this
     */
    public function rootpath($rootpath = null)
    {
        $this->rootpath = $rootpath;

        return $this;
    }

    /**
     * 禁止上传
     *
     * @param boolen $value
     * @return $this
     */
    public function disableUpload(bool $value = true)
    {
        $this->disableUpload = $value;

        return $this;
    }

    /**
     * 禁止创建文件夹
     *
     * @param boolen $value
     * @return $this
     */
    public function disableCreateFolder(bool $value = true)
    {
        $this->disableCreateFolder = $value;

        return $this;
    }

    /**
     * 显示标题
     *
     * @param boolen $value
     * @return $this
     */
    public function showTitle(bool $value = true)
    {
        $this->showTitle = $value;

        return $this;
    }

    /**
     * 可多选，默认为可多选
     *
     * @param boolen $value
     * @return $this
     */
    public function multipleChoice(bool $value = true)
    {
        $this->multipleChoice = $value;

        return $this;
    }

    /**
     * 显示图标
     *
     * @param boolen $value
     * @return $this
     */
    public function showIcon(bool $value = true)
    {
        $this->showIcon = $value;

        return $this;
    }

    /**
     * 图片裁剪
     *
     * @return $this
     */
    public function resize($width, $height)
    {
        $this->resize = [$width, $height];

        return $this;
    }

    /**
     * 保存完整链接
     *
     * @param boolen $value
     * @return $this
     */
    public function saveFullUrl(bool $value = true)
    {
        $this->saveFullUrl = $value;

        return $this;
    }

    /**
     * 设置类型
     *
     * 类型包括：blend、image、xls、word、
     *      ppt、pdf、code、zip、text、audio、video
     * 其中 blend 为全部类型
     *
     * @param string $type
     * @return $this
     */
    public function type($type = 'image')
    {
        $this->type = $type;

        return $this;
    }

    /**
     * 设置驱动磁盘
     *
     * @param string $disk
     * @return $this
     */
    public function disk($disk = '')
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * 设置当前可用目录
     *
     * @param string $path
     * @return $this
     */
    public function path($path = '')
    {
        $this->path = $path;
        
        return $this;
    }

    /**
     * 设置限制数量.
     *
     * @param int $limit
     * @return $this
     */
    public function limit($limit = 1)
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * 移除
     *
     * @param boolen $value
     * @return $this
     */
    public function remove(bool $value = true)
    {
        $this->remove = $value;
        
        return $this;
    }

    /**
     * 设置 input accept 属性
     * 常用类型 * /* audio/* video/* image/*
     *
     * @return $this
     */
    public function accept(string $accept)
    {
        $this->accept = $accept;
        
        return $this;
    }

    /**
     * 设置上传保存文件名类型
     *
     * @param string $type uniqid|datetime|sequence|original
     * @return $this
     */
    public function nametype($type = 'uniqid')
    {
        if (! in_array($type, ['uniqid', 'datetime', 'sequence', 'original'])) {
            $type = 'uniqid';
        }
        
        $this->nametype = $type;
        return $this;
    }

    /**
     * 唯一 ID 命名
     *
     * @return $this
     */
    public function uniqidName()
    {
        $this->nametype("uniqid");
        
        return $this;
    }

    /**
     * 时间格式命名
     *
     * @return $this
     */
    public function datetimeName()
    {
        $this->nametype("datetime");
        
        return $this;
    }

    /**
     * sequence 格式命名
     *
     * @return $this
     */
    public function sequenceName()
    {
        $this->nametype("sequence");
        
        return $this;
    }

    /**
     * 原始名称命名
     *
     * @return $this
     */
    public function originalName()
    {
        $this->nametype("original");
        
        return $this;
    }

    /**
     * 设置每页数量
     *
     * @param int $pageSize
     * @return $this
     */
    public function pageSize($pageSize = 120)
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * 渲染
     *
     * @return mixed
     */
    public function render()
    {
        $path = $this->path;
        $limit = $this->limit;
        $type = $this->type;
        $disk = $this->disk;
        $accept = $this->accept;
        $nametype = $this->nametype;
        $pageSize = $this->pageSize;
        $remove = ($this->remove == true) ? 1 : 0;
        
        $rootpath = "";
        if (! empty($this->rootpath)) {
            $rootpath = $this->rootpath;
        } else {
            if (! empty($disk)) {
                $rootpath = MediaManager::create()
                    ->withDisk($disk)
                    ->buildUrl('');
            } else {
                $rootpath = MediaManager::create()
                    ->defaultDisk()
                    ->buildUrl('');
            }
        }
        
        if (empty($this->uploadUrl)) {
            $this->uploadUrl = admin_route('admin.lake-form-media.upload');
        }
        
        if (empty($this->listUrl)) {
            $this->listUrl = admin_route('admin.lake-form-media.get-files');
        }
        
        if (empty($this->createFolderUrl)) {
            $this->createFolderUrl = admin_route('admin.lake-form-media.create-folder');
        }
        
        if ($this->disableUpload) {
            $this->uploadUrl = '';
        }
        if ($this->disableCreateFolder) {
            $this->createFolderUrl = '';
        }
        
        $showTitle = ($this->showTitle == true) ? 1 : 0;
        $multipleChoice = ($this->multipleChoice == true) ? 1 : 0;
        $showIcon = ($this->showIcon == true) ? 1 : 0;
        
        $resize = $this->resize;
        
        $saveFullUrl = ($this->saveFullUrl == true) ? 1 : 0;

        $this->addVariables([
            'options' => [
                'path'   => $path,
                'limit'  => $limit,
                'remove' => $remove,
                'type'   => $type,
                'disk'   => $disk,
                
                'accept'   => $accept,
                'nametype' => $nametype,
                'pagesize' => $pageSize,
                
                'showtitle'      => $showTitle,
                'multiplechoice' => $multipleChoice,
                'showicon'       => $showIcon,
                
                'resize'      => implode(',', $resize),
                
                'saveFullUrl' => $saveFullUrl,
                
                'rootpath'          => $rootpath,
                'get_files_url'     => $this->listUrl,
                'upload_url'        => $this->uploadUrl,
                'create_folder_url' => $this->createFolderUrl,
            ],
        ]);

        return parent::render();
    }

}
