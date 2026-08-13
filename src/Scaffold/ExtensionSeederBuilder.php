<?php

namespace Dcat\Admin\Scaffold;

use Illuminate\Support\Str;

/**
 * 生成扩展包用的幂等 Seeder 源码（具名类、无 namespace）.
 *
 * 约束同 ExtensionMigrationBuilder：
 *   - 必须「具名类」「无 namespace」，文件名 seed_<table>_data.php；
 *   - 需在 version.php 中登记，且应排在 create_* 迁移之后.
 *
 * 生成的 seeder 幂等：表已有数据则直接 return。
 * 对每个非系统字段产出一个合理的 mock 默认值（图片/url/邮箱/手机/枚举/金额/布尔/时间），
 * 开发者可按需替换。遵循 TaskGanttChart/updates/seed_*.php 的范例风格.
 */
class ExtensionSeederBuilder
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
     * Seeder 类名（无 namespace）.
     * miniapp_configs → SeedMiniappConfigsData.
     */
    public function classNameFor(string $table): string
    {
        return 'Seed'.Str::studly($table).'Data';
    }

    /**
     * Seeder 文件名.
     */
    public function fileNameFor(string $table): string
    {
        return 'seed_'.$table.'_data.php';
    }

    /**
     * 生成 seeder 源码.
     *
     * @param  string  $table
     * @param  array   $columns  normalized columns（占位表为空数组）
     * @param  int     $count    生成的 mock 行数
     * @return string
     */
    public function build(string $table, array $columns, int $count = 5): string
    {
        $className = $this->classNameFor($table);
        $rowsCode = $this->buildRowsCode($table, $columns, $count);

        return <<<PHP
<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class {$className} extends Seeder
{
    /**
     * Run the seeds.
     *
     * @return void
     */
    public function run()
    {
        // 幂等：表已有数据则跳过
        if (DB::table('{$table}')->count() > 0) {
            return;
        }

        \$rows = [
{$rowsCode}
        ];

        DB::table('{$table}')->insert(\$rows);
    }
}

PHP;
    }

    /**
     * 渲染 mock 行数组的 PHP 源码片段.
     */
    protected function buildRowsCode(string $table, array $columns, int $count): string
    {
        $indent = str_repeat(' ', 12);
        $fields = $columns ? $this->converter->convertTable($columns) : [];

        $lines = [];
        for ($i = 1; $i <= $count; $i++) {
            $pairs = [];
            foreach ($fields as $name => $field) {
                if ($field['is_primary']) {
                    continue;
                }
                if ($name === 'created_at' || $name === 'updated_at') {
                    $pairs[] = "'{$name}' => now()";
                    continue;
                }
                if ($name === 'deleted_at') {
                    continue;
                }
                $value = $this->mockValue($name, $field, $i);
                $pairs[] = "'{$name}' => {$value}";
            }
            // 占位表兜底
            if ($pairs === []) {
                $pairs[] = "'name' => '{$table} #{$i}'";
            }
            $lines[] = $indent.'['.implode(', ', $pairs).'],';
        }

        return implode("\n", $lines);
    }

    /**
     * 根据字段语义生成 mock 值的 PHP 字面量.
     *
     * 优先级：数据库类型优先（保证 tinyint/integer 出数字），类型为 string 时
     * 再按字段名匹配图片/链接/邮箱/手机/密码/IP 等语义值。default 为空字符串时不采用，
     * 改用语义默认值，避免生成大量空 mock.
     *
     * @return string
     */
    protected function mockValue(string $name, array $field, int $index): string
    {
        $lower = strtolower($name);
        $comment = strtolower((string) $field['comment']);
        $method = $field['method'];

        // ---- 数值类型：必须出数字，避免被字段名误判 ----
        if ($method === 'boolean') {
            return $index % 2 === 0 ? '1' : '0';
        }
        if (in_array($method, ['decimal', 'float', 'double'], true)) {
            return "'".number_format($index * 9.9, 2, '.', '')."'";
        }
        if (in_array($method, ['integer', 'bigInteger', 'smallInteger', 'mediumInteger'], true)) {
            return (string) $index;
        }
        if ($method === 'tinyInteger') {
            // tinyint：枚举状态用 0，其余用序号
            return Str::contains($comment, '状态') || Str::contains($lower, ['status', 'state', 'type', 'verified', 'certified', 'deleted', 'enabled', 'visible', 'active']) ? '0' : (string) $index;
        }

        // ---- 时间类型 ----
        if (in_array($method, ['dateTime', 'timestamp', 'timestampTz'], true) || $this->looksLike($lower, ['time']) || Str::endsWith($lower, '_at')) {
            return 'now()';
        }
        if ($method === 'date') {
            return 'now()->toDateString()';
        }
        if (in_array($method, ['time', 'timeTz'], true)) {
            return "'09:00:00'";
        }
        if ($method === 'year') {
            return "'".date('Y')."'";
        }

        // ---- JSON / 长文本 ----
        if ($method === 'json') {
            return "'{\"key\":\"value{$index}\"}'";
        }
        if (in_array($method, ['text', 'mediumText', 'longText'], true)) {
            return "'".addslashes("这是 {$name} 的示例内容 #{$index}。")."'";
        }

        // ---- 字符串类型：按字段名给语义值 ----
        // 密码（最特殊）
        if ($this->looksLike($lower, ['password', 'passwd', 'pwd'])) {
            return "bcrypt('password')";
        }
        // 图片 / 文件
        if ($this->looksLike($lower, ['img', 'image', 'avatar', 'cover', 'banner', 'logo', 'thumbnail', 'photo', 'pic', 'icon'])) {
            return "'https://placehold.co/600x400?text=".Str::studly($name)."+{$index}'";
        }
        // IP 地址
        if ($this->looksLike($lower, ['ip', 'ipaddress']) || Str::endsWith($lower, '_ip')) {
            return "'127.0.0.1'";
        }
        // 链接
        if ($this->looksLike($lower, ['url', 'website', 'homepage', 'link'])) {
            return "'https://example.com/{$lower}/{$index}'";
        }
        // 邮箱
        if ($this->looksLike($lower, ['email'])) {
            return "'user{$index}@example.com'";
        }
        // 手机
        if ($this->looksLike($lower, ['mobile', 'phone', 'tel'])) {
            return "'138".str_pad((string) $index, 8, '0', STR_PAD_LEFT)."'";
        }

        // ---- 兜底字符串 ----
        // 有非空 default 时采用（如 '0'、'active'），空字符串 default 不采用
        if ($field['has_default'] && (string) $field['default'] !== '') {
            return $this->export($field['default']);
        }

        $label = $field['comment'] !== '' ? $field['comment'] : $name;

        return "'".addslashes($label.' '.$index)."'";
    }

    /**
     * 字段名是否匹配任一关键词（精确 / _前缀 / _后缀）.
     *
     * @param  string  $name
     * @param  array   $terms
     */
    protected function looksLike(string $name, array $terms): bool
    {
        foreach ($terms as $term) {
            if ($name === $term || str_ends_with($name, '_'.$term) || str_starts_with($name, $term.'_')) {
                return true;
            }
        }

        return false;
    }

    /**
     * 导出标量为 PHP 字面量.
     *
     * @param  mixed  $value
     * @return string
     */
    protected function export($value): string
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

        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], (string) $value)."'";
    }
}
