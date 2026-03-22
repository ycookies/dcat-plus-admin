<?php

namespace Dcat\Admin\Grid\Displayers;

class Link extends AbstractDisplayer
{
    public function display($href = '', $target = '_blank',$view = '')
    {
        if ($href instanceof \Closure) {
            $href = $href->bindTo($this->row);

            $href = call_user_func($href, $this->value);
        } else {
            $href = $href ?: $this->value;
        }
        $str = $this->value;
        if(!empty($view)){
            $str = $view;
        }

        return "<a href='$href' target='$target'>{$str}</a>";
    }
}
