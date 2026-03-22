<?php

namespace Dcat\Admin\Http\Renderable;

use Dcat\Admin\Grid;
use Dcat\Admin\Grid\LazyRenderable;
use Dcat\Admin\Models\Administrator;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\SkuAttribute;
use Dcat\Admin\Form\Extend\Sku\DelSkuAttrAction;

class SkuAttributesTable extends LazyRenderable
{
    use LazyWidget;



    public function grid(): Grid
    {
        return Grid::make(new SkuAttribute(), function (Grid $grid) {
            $grid->setResource('/sku-action');
            $grid->model()->orderByDesc('id');
            $grid->number();
            $grid->column('attr_name', admin_trans('admin.attr_name'));
            $grid->column('attr_type', admin_trans('admin.attr_type'))
                ->using(SkuAttribute::$attrType);
            $grid->column('sort', admin_trans('admin.order'));
            $grid->column('attr_value', admin_trans('admin.attr_value'))->explode()->label();
            $grid->created_at->dateTime('Y-m-d H:s');
            $grid->disableViewButton();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
            $title = '<a  href="javascript:void(0);" data-url="'.admin_url('sku-action/create').'" class="btn btn-success btn-sm add-sku">添加规格</a>';
            \Dcat\Admin\Form::dialog('添加规格')
                ->click('.add-sku')
                ->success('Dcat.reload()')
                ->dimensions('800px', '600px');
            $grid->showQuickEditButton();
            $grid->tools($title);
            $grid->setActionClass(Grid\Displayers\Actions::class);
            $grid->actions(function ($actions) {
                // 去掉删除
                $actions->append(new DelSkuAttrAction());
                $actions->disableView();
                $actions->disableDelete();
                // 去掉编辑
                $actions->disableEdit();
            });
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id')->width(3);
                $filter->like('attr_name', admin_trans('admin.attr_name'))->width(3);
                $filter->equal('attr_type', admin_trans('admin.attr_type'))->width(3)->select($this->attrType);
            });
        });
    }
}
