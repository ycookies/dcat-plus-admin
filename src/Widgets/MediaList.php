<?php

namespace Dcat\Admin\Widgets;

class MediaList extends Widget {
    /**
     * @var string
     */
    protected $view = 'admin::widgets.media-list';

    /**
     * @var array
     */
    protected $items = [];

    protected $img_center = '';
    protected $max_width = '88px';
    protected $target = '_self';

    // 内容展示的行数
    protected $row_num = '3';

    /**
     * Collapse constructor.
     */
    public function __construct() {
        $this->id('media-' . uniqid());
        $this->class('box-group');
        $this->style('margin-bottom: 20px');
    }

    public function imgCenter($center = false) {
        if ($center) {
            $this->img_center = 'align-self-center';
        }
        return $this;
    }

    public function imgMaxWidth($width = '88px') {
        $this->max_width = $width;
        return $this;
    }
    public function rowNum($num = '3') {
        $this->row_num = $num;
        return $this;
    }

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
    public function add($title, $content, $media_img, $link = 'javascript:void(0);') {
        $this->items[] = [
            'title'             => $title,
            'content'           => $content,
            'media_img'         => $media_img,
            'link'              => !empty($link) ? $link : 'javascript:void(0);',
            'align_self_center' => '',
        ];
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables() {
        return [
            'id'         => $this->id,
            'img_center' => $this->img_center,
            'max_width'  => $this->max_width,
            'row_num'    => $this->row_num,
            'target'     => $this->target,
            'items'      => $this->items,
            'attributes' => $this->formatAttributes(),
        ];
    }

}
