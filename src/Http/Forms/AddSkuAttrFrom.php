<?php

namespace Dcat\Admin\Http\Forms;

use Dcat\Admin\Admin;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Exception\RuntimeException;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;
use Dcat\Admin\Models\SkuAttribute;

class AddSkuAttrFrom extends Form implements LazyRenderable
{
    use LazyWidget;

    /**
     * @desc 处理 数据导入
     * @param array $input
     * @return \Dcat\Admin\Http\JsonResponse
     */
    public function handle(array $input)
    {
        $data = [
            'app_name' => 'admin',
            'admin_id' => Admin::user()->id,
            'attr_name' => $input['attr_name'],
            'attr_type' => $input['attr_type'],
            'attr_value' => $input['attr_value'],
            'sort' => $input['sort'],
        ];
        SkuAttribute::firstOrCreate(['attr_name' => $input['attr_name']],$data);
        return $this->response()->success(trans('admin.save_succeeded'))->refresh();
    }

    public function form()
    {
        $this->text('attr_name', trans('admin.attr_name'))->required();
        $this->radio('attr_type', trans('admin.attr_type'))->options(SkuAttribute::$attrType)->required();
        $this->list('attr_value', trans('admin.attr_value'));
        $this->number('sort', trans('admin.order'))->default(0)->min(0)->max(100);
    }
    
}
