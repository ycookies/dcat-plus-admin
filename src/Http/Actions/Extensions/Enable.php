<?php

namespace Dcatplus\Admin\Http\Actions\Extensions;

use Dcatplus\Admin\Admin;
use Dcatplus\Admin\Grid\RowAction;

class Enable extends RowAction
{
    public function title()
    {
        return sprintf('<b>%s</b>', trans('admin.enable'));
    }

    public function handle()
    {
        Admin::extension()->enable($this->getKey());

        return $this
            ->response()
            ->success(trans('admin.update_succeeded'))
            ->refresh();
    }
}
