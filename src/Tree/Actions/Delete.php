<?php

namespace Dcat\Admin\Tree\Actions;

use Dcat\Admin\Tree\RowAction;

class Delete extends RowAction
{
    public function html()
    {
        $url = request()->fullUrl();
        $tips_title = trans('admin.delete');
        return <<<HTML
<a href="javascript:void(0);" 
    data-message="ID - {$this->getKey()}" 
    data-redirect="{$url}"
    data-url="{$this->resource()}/{$this->getKey()}" data-action="delete"><i class="feather icon-trash tips" data-title="{$tips_title}"></i>&nbsp;</a>
HTML;
    }
}
