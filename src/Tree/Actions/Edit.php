<?php

namespace Dcatplus\Admin\Tree\Actions;

use Dcatplus\Admin\Tree\RowAction;

class Edit extends RowAction
{
    public function html()
    {
        return <<<HTML
<a href="{$this->resource()}/{$this->getKey()}/edit"><i class="feather icon-edit-1"></i>&nbsp;</a>
HTML;
    }
}
