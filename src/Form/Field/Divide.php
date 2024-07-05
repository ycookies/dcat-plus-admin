<?php

namespace Dcatplus\Admin\Form\Field;

use Dcatplus\Admin\Form\Field;

class Divide extends Field
{
    public function __construct($label = null)
    {
        $this->label = $label;
    }

    public function render()
    {
        if (! $this->label) {
            return '<hr/>';
        }

        return <<<HTML
<div class="mt-2 text-center mb-2 form-divider">
  <span>{$this->label}</span>
</div>
HTML;
    }
}
