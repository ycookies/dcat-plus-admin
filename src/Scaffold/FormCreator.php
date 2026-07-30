<?php

namespace Dcat\Admin\Scaffold;

use Dcat\Admin\Scaffold\Support\SemanticFieldResolver;

trait FormCreator
{
    /**
     * @param  string  $primaryKey
     * @param  array  $fields
     * @param  bool  $timestamps
     * @return string
     */
    protected function generateForm(?string $primaryKey = null, ?array $fields = null, $timestamps = null)
    {
        $primaryKey = $primaryKey ?: request('primary_key', 'id');
        $primaryKey = SemanticFieldResolver::validFieldName($primaryKey) ? $primaryKey : 'id';
        $fields = $fields === null ? request('fields', []) : $fields;
        $timestamps = $timestamps === null ? request('timestamps') : $timestamps;

        $rows = [
            "\$form->display('{$primaryKey}');"
        ];

        foreach ($fields as $field) {
            $name = (string) ($field['name'] ?? '');
            if (! $name || $name === $primaryKey || ! SemanticFieldResolver::validFieldName($name)) {
                continue;
            }

            $rows[] = '            '.$this->formFieldCode($name, $field, SemanticFieldResolver::resolve($field));
        }
        if ($timestamps) {
            $rows[] = <<<'EOF'

            $form->display('created_at');
            $form->display('updated_at');
EOF;
        }

        return implode("\n", $rows);
    }

    /**
     * @param array<string, mixed> $field
     * @param array{type:string,label:string,options:array} $definition
     */
    protected function formFieldCode(string $name, array $field, array $definition): string
    {
        $fieldCode = "\$form->{$this->formMethod($definition['type'])}("
            .SemanticFieldResolver::export($name).', '
            .SemanticFieldResolver::export($definition['label']).')';

        if ($definition['type'] === 'status' && $definition['options']) {
            $fieldCode .= '->options('.SemanticFieldResolver::export($definition['options']).')';
        }
        if ($definition['type'] === 'json') {
            $fieldCode .= "->help('JSON')";
        }

        if ($this->shouldRequire($field, $definition['type'])) {
            $fieldCode .= '->required()';
        }

        return $fieldCode.';';
    }

    protected function formMethod(string $type): string
    {
        return match ($type) {
            'image' => 'image',
            'file' => 'file',
            'url' => 'url',
            'email' => 'email',
            'phone' => 'mobile',
            'password' => 'password',
            'color' => 'color',
            'status' => 'select',
            'boolean' => 'switch',
            'date' => 'date',
            'datetime' => 'datetime',
            'time' => 'time',
            'integer' => 'number',
            'decimal' => 'decimal',
            'json', 'long_text' => 'textarea',
            default => 'text',
        };
    }

    /**
     * @param array<string, mixed> $field
     */
    protected function shouldRequire(array $field, string $type): bool
    {
        if (in_array($type, ['image', 'file', 'password'], true)) {
            return false;
        }

        $nullable = ($field['nullable'] ?? '') === 'on' || ($field['nullable'] ?? false) === true;
        $default = $field['default'] ?? null;

        return ! $nullable && ($default === null || $default === '');
    }
}
