<?php

namespace Dcat\Admin\Form\Steps;

use Dcat\Admin\Admin;
use Dcat\Admin\Form as ParentForm;
use Dcat\Admin\Widgets\Form as WidgetForm;

class Form extends WidgetForm
{
    /**
     * @var string
     */
    protected $view = 'admin::form.steps.form';

    /**
     * @var array
     */
    protected $buttons = [];

    /**
     * @var ParentForm
     */
    protected $form;

    /**
     * @var Builder
     */
    protected $parent;

    /**
     * @var int
     */
    protected $index;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $description;

    /**
     * FormStep constructor.
     *
     * @param ParentForm $form
     * @param string     $title
     * @param int        $index
     */
    public function __construct(ParentForm $form, ?string $title = null, int $index = 0)
    {
        $this->setForm($form);
        $this->initFields();

        $this->setTitle($title);
        $this->setIndex($index);
    }

    /**
     * 设置父表单
     *
     * @param ParentForm $form
     *
     * @return $this
     */
    protected function setForm(?ParentForm $form)
    {
        $this->form = $form;
        $this->parent = $form->multipleSteps();

        $this->prepareFileFields();

        return $this;
    }

    /**
     * 获取模型数据（覆盖父类方法，返回父表单的模型）
     *
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Support\Fluent
     */
    public function model()
    {
        return $this->form->model();
    }

    /**
     * 设置步骤标题
     *
     * @param string|\Closure $title
     *
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = value($title);

        return $this;
    }

    /**
     * 获取步骤标题
     *
     * @return string
     */
    public function title()
    {
        return $this->title;
    }

    /**
     * 设置步骤描述
     *
     * @param string|\Closure $content
     *
     * @return $this
     */
    public function setDescription($content)
    {
        $this->description = value($content);

        return $this;
    }

    /**
     * 获取步骤描述
     *
     * @return string
     */
    public function description()
    {
        return $this->description;
    }

    /**
     * 设置步骤索引
     *
     * @param int $content
     *
     * @return $this
     */
    public function setIndex(?int $index = null)
    {
        $this->index = $index;

        return $this;
    }

    /**
     * 获取步骤索引
     *
     * @return int
     */
    public function index()
    {
        return $this->index;
    }

    /**
     * 获取元素ID（用于步骤导航）
     *
     * @return string
     */
    public function getElementId()
    {
        return 'step-'.$this->index.'-'.md5($this->title);
    }

    /**
     * 打开标签
     *
     * @return string
     */
    protected function open()
    {
        if ($this->index > 0) {
            $this->setHtmlAttribute('style', 'display:none');
        }

        $this->setHtmlAttribute('data-toggle', 'validator');
        $this->setHtmlAttribute('role', 'form');

        return <<<HTML
<div {$this->formatHtmlAttributes()}>
HTML;
    }

    /**
     * 关闭标签
     *
     * @return string
     */
    protected function close()
    {
        return '</div>';
    }

    /**
     * 准备文件上传字段
     *
     * @return void
     */
    protected function prepareFileFields()
    {
        $this->form->uploaded(function (ParentForm $form, ParentForm\Field $field, $file, $response) {
            if (($value = $response->toArray()) && ! empty($value['id'])) {
                $form->multipleSteps()->stash(
                    [$field->column() => $value['id']],
                    true
                );
            }
        });
    }

    /**
     * 填充暂存数据（步骤间跳转时使用）
     *
     * @param array|\Closure|null $data
     *
     * @return $this
     */
    public function fillStash($data = null)
    {
        $stashed = $this->parent->fetchStash();

        if (! empty($stashed)) {
            foreach ($this->fields() as $field) {
                $column = $field->column();
                if (!is_string($column)) {
                    continue;
                }
                if (isset($stashed[$column])) {
                    $this->fillFieldValue($field, $stashed[$column]);
                }
            }
        }

        return $this;
    }

    /**
     * 填充字段值
     *
     * @param \Dcat\Admin\Form\Field $field
     * @param mixed                  $value
     */
    protected function fillFieldValue(\Dcat\Admin\Form\Field $field, $value)
    {
        $field->value($value);
    }

    /**
     * 渲染步骤表单
     *
     * @return string
     */
    public function render()
    {
        // 填充数据（编辑模式或步骤间跳转）
        $this->fillStash();

        //Admin::requireAssets('@admin::form.steps.steps');

        return parent::render(); // TODO: Change the autogenerated stub
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

        $this->parent->leaving(
            <<<JS
if (args.index == {$this->index}) {
    {$script}
}
JS
        );

        return $this;
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

        $this->parent->shown(
            <<<JS
if (args.index == {$this->index}) {
    {$script}
}
JS
        );

        return $this;
    }
}
