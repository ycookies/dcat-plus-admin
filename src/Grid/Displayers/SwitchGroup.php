<?php

namespace Dcatplus\Admin\Grid\Displayers;

use Dcatplus\Admin\Admin;
use Illuminate\Support\Arr;

class SwitchGroup extends SwitchDisplay
{
    public function display($columns = [], $color = '', $refresh = false)
    {
        if ($columns instanceof \Closure) {
            $columns = $columns->call($this->row, $this);
        }

        if ($color) {
            $this->color($color);
        }

        if (! Arr::isAssoc($columns)) {
            $labels = array_map('admin_trans_field', $columns);
            $columns = array_combine($columns, $labels);
        }

        $color = $this->color ?: Admin::color()->primary();

        return Admin::view('admin::grid.displayer.switchgroup', [
            'row'      => $this->row->toArray(),
            'key'      => $this->getKey(),
            'columns'  => $columns,
            'resource' => $this->resource(),
            'color'    => $color,
            'refresh'  => $refresh,
        ]);
    }
}
