<?php

namespace Dcat\Admin\Form\Extend\Diyforms;

use Dcat\Admin\Admin;
use Illuminate\Support\ServiceProvider;
use Dcat\Admin\Form;
use Dcat\Admin\Show\Field;
use Dcat\Admin\Form\Extend\Diyforms\Form\DiyForm as FormDiyForm;
use Dcat\Admin\Form\Extend\Diyforms\Show\DiyForm as showDiyForm;

class DcatDiyFormServiceProvider extends ServiceProvider
{
    public function boot()
    {
        //加载插件
        Admin::booting(static function () {
            Form::extend('diyForm', FormDiyForm::class);
            Field::extend('diyForm', showDiyForm::class);
        });

    }

    public function settingForm()
    {
        return new Setting($this);
    }
}
