<?php

namespace Dcat\Admin\Scaffold;

use Illuminate\Support\Str;

/**
 * 生成扩展包用的具名类 migration 源码（无 namespace、无时间戳前缀）.
 *
 * 与 Laravel 原生 migration 的关键差异（受 DatabaseUpdater::getClassFromFile 约束）：
 *   - 必须「具名类」且「无 namespace」，匿名类不会被识别执行；
 *   - 文件名无时间戳前缀，命名 create_<table>_table.php；
 *   - 需在扩展包 version.php 中登记才会执行.
 *
 * 不复用 MigrationCreator::buildBluePrint，因为它会强制 bigIncrements 主键、
 * 丢失 decimal 精度、且无法表达 unsigned / index。这里自行渲染 schema 行，
 * 忠实保留反推得到的字段语义.
 */
class ExtensionMigrationBuilder
{
    /**
     * @var DbColumnToSchemaFields
     */
    protected $converter;

    public function __construct(?DbColumnToSchemaFields $converter = null)
    {
        $this->converter = $converter ?: new DbColumnToSchemaFields();
    }

    /**
     * create_<table>_table.php 的迁移类名（无 namespace）.
     * miniapp_configs → CreateMiniappConfigsTable.
     */
    public function classNameForCreate(string $table): string
    {
        return 'Create'.Str::studly(Str::singular($table)).'Table';
    }

    /**
     * create_<table>_table.php 的文件名.
     */
    public function fileNameForCreate(string $table): string
    {
        return 'create_'.$table.'_table.php';
    }

    /**
     * 反推表生成 create migration 源码.
     *
     * @param  string  $table
     * @param  array   $columns  normalized columns (fieldName => column)
     * @param  string  $comment  表注释（业务实体说明），为空时从字段注释推断
     * @return string  完整 PHP 源码
     */
    public function buildCreateFromColumns(string $table, array $columns, string $comment = ''): string
    {
        $fields = $this->converter->convertTable($columns);

        $structure = $this->renderStructure($table, $fields, $comment);
        $className = $this->classNameForCreate($table);

        return $this->wrapCreateClass($table, $className, $structure, true);
    }

    /**
     * 为不存在的表生成「占位」create migration（开发者后续补充字段）.
     *
     * @param  string  $physicalTable  Schema::create 的表名（含扩展前缀）
     * @param  string  $logicTable     逻辑表名（用于注释里的业务名，不含前缀）
     * @param  string  $comment        表注释（业务实体说明）
     * @return string
     */
    public function buildPlaceholderCreate(string $physicalTable, string $logicTable, string $comment = ''): string
    {
        $structure = $this->renderPlaceholderStructure($physicalTable, $logicTable, $comment);
        $className = $this->classNameForCreate($physicalTable);

        return $this->wrapCreateClass($physicalTable, $className, $structure, false);
    }

    /**
     * 渲染 Schema::create 闭包体（真实反推字段）.
     *
     * @param  string  $table
     * @param  array   $fields  DbColumnToSchemaFields 输出
     * @param  string  $comment
     * @return string
     */
    protected function renderStructure(string $table, array $fields, string $comment): string
    {
        $indent = str_repeat(' ', 12);
        $lines = [];

        $lines[] = $indent.'$table->engine = \'InnoDB\';';

        $hasTimestamps = false;
        $indexLines = [];

        foreach ($fields as $name => $field) {
            if ($field['is_primary']) {
                $lines[] = $indent.'$table->id();';
                continue;
            }
            if ($name === 'created_at' || $name === 'updated_at') {
                $hasTimestamps = true;
                continue;
            }
            if ($name === 'deleted_at') {
                continue; // 通过 softDeletes 单独处理
            }

            $lines[] = $indent.$this->renderColumn($field);

            if ($field['index'] && ! $field['unique']) {
                $indexLines[] = $indent."\$table->index('".$name."');";
            }
        }

        if ($hasTimestamps) {
            $lines[] = $indent.'$table->timestamps();';
        }
        if (isset($fields['deleted_at'])) {
            $lines[] = $indent.'$table->softDeletes();';
        }

        foreach ($indexLines as $line) {
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    /**
     * 渲染占位结构：只有 id + name + timestamps + TODO 注释.
     */
    protected function renderPlaceholderStructure(string $physicalTable, string $logicTable, string $comment): string
    {
        $indent = str_repeat(' ', 12);
        // 占位字段注释用逻辑表名的业务名（不含扩展前缀），更友好
        $entityComment = $comment !== '' ? $comment : Str::studly($logicTable);

        return implode("\n", [
            $indent.'$table->engine = \'InnoDB\';',
            $indent.'// TODO: 按 docs/database/schema.md 补充字段，类型/注释遵循 database-schema.md 约定',
            $indent.'$table->id();',
            $indent."\$table->string('name')->comment('".addslashes($entityComment).'名称\');',
            $indent.'$table->timestamps();',
        ]);
    }

    /**
     * 渲染单列 schema 调用.
     */
    protected function renderColumn(array $field): string
    {
        $method = $field['method'];
        $args = $field['args'];

        // 参数列表：方法名始终在最前
        $parts = array_merge(["'".$field['name']."'"], array_map([$this, 'exportArg'], $args));
        $column = '$table->'.$method.'('.implode(', ', $parts).')';

        if ($field['unsigned'] && in_array($method, ['integer', 'bigInteger', 'tinyInteger', 'smallInteger', 'mediumInteger'], true)) {
            $column .= '->unsigned()';
        }
        if ($field['nullable']) {
            $column .= '->nullable()';
        }
        if ($field['has_default']) {
            $column .= '->default('.$this->exportArg($field['default']).')';
        }
        if ($field['unique']) {
            $column .= '->unique()';
        }
        if ($field['comment'] !== '') {
            $column .= '->comment('.$this->exportArg($field['comment']).')';
        }

        return $column.';';
    }

    /**
     * 导出标量为 PHP 字面量.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function exportArg($value): string
    {
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if ($value === null) {
            return 'null';
        }

        // 字符串：单引号 + 转义单引号与反斜杠
        $escaped = str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $value);

        return "'".$escaped."'";
    }

    /**
     * 包裹成完整的具名类 migration（无 namespace）.
     */
    protected function wrapCreateClass(string $table, string $className, string $structure, bool $reversed): string
    {
        $header = $reversed
            ? '// 反推自现有表 '.$table.'；部署到新环境时按此结构建表。如需调整请直接编辑本文件。'
            : '// 占位迁移：表 '.$table.' 尚未在数据库中，字段为骨架，请补充后执行。';

        $tableComment = $reversed
            ? '反推生成：'.$table
            : '骨架：'.$table;

        return <<<PHP
<?php

{$header}

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class {$className} extends Migration
{
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
{$structure}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
}

PHP;
    }
}
