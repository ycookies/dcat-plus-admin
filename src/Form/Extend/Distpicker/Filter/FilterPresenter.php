<?php

namespace Dcat\Admin\Form\Extend\Distpicker\Filter;

use Dcat\Admin\Grid\Filter\Presenter\Presenter;

class FilterPresenter extends Presenter
{
    public function view(): string
    {
        return 'admin::form.extend.distpicker.filter';
    }
}
