<?php

namespace Dcat\Admin\Scaffold;

use Dcat\Admin\Scaffold\Support\SemanticFieldResolver;

trait ShowCreator
{
    /**
     * @param  string  $primaryKey
     * @param  array  $fields
     * @return string
     */
    protected function generateShow(?string $primaryKey = null, ?array $fields = null, $timestamps = null)
    {
        $primaryKey = $primaryKey ?: request('primary_key', 'id');
        $primaryKey = SemanticFieldResolver::validFieldName($primaryKey) ? $primaryKey : 'id';
        $fields = $fields === null ? request('fields', []) : $fields;
        $timestamps = $timestamps === null ? request('timestamps') : $timestamps;

        $rows = [];

        if ($primaryKey) {
            $rows[] = "            \$show->field('{$primaryKey}');";
        }

        foreach ($fields as $field) {
            $name = (string) ($field['name'] ?? '');
            if (! $name || $name === $primaryKey || ! SemanticFieldResolver::validFieldName($name)) {
                continue;
            }

            $definition = SemanticFieldResolver::resolve($field);
            if ($definition['type'] === 'password') {
                continue;
            }

            $rows[] = '            '.$this->showFieldCode($name, $definition);
        }

        if ($timestamps) {
            $rows[] = '            $show->field(\'created_at\');';
            $rows[] = '            $show->field(\'updated_at\');';
        }

        return trim(implode("\n", $rows));
    }

    /**
     * @param array{type:string,options:array} $definition
     */
    protected function showFieldCode(string $name, array $definition): string
    {
        $field = "\$show->field(".SemanticFieldResolver::export($name).')';

        return match ($definition['type']) {
            'image' => $field.'->image();',
            'file' => $field.'->file();',
            'status' => $definition['options']
                ? $field.'->using('.SemanticFieldResolver::export($definition['options']).')->label();'
                : $field.';',
            'boolean' => $field.'->bool();',
            'json' => $field.'->json();',
            default => $field.';',
        };
    }
}
