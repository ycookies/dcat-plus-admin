<?php

namespace Dcat\Admin\Http\Controllers;

use Dcat\Admin\Models\SkuAttribute;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Admin;

class SkuAttributeController extends AdminController
{
    /**
     * page index
     */
    public function index(Content $content)
    {
        return $content
            ->header(admin_trans('admin.list'))
            ->description(admin_trans('admin.all'))
            ->breadcrumb(['text'=>admin_trans('admin.list'),'url'=>''])
            ->body($this->grid());
    }

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SkuAttribute(), function (Grid $grid) {
            $grid->model()->orderBy('id','DESC');
            $grid->number();
            $grid->column('app_name');
            $grid->column('admin_id');
            $grid->column('attr_name');
            $grid->column('attr_type')->select(SkuAttribute::$attrType);
            $grid->column('attr_value')->explode()->label();
            $grid->column('sort')->editable();
            $grid->column('created_at');
            $grid->showQuickEditButton();
            $grid->enableDialogCreate();
            $grid->setActionClass(Grid\Displayers\Actions::class); // 行操作按钮显示方式 图标方式
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                // $actions->disableDelete(); //  禁用删除
                $actions->disableEdit();   //  禁用修改
                // $actions->disableQuickEdit(); //禁用快速修改(弹窗形式)
                // $actions->disableView(); //  禁用查看
            });
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new SkuAttribute(), function (Show $show) {
            $show->field('id');
            $show->field('app_name');
            $show->field('admin_id');
            $show->field('attr_name');
            $show->field('attr_type');
            $show->field('attr_value');
            $show->field('sort');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new SkuAttribute(), function (Form $form) {
            $form->display('id');
            //$form->text('app_name');
            $form->hidden('admin_id')->value(Admin::user()->id);
            $form->text('attr_name', admin_trans('admin.attr_name'))->required();
            $form->radio('attr_type', admin_trans('admin.attr_type'))->options(SkuAttribute::$attrType)->required();
            $form->list('attr_value', admin_trans('admin.attr_value'));
            $form->number('sort', admin_trans('admin.order'))->default(0)->min(0)->max(100);
            $form->saved(function (Form $form, $result) {
                if ($form->isCreating()) {
                    // 自增ID
                    $newId = $result;
                    // 也可以这样获取自增ID
                    $newId = $form->getKey();
                    if (! $newId) {
                        return $form->response()->error(admin_trans('admin.save_failed'));
                    }
                }
                return $form->response()->success(admin_trans('admin.save_succeeded'));
            });
        });
    }
}
