<?php

namespace Dcat\Admin\Form\Extend\Distpicker\Grid;

use Dcat\Admin\Grid\Displayers\AbstractDisplayer;
use Dcat\Admin\Form\Extend\Distpicker\DcatDistpickerHelper;

class Distpicker extends AbstractDisplayer
{
    public function display()
    {
        return DcatDistpickerHelper::getAreaName($this->value);
    }
}
