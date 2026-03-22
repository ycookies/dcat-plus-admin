<?php

namespace Dcat\Admin\Tree\Actions;

use Dcat\Admin\Form;
use Dcat\Admin\Tree\RowAction;

class QuickEdit extends RowAction
{
    protected $dialogFormDimensions = ['700px', '670px'];

    public function html()
    {
        [$width, $height] = $this->dialogFormDimensions;

        Form::dialog(trans('admin.edit'))
            ->click('.tree-quick-edit')
            ->success('Dcat.reload()')
            ->dimensions($width, $height);
        $tips_title = trans('admin.quick_edit');
        return <<<HTML
<a href="javascript:void(0);" data-url="{$this->resource()}/{$this->getKey()}/edit" class="tree-quick-edit"><i class="feather icon-edit tips" data-title="{$tips_title}"></i>&nbsp;</a>
HTML;
    }
}
