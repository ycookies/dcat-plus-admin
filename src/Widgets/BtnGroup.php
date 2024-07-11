<?php

namespace Dcat\Admin\Widgets;

use Illuminate\Contracts\Support\Renderable;

class BtnGroup extends Widget
{
    /**
     * @var string
     */
    protected $view = 'admin::widgets.btn-group';

    protected $group_class = 'btn-group';
    /**
     * @var array
     */
    protected $items = [];

    /**
     * Collapse constructor.
     */
    public function __construct()
    {
        $this->id('btn-group-box-'.uniqid());
        $this->class('box-group');
        $this->style('margin-bottom: 20px');
    }

    // 垂直展示
    public function vertical() {
        $this->group_class = 'btn-group-vertical';
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
    public function add($btntxt, $icon = '')
    {
        $this->items[] = [
            'btntxt'   => $btntxt,
            'icon' => $icon,
            'styles' => 'btn-white',
            'link' => 'javascript:void(0);',
        ];
        return $this;
    }

    public function styles($styles){
        $this->items[count($this->items)-1]['styles'] = $styles;
        return $this;
    }

    public function link($url){
        $this->items[count($this->items)-1]['link'] = $url;
        return $this;
    }
    
    /*public function dropdown($title,$menu){
        $this->items[count($this->items)-1]['dropdown']['title'] = $title;
        $this->items[count($this->items)-1]['dropdown']['title'] = $title;
        return $this;
    }*/

    /**
     * {@inheritdoc}
     */
    public function defaultVariables()
    {
        return [
            'id'         => $this->id,
            'group_class' => $this->group_class,
            'items'      => $this->items,
            'attributes' => $this->formatAttributes(),
        ];
    }

}
