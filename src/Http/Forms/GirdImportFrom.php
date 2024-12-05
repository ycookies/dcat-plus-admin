<?php

namespace Dcat\Admin\Http\Forms;

use Dcat\Admin\Admin;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Exception\RuntimeException;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;
use Dcat\EasyExcel\Excel;
use Illuminate\Support\Facades\Schema;
use function Nuwave\Lighthouse\Schema\AST\qualifiedArgType;

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
        // Set unlimited execution time for import
        set_time_limit(0);
        ini_set('memory_limit', '-1');
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

        $headers = array_diff($headers, ['numberNo']);
        // 验证表头与数据库字段是否匹配
        $diff = array_diff($headers, $tableColumns);

        if (!empty($diff)) {
            return $this->response()
                ->error('Excel表头与数据库字段不匹配，请检查模板是否正确。不匹配的字段: ' . implode(', ', $diff));
        }

        // 检查全部数据是否符合要求
        // 获取所有数据并验证
        $errors = [];
        $rowNumber = 1;
        $sheet->chunk(100, function($collection) use (&$errors, &$rowNumber, $modelInstance) {
            foreach ($collection as $row) {
                foreach ($row as $column => $value) {
                    if ($column === 'numberNo') {
                        continue;
                    }
                    // 检查字段是否允许为空
                    $columnType = Schema::getColumnType($modelInstance->getTable(), $column);
                    $isNullable = Schema::getConnection()->getDoctrineColumn($modelInstance->getTable(), $column)->getNotnull();
                    if ($isNullable && ($value === null || $value === '')) {
                        $errors[] = "第{$rowNumber}行的 {$column} 字段不能为空";
                        continue;
                    }
                    
                    if ($value !== null && $value !== '') {
                        // 检查数值类型
                        if (in_array($columnType, ['integer', 'bigint', 'smallint', 'tinyint']) && !is_numeric($value)) {
                            $errors[] = "第{$rowNumber}行的 {$column} 字段必须是数字类型";
                        }
                        
                        // 检查日期类型
                        if (in_array($columnType, ['date', 'datetime', 'timestamp', 'time', 'year']) && !strtotime($value)) {
                            $errors[] = "第{$rowNumber}行的 {$column} 字段必须是有效的日期格式";
                            
                            // 针对time类型做额外验证
                            if ($columnType === 'time') {
                                if (!preg_match('/^(?:2[0-3]|[01][0-9]):[0-5][0-9]:[0-5][0-9]$/', $value)) {
                                    $errors[] = "第{$rowNumber}行的 {$column} 字段必须是有效的时间格式(HH:mm:ss)";
                                }
                            }
                            
                            // 针对year类型做额外验证
                            if ($columnType === 'year') {
                                if (!preg_match('/^[12][0-9]{3}$/', $value)) {
                                    $errors[] = "第{$rowNumber}行的 {$column} 字段必须是有效的年份格式(YYYY)";
                                }
                            }
                        }
                        
                        // 检查decimal/float类型
                        if (in_array($columnType, ['decimal', 'float', 'double', 'real']) && !is_numeric($value)) {
                            $errors[] = "第{$rowNumber}行的 {$column} 字段必须是有效的数字格式";
                        }
                        
                        // 检查布尔类型
                        if ($columnType === 'boolean' && !in_array(strtolower($value), ['0', '1', 'true', 'false'])) {
                            $errors[] = "第{$rowNumber}行的 {$column} 字段必须是布尔值(true/false或0/1)";
                        }
                        
                        // 检查字符串长度
                        if ($columnType === 'char') {
                            $length = Schema::getConnection()->getDoctrineColumn($modelInstance->getTable(), $column)->getLength();
                            if (mb_strlen($value) > $length) {
                                $errors[] = "第{$rowNumber}行的 {$column} 字段长度不能超过{$length}个字符";
                            }
                        }
                    }
                }
                $rowNumber++;
            }
        });

        if (!empty($errors)) {
            return $this->response()->error('数据验证失败：<br>' . implode('<br>', $errors));
        }

        // 读取并保存数据
        $sheet->chunk(100, function($collection) use ($model,$modelInstance) {
            $rows = $collection->toArray();
            if (!empty($rows)) {
                $errors = [];
                foreach ($rows as $key => $items) {
                    if(!empty($items['numberNo'])) {
                        unset($rows[$key]['numberNo']);
                    }
                }
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
            ->disk($this->disk())
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
