<?php

namespace Dcatplus\Admin\Http\Actions\Extensions;

use Dcatplus\Admin\Grid\Tools\AbstractTool;
use Dcatplus\Admin\Http\Forms\InstallFromLocal as InstallFromLocalForm;
use Dcatplus\Admin\Widgets\Modal;

class InstallFromLocal extends AbstractTool
{
    protected $style = 'btn btn-primary';

    public function html()
    {
        return Modal::make()
            ->lg()
            ->title($title = trans('admin.install_from_local'))
            ->body(InstallFromLocalForm::make())
            ->button("<button class='btn btn-primary'><i class=\"feather icon-folder\"></i> &nbsp;{$title}</button> &nbsp;");
    }
}
