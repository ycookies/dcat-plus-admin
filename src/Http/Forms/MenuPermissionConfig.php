<?php

namespace Dcat\Admin\Http\Forms;

use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class MenuPermissionConfig extends Form implements LazyRenderable
{
    use LazyWidget;

    public function handle(array $input)
    {
        return $this->success('保存成功');
    }

    public function default()
    {
        return [
            // 展示上个页面传递过来的值
            'name' => $this->payload['name'] ?? '',
        ];
    }

    public function form()
    {
        $this->text('name', trans('admin.name'))->required()->help('用户昵称');
        
    }
}
