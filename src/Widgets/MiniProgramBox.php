<?php

namespace Dcat\Admin\Widgets;

use Illuminate\Contracts\Support\Renderable;

class MiniProgramBox extends Widget
{
    /**
     * @var string
     */
    protected $view = 'admin::widgets.mini-program-box';
    protected $title;
    protected $content;
    /**
     * @var array
     */
    protected $items = [];

    /**
     * Collapse constructor.
     */
    public function __construct($title = '',$content = null)
    {
        $this->content($content);
        $this->id('MiniProgramBox-'.uniqid());
        $this->class('box-group');
        $this->style('margin-bottom: 20px');
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
     * {@inheritdoc}
     */
    public function defaultVariables()
    {
        return [
            'id'         => $this->id,
            'title'      => $this->title,
            'content'    => $this->toString($this->content),
            'attributes' => $this->formatAttributes(),
        ];
    }

}
