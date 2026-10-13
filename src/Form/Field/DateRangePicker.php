<?php

namespace Dcat\Admin\Form\Field;

/**
 * 日期区间选择器（daterangepicker，不含时间）：一个输入框内选择起止日期，
 * 提交拆为两个隐藏 input 写回 start/end 两列，存储格式 YYYY-MM-DD（与 date 字段一致）：
 * $form->dateRangePicker('start_date', 'end_date', '起止日期');
 */
class DateRangePicker extends DatetimeRangePicker
{
    protected string $pickerFormat = 'YYYY-MM-DD';

    protected bool $withTime = false;
}
