<?php

namespace Dcat\Admin\Http\Forms;

use Dcat\Admin\Admin;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Exception\RuntimeException;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class GirdImportFrom extends Form implements LazyRenderable
{
    use LazyWidget;

    /**
     * @desc 处理 数据导入
     * @param array $input
     * @return \Dcat\Admin\Http\JsonResponse
     */
    public function handle(array $input)
    {
        $file = $input['file'];

        if (! $file) {
            return $this->response()->error('Invalid arguments.');
        }

        try {

            if (! $extensionName) {
                return $this->response()->error(trans('admin.invalid_extension_package'));
            }

            return $this->response()
                ->success(implode('<br>', $manager->updateManager()->notes))
                ->refresh();
        } catch (\Throwable $e) {
            Admin::reportException($e);

            return $this->response()->error($e->getMessage());
        } finally {
            if (! empty($path)) {
                @unlink($path);
            }
        }
    }

    public function form()
    {
        $import_tpl_url = !empty($this->payload['import_tpl_url'])?$this->payload['import_tpl_url']:'-';
        $this->file('file','上传数据 <br/> (.xlsx)')
            ->required()
            ->rules('required', ['required' => '文件不能为空'])
            //->disk($this->disk())
            //->accept('zip', 'application/zip')
            ->autoUpload()->help('<span class="text-danger">请使用模板导入数据.只支持上传.xlsx表格文件</span> <a href="'.$import_tpl_url.'" target="_blank">下载模板</a>');
    }

    protected function getFilePath($file)
    {
        $root = config("filesystems.disks.{$this->disk()}.root");

        if (! $root) {
            throw new RuntimeException(sprintf('Missing \'root\' for disk [%s].', $this->disk()));
        }

        return rtrim($root, '/').'/'.$file;
    }

    protected function disk()
    {
        return config('admin.extension.disk') ?: 'local';
    }
}
