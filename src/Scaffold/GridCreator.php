<?php

namespace Dcat\Admin\Scaffold;

trait GridCreator
{
    /**
     * @param  string  $primaryKey
     * @param  array  $fields
     * @return string
     */
    protected function generateGrid(string $primaryKey = null, array $fields = [], $timestamps = null)
    {
        $primaryKey = $primaryKey ?: request('primary_key', 'id');
        $fields = $fields ?: request('fields', []);
        $timestamps = $timestamps === null ? request('timestamps') : $timestamps;

        $rows = [
            "\$grid->column('{$primaryKey}')->sortable();",
        ];

        foreach ($fields as $field) {
            if (empty($field['name'])) {
                continue;
            }

            if ($field['name'] == $primaryKey) {
                continue;
            }

            $rows[] = "            \$grid->column('{$field['name']}');";
        }

        if ($timestamps) {
            $rows[] = '            $grid->column(\'created_at\');';
            $rows[] = '            $grid->column(\'updated_at\')->sortable();';
        }

        $rows[] = <<<EOF
            // \$grid->setActionClass(Grid\Displayers\Actions::class); // 行操作按钮显示方式 图标方式
            \$grid->actions(function (Grid\Displayers\Actions \$actions) {
                // \$actions->disableDelete(); //  禁用删除
                // \$actions->disableEdit();   //  禁用修改
                // \$actions->disableQuickEdit(); //禁用快速修改(弹窗形式)
                // \$actions->disableView(); //  禁用查看
            });
            \$grid->filter(function (Grid\Filter \$filter) {
                \$filter->equal('$primaryKey');
        
            });
EOF;

        return implode("\n", $rows);
    }
}
