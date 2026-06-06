<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Models\Notification;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;

class NotificationController extends AdminController
{
    protected $title = '通知管理';
    protected function grid()
    {
        return Grid::make(new Notification(), function (Grid $grid) {
            $grid->model()->orderByDesc('id');
            $grid->column('id', 'ID')->sortable();
            $grid->column('title', '标题');
            $grid->column('type', '类型')->using([
                'info' => '信息', 'warning' => '警告', 'success' => '成功', 'danger' => '危险',
            ])->label([
                'info' => 'info', 'warning' => 'warning', 'success' => 'success', 'danger' => 'danger',
            ]);
            $grid->column('is_active', '启用')->switch();
            $grid->column('created_at', '创建时间')->date('Y-m-d H:i');

            $grid->filter(function ($filter) {
                $filter->panel();
                $filter->like('title', '标题')->width(3);
                $filter->equal('type', '类型')->select([
                    'info' => '信息', 'warning' => '警告', 'success' => '成功', 'danger' => '危险',
                ])->width(3);
            });

            $grid->quickSearch('title');
            $grid->enableDialogCreate();
            $grid->showColumnSelector();
        });
    }

    protected function form()
    {
        return Form::make(new Notification(), function (Form $form) {
            $form->text('title', '标题')->required();
            $form->editor('content', '内容');
            $form->select('type', '类型')->options([
                'info' => '信息', 'warning' => '警告', 'success' => '成功', 'danger' => '危险',
            ])->default('info');
            $form->switch('is_active', '启用')->default(1);
        });
    }

    protected function detail($id)
    {
        return Show::make($id, new Notification(), function (Show $show) {
            $show->field('id', 'ID');
            $show->field('title', '标题');
            $show->field('content', '内容');
            $show->field('type', '类型');
            $show->field('is_active', '启用');
            $show->field('created_at', '创建时间');
            $show->field('updated_at', '更新时间');
        });
    }
}