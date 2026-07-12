<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Models\Help;
use Dcat\Admin\Models\HelpCategory;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

class HelpController extends AdminController
{
    protected $title = '帮助内容';

    protected function grid()
    {
        return Grid::make(Help::with('category'), function (Grid $grid) {
            $grid->model()->orderBy('sort')->orderByDesc('id');
            $grid->column('id', 'ID')->sortable();
            $grid->column('category.name', '分类');
            $grid->column('title', '标题');
            $grid->column('content', '内容')->limit(50);
            $grid->column('link', '链接')->link();
            $grid->column('sort', '排序')->editable();
            $grid->column('is_active', '启用')->switch();
            $grid->column('created_at', '创建时间');

            $grid->filter(function ($filter) {
                $filter->like('title', '标题');
                $filter->equal('category_id', '分类')->select(
                    HelpCategory::where('is_active', true)->pluck('name', 'id')
                );
            });

            $grid->quickSearch('title');
            //$grid->enableDialogCreate();
        });
    }
    protected function detail($id)
    {
        return Show::make($id, new Help(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('category.name', '分类');
            $show->field('title', '标题');
            $show->field('content', '内容')->html();
            $show->field('link', '链接');
            $show->field('link_target', '链接打开方式');
            $show->field('sort', '排序');
            $show->field('is_active', '启用');
            $show->field('created_at', '创建时间');
        });
    }

    protected function form()
    {
        return Form::make(new Help(), function (Form $form) {
            $form->select('category_id', '分类')
                ->options(HelpCategory::where('is_active', true)->pluck('name', 'id'))
                ->required();
            $form->text('title', '标题')->required();
            $form->editor('content', '内容')->options([
                                'toolbarShows' => [
                                    'ai' => false,
                                ],
                                'shortcutMenuShows' => [
                                    'ai' => false,
                                ],
                            ]);
            $form->url('link', '链接');
            $form->select('link_target', '链接打开方式')
                ->options(['_self' => '当前窗口', '_blank' => '新窗口'])
                ->default('_self');
            $form->number('sort', '排序')->default(0);
            $form->switch('is_active', '启用')->default(1);
        });
    }
}