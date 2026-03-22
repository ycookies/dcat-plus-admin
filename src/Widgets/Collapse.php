<?php

namespace Dcat\Admin\Widgets;

use Illuminate\Contracts\Support\Renderable;

class Collapse extends Widget
{
    /**
     * @var string
     */
    protected $view = 'admin::widgets.collapse';

    /**
     * @var array
     */
    protected $items = [];

    /**
     * Collapse constructor.
     */
    public function __construct()
    {
        $this->id('accordion-'.uniqid());
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
    public function add($title, $content)
    {
        $this->items[] = [
            'title'   => $title,
            'content' => $content,
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
