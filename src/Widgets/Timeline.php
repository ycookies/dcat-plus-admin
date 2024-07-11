<?php

namespace Dcat\Admin\Widgets;

use Illuminate\Contracts\Support\Renderable;

class Timeline extends Widget {
    /**
     * @var string
     */
    protected $view = 'admin::widgets.timeline';

    /**
     * @var array
     */
    protected $items = [];

    /**
     * Collapse constructor.
     */
    public function __construct() {
        $this->id('timeline-box-' . uniqid());
        $this->class('box-group');
        $this->style('margin-bottom: 20px');
    }

    /**
     * Add item.
     *
     * @param string $title
     * @param string $content
     *
     * @return $this
     */
    public function add($time_label, $title, $time, $content = '') {
        $this->items[] = [
            'time_label' => $time_label,  // line time
            'title'      => $title,
            'content'    => $content,
            'time'       => $time, // card time
            'icon' => 'fa fa-bullseye bg-green',
        ];
        return $this;
    }

    // 图标
    public function icons($strings) {
        $this->items[count($this->items) - 1]['icon'] = $strings;
        return $this;
    }

    /**
     * @desc vue方式获取数据
     * @param $apiurl 请求地址
     * @param array $param 请求参数
     * @param string $method 请求方式
     * @param string $headers 请求头
     * author eRic
     * dateTime 2024-07-10 18:07
     */
    public function vueAjax($apiurl,$param = [],$method = 'POST',$headers = []){

        $this->view = 'admin::widgets-vue.timeline';
        $this->addVariables(['ajax_url'=> $apiurl]);
        $this->addVariables(['ajax_method'=> $method]);
        $this->addVariables(['ajax_data'=> json_encode($param,JSON_UNESCAPED_UNICODE)]);
        $this->addVariables(['ajax_headers'=> json_encode($param,JSON_UNESCAPED_UNICODE)]);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables() {
        return [
            'id'         => $this->id,
            'items'      => $this->items,
            'attributes' => $this->formatAttributes(),
        ];
    }

}
