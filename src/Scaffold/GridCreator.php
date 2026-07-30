<?php

namespace Dcat\Admin\Scaffold;

use Dcat\Admin\Scaffold\Support\SemanticFieldResolver;

trait GridCreator
{
    /**
     * @param  string  $primaryKey
     * @param  array  $fields
     * @return string
     */
    protected function generateGrid(?string $primaryKey = null, ?array $fields = null, $timestamps = null)
    {
        $primaryKey = $primaryKey ?: request('primary_key', 'id');
        $primaryKey = SemanticFieldResolver::validFieldName($primaryKey) ? $primaryKey : 'id';
        $fields = $fields === null ? request('fields', []) : $fields;
        $timestamps = $timestamps === null ? request('timestamps') : $timestamps;

        $rows = [
            "\$grid->model()->orderBy('{$primaryKey}', 'desc');",
            "            \$grid->column('{$primaryKey}');",
        ];

        foreach ($fields as $field) {
            $name = (string) ($field['name'] ?? '');
            if (! $name || $name === $primaryKey || ! SemanticFieldResolver::validFieldName($name)) {
                continue;
            }

            $definition = SemanticFieldResolver::resolve($field);
            if ($definition['type'] === 'password') {
                continue;
            }

            $rows[] = '            '.$this->gridColumnCode($name, $definition);
        }

        if ($timestamps) {
            $rows[] = '            $grid->column(\'created_at\')->date(\'Y-m-d H:i\');';
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

    /**
     * @param array{type:string,options:array} $definition
     */
    protected function gridColumnCode(string $name, array $definition): string
    {
        $column = "\$grid->column(".SemanticFieldResolver::export($name).')';

        return match ($definition['type']) {
            'image' => $column."->image('', 44, 44);",
            'file' => $column.'->downloadable();',
            'status' => $definition['options']
                ? $column.'->using('.SemanticFieldResolver::export($definition['options']).')->label();'
                : $column.';',
            'boolean' => $column.'->bool();',
            'long_text', 'json' => $column.'->limit(80);',
            'date', 'datetime', 'time', 'integer', 'decimal' => $column.'->sortable();',
            default => $column.';',
        };
    }
}
