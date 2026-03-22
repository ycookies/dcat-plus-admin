<?php

namespace Dcat\Admin\Widgets;
use Dcat\Admin\Admin;

class PricingCard extends Widget {
    /**
     * @var string
     */
    protected $view = 'admin::widgets.pricing-card';

    /**
     * @var array
     */
    protected $items = [];

    protected $col = 4;

    protected $center = false;

    protected $clickFc = '';

    protected $modals;
    /**
     * Collapse constructor.
     */
    public function __construct() {
        $this->id('pricing-card-' . uniqid());
        $this->class('box-group');
        $this->style('margin-bottom: 20px');
    }

    public function columns($columns = 4) {
        $this->col = $columns;
    }
    public function isCenter(){

        $this->center = true;
    }

    /**
     * Add item.
     *
     * @param string $title
     * @param string $content
     *
     * @return $this
     */
    public function add($title, $money) {
        $this->items[] = [
            'title' => $title,
            'money' => $money,
            'btn' => '订购',
        ];
        return $this;
    }

    public function head(string $strings){
        $this->items[count($this->items)-1]['head'] = $strings;
        return $this;
    }

    public function footer(string $strings){
        $this->items[count($this->items)-1]['footer'] = $strings;
        return $this;
    }

    public function li(array $lists){
        $this->items[count($this->items)-1]['li'] = $lists;
        return $this;
    }

    public function active($bool = true){
        $this->items[count($this->items)-1]['active'] = $bool;
        return $this;
    }
    public function btnTxt($string){
        $this->items[count($this->items)-1]['btn'] = $string;
        return $this;
    }

    // btn 事件
    public function btnClick($payUrl){
        //$previewUrl = '/'.request()->path().'/preview';
        //$query_string = $this->items[count($this->items)-1];
        //$previewUrl =
        Admin::script(
            <<<SCRIPT
$('.dingyuePay').click(function () {
    layer.open({
        type: 2,
        title: '订阅支付费用',
        area: ['300px','400px'],
        btn: ['我已支付', '取消'],
        content: '$payUrl',
        yes: function(index, layero){
                                   window.location.reload();
                                    layer.close(index);
                                }
    });
});
SCRIPT);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables() {
        return [
            'id'         => $this->id,
            'center' => $this->center,
            'clickFc'=> $this->clickFc,
            'columns'    => $this->col,
            'items'      => $this->items,
            'modals' => $this->modals,
            'attributes' => $this->formatAttributes(),
        ];
    }

}
