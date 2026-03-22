<?php

namespace Dcat\Admin\Widgets;

use Illuminate\Contracts\Support\Renderable;

class ListGroup extends Widget
{
    /**
     * @var string
     */
    protected $view = 'admin::widgets.list-group';

    /**
     * @var array
     */
    protected $items = [];

    /**
     * Collapse constructor.
     */
    public function __construct()
    {
        $this->id('list-group-box-'.uniqid());
        $this->class('box-group');
        $this->style('margin-bottom: 20px');
    }

    /**
     * @desc vue方式获取数据
     * @param $apiurl 请求地址
     * @param array $param 请求参数
     * @param string $method 请求方式
     * author eRic
     * dateTime 2024-07-10 18:07
     */
    public function vueAjax($apiurl,$param = [],$method = 'POST',$headers = []){

        $this->view = 'admin::widgets-vue.list-group';
        $this->addVariables(['ajax_url'=> $apiurl]);
        $this->addVariables(['ajax_method'=> $method]);
        $this->addVariables(['ajax_data'=> json_encode($param,JSON_UNESCAPED_UNICODE)]);
        $this->addVariables(['ajax_headers'=> json_encode($param,JSON_UNESCAPED_UNICODE)]);
        return $this;
    }

    /**
     * Add item.
     *
     * @param string $title
     * @param string $content
     *
     * @return $this
     */
    public function add($title, $content,$link)
    {
        $this->items[] = [
            'title'   => $title,
            'content' => $content,
            'link' => $link
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables()
    {
        return [
            'id'         => $this->id,
            'items'      => $this->items,
            'attributes' => $this->formatAttributes(),
        ];
    }

}
