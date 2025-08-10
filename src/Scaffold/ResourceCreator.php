<?php

namespace Dcat\Admin\Scaffold;

use Dcat\Admin\Exception\AdminException;
use Dcat\Admin\Support\Helper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ResourceCreator
{
    /**
     * Resource full name.
     *
     * @var string
     */
    protected $name;

    /**
     * Table name.
     *
     * @var string
     */
    protected $tableName;

    /**
     * Table comment or title.
     *
     * @var string
     */
    protected $tableTitle;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * Data type mapping for PHP types.
     *
     * @var array
     */
    protected static $phpTypeMap = [
        'int'                => 'int',
        'integer'            => 'int', 
        'tinyint'            => 'int',
        'smallint'           => 'int',
        'mediumint'          => 'int',
        'bigint'             => 'int',
        'decimal'            => 'float',
        'float'              => 'float',
        'double'             => 'float',
        'varchar'            => 'string',
        'char'               => 'string',
        'text'               => 'string',
        'mediumtext'         => 'string',
        'longtext'           => 'string',
        'date'               => 'string',
        'datetime'           => 'string',
        'timestamp'          => 'string',
        'time'               => 'string',
        'json'               => 'array',
        'boolean'            => 'bool',
        'enum'               => 'string',
    ];

    /**
     * ResourceCreator constructor.
     *
     * @param  string  $name
     * @param  string|null  $tableName
     * @param  string|null  $tableTitle
     * @param  null  $files
     */
    public function __construct($name, $tableName = null, $tableTitle = null, $files = null)
    {
        $this->name = $name;
        $this->tableName = $tableName;
        $this->tableTitle = $tableTitle;
        $this->files = $files ?: app('files');
    }

    /**
     * Create a resource.
     *
     * @param  string  $model
     * @return string
     *
     * @throws \Exception
     */
    public function create($model)
    {
        $path = $this->getPath($this->name);
        $dir = dirname($path);

        if (! is_dir($dir)) {
            $this->files->makeDirectory($dir, 0755, true);
        }

        if ($this->files->exists($path)) {
            throw new AdminException("Resource [$this->name] already exists!");
        }

        $stub = $this->files->get($this->getStub());

        $slug = str_replace('Resource', '', class_basename($this->name));
        $model = $model ?: 'App\\Models\\'.$slug;

        // Generate dynamic fields if table name is provided
        $dynamicFields = '';
        if ($this->tableName) {
            $dynamicFields = $this->generateFields();
        }

        $this->files->put($path, $this->replace($stub, $this->name, $model, $slug, $dynamicFields));
        $this->files->chmod($path, 0777);

        return $path;
    }

    /**
     * Generate dynamic fields based on table schema.
     *
     * @return string
     */
    protected function generateFields()
    {
        $fields = $this->getTableColumns();
        $fieldStrings = [];

        foreach ($fields as $fieldName => $fieldInfo) {
            $comment = $fieldInfo['comment'] ?: $fieldName;
            $phpType = $this->getPhpType($fieldInfo['type']);
            
            $fieldStrings[] = sprintf(
                "            /**\n             * %s\n             * @var %s\n             */\n            '%s' => \$this->%s,",
                $comment,
                $phpType,
                $fieldName,
                $fieldName
            );
        }

        return implode("\n", $fieldStrings);
    }

    /**
     * Get table columns information.
     *
     * @return array
     */
    protected function getTableColumns()
    {
        try {
            $database = config('database.connections.mysql.database');
            $prefix = config('database.connections.mysql.prefix', '');
            $tableName = $prefix . $this->tableName;
            
            $sql = sprintf('SELECT * FROM information_schema.columns WHERE table_schema = "%s" AND TABLE_NAME = "%s" ORDER BY `ORDINAL_POSITION` ASC', $database, $tableName);
            
            $columns = DB::select($sql);
            $result = [];
            
            foreach ($columns as $column) {
                $column = (array)$column;
                $fieldName = $column['COLUMN_NAME'];
                $dataType = strtolower($column['DATA_TYPE']);
                
                if (Str::contains(strtolower($column['COLUMN_TYPE']), 'unsigned')) {
                    $dataType .= '@unsigned';
                }
                
                $result[$fieldName] = [
                    'type' => $dataType,
                    'comment' => $column['COLUMN_COMMENT'],
                    'nullable' => $column['IS_NULLABLE'] === 'YES',
                    'default' => $column['COLUMN_DEFAULT'],
                    'key' => $column['COLUMN_KEY'],
                ];
            }
            
            return $result;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get PHP type from database type.
     *
     * @param string $dbType
     * @return string
     */
    protected function getPhpType($dbType)
    {
        $baseType = str_replace('@unsigned', '', $dbType);
        return static::$phpTypeMap[$baseType] ?? 'string';
    }

    /**
     * @param  string  $stub
     * @param  string  $name
     * @param  string  $model
     * @param  string  $slug
     * @param  string  $dynamicFields
     * @return string
     */
    protected function replace($stub, $name, $model, $slug, $dynamicFields = '')
    {
        $stub = $this->replaceClass($stub, $name);

        // Use table title if provided, otherwise use slug
        $schemaName = $this->tableTitle ?: $slug;

        $fieldsContent = $dynamicFields ?: "            /**\n             * ID\n             * @var int\n             */\n            'id' => \$this->id,";

        return str_replace(
            [
                'DummyModelNamespace',
                'DummyModel',
                'DummyTitle',
                'DummySlug',
                'DummySchemaName',
                'DummyFields',
            ],
            [
                $model,
                class_basename($model),
                $slug,
                strtolower($slug),
                $schemaName,
                $fieldsContent,
            ],
            $stub
        );
    }

    /**
     * Get resource namespace from giving name.
     *
     * @param  string  $name
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);

        return str_replace(['DummyClass', 'DummyNamespace'], [$class, $this->getNamespace($name)], $stub);
    }

    /**
     * Get file path from giving resource name.
     *
     * @param  string  $name
     * @return string
     */
    public function getPath($name)
    {
        return Helper::guessClassFileName($name);
    }

    /**
     * Get stub file path.
     *
     * @return string
     */
    public function getStub()
    {
        return __DIR__.'/stubs/resource.stub';
    }
}