<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Grid\LazyRenderable as LazyGrid;
use Dcat\Admin\Traits\LazyWidget;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Str;

class Card extends Widget {
    protected $view = 'admin::widgets.card';
    protected $title;
    protected $content;
    protected $footer;
    protected $tools = [];
    protected $divider = false;
    protected $padding;
    protected $outline = '';
    protected $card_color;
    protected $collapse = false;
    protected $remove = false;
    public function __construct($title = '', $content = null) {
        if ($content === null) {
            $content = $title;
            $title   = '';
        }

        $this->title($title);
        $this->content($content);

        $this->class('card');
        $this->id('card-' . Str::random(8));
    }

    /**
     * @return $this
     */
    public function withHeaderBorder() {
        $this->divider = true;

        return $this;
    }

    /**
     * 设置卡片间距.
     *
     * @param  string $padding
     */
    public function padding(string $padding) {
        $this->padding = 'padding:' . $padding;

        return $this;
    }

    public function noPadding() {
        $this->padding('0');
        return $this;
    }

    public function outline($color = 'card-primary') {
        $this->outline = $color;
        return $this;
    }

    public function setCardColor($color = 'card-primary') {
        $this->class('card ' . $color);
        return $this;
    }
    
    public function collapse() {
        $this->collapse = true;

        return $this;
    }
    
    public function remove() {
        $this->remove = true;
        return $this;
    }

    /**
     * @param  string|\Closure|Renderable|LazyWidget $content
     * @return $this
     */
    public function content($content) {
        if ($content instanceof LazyGrid) {
            $content->simple();
        }

        $this->content = $this->formatRenderable($content);

        return $this;
    }

    /**
     * @param  string $content
     * @return $this
     */
    public function footer($content) {
        $this->footer = $content;

        return $this;
    }

    /**
     * @param  string $title
     * @return $this
     */
    public function title($title) {
        $this->title = $title;

        return $this;
    }

    /**
     * @param  string|Renderable|\Closure $content
     * @return $this
     */
    public function tool($content) {
        $this->tools[] = $this->toString($content);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables() {
        return [
            'title'      => $this->title,
            'content'    => $this->toString($this->content),
            'footer'     => $this->toString($this->footer),
            'tools'      => $this->tools,
            'attributes' => $this->formatHtmlAttributes(),
            'padding'    => $this->padding,
            'divider'    => $this->divider,
            'collapse'   => $this->collapse,
            'remove'     => $this->remove,
            'outline'    => $this->outline,
            'card_color' => $this->card_color,
        ];
    }
}
