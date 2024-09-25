<?php

namespace Dcat\Admin\Form\Field;

class MultiDate extends Text
{
    public static $js = [
        '@moment',
        'https://cdn.jsdelivr.net/npm/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js',
        'https://cdn.jsdelivr.net/npm/bootstrap-datepicker/dist/locales/bootstrap-datepicker.zh-CN.min.js',
    ];
    public static $css = [
        'https://cdn.jsdelivr.net/npm/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css',
    ];

    protected $format = 'yyyy-mm-dd';

    public function format($format)
    {
        $this->format = $format;

        return $this;
    }

    protected function prepareInputValue($value)
    {
        if ($value === '') {
            $value = null;
        }

        return $value;
    }

    public function render()
    {

        $this->options['format'] = $this->format;
/*        $this->options['locale'] = config('app.locale');
        $this->options['allowInputToggle'] = true;*/
        $this->options['multidate'] = true;
        $this->options['language'] = 'zh-CN';

        $options = admin_javascript_json($this->options);

        $this->script = <<<JS
Dcat.init('{$this->getElementClassSelector()}', function (self) {
    self.datepicker({$options});
});
JS;

        $this->prepend('<i class="fa fa-calendar fa-fw"></i>')
            ->defaultAttribute('style', 'width: 200px;flex:none');

        return parent::render();
    }
}