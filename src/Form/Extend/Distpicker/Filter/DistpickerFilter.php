<?php

namespace Dcat\Admin\Form\Extend\Distpicker\Filter;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Filter\AbstractFilter;
use Dcat\Laravel\Database\WhereHasInServiceProvider;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DistpickerFilter extends AbstractFilter
{
    /**
     * @var array
     */
    protected $column = [];

    protected static $js = [
        //'@extension/super-eggs/dcat-distpicker/dist/distpicker.js',
    ];

    /**
     * @var array
     */
    protected $value = [];

    /**
     * @var array
     */
    protected $defaultValue = [];

    private $placeholder = [];

    /**
     * DistpickerFilter constructor.
     * @param  string  $province
     * @param  string  $city
     * @param  string  $district
     * @param  string  $label
     */
    public function __construct(string $province, string $city = "", string $district = "", string $label = '')
    {
        $column = Array_filter(compact('province', 'city', 'district'));

        parent::__construct($column, empty($label) ? '地区选择' : $label);

        $this->setPresenter(new FilterPresenter());
    }


    public function getElementName()
    {
        return $this->originalColumn();
    }

    /**
     * @return array
     * @author super-eggs
     */
    public function getColumn(): array
    {
        $columns = [];

        $parentName = $this->parent->getName();

        foreach ($this->column as $column) {
            $columns[] = $parentName ? "{$parentName}_{$column}" : $column;
        }


        return $columns;
    }

    /**
     * @param  array  $inputs
     * @return array|array[]|mixed|void|null
     * @author super-eggs
     */
    public function condition($inputs)
    {
        $value_arr = array();
        foreach ($this->column as $column) {
            $value_arr[$column] = Arr::get($inputs, $column);
        }
        $value = array_filter($value_arr);

        if (empty($value)) {
            return;
        }

        $this->value = $value;

        if (! $this->value) {
            return [];
        }

        if (Str::contains(key($value), '.')) {
            return $this->buildRelationQuery($value);
        }

        return [$this->query => [$value]];
    }

    /**
     * 建立关系查询
     * {@inheritdoc}
     */
    protected function buildRelationQuery($relColumn, ...$params): array
    {
        $data = [];
        foreach ($relColumn as $column => $value) {
            Arr::set($data, $column, $value);
        }
        $relation = key($data);
        $args = $data[$relation];
        // 增加对whereHasIn的支持
        $method = class_exists(WhereHasInServiceProvider::class) ? 'whereHasIn' : 'whereHas';

        return [
            $method => [
                $relation, function ($relation) use ($args) {
                    call_user_func_array([$relation, $this->query], [$args]);
                },
            ],
        ];
    }

    /**
     * @param  array  $column
     * @return array|string
     * @author super-eggs
     */
    public function formatName($column)
    {
        $columns = [];

        foreach ($column as $col => $name) {
            $columns[$col] = parent::formatName($name);
        }

        return $columns;
    }

    /**
     * 格式编号
     * @param  array|string  $columns
     * @return string
     * @author super-eggs
     */
    protected function formatId($columns): string
    {
        if (is_array($columns)) {
            $columns = 'district';
        }

        return $this->parent->grid()->makeName('filter-column-'.str_replace('.', '-', $columns));
    }

    /**
     * 设置js脚本。
     * Setup js scripts.
     */
    protected function setupScript(): void
    {
        $province = old($this->column['province'],
            Arr::get($this->value, $this->column['province'])) ?: Arr::get($this->placeholder,
            $this->column['province']);
        $city = "";
        $district = "";
        if (isset($this->column['city'])) {
            $city = old($this->column['city'],
                Arr::get($this->value, $this->column['city'])) ?: Arr::get($this->placeholder,
                $this->column['city']);
        }
        if (isset($this->column['district'])) {
            $district = old($this->column['district'],
                Arr::get($this->value, $this->column['district'])) ?: Arr::get($this->placeholder,
                $this->column['district']);
        }
        $id = uniqid('distpicker-filter-', false);
        $color = Admin::color()->primary();
        $script = <<<JS
$("#{$id}").distpicker({
  province: '$province',
  city: '$city',
  district: '$district'
});
$('.distpicker_select').on('mouseover', function () {
    var idx = layer.tips($(this).children('option:selected').text(), this, {
      tips: ['1', '{$color}'],
      time: 0,
      maxWidth: 210
    });
    $(this).attr('layer-idx', idx);
}).on('mouseleave', function () {
    layer.close($(this).attr('layer-idx'));
    $(this).attr('layer-idx', '');
});
JS;
        Admin::script($script);
        Admin::js(static::$js);

        $this->addVariables(compact('id'));
    }


    protected function defaultVariables(): array
    {
        $this->setupScript();

        return parent::defaultVariables();
    }


}
