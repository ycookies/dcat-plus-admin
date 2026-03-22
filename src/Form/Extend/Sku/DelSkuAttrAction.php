<?php
namespace Dcat\Admin\Form\Extend\Sku;

use Dcat\Admin\Admin;
use Dcat\Admin\Actions\Action;
use Dcat\Admin\Models\SkuAttribute;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Http\Request;

class DelSkuAttrAction extends RowAction
{

    protected $title = '删除';

    public function handle(Request $request)
    {
        // 获取当前行ID
        $id = $this->getKey();
        SkuAttribute::where(['id'=> $id])->delete();
        return $this->response()->success('删除成功')->refresh();
    }
    
    /**
     * 确认弹窗信息，如不需要可以删除此方法
     *
     * @return string|void
     */
    public function confirm()
    {
        return ['确认要删除吗?', '删除后不可恢复'];
    }
}