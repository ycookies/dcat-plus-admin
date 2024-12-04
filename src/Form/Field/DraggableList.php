<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Admin;
use Dcat\Admin\Models\SkuAttribute;
use Dcat\Admin\Form\Field;
use Dcat\Admin\Widgets\Modal;
use Dcat\Admin\Http\Renderable\SkuAttributesTable;
use Dcat\Admin\Http\Forms\AddSkuAttrFrom;
use Illuminate\Support\Str;

class DraggableList extends Field
{
    protected $view = 'admin::form.draggable-list';
    public static $js = [
        '@sortable'
    ];

    public static $css = [
        '@jquery.nestable',
    ];

    public function render()
    {
/*        $uploadUrl = admin_setting('sku_plus_img_upload_url') ?: '/admin/sku-image-upload';
        $deleteUrl = admin_setting('sku_plus_img_remove_url') ?: '/admin/sku-image-remove';
        $skuAttributes = SkuAttribute::orderBy('sort', 'desc')->get();
        $manageSkuAttrModal = $this->manageSkuAttrModal();
        $addSkuAttrFrom = $this->addSkuAttrModal();
        $this->script = <<< EOF
        window.DemoSku = new JadeKunSKU('{$this->getElementClassSelector()}');
EOF;
        $this->addVariables(compact('skuAttributes','manageSkuAttrModal','addSkuAttrFrom', 'uploadUrl', 'deleteUrl'));*/
        $id = Str::random(8);
        $createForm = $this->createForm();
        //$this->addVariables(['id'=> $id]);
        $this->addVariables(compact('id','createForm'));

        return parent::render();
    }
    public function createForm(){
        $title = '<button type="button" class="btn btn-info btn-sm"><i class="fa fa-plus"></i> 新增</button>';
        $modal = Modal::make();
        $modal->staticBackdrop();
        $modal->title('新增');
        $modal->xl();
        $modal->body(SkuAttributesTable::make());
        $modal->button($title);
        return $modal->render();
    }

    public function manageSkuAttrModal(){
        $title = '<button type="button" class="btn btn-info btn-sm">管理规格</button>';
        $modal = Modal::make();
        $modal->staticBackdrop();
        $modal->title('管理规格');
        $modal->xl();
        $modal->body(SkuAttributesTable::make());
        $modal->button($title);
        return $modal->render();
    }

    public function addSkuAttrModal(){
        $title = '<button type="button" class="btn btn-success btn-sm">添加规格</button>';
        $modal = Modal::make();
        $modal->staticBackdrop();
        $modal->title('添加规格');
        $modal->lg();
        $modal->body(AddSkuAttrFrom::make());
        $modal->button($title);
        return $modal->render();
    }

    /**
     * 添加扩展列.
     *
     * @param  array  $column
     * @return $this
     */
    public function addColumn(array $column = []): self
    {
        $this->addVariables(['extra_column' => json_encode($column)]);

        return $this;
    }
}
