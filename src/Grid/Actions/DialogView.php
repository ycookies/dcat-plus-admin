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
 *
 * 支持自定义弹层尺寸：
 *  - 预设：'sm'（600px×60%） / 'lg'（800px×80%） / 'xl'（1140px×90%）
 *  - 自定义：['800px', '600px'] 数组，或 '80%' 宽度字符串（高度取 80%）
 *  - 不传：使用 config admin.dialog_form_area 默认尺寸
 */
class DialogView extends RowAction
{
    /**
     * 弹层尺寸（预设名/数组/宽度字符串）.
     *
     * @var mixed
     */
    protected $size;

    /**
     * @param  mixed  $title
     * @param  mixed  $size
     */
    public function __construct($title = null, $size = null)
    {
        parent::__construct($title);

        $this->size = $size;
    }

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
        [$width, $height] = $this->size
            ? $this->parseSize($this->size)
            : $this->parent->option('dialog_form_area');

        Form::dialog(trans('admin.show'))
            ->click(".{$this->getElementClass()}")
            ->dimensions($width, $height)
            ->viewOnly();

        $this->setHtmlAttribute([
            'data-url' => $this->parent->urlWithConstraints("{$this->resource()}/{$this->getKey()}"),
        ]);

        return parent::render();
    }

    /**
     * 尺寸解析：预设名 → 数组；数组透传；宽度字符串补默认高度.
     *
     * @param  mixed  $size
     * @return array [width, height]
     */
    protected function parseSize($size): array
    {
        $presets = [
            'sm' => ['600px', '60%'],
            'lg' => ['800px', '80%'],
            'xl' => ['1140px', '90%'],
        ];

        if (isset($presets[$size])) {
            return $presets[$size];
        }

        if (is_array($size)) {
            return [$size[0] ?? '700px', $size[1] ?? '670px'];
        }

        return [(string) $size, '80%'];
    }
}
