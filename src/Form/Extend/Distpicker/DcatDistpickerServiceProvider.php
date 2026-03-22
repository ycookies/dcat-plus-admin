<?php

namespace Dcat\Admin\Form\Extend\Distpicker;

use Dcat\Admin\Admin;
use Illuminate\Support\ServiceProvider;
use Dcat\Admin\Form;
use Dcat\Admin\Grid\Column;
use Dcat\Admin\Grid\Filter;
use Dcat\Admin\Form\Extend\Distpicker\Filter\DistpickerFilter;
use Dcat\Admin\Form\Extend\Distpicker\Form\Distpicker;
use Dcat\Admin\Form\Extend\Distpicker\Grid\Distpicker as GridDistpicker;

class DcatDistpickerServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //加载插件
        Admin::booting(static function () {
            Column::extend('distpicker', GridDistpicker::class);
            Form::extend('distpicker', Distpicker::class);
            Filter::extend('distpicker', DistpickerFilter::class);
        });
    }
}
