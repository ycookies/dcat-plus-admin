<?php

namespace Dcat\Admin\Grid\Displayers;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Storage;
use Dcat\Admin\Support\Helper;

class Icon extends AbstractDisplayer
{
    // 图标大小 
    public function display($size = 14, $color = null)
    {
        if ($this->value instanceof Arrayable) {
            $this->value = $this->value->toArray();
        }
        $this->value = Helper::array($this->value);
        return collect((array) $this->value)->filter()->map(function ($icon) use ($size, $color) {
            $style = "font-size:{$size}px;";
            if ($color) {
                $style .= "color:{$color};";
            }
            // 如果 fa- 开头，则使用 font-awesome 图标 需要加 fa fa-fw
            if (strpos($icon, 'fa-') === 0) {
                $icon = "fa fa-fw {$icon}";
            }
            return "<i class='{$icon}' style='{$style}'></i>";
        })->implode('&nbsp;');
    }
}
