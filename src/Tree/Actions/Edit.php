<?php

namespace Dcat\Admin\Tree\Actions;

use Dcat\Admin\Tree\RowAction;

class Edit extends RowAction
{
    public function html()
    {
        $tips_title = trans('admin.edit');
        return <<<HTML
<a href="{$this->resource()}/{$this->getKey()}/edit"><i class="feather icon-edit-1 tips" data-title="{$tips_title}"></i>&nbsp;</a>
HTML;
    }
}
