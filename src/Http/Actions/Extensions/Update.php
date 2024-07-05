<?php

namespace Dcatplus\Admin\Http\Actions\Extensions;

use Dcatplus\Admin\Admin;
use Dcatplus\Admin\Grid\RowAction;

class Update extends RowAction
{
    public function title()
    {
        $replace = ['version' => $this->row->extension->getLocalLatestVersion()];

        return sprintf('<b>%s</b>', trans('admin.upgrade_to_version', $replace));
    }

    public function handle()
    {
        $manager = Admin::extension()
            ->updateManager()
            ->update($this->getKey());

        return $this
            ->response()
            ->success(implode('<br>', $manager->notes))
            ->refresh();
    }
}
