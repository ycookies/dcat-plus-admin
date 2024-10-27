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
            $grid->model()->orderByDesc('id');
            $grid->id->sortable();
            $grid->column('attr_name', '属性名称');
            $grid->column('attr_type', '属性类型')
                ->using(SkuAttribute::$attrType)
                ->label([
                    'checkbox' => 'info',
                    'radio' => 'primary'
                ]);
            $grid->column('sort', '排序')->help('排序越大越靠前');
            $grid->column('attr_value', '属性值')->explode()->label();
            $grid->created_at;
            $grid->disableViewButton();
            $grid->disableRowSelector();
            $grid->disableCreateButton();
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
                $filter->like('attr_name', '属性名称')->width(3);
                $filter->equal('attr_type', '属性类型')->width(3)->select($this->attrType);
            });
        });
    }
}
