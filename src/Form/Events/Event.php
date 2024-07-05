<?php

namespace Dcatplus\Admin\Form\Events;

use Dcatplus\Admin\Form;

abstract class Event
{
    /**
     * @var Form
     */
    public $form;

    public $payload = [];

    public function __construct(Form $form, array $payload = [])
    {
        $this->form = $form;
        $this->payload = $payload;
    }
}
