<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Admin;
use Dcat\Admin\Models\SkuAttribute;
use Dcat\Admin\Form\Field;
use Dcat\Admin\Widgets\Modal;
use Dcat\Admin\Http\Renderable\SkuAttributesTable;
use Dcat\Admin\Http\Forms\AddSkuAttrFrom;
use Dcat\Admin\Support\InternalRouteToken;
class SkuField extends Field
{
    protected $view = 'admin::form.extend.sku.sku';
    public static $js = [
        '@moment',
        '@goods-sku',
    ];

    public static $css = [
        '@goods-sku'
    ];

    public function render()
    {
        $token = app(InternalRouteToken::class);
        $uploadUrl = admin_setting('sku_plus_img_upload_url')
            ?: $token->append(admin_route('dcat-sys.sku.upload'), 'sku.write', ['directory' => 'sku']);
        $deleteUrl = admin_setting('sku_plus_img_remove_url')
            ?: $token->append(admin_route('dcat-sys.sku.remove'), 'sku.write', ['directory' => 'sku']);
        $skuAttributes = SkuAttribute::orderBy('sort', 'desc')->get();
        $manageSkuAttrModal = $this->manageSkuAttrModal();
        $addSkuAttrFrom = $this->addSkuAttrModal();
        $this->script = <<< EOF
        window.DemoSku = new JadeKunSKU('{$this->getElementClassSelector()}');
EOF;
        $this->addVariables(compact('skuAttributes','manageSkuAttrModal','addSkuAttrFrom', 'uploadUrl', 'deleteUrl'));

        return parent::render();
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
