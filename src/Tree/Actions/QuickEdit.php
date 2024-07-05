<?php

namespace Dcatplus\Admin\Tree\Actions;

use Dcatplus\Admin\Form;
use Dcatplus\Admin\Tree\RowAction;

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

        return <<<HTML
<a href="javascript:void(0);" data-url="{$this->resource()}/{$this->getKey()}/edit" class="tree-quick-edit"><i class="feather icon-edit"></i>&nbsp;</a>
HTML;
    }
}
