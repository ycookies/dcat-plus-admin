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

        // 记录构建阶段用户是否显式禁用了创建按钮（下方简化模式会再次覆盖，
        // 以此区分"用户意图"与"框架默认"，决定是否自动重新启用）
        $createExplicitlyDisabled = $grid->option('create_button') === false;

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
            $grid->actions(function (Grid\Displayers\Actions $actions) use ($grid) {
                $actions->quickEdit();
                // 弹窗查看尺寸可经 $grid->dialogViewSize('xl'|['w','h']) 配置
                $actions->dialogView($grid->option('dialog_view_size'));
                $actions->disableEdit();
                $actions->disableView();
            });
        }

        // 异步表格默认启用「弹窗创建」：无需用户手写
        // showCreateButton() + enableDialogCreate()（配合行操作的
        // setResource 显式资源路径即可用）；用户已显式禁用创建按钮时尊重配置
        if (! $createExplicitlyDisabled) {
            $grid->showCreateButton();
            $grid->enableDialogCreate();
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
