<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Models\HelpCategory;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

class HelpCategoryController extends AdminController
{
    protected $title = '帮助分类';

    protected function grid()
    {
        return Grid::make(new HelpCategory(), function (Grid $grid) {
            $grid->model()->orderBy('sort')->orderByDesc('id');
            $grid->column('id', 'ID')->sortable();
            $grid->column('name', '分类名称');
            $grid->column('icon', '图标');
            $grid->column('sort', '排序')->editable();
            $grid->column('is_active', '启用')->switch();
            $grid->column('created_at', '创建时间');

            $grid->quickSearch('name');
            $grid->enableDialogCreate();
        });
    }

    protected function form()
    {
        return Form::make(new HelpCategory(), function (Form $form) {
            $form->text('name', '分类名称')->required();
            $form->icon('icon', '图标')->default('feather icon-help-circle');
            $form->number('sort', '排序')->default(0);
            $form->switch('is_active', '启用')->default(1);
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new HelpCategory(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('name', '分类名称');
            $show->field('icon', '图标');
            $show->field('sort', '排序');
            $show->field('is_active', '启用');
            $show->field('created_at', '创建时间');
        });
    }
}