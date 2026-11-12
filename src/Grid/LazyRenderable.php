<?php

namespace Dcat\Admin\Grid;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Support\LazyRenderable as Renderable;

abstract class LazyRenderable extends Renderable
{
    const SIMPLE_NAME = '_simple_';

    const ROW_SELECTOR_COLUMN_NAME = '_row_columns_';

    /**
     * 是否启用简化模式.
     *
     * @var bool
     */
    protected $simple = false;

    /**
     * 创建表格.
     *
     * @return Grid
     */
    abstract public function grid(): Grid;

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $this->addStyle();

        $class = $this->allowSimpleMode() ? 'simple-grid' : null;

        return <<<HTML
<div class="$class">{$this->prepare($this->grid())->render()}</div>
HTML;
    }

    protected function addStyle()
    {
        Admin::style('.select2-container--open{z-index:29891015}');
    }

    /**
     * 是否启用简化模式.
     *
     * @param  bool  $value
     * @return $this
     */
    public function simple(bool $value = true)
    {
        return $this->payload([static::SIMPLE_NAME => $value]);
    }

    /**
     * @param  Grid  $grid
     * @return Grid
     */
    protected function prepare(Grid $grid)
    {
        if (! $grid->getName()) {
            $grid->setName($this->getDefaultName());
        }

        if ($this->allowSimpleMode()) {
            $grid->disableCreateButton();
            $grid->disablePerPages();
            $grid->disableBatchDelete();
            $grid->disableRefreshButton();

            $grid->filter()
                ->panel()
                ->view('admin::filter.simple-container');

            $grid->rowSelector()->click();
        }

        // 异步表格（弹窗/展开行）行操作适配：整页编辑与查看会离开当前上下文
        // （弹窗场景下不可回退），自动替换为快捷编辑（弹窗表单）与弹窗查看。
        // 用户已自定义 actions() 时尊重其配置，不自动替换。
        if (! $grid->hasCustomActions()) {
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                $actions->quickEdit();
                $actions->dialogView();
                $actions->disableEdit();
                $actions->disableView();
            });
        }

        if (! empty($this->payload[static::ROW_SELECTOR_COLUMN_NAME])) {
            [$key, $visibleColumn] = $this->payload[static::ROW_SELECTOR_COLUMN_NAME];

            $key && $grid->rowSelector()->idColumn($key);

            $visibleColumn && $grid->rowSelector()->titleColumn($visibleColumn);
        }

        return $grid->async(false);
    }

    /**
     * 判断是否启用简化模式.
     *
     * @return bool
     */
    public function allowSimpleMode()
    {
        return $this->simple || $this->_simple_;
    }

    /**
     * 获取默认名称.
     *
     * @return string
     */
    protected function getDefaultName()
    {
        return strtolower(str_replace('\\', '-', static::class));
    }
}
