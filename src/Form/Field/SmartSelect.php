<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;
use Dcat\Admin\Form\Extend\Distpicker\DcatDistpickerHelper;

class SmartSelect extends Field
{
    /**
     * @var string
     */
    protected $view = 'admin::form.extend.distpicker.smart-select';

    /**
     * @var array
     */
    protected static $js = [
        //'@select2',
    ];
    protected static $css = [
        //'@select2',
    ];

    /**
     * @var array
     */
    protected $columnKeys = ['province', 'city', 'district'];

    /**
     * @var array
     */
    protected $placeholder = [];
    
    
    protected $defaultLabel = [];

    protected $selectValueUrl;
    
    protected $input_width = '120px';
    /**
     * Distpicker constructor.
     *
     * @param  array  $column
     * @param  array  $arguments
     */
    public function __construct(array $column, $arguments)
    {
        parent::__construct($column, $arguments);
        $this->column = $this->arrayCombine($column, $column);
        $this->selectValueUrl = admin_url('get-select-value');
        $this->label = empty($arguments) ? '多项选择' : current($arguments);
    }

    /**
     * 合并两个数组来创建一个新数组
     * @param  array  $keys
     * @param  array  $values
     * @return array
     * @author super-eggs
     */
    protected function arrayCombine(array $keys, array $values): array
    {
        $arr = array();
        foreach ($values as $k => $value) {
            $arr[$keys[$k]] = $value;
        }

        return $arr;
    }

    public function defaultLabel(array $option){
        $this->defaultLabel = $option;
        return $this;
    }

    public function setGetSelectValueUrl($url){
        $this->selectValueUrl = $url;
        return $this;
    }
    public function inputWidth($width){
        $this->input_width = $width;
        return $this;
    }

    /**
     * 获取验证器
     * @param  array  $input
     * @return false|Application|Factory|Validator|mixed
     */
    public function getValidator(array $input)
    {
        if ($this->validator) {
            return $this->validator->call($this, $input);
        }

        $rules = $attributes = [];

        if (!$fieldRules = $this->getRules()) {
            return false;
        }

        foreach ($this->column as $column) {
            if (!Arr::has($input, $column)) {
                continue;
            }
            $input[$column] = Arr::get($input, $column);
            $rules[$column] = $fieldRules;
            $attributes[$column] = $this->label."[$column]";
        }

        return \validator($input, $rules, $this->getValidationMessages(), $attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        $id = uniqid('smart-select-', false);
        $select_list = $this->defaultLabel;
        $selectValueUrl = $this->selectValueUrl;
        $input_width = $this->input_width;
        $this->addVariables(compact('input_width'));
        $this->addVariables(compact('selectValueUrl'));
        $this->addVariables(compact('select_list'));
        $this->addVariables(compact('id'));

        return parent::render();
    }
}
