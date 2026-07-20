<?php

namespace Dcat\Admin\Grid\Tools;

use Dcat\Admin\Grid\BatchAction;
use Dcat\Admin\Support\Authorization\GridActionPermission;

class PermissionDeniedBatchDelete extends BatchAction
{
    public function __construct($title)
    {
        $this->title = $title;
    }

    public function render()
    {
        $content = '<i class="feather icon-trash"></i> '.$this->title;

        return GridActionPermission::deniedControl($content, 'batch_delete');
    }
}
