<?php

namespace Dcat\Admin\Form\Field;

class Table extends ArrayField
{
    /**
     * @var string
     */
    protected $viewMode = 'table';

    public function render()
    {
        if (! $this->shouldRender()) {
            return '';
        }

        return $this->renderTable();
    }
}
