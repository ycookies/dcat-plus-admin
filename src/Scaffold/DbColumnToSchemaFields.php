<?php

namespace Dcat\Admin\Scaffold;

use Illuminate\Support\Str;

/**
 * 把 TableColumnInspector::getNormalizedColumns() 反推得到的字段结构，
 * 转换成可生成 Laravel migration 的 schema 方法描述.
 *
 * 输入 normalized column：
 *   ['type' => 'varchar(255)@unsigned', 'comment' => '', 'nullable' => bool,
 *    'default' => mixed, 'key' => 'PRI|UNI|MUL|', 'id' => bool]
 *
 * 输出 field 描述（供 ExtensionMigrationBuilder 渲染 schema 行）：
 *   ['name' => string, 'method' => 'string|integer|decimal|boolean|json|dateTime|...',
 *    'args' => [mixed,...], 'nullable' => bool, 'default' => mixed|null,
 *    'has_default' => bool, 'unsigned' => bool, 'unique' => bool, 'index' => bool,
 *    'comment' => string, 'is_primary' => bool, 'raw_type' => string]
 *
 * 设计目标：忠实保留原表的类型 / 精度 / unsigned / 注释，便于把反推产物部署到新环境.
 */
class DbColumnToSchemaFields
{
    /**
     * 转换整张表的 normalized columns.
     *
     * @param  array  $columns  fieldName => normalized column
     * @return array<string, array>
     */
    public function convertTable(array $columns): array
    {
        $fields = [];
        foreach ($columns as $name => $column) {
            $fields[$name] = $this->convertColumn($name, (array) $column);
        }

        return $fields;
    }

    /**
     * 转换单个字段.
     *
     * @param  string  $name
     * @param  array   $column  normalized column
     * @return array
     */
    public function convertColumn(string $name, array $column): array
    {
        $rawType = (string) ($column['type'] ?? 'varchar(255)');
        $unsigned = Str::contains($rawType, '@unsigned');
        // 剥离 @unsigned 标记，得到纯 DB 类型定义
        $dbType = trim(Str::replaceFirst('@unsigned', '', $rawType));

        $key = (string) ($column['key'] ?? '');
        $nullable = (bool) ($column['nullable'] ?? false);
        $hasDefault = array_key_exists('default', $column) && $column['default'] !== null;
        $default = $hasDefault ? $column['default'] : null;

        [$method, $args] = $this->mapType($name, $dbType);

        return [
            'name' => $name,
            'method' => $method,
            'args' => $args,
            'nullable' => $nullable,
            'default' => $default,
            'has_default' => $hasDefault,
            'unsigned' => $unsigned,
            'unique' => $key === 'UNI',
            'index' => $key === 'MUL',
            'comment' => (string) ($column['comment'] ?? ''),
            'is_primary' => (bool) ($column['id'] ?? false) || $key === 'PRI',
            'raw_type' => $dbType,
        ];
    }

    /**
     * DB 类型（带括号参数）→ Laravel schema 方法 + 参数.
     *
     * @return array{0:string, 1:array}
     */
    protected function mapType(string $name, string $dbType): array
    {
        // 取括号前的类型名，如 varchar(255) → varchar，decimal(10,2) → decimal
        $base = strtolower(Str::before($dbType, '('));
        // 解析括号内参数，如 varchar(255) → [255]，decimal(10,2) → [10,2]
        $params = [];
        if (preg_match('/\(([^)]*)\)/', $dbType, $m)) {
            $params = array_map(
                fn ($v) => is_numeric(trim($v)) ? (str_contains(trim($v), '.') ? (float) trim($v) : (int) trim($v)) : trim($v),
                explode(',', $m[1])
            );
        }

        switch ($base) {
            case 'tinyint':
            case 'tinyinteger':
                // tinyint(1) 视为 boolean，其余视为 tinyInteger
                return isset($params[0]) && $params[0] === 1
                    ? ['boolean', []]
                    : ['tinyInteger', []];
            case 'smallint':
            case 'smallinteger':
                return ['smallInteger', []];
            case 'mediumint':
            case 'mediuminteger':
                return ['mediumInteger', []];
            case 'int':
            case 'integer':
                return ['integer', []];
            case 'bigint':
            case 'biginteger':
                return ['bigInteger', []];
            case 'decimal':
            case 'numeric':
                return ['decimal', $params];
            case 'float':
                return ['float', []];
            case 'double':
                return ['double', []];
            case 'real':
                return ['float', []];
            case 'bit':
                return ['boolean', []];
            case 'char':
                return ['char', $params];
            case 'varchar':
            case 'string':
                return ['string', $params];
            case 'text':
                return ['text', []];
            case 'mediumtext':
                return ['mediumText', []];
            case 'longtext':
                return ['longText', []];
            case 'json':
            case 'jsonb':
                return ['json', []];
            case 'date':
                return ['date', []];
            case 'datetime':
                return ['dateTime', []];
            case 'timestamp':
                return ['timestamp', []];
            case 'timestamptz':
                return ['timestampTz', []];
            case 'time':
                return ['time', []];
            case 'timetz':
                return ['timeTz', []];
            case 'year':
                return ['year', []];
            case 'binary':
            case 'blob':
            case 'varbinary':
                return ['binary', []];
            case 'uuid':
                return ['uuid', []];
            case 'inet':
            case 'ipaddress':
                return ['ipAddress', []];
            case 'macaddr':
                return ['macAddress', []];
            case 'enum':
            case 'set':
                // enum/set 回退为 string，无法可靠还原选项
                return ['string', []];
            default:
                // 兜底：长字符串
                return ['string', []];
        }
    }
}
