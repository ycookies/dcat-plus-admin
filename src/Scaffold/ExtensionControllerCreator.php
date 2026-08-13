<?php

namespace Dcat\Admin\Scaffold;

/**
 * ControllerCreator 的扩展包专用子类.
 *
 * 唯一差异：覆盖 getPath()，强制把后台控制器写到扩展包 src/ 下，
 * 而不依赖 Helper::guessClassFileName（后者只识别根项目 composer.json 的 psr-4，
 * 对扩展包命名空间会落到根项目错误位置）。
 *
 * 其余逻辑（Grid / Form / Show / DummyNamespace 替换）全部复用父类。
 */
class ExtensionControllerCreator extends ControllerCreator
{
    /**
     * 扩展包 src/ 的绝对路径。
     *
     * @var string
     */
    protected $extensionSrcPath;

    /**
     * 扩展包根命名空间。
     *
     * @var string
     */
    protected $extensionNamespace;

    /**
     * @param  string  $name               Controller FQN
     * @param  string  $extensionSrcPath   扩展包 src/ 绝对路径
     * @param  string  $extensionNamespace 扩展包根命名空间，如 Foo\Bar
     * @param  null    $files
     */
    public function __construct(string $name, string $extensionSrcPath, string $extensionNamespace, $files = null)
    {
        parent::__construct($name, $files);

        $this->extensionSrcPath = rtrim($extensionSrcPath, '/');
        $this->extensionNamespace = trim($extensionNamespace, '\\');
    }

    /**
     * 把控制器 FQN 映射到扩展包 src/ 下的物理路径。
     *
     * @param  string  $name
     * @return string
     */
    public function getPath($name)
    {
        $relative = ltrim(str_replace($this->extensionNamespace, '', $name), '\\');

        return $this->extensionSrcPath.'/'.str_replace('\\', '/', $relative).'.php';
    }
}
