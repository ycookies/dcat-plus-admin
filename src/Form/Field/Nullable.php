<?php

namespace Dcatplus\Admin\Form\Field;

use Dcatplus\Admin\Form\Field;

class Nullable extends Field
{
    public function __construct()
    {
    }

    public function __call($method, $parameters)
    {
        return $this;
    }

    public function render()
    {
    }
}
