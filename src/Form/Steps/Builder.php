<?php

namespace Dcat\Admin\Form\Steps;

use Closure;
use Dcat\Admin\Form as ParentForm;
use Dcat\Admin\Http\JsonResponse;
use Dcat\Admin\Form\Steps\Form;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Builder
{
    const CURRENT_VALIDATION_STEP = 'CURRENT_VALIDATION_STEP';
    const ALL_STEPS = 'ALL_STEPS';

    /**
     * @var ParentForm
     */
    protected $form;

    /**
     * @var Form[]
     */
    protected $FormSteps = [];

    /**
     * @var CompletionPage
     */
    protected $completionPage;

    /**
     * @var array
     */
    protected $options = [
        'selected' => 0,
        'width'    => '1000px',
        'padding'  => '30px 18px 30px',
        'remember' => false,
        'shown'    => [],
        'leaving'  => [],
        'layout'   => 'horizontal',  // 'horizontal' or 'vertical'
    ];

    public function __construct(ParentForm $form)
    {
        $this->form = $form;

        $this->initForm();
    }

    /**
     * 添加步骤
     *
     * @param string|Form|Form[] $title
     * @param \Closure|null      $callback
     *
     * @return $this
     */
    public function add($title, ?\Closure $callback = null)
    {
        if (is_array($title)) {
            foreach ($title as $key => $form) {
                $this->addStep($form, $callback);
            }

            return $this;
        }

        $step = $title instanceof Form ? $title : new Form($this->form, $title, count($this->FormSteps));

        $this->addStep($step, $callback);

        return $this;
    }

    /**
     * 添加步骤
     *
     * @param Form          $step
     * @param \Closure|null $callback
     *
     * @return void
     */
    protected function addStep(Form $step, ?\Closure $callback = null)
    {
        $this->FormSteps[] = $step;

        if ($callback) {
            $callback($step);
        }
    }

    /**
     * 获取所有步骤
     *
     * @return Form[]
     */
    public function all()
    {
        return $this->FormSteps;
    }

    /**
     * 获取所有字段
     *
     * @return ParentForm\Field[]|Collection
     */
    public function fields()
    {
        return $this->form->builder()->fields();
    }

    /**
     * 步骤数量
     *
     * @return int
     */
    public function count()
    {
        return count($this->FormSteps);
    }

    /**
     * 设置选项
     *
     * @param string|array $key
     * @param mixed        $value
     *
     * @return $this
     */
    public function option($key, $value = null)
    {
        if (is_array($key)) {
            $this->options = array_merge($this->options, $key);
        } else {
            $this->options[$key] = $value;
        }

        return $this;
    }

    /**
     * 获取选项
     *
     * @param string|null $key
     * @param null        $default
     *
     * @return array|mixed|null
     */
    public function getOption($key = null, $default = null)
    {
        if ($key === null) {
            return $this->options;
        }

        return $this->options[$key] ?? $default;
    }

    /**
     * 选择步骤
     *
     * @param int $index
     *
     * @return $this
     */
    public function select(int $index)
    {
        return $this->option('selected', $index);
    }

    /**
     * 设置容器内边距
     *
     * @param string $padding
     *
     * @return $this
     */
    public function padding(string $padding)
    {
        return $this->option('padding', $padding);
    }

    /**
     * 设置容器最大宽度
     *
     * @param string $width
     *
     * @return $this
     */
    public function width(string $width)
    {
        return $this->option('width', $width);
    }
    /**
     * 设置步骤导航布局
     *
     * @param string $layout 'horizontal'（左右布局，默认）或 'vertical'（上下布局）
     *
     * @return $this
     */
    public function layout(string $layout)
    {
        return $this->option('layout', $layout);
    }
    /**
     * 记住输入数据
     *
     * @param bool $value
     *
     * @return $this
     */
    public function remember(bool $value = true)
    {
        return $this->option('remember', $value);
    }

    /**
     * 设置完成页面
     *
     * @param string|Closure $title
     * @param Closure|null   $callback
     *
     * @return $this|CompletionPage
     */
    public function done($title = null, ?Closure $callback = null)
    {
        if ($title === null && $callback === null) {
            if (! $this->completionPage) {
                $this->makeDefaultCompletionPage();
            }

            return $this->completionPage;
        }

        if ($title instanceof Closure) {
            $callback = $title;
            $title = trans('admin.done');
        }

        $this->completionPage = new CompletionPage($this->form, $title, $callback);

        return $this;
    }

    /**
     * 创建默认完成页面
     *
     * @return void
     */
    protected function makeDefaultCompletionPage()
    {
        $this->done(function () {
            $resource = $this->form->resource(0);

            $data = [
                'title'       => trans('admin.save_succeeded'),
                'description' => '',
                'createUrl'   => $resource.'/create',
                'backUrl'     => $resource,
            ];

            return view('admin::form.steps.completion-page', $data);
        });
    }

    /**
     * 暂存输入数据
     *
     * @param array $data
     * @param bool  $merge
     *
     * @return void
     */
    public function stash(array $data, bool $merge = false)
    {
        if (! $this->options['remember']) {
            return;
        }

        if ($merge) {
            $data = array_merge($this->fetchStash(), $data);
        }

        session()->put($this->getStashKey(), $data);
    }

    /**
     * 获取暂存数据
     *
     * @return array
     */
    public function fetchStash()
    {
        if (! $this->options['remember']) {
            return [];
        }

        return session()->get($this->getStashKey()) ?: [];
    }

    /**
     * 清空暂存数据
     *
     * @return void
     */
    public function flushStash()
    {
        if (! $this->options['remember']) {
            return;
        }

        session()->remove($this->getStashKey());
    }

    /**
     * 删除暂存数据中的指定键
     *
     * @param string|array $keys
     *
     * @return void
     */
    public function forgetStash($keys)
    {
        $data = $this->fetchStash();

        Arr::forget($data, $keys);

        $this->stash($data);
    }

    /**
     * 获取暂存键名
     *
     * @return string
     */
    protected function getStashKey()
    {
        return 'step-form-input:'.admin_controller_slug();
    }

    /**
     * 选择步骤（从暂存数据）
     *
     * @return void
     */
    protected function selectStep()
    {
        if (! $this->options['remember'] || ! $input = $this->fetchStash()) {
            return;
        }

        $current = $input[static::CURRENT_VALIDATION_STEP] ?? null;
        $allStep = $input[static::ALL_STEPS] ?? null;

        unset($input[static::CURRENT_VALIDATION_STEP], $input[static::ALL_STEPS]);

        if ($current !== null && $current !== '' && ! empty($input)) {
            $this->select((int) ($current + 1));
        }

        if (! empty($allStep) && ! empty($input)) {
            $this->select($this->count() - 1);
        }
    }

    /**
     * 构建表单
     *
     * @return string
     */
    public function build()
    {
        $this->selectStep();

        $this->prepareForm();

        return $this->renderFields();
    }

    /**
     * 准备表单
     *
     * @return void
     */
    protected function prepareForm()
    {
        foreach ($this->FormSteps as $step) {
            $step->action($this->form->action());
        }
    }

    /**
     * 渲染字段
     *
     * @return string
     */
    public function renderFields()
    {
        $html = '';

        foreach ($this->FormSteps as $step) {
            $html .= (string) $step->render();
        }

        return $html;
    }

    /**
     * 注册步骤显示事件
     *
     * @param string $script
     *
     * @return $this
     */
    public function shown($script)
    {
        $script = value($script);

        $this->options['shown'][] = <<<JS
function (args) {
    {$script}
}
JS;

        return $this;
    }

    /**
     * 注册步骤离开事件
     *
     * @param string $script
     *
     * @return $this
     */
    public function leaving($script)
    {
        $script = value($script);

        $this->options['leaving'][] = <<<JS
function (args) {
    {$script}
}
JS;

        return $this;
    }

    /**
     * 获取字段所在步骤索引
     *
     * @param string|\Dcat\Admin\Form\Field $column
     *
     * @return false|int
     */
    public function fieldIndex($column)
    {
        $columnName = $column instanceof \Dcat\Admin\Form\Field ? $column->column() : $column;

        foreach ($this->FormSteps as $index => $step) {
            foreach ($step->fields() as $field) {
                if ($field->column() == $columnName) {
                    return $index;
                }
            }
        }

        return false;
    }

    /**
     * 初始化
     */
    protected function initForm()
    {
        $this->form->builder()->view('admin::form.steps.steps');

        $this->form->builder()->addVariables(['steps' => $this]);

        $self = $this;
        $this->form->submitted(function ($form) use ($self) {
            $self->prepareFormStepFields($form->input());

            // 验证步骤表单
            if ($self->isFormStepValidationRequest()) {
                return $self->validateFormStep($form->input());
            }
        });

        $this->form->saved(function () use ($self) {
            $self->flushStash();

            return $self->responseCompletionPage();
        });

        $this->form->fileDeleted(function ($form, $field) use ($self) {
            // 移除暂存数据
            $self->stashIndexByField($field->column());
            $self->forgetStash($field->column());
        });
    }

    /**
     * 准备步骤字段
     *
     * @param array $input
     *
     * @return void
     */
    protected function prepareFormStepFields(array $input)
    {
        if (
            empty($this->count())
            || (! isset($input[static::ALL_STEPS]) && ! $this->isFormStepValidationRequest())
        ) {
            return;
        }

        // 获取父表单中声明忽略的字段列表
        $ignoredFields = method_exists($this->form, 'getIgnoredColumns') ? $this->form->getIgnoredColumns() :
                         (property_exists($this->form, 'ignore') ? $this->form->ignore : []);

        // 收集所有步骤的字段（排除已被忽略的字段）
        $fields = [];
        foreach ($this->FormSteps as $step) {
            foreach ($step->fields() as $field) {
                $column = $field->column();
                // 如果字段在忽略列表中，跳过
                if (in_array($column, $ignoredFields)) {
                    continue;
                }
                $fields[] = $field;
            }
        }

        // 将字段添加到父表单的隐藏字段中，确保验证和保存正常工作
        foreach ($fields as $field) {
            $this->form->pushField($field);
        }
    }

    /**
     * 判断是否为步骤验证请求
     *
     * @return bool
     */
    protected function isFormStepValidationRequest()
    {
        $index = request(static::CURRENT_VALIDATION_STEP);

        return $index !== '' && $index !== null;
    }

    /**
     * 验证步骤表单
     *
     * @param array $input
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    protected function validateFormStep(array $input)
    {
        // 获取当前步骤索引
        $currentStepIndex = (int) request(static::CURRENT_VALIDATION_STEP);

        // 获取当前步骤的字段
        $currentStepFields = [];
        if (isset($this->FormSteps[$currentStepIndex])) {
            foreach ($this->FormSteps[$currentStepIndex]->fields() as $field) {
                $currentStepFields[] = $field->column();
            }
        }

        // 只验证当前步骤的字段
        $currentStepInput = [];
        foreach ($input as $key => $value) {
            if (in_array($key, $currentStepFields)) {
                $currentStepInput[$key] = $value;
            }
        }

        // 处理验证错误
        if ($validationMessages = $this->form->validationMessages($currentStepInput)) {
            return $this->form->validationErrorsResponse($validationMessages);
        }

        // 暂存输入数据
        $this->stash($input);

        return JsonResponse::make()
            ->success('Success')
            ->send();
    }

    /**
     * 响应完成页面
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response|void
     */
    protected function responseCompletionPage()
    {
        return response($this->done()->render());
    }

    /**
     * 记录字段所在步骤到暂存
     *
     * @param string|\Dcat\Admin\Form\Field $field
     *
     * @return void
     */
    public function stashIndexByField($field)
    {
        if (! $this->options['remember']) {
            return;
        }

        $data = $this->fetchStash();

        $data[self::CURRENT_VALIDATION_STEP] = ($this->fieldIndex($field) ?: 0) - 1;

        unset($data[self::ALL_STEPS]);

        $this->stash($data);
    }
}
