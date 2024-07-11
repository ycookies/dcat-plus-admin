<?php

namespace Dcat\Admin\Form\Steps;

//use Dcat\Admin\Extend\ServiceProvider;
use Dcat\Admin\Form as BaseForm;
use Illuminate\Support\ServiceProvider;

class FormStepServiceProvider extends ServiceProvider
{
	public function boot()
	{
		//parent::init();

		//
        BaseForm::macro('multipleSteps', function ($builder = null) {
            if (empty($this->context['steps'])) {
                $this->context['steps'] = new Builder($this);
            }

            if ($builder) {
                if ($builder instanceof \Closure) {
                    $builder($this->context['steps']);
                } elseif (is_array($builder)) {
                    $this->context['steps']->add($builder);
                }
            }

            return $this->context['steps'];
        });
	}
}
