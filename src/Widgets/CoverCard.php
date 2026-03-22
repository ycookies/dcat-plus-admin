<?php

namespace Dcat\Admin\Widgets;

use Illuminate\Contracts\Support\Renderable;

class CoverCard extends Widget
{
    /**
     * @var string
     */
    protected $view = 'admin::widgets.cover-card';

    /**
     * @var array
     */
    protected $items = [];

    /**
     * Collapse constructor.
     */
    public function __construct()
    {
        $this->id('CoverCard-'.uniqid());
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
    public function add($title = '', $content ='',$link="javascript:void(0);")
    {
        $this->items[] = [
            'title'   => $title,
            'content' => $content,
            'link'=> $link,
            'avatar_circle' => '',
        ];
        return $this;
    }
    // 背景图片
    public function bg($img){
        $this->items[count($this->items)-1]['bg'] = $img;
        return $this;
    }

    // 头像
    public function avatar($avatar){
        $this->items[count($this->items)-1]['avatar'] = $avatar;
        return $this;
    }
    // 头像 圆形
    public function avatarCircle($bool = true){
        $this->items[count($this->items)-1]['avatar_circle'] = 'avatar-rounded';
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
