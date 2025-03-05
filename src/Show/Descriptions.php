<?php

namespace Dcat\Admin\Show;

use Dcat\Admin\Show;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Dcat\Admin\Show\Html;

class Descriptions implements Renderable
{
    /**
     * Callback for add field to current row.s.
     *
     * @var \Closure
     */
    protected $callback;

    /**
     * Parent show.
     *
     * @var Show
     */
    protected $show;

    /**
     * @var Collection
     */
    protected $fields;

    /**
     * Default field width for appended field.
     *
     * @var int
     */
    protected $defaultFieldWidth = 12;

    /**
     * @var int
     */
    protected $columns = 1;
    
    protected $label_width = '120px';

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
    protected $desc_shadow = '';
    
    protected $label_justify_content = 'center';
    protected $content_justify_content = 'flex-start';
    protected $content_white_space = 'white-space:nowrap';
    /**
     * Row constructor.
     *
     * @param  \Closure  $callback
     * @param  Show  $show
     */
    public function __construct(\Closure $callback, Show $show)
    {
        $this->callback = $callback;

        $this->show = $show;

        $this->fields = new Collection();

        call_user_func($this->callback, $this);
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
     * Set number of columns.
     *
     * @param int $columns
     * @return $this
     */
    public function labelWidth($width = '120px')
    {
        $this->label_width = $width;

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
     * Set desc shadow.
     *
     * @param string $shadow
     * @return $this
     */
    public function descShadow()
    {
        $this->desc_shadow = 'box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);';

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

    public function labelJustifyContent($value = 'center')
    {
        $this->label_justify_content = $value;

        return $this;
    }

    public function contentJustifyContent($value = 'flex-start')
    {
        $this->content_justify_content = $value;

        return $this;
    }

    // 是否内容换行
    public function contentWhiteSpace(bool $value = true)
    {
        if($value){
            $this->content_white_space = 'white-space:normal';
        }


        return $this;
    }
    
    /**
     * @return Collection|\Dcat\Admin\Show\Field[]
     */
    public function fields()
    {
        return $this->fields;
    }

    /**
     * Set width for a incomming field.
     *
     * @param  int  $width
     * @return $this
     */
    public function width($width = 12)
    {
        $this->defaultFieldWidth = $width;

        return $this;
    }

    /**
     * Add field.
     *
     * @param  string  $name
     * @param  string  $label
     * @return \Dcat\Admin\Show\Field
     */
    public function field($name, $label = '')
    {

        //$field = $this->show->model()->toArray();
        $field = $this->show->field($name, $label);
        $this->pushField($field);

        return $field;
    }



    /**
     * Render the row.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function render()
    {
        return view('admin::show.descriptions', [
            'fields' => $this->fields,
            'columns' => $this->columns,
            'label_width' => $this->label_width,
            'card' => $this->card,
            'header' => $this->header,
            'footer' => $this->footer,
            'shadow' => $this->shadow,
            'desc_shadow' => $this->desc_shadow,
            'label_justify_content' => $this->label_justify_content,
            'content_justify_content' => $this->content_justify_content,
            'content_white_space' => $this->content_white_space,
        ]);
    }

    /**
     * Add field.
     *
     * @param $name
     * @return \Dcat\Admin\Show\Field|Collection
     */
    public function __get($name)
    {
        $field = $this->show->field($name);

        $this->pushField($field);

        return $field;
    }

    /**
     * @param $method
     * @param $arguments
     * @return \Dcat\Admin\Show\Field
     */
    public function __call($method, $arguments)
    {
        $field = $this->show->__call($method, $arguments);

        $this->pushField($field);

        return $field;
    }

    /**
     * @param  \Dcat\Admin\Show\Field  $field
     * @return void
     */
    protected function pushField($field)
    {
        $this->fields->push([
            'width'   => $this->defaultFieldWidth,
            'element' => $field,
        ]);
    }
}
