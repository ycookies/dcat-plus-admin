<?php

namespace Dcat\Admin\Widgets;

use Illuminate\Contracts\Support\Renderable;
use Dcat\Admin\Show;
use Illuminate\Support\Collection;

class Descriptions extends Widget
{
    /**
     * @var string
     */
    protected $view = 'admin::widgets.descriptions';

    /**
     * @var array
     */
    protected $items = [];

    /**
     * @var int
     */
    protected $columns = 1;

    /**
     * @var bool
     */
    protected $card = false;

    /**
     * @var string|null
     */
    protected $header = null;

    /**
     * @var string|null
     */
    protected $footer = null;

    protected $shadow = null;

    /**
     * Parent show.
     *
     * @var Show
     */
    protected $show;

    /**
     * Descriptions constructor.
     */

    /**
     * Callback for add field to current row.s.
     *
     * @var \Closure
     */
    protected $callback;
    
    protected $tips = '';


    public function __construct()
    {
        $this->id('descriptions-'.uniqid());
        $this->class('box-group');
        $this->style('margin-bottom: 20px');
    }
    
    public function setItems(array $items){
         $this->items = $items;
         return $this;
    }
    
    public function setTips(bool $value = true){
        
        if($value === true){
            $this->tips = 'tips';
        }
        return $this;
    }

    /**
     * Set number of columns.
     *
     * @param int $columns
     * @return $this
     */
    public function columns(int $columns)
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * Enable card layout.
     *
     * @param bool $card
     * @return $this
     */
    public function card(bool $card = true)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Set card header.
     *
     * @param string $header
     * @return $this
     */
    public function header($header)
    {
        $this->header = $header;

        return $this;
    }

    /**
     * Set card shadow.
     *
     * @param string $shadow
     * @return $this
     */
    public function shadow()
    {
        $this->shadow = 'shadow';

        return $this;
    }

    /**
     * Set card footer.
     *
     * @param string $footer
     * @return $this
     */
    public function footer($footer)
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * Add field.
     *
     * @param string $label
     * @param mixed $content
     * @return $this
     */
    public function field($label, $content = '')
    {
        $this->items[] = [
            'label' => $label,
            'content' => $content,
            'dedicated_line' => false,
        ];

        return $this;
    }

    /**
     * Set the last field to be dedicated line.
     *
     * @return $this
     */
    public function dedicatedLine()
    {
        if (!empty($this->items)) {
            $lastIndex = count($this->items) - 1;
            $this->items[$lastIndex]['dedicated_line'] = true;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables()
    {
        return [
            'id' => $this->id,
            'items' => $this->items,
            'columns' => $this->columns,
            'card' => $this->card,
            'header' => $this->header,
            'footer' => $this->footer,
            'shadow' => $this->shadow,
            'tips' => $this->tips,
            'attributes' => $this->formatAttributes(),
        ];
    }
}
