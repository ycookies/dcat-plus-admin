<?php

namespace Dcat\Admin\Scaffold;

use Dcat\Admin\Exception\AdminException;
use Dcat\Admin\Support\Helper;
use Illuminate\Support\Str;

class ModelCreator
{
    /**
     * Table name.
     *
     * @var string
     */
    protected $tableName;

    /**
     * Model name.
     *
     * @var string
     */
    protected $name;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * ModelCreator constructor.
     *
     * @param  string  $tableName
     * @param  string  $name
     * @param  null  $files
     */
    public function __construct($tableName, $name, $files = null)
    {
        $this->tableName = $tableName;

        $this->name = $name;

        $this->files = $files ?: app('files');
    }

    /**
     * Create a new migration file.
     *
     * @param  string  $keyName
     * @param  bool|true  $timestamps
     * @param  bool|false  $softDeletes
     * @return string
     *
     * @throws \Exception
     */
    public function create($keyName = 'id', $timestamps = true, $softDeletes = false)
    {
        $path = $this->getpath($this->name);
        $dir = dirname($path);

        if (! is_dir($dir)) {
            $this->files->makeDirectory($dir, 0755, true);
        }

        if ($this->files->exists($path)) {
            throw new AdminException("Model [$this->name] already exists!");
        }

        $stub = $this->files->get($this->getStub());

        $stub = $this->replaceClass($stub, $this->name)
            ->replaceNamespace($stub, $this->name)
            ->replaceSoftDeletes($stub, $softDeletes)
            ->replaceDatetimeFormatter($stub)
            ->replaceTable($stub, $this->name)
            ->replaceTimestamp($stub, $timestamps)
            ->replaceFillable($stub)
            ->replacePrimaryKey($stub, $keyName)
            ->replaceSpace($stub);

        $this->files->put($path, $stub);
        $this->files->chmod($path, 0777);

        return $path;
    }

    /**
     * Get path for migration file.
     *
     * @param  string  $name
     * @return string
     */
    public function getPath($name)
    {
        return Helper::guessClassFileName($name);
    }

    /**
     * Get namespace of giving class full name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Replace class dummy.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceClass(&$stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        $stub = str_replace('DummyClass', $class, $stub);

        return $this;
    }

    /**
     * Replace namespace dummy.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceNamespace(&$stub, $name)
    {
        $stub = str_replace(
            'DummyNamespace',
            $this->getNamespace($name),
            $stub
        );

        return $this;
    }

    /**
     * Replace soft-deletes dummy.
     *
     * @param  string  $stub
     * @param  bool  $softDeletes
     * @return $this
     */
    protected function replaceSoftDeletes(&$stub, $softDeletes)
    {
        $import = $use = '';

        if ($softDeletes) {
            $import = 'use Illuminate\\Database\\Eloquent\\SoftDeletes;';
            $use = 'use SoftDeletes;';
        }

        $stub = str_replace(['DummyImportSoftDeletesTrait', 'DummyUseSoftDeletesTrait'], [$import, $use], $stub);

        return $this;
    }

    /**
     * Replace datetimeFormatter dummy.
     *
     * @param  string  $stub
     * @param  bool  $softDeletes
     * @return $this
     */
    protected function replaceDatetimeFormatter(&$stub)
    {
        $import = $use = '';

        if (version_compare(app()->version(), '7.0.0') >= 0) {
            $import = 'use Dcat\\Admin\\Traits\\HasDateTimeFormatter;';
            $use = 'use HasDateTimeFormatter;';
        }

        $stub = str_replace(['DummyImportDateTimeFormatterTrait', 'DummyUseDateTimeFormatterTrait'], [$import, $use], $stub);

        return $this;
    }

    /**
     * Replace primarykey dummy.
     *
     * @param  string  $stub
     * @param  string  $keyName
     * @return $this
     */
    protected function replacePrimaryKey(&$stub, $keyName)
    {
        $modelKey = $keyName == 'id' ? '' : "protected \$primaryKey = '$keyName';\n";

        $stub = str_replace('DummyModelKey', $modelKey, $stub);

        return $this;
    }

    /**
     * Replace Table name dummy.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return $this
     */
    protected function replaceTable(&$stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        $table = Str::plural(strtolower($class)) !== $this->tableName ? "protected \$table = '$this->tableName';\n" : '';

        $stub = str_replace('DummyModelTable', $table, $stub);

        return $this;
    }

    /**
     * Replace timestamps dummy.
     *
     * @param  string  $stub
     * @param  bool  $timestamps
     * @return $this
     */
    protected function replaceTimestamp(&$stub, $timestamps)
    {
        $useTimestamps = $timestamps ? '' : "public \$timestamps = false;\n";

        $stub = str_replace('DummyTimestamp', $useTimestamps, $stub);

        return $this;
    }

    /**
     * Replace fillable dummy.
     *
     * @param  string  $stub
     * @param  bool  $timestamps
     * @return $this
     */
    protected function replaceFillable(&$stub)
    {
        $modelFillable = $this->formatFillableWithComments();
        $stub = str_replace('DummyFillable', $modelFillable, $stub);

        return $this;
    }

    /**
     * 获取表的所有字段及其注释
     */
    protected function getTableColumnsWithComments()
    {
        $fields = \DB::getSchemaBuilder()->getColumnListing($this->tableName);

        // 获取字段注释（兼容 MySQL 5.7 和 8）
        $comments = \Illuminate\Support\Facades\DB::table('information_schema.columns')
            ->select('COLUMN_NAME as column_name', 'COLUMN_COMMENT as column_comment')
            ->where('TABLE_SCHEMA', config('database.connections.mysql.database'))
            ->where('TABLE_NAME', $this->tableName)
            ->get()
            ->pluck('column_comment', 'column_name')
            ->toArray();

        // 排除的字段
        $excludedFields = ['id', 'created_at', 'updated_at', 'deleted_at', 'password'];

        // 组合字段和注释
        $result = [];
        foreach ($fields as $column) {
            // 跳过排除的字段
            if (in_array($column, $excludedFields)) {
                continue;
            }
            $result[$column] = $comments[$column] ?? null; // 如果字段没有注释，返回 null
        }

        return $result;
    }
    

    /**
     * 将字段和注释转换为 protected $fillable 的形式
     */
    protected  function formatFillableWithComments()
    {
        $columnsWithComments = $this->getTableColumnsWithComments();
        $fillable = [];

        foreach ($columnsWithComments as $column => $comment) {
            // 格式化每个字段和注释
            $fillable[] = "'{$column}', // {$comment}";
        }

        // 拼接成 protected $fillable 的形式
        $fillableString = "// 可以被批量赋值的属性 也方便查看表所有字段及注释\n";
        $fillableString  .= "   /** protected \$fillable = [\n";
        $fillableString .= '        ' . implode(" \n        ", $fillable) . "\n";
        $fillableString .= '    ]; */';

        return $fillableString;
    }

    /**
     * Replace spaces.
     *
     * @param  string  $stub
     * @return mixed
     */
    public function replaceSpace($stub)
    {
        return str_replace(["\n\n\n", "\n    \n"], ["\n\n", ''], $stub);
    }

    /**
     * Get stub path of model.
     *
     * @return string
     */
    public function getStub()
    {
        return __DIR__.'/stubs/model.stub';
    }
}
