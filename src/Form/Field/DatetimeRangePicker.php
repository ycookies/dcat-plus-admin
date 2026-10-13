<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;

/**
 * 日期时间区间选择器（daterangepicker）：一个输入框内选择起止区间，
 * apply 时拆写入两个隐藏 input，按 start/end 两列提交。
 *
 * 存储结构、回显与框架 datetimeRange 字段完全一致，可无缝替换：
 * $form->datetimeRangePicker('start_at', 'end_at', '起止时间');
 */
class DatetimeRangePicker extends Field
{
    protected $view = 'admin::form.single-datetime-range';

    protected static $js = [
        '@moment',
        '@daterangepicker',
    ];

    protected static $css = [
        '@daterangepicker',
    ];

    /** 选择器格式（子类可覆盖为纯日期）。 */
    protected string $pickerFormat = 'YYYY-MM-DD HH:mm:ss';

    /** 是否带时间选择。 */
    protected bool $withTime = true;

    protected string $endColumn;

    public function __construct($column, $arguments = [])
    {
        // 对齐 datetimeRange 签名：($startColumn, $endColumn, $label)
        $this->endColumn = (string) ($arguments[0] ?? '');
        array_shift($arguments);

        parent::__construct($column, $arguments);
    }

    /**
     * 回显：把模型里 start/end 两列拼成本字段的展示值。
     * 纯日期版（withTime=false）截掉库存 datetime 的时间部分，避免显示成带时间。
     */
    public function fill($data)
    {
        parent::fill($data);

        $start = (string) ($this->value ?: '');
        $end = (string) (data_get($data, $this->endColumn) ?: '');
        if (! $this->withTime) {
            $start = substr($start, 0, 10);
            $end = substr($end, 0, 10);
        }

        $this->value = ['start' => $start, 'end' => $end];

        return $this;
    }

    public function variables()
    {
        // class 用两列名的 md5，稳定且同表单内唯一（编辑页重复渲染也一致）
        $rangeClass = 'dr-' . md5($this->column . '|' . $this->endColumn);

        return array_merge(parent::variables(), [
            'rangeClass'   => $rangeClass,
            'startName'    => $this->formatName($this->column),
            'endName'      => $this->formatName($this->endColumn),
            'pickerFormat' => $this->pickerFormat,
            'withTime'     => $this->withTime,
        ]);
    }
}
