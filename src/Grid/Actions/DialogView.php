<?php

namespace Dcat\Admin\Grid\Actions;

use Dcat\Admin\Form;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\DialogForm;

/**
 * 弹窗查看详情（异步表格自动替换整页查看用）.
 *
 * 以 layer 弹层打开详情页（DialogForm 加 viewOnly：无提交/重置按钮，
 * 关闭即退出）。详情页经 DialogForm::is() 最小化渲染，无侧栏导航。
 */
class DialogView extends RowAction
{
    /**
     * @return array|null|string
     */
    public function title()
    {
        if ($this->title) {
            return $this->title;
        }

        return '<i class="feather icon-eye"></i> '.__('admin.show').' &nbsp;&nbsp;';
    }

    public function render()
    {
        [$width, $height] = $this->parent->option('dialog_form_area');

        Form::dialog(trans('admin.show'))
            ->click(".{$this->getElementClass()}")
            ->dimensions($width, $height)
            ->viewOnly();

        $this->setHtmlAttribute([
            'data-url' => $this->parent->urlWithConstraints("{$this->resource()}/{$this->getKey()}"),
        ]);

        return parent::render();
    }
}
