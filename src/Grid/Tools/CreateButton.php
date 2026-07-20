<?php

namespace Dcat\Admin\Grid\Tools;

use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Support\Authorization\GridActionPermission;
use Illuminate\Contracts\Support\Renderable;

class CreateButton implements Renderable
{
    /**
     * @var Grid
     */
    protected $grid;

    protected $mode;

    public function __construct(Grid $grid)
    {
        $this->grid = $grid;
        $this->mode = $grid->option('create_mode');
    }

    protected function renderDialogCreateButton()
    {
        if ($this->mode !== Grid::CREATE_MODE_DIALOG) {
            return;
        }

        $new = trans('admin.new');
        $url = $this->grid->getCreateUrl();
        $class = $this->grid->makeName('dialog-create');

        [$width, $height] = $this->grid->option('dialog_form_area');

        Form::dialog($new)
            ->click(".{$class}")
            ->success('Dcat.reload()')
            ->dimensions($width, $height);

        return "<button data-url='$url' class='btn btn-primary {$class}'><i class='feather icon-plus'></i><span class='d-none d-sm-inline'>&nbsp; $new</span></button>";
    }

    protected function renderCreateButton()
    {
        if ($this->mode && $this->mode !== Grid::CREATE_MODE_DEFAULT) {
            return;
        }

        $new = trans('admin.new');
        $url = $this->grid->getCreateUrl();

        return "<a href='{$url}' class='btn btn-primary'>
    <i class='feather icon-plus'></i><span class='d-none d-sm-inline'>&nbsp;&nbsp;{$new}</span>
</a>";
    }

    public function render()
    {
        if (! GridActionPermission::allows($this->grid, 'create')) {
            if (GridActionPermission::mode() === GridActionPermission::MODE_HIDE) {
                return '';
            }

            $new = trans('admin.new');
            $content = "<i class='feather icon-plus'></i><span class='d-none d-sm-inline'>&nbsp;&nbsp;{$new}</span>";
            $tag = $this->mode === Grid::CREATE_MODE_DIALOG ? 'button' : 'a';

            return $this->grid->tools()->format(
                GridActionPermission::deniedControl($content, 'create', 'btn btn-primary', $tag)
            );
        }

        return $this->grid->tools()->format(
            "{$this->renderCreateButton()}{$this->renderDialogCreateButton()}"
        );
    }
}
