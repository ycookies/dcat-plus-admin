<?php

namespace Dcatplus\Admin\Grid\Tools;

use Dcatplus\Admin\Grid\BatchAction;

class BatchDelete extends BatchAction
{
    public function __construct($title)
    {
        $this->title = $title;
    }

    public function render()
    {
        $redirect = request()->fullUrl();

        return <<<HTML
<a  data-name="{$this->parent->getName()}" 
    data-action="batch-delete" 
    data-redirect="{$redirect}"
    data-url="{$this->resource()}"><i class="feather icon-trash"></i> {$this->title}</a>
HTML;
    }
}
