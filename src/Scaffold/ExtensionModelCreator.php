<?php

namespace Dcat\Admin\Scaffold;

/**
 * ModelCreator 的扩展包专用子类.
 *
 * 唯一差异：覆盖 getPath()，强制把模型写到指定基目录（扩展包 src/）下，
 * 而不依赖 Helper::guessClassFileName（后者只识别根项目 composer.json 的 psr-4，
 * 对扩展包命名空间会落到根项目错误位置）.
 *
 * 其余 stub 替换逻辑（fillable / namespace / table / softDeletes 等）全部复用父类.
 */
class ExtensionModelCreator extends ModelCreator
{
    /**
     * 扩展包 src/ 的绝对路径，模型将落到 {basePath}/Models/{Class}.php.
     *
     * @var string
     */
    protected $extensionSrcPath;

    /**
     * 扩展包根命名空间（用于把 FQN 转成相对路径）.
     *
     * @var string
     */
    protected $extensionNamespace;

    /**
     * @param  string       $tableName
     * @param  string       $name           Model FQN，如 Foo\\Bar\\Models\\User
     * @param  string       $extensionSrcPath  扩展包 src/ 绝对路径
     * @param  string       $extensionNamespace  扩展包根命名空间，如 Foo\\Bar
     * @param  string|null  $connectionName
     * @param  array        $columns
     */
    public function __construct(
        string $tableName,
        string $name,
        string $extensionSrcPath,
        string $extensionNamespace,
        ?string $connectionName = null,
        array $columns = []
    ) {
        parent::__construct($tableName, $name, null, $connectionName, $columns);

        $this->extensionSrcPath = rtrim($extensionSrcPath, '/');
        $this->extensionNamespace = trim($extensionNamespace, '\\');
    }

    /**
     * 把模型 FQN 映射到扩展包 src/ 下的物理路径.
     *
     * @param  string  $name  Model FQN
     * @return string
     */
    public function getPath($name)
    {
        $relative = ltrim(str_replace($this->extensionNamespace, '', $name), '\\');

        return $this->extensionSrcPath.'/'.str_replace('\\', '/', $relative).'.php';
    }
}
