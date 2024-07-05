<?php

namespace Dcatplus\Admin\Http\Actions\Extensions;

use Dcatplus\Admin\Admin;
use Dcatplus\Admin\Grid\RowAction;

class Uninstall extends RowAction
{
    public function title()
    {
        $label = trans('admin.uninstall');

        return "<span class='text-danger'>{$label}</span>";
    }

    public function confirm()
    {
        return [trans('admin.confirm_uninstall'), $this->getKey()];
    }

    public function handle()
    {
        $manager = Admin::extension()
            ->updateManager()
            ->rollback($this->getKey());

        Admin::extension()->get($this->getKey())->uninstall();

        return $this
            ->response()
            ->success(implode('<br>', $manager->notes))
            ->refresh();
    }
}
