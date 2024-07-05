<?php

namespace Dcatplus\Admin\Form\Field;

use Dcatplus\Admin\Support\Helper;

class MultipleSelect extends Select
{
    protected function formatFieldData($data)
    {
        return Helper::array($this->getValueFromData($data));
    }

    protected function prepareInputValue($value)
    {
        return Helper::array($value, true);
    }
}
