<?php

namespace Dcat\Admin\Form\Extend\Diyforms\Show;

use Dcat\Admin\Show\AbstractField;

class DiyForm extends AbstractField
{
    // 这个属性设置为false则不会转义HTML代码
    public $escape = false;

    protected $view = 'admin::form.extend.diy-form.show';

    /**
     * @param null $preview_html 附加的预览html代码
     * @param null $show_mask 是否添加遮罩
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function render($preview_html = '', $show_mask = true)
    {
        admin_js('@admin/dcat/js/vue.js');

        $value = $this->value;

        return view($this->view, compact('preview_html', 'value', 'show_mask'));
    }
}
