<?php

namespace Dcat\Admin\Scaffold;

/**
 * ApiControllerCreator 的扩展包专用子类.
 *
 * 与父类的差异：
 * 1. 覆盖 getPath() —— 控制器写到扩展包 src/ 下（不依赖 guessClassFileName）。
 * 2. 覆盖 getStub() —— 用扩展版 stub（命名空间为占位符 {namespace}，而非硬编码 App\Admin\Api\Controllers）。
 * 3. 覆盖 replace() —— 在父类替换之外，额外替换 {namespace} / {baseNamespace} /
 *    {modelNamespace} / {resourceNamespace} / {group} / DummyModelResource。
 *
 * 支持两种 API 形态：member-api（继承 App\Api\Controllers\BaseApiController）、
 * admin-api（继承 App\Admin\Api\Controllers\BaseApiController），通过构造参数 $kind 切换 stub。
 */
class ExtensionApiControllerCreator extends ApiControllerCreator
{
    /** member-api 形态 */
    public const KIND_MEMBER = 'member';
    /** admin-api 形态 */
    public const KIND_ADMIN = 'admin';

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
     * API 形态：member / admin。
     *
     * @var string
     */
    protected $kind;

    /**
     * 控制器命名空间（如 Foo\Bar\Http\Api\Controllers）。
     *
     * @var string
     */
    protected $controllerNamespace;

    /**
     * BaseApiController 的 FQN。
     *
     * @var string
     */
    protected $baseNamespace;

    /**
     * Resource FQN（如 Foo\Bar\Http\Resources\DishResource）。
     *
     * @var string
     */
    protected $resourceFqn;

    /**
     * Scramble 分组名（控制器中文名）。
     *
     * @var string
     */
    protected $group;

    /**
     * @param  string  $name               Controller FQN
     * @param  string  $extensionSrcPath   扩展包 src/ 绝对路径
     * @param  string  $extensionNamespace 扩展包根命名空间，如 Foo\Bar
     * @param  string  $kind               member|admin
     * @param  string  $controllerNamespace 控制器命名空间
     * @param  string  $resourceFqn        Resource FQN
     * @param  string  $group              Scramble 分组名
     * @param  null    $files
     */
    public function __construct(
        string $name,
        string $extensionSrcPath,
        string $extensionNamespace,
        string $kind,
        string $controllerNamespace,
        string $resourceFqn,
        string $group,
        $files = null
    ) {
        parent::__construct($name, $files);

        $this->extensionSrcPath = rtrim($extensionSrcPath, '/');
        $this->extensionNamespace = trim($extensionNamespace, '\\');
        $this->kind = $kind === self::KIND_ADMIN ? self::KIND_ADMIN : self::KIND_MEMBER;
        $this->controllerNamespace = trim($controllerNamespace, '\\');
        $this->resourceFqn = trim($resourceFqn, '\\');
        $this->group = $group;
        $this->baseNamespace = $this->kind === self::KIND_ADMIN
            ? 'App\\Admin\\Api\\Controllers\\BaseApiController'
            : 'App\\Api\\Controllers\\BaseApiController';
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

    /**
     * {@inheritdoc}
     */
    public function getStub()
    {
        if ($this->kind === self::KIND_ADMIN) {
            return __DIR__.'/stubs/extension/admin_api_controller.stub';
        }

        return __DIR__.'/stubs/extension/member_api_controller.stub';
    }

    /**
     * 在父类替换基础上，补齐扩展版 stub 的额外占位符。
     *
     * @param  string  $stub
     * @param  string  $name
     * @param  string  $model
     * @param  string  $slug
     * @param  string  $table
     * @return string
     */
    protected function replace($stub, $name, $model, $slug, $table)
    {
        $stub = parent::replace($stub, $name, $model, $slug, $table);

        $resourceShort = class_basename($this->resourceFqn);

        return str_replace(
            [
                '{namespace}',
                '{baseNamespace}',
                '{modelNamespace}',
                '{resourceNamespace}',
                '{group}',
                'DummyModelResource',
            ],
            [
                $this->controllerNamespace,
                $this->baseNamespace,
                ltrim($model, '\\'),
                $this->resourceFqn,
                $this->group ?: class_basename($model),
                $resourceShort,
            ],
            $stub
        );
    }
}
