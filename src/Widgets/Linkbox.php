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

    /**
     * Collapse constructor.
     */
    public function __construct()
    {
        $this->id('link-box-'.uniqid());
        $this->class('box-group');
        $this->style('margin-bottom: 20px');
    }

    public function groupTitle($title){
        $this->group_title = $title;
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

        /*foreach ($this->items as $key => &$item) {
            $item['hot'] = $v;
        }*/
        //$this->hot = $v;
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
            //'hot' => $this->hot,
            'attributes' => $this->formatAttributes(),
        ];
    }

}
