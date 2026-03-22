<?php

namespace Dcat\Admin\Widgets;

use Illuminate\Contracts\Support\Renderable;

class Linkbox extends Widget
{
    /**
     * @var string
     */
    protected $view = 'admin::widgets.link-box';

    protected $group_title;
    /**
     * @var array
     */
    protected $items = [];

    protected $hot = false;
    protected $target = '_self';
    /**
     * Collapse constructor.
     */
    public function __construct()
    {
        $this->id('link-box-'.uniqid());
        $this->class('box-group');
        $this->style('margin-bottom: 20px');
    }

    /**
     * 分组标题
     */
    public function groupTitle($title){
        $this->group_title = $title;
        return $this;
    }

    /**
     * 链接打开方式 _self  _blank
     */
    public function target($target = '_self') {
        $this->target = $target;
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
    public function add($icon, $title,$sub_title,$link,$bg_value='bg-info',$hot = false)
    {
        $this->items[] = [
            'icon' => $icon,
            'title'   => $title,
            'sub_title'=> $sub_title,
            'link'=> $link,
            'hot' => $hot,
            'bg_value' => $bg_value
        ];

        return $this;
    }
    
    public function hot($v = true){
        $this->items[count($this->items)-1]['hot'] = $v;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables()
    {
        return [
            'id'         => $this->id,
            'group_title' => $this->group_title,
            'items'      => $this->items,
            'target'     => $this->target,
            'attributes' => $this->formatAttributes(),
        ];
    }

}
