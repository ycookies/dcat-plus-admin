<?php

namespace Dcatplus\Admin\Grid\Actions;

use Dcatplus\Admin\Grid\RowAction;

class Show extends RowAction
{
    /**
     * @return array|null|string
     */
    public function title()
    {
        if ($this->title) {
            return $this->title;
        }

        return '<i class="feather icon-eye"></i> '.__('admin.show').' &nbsp;&nbsp;';
    }

    /**
     * @return string
     */
    public function href()
    {
        return $this->parent->urlWithConstraints("{$this->resource()}/{$this->getKey()}");
    }
}
