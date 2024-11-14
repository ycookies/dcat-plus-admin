<?php

namespace Dcat\Admin\Http\Forms;

use Dcat\Admin\Admin;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Exception\RuntimeException;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;
use Dcat\EasyExcel\Excel;
use Illuminate\Support\Facades\Schema;

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
        $model_name = $input['model_name'];
        $model = '\\'.base64_decode($model_name);

        // 获取上传文件路径
        $filePath = $this->getFilePath($file);
        
        // 读取Excel文件
        $excel = Excel::import($filePath);
        
        // 获取第一个sheet
        $sheet = $excel->first();
        
        // 获取表头
        $headers = [];
        $sheet->chunk(1, function($collection) use (&$headers) {
            $headers = array_keys($collection->first());
        });
        // 获取model的表字段
        $modelInstance = new $model();
        $tableColumns = Schema::getColumnListing($modelInstance->getTable());
        // 移除一些自动管理的字段
        $excludeColumns = ['id', 'created_at', 'updated_at'];
        $tableColumns = array_diff($tableColumns, $excludeColumns);
        
        // 验证表头与数据库字段是否匹配
        $diff = array_diff($headers, $tableColumns);
        if (!empty($diff)) {
            return $this->response()
                ->error('Excel表头与数据库字段不匹配，请检查模板是否正确。不匹配的字段: ' . implode(', ', $diff));
        }
        
        // 读取并保存数据
        $sheet->chunk(100, function($collection) use ($model) {
            $rows = $collection->toArray();
            if (!empty($rows)) {
                $model::insert($rows);
            }
        });

        return $this->response()->success('导入成功')->refresh();
    }

    public function form()
    {
        $import_tpl_url = !empty($this->payload['import_tpl_url'])?$this->payload['import_tpl_url']:'-';
        $model_name = !empty($this->payload['model'])?$this->payload['model']:'-';
        $table_titles = !empty($this->payload['table_titles'])?$this->payload['table_titles']:'-';
        $this->hidden('model_name')->value($model_name);
        $this->hidden('table_titles')->value($table_titles);
        $this->file('file','上传数据 <br/> (.xlsx)')
            ->required()
            ->rules('required', ['required' => '文件不能为空'])
            ->accept('xlsx,xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel')
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
