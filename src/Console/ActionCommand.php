<?php

namespace Dcat\Admin\Console;

class ActionCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:action
        {action? : The type of action (default/grid-batch/grid-row/grid-tool/form-tool/show-tool/tree-row/tree-tool/lazy-table/lazy-form)}
        {name? : The name of action class}
        {--namespace= : The namespace of the action class}
        {--base= : The application path}
        {--force : Overwrite the action class if it already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make a admin action';

    /**
     * @var string
     */
    protected $choice;

    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $namespace;

    /**
     * 异步加载渲染类（非 action）映射的命名空间段。
     *
     * 这两种类型不是 action 类，而是异步加载渲染类，
     * 命名空间不在 Actions 下：表格渲染类放在 Renderable，工具表单放在 Forms。
     *
     * @var array
     */
    protected $lazyTypeNamespaceMap = [
        'lazy-table' => ['Renderable', null],
        'lazy-form'  => ['Forms', null],
    ];

    /**
     * @var array
     */
    protected $namespaceMap = [
        'grid-batch' => 'Grid',
        'grid-row'   => 'Grid',
        'grid-tool'  => 'Grid',
        'form-tool'  => 'Form',
        'show-tool'  => 'Show',
        'tree-row'   => 'Tree',
        'tree-tool'  => 'Tree',
    ];

    public function handle()
    {
        // 1. action 类型：有参数则用参数并校验，否则交互式选择
        if ($actionArg = $this->argument('action')) {
            if (! in_array($actionArg, $this->actionTyps(), true)) {
                $this->error(sprintf('Invalid action type [%s]. Valid types are: %s', $actionArg, implode(', ', $this->actionTyps())));

                return false;
            }
            $this->choice = $actionArg;
        } else {
            $this->choice = $this->choice(
                'Which type of action would you like to make?',
                $this->actionTyps()
            );
        }

        // 2. 类名：有参数则用参数，否则交互式输入
        if ($nameArg = $this->argument('name')) {
            $this->className = ucfirst(trim($nameArg));
        } else {
            INPUT_NAME:

            $this->className = ucfirst(trim($this->ask('Please enter a name of action class')));

            if (! $this->className) {
                goto INPUT_NAME;
            }
        }

        // 3. 命名空间：传了 --namespace 就直接用；
        //    否则参数模式下采用类型对应的默认命名空间（不再停下来询问，对 AI 友好），
        //    纯交互模式（无 action 参数）下才交互式询问。
        if ($namespaceOpt = $this->option('namespace')) {
            $this->namespace = ucfirst(trim($namespaceOpt));
        } elseif ($actionArg) {
            $this->namespace = $this->getDefaultNamespace(null);
        } else {
            $this->namespace = ucfirst(trim($this->ask('Please enter the namespace of action class', $this->getDefaultNamespace(null))));
        }

        // 4. 应用路径：传了 --base 就直接用；
        //    否则参数模式下沿用当前应用路径（app/），纯交互模式才询问。
        if ($baseOpt = $this->option('base')) {
            $this->baseDirectory = trim($baseOpt);
        } elseif (! $actionArg) {
            $this->askBaseDirectory();
        }

        return parent::handle();
    }

    /**
     * @return array
     */
    protected function actionTyps()
    {
        return [
            'default',
            'grid-batch',
            'grid-row',
            'grid-tool',
            'form-tool',
            'show-tool',
            'tree-row',
            'tree-tool',
            'lazy-table',
            'lazy-form',
        ];
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
        $stub = parent::replaceClass($stub, $name);

        return str_replace(
            [
                'DummyName',
            ],
            [
                $this->className,
            ],
            $stub
        );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    public function getStub()
    {
        return __DIR__."/stubs/actions/{$this->choice}.stub";
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        if ($this->namespace) {
            return $this->namespace;
        }

        $segments = explode('\\', config('admin.route.namespace'));
        array_pop($segments);

        // 异步加载渲染类不在 Actions 命名空间下：表格放在 Renderable，工具表单放在 Forms
        if (isset($this->lazyTypeNamespaceMap[$this->choice])) {
            array_push($segments, $this->lazyTypeNamespaceMap[$this->choice][0]);

            return implode('\\', $segments);
        }

        array_push($segments, 'Actions');

        if (isset($this->namespaceMap[$this->choice])) {
            array_push($segments, $this->namespaceMap[$this->choice]);
        }

        return implode('\\', $segments);
    }

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        $this->type = $this->qualifyClass($this->className);

        return $this->className;
    }
}
