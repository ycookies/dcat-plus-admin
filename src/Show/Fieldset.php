<?php

namespace Dcat\Admin\Show;

use Dcat\Admin\Show;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;

class Fieldset implements Renderable
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
    /**
     * @var bool
     */
    protected $card = false;

    /**
     * @var string|null
     */
    protected $title = null;

    /**
     * @var string|null
     */
    protected $shadow = null;
    protected $content_white_space = 'white-space:nowrap;';
    

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
        $columns_value_convert = [
            1 => 12,
            2 => 6,
            3 => 4,
            4 => 3,
            5 => 2,
            6 => 2,
        ];
        $this->columns = !empty($columns_value_convert[$columns]) ? $columns_value_convert[$columns]:12;

        return $this;
    }
    /**
     * Set card header.
     *
     * @param string $header
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

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
     * Set card shadow.
     *
     * @param string $shadow
     * @return $this
     */
    public function shadow()
    {
        $this->shadow = 'box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);';

        return $this;
    }
    public function setShadow($shadow)
    {
        $this->shadow = $shadow;

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
        return view('admin::show.fieldset', [
            'fields' => $this->fields,
            'columns' => $this->columns,
            'title' => $this->title,
            'shadow' => $this->shadow,
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
