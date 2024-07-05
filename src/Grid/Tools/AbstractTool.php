<?php

namespace Dcatplus\Admin\Grid\Tools;

use Dcatplus\Admin\Grid;

abstract class AbstractTool extends Grid\GridAction
{
    /**
     * @var string
     */
    protected $style = 'btn btn-white waves-effect';

    /**
     * @return string
     */
    protected function html()
    {
        $this->appendHtmlAttribute('class', $this->style);

        return <<<HTML
<button {$this->formatHtmlAttributes()}>{$this->title()}</button>
HTML;
    }
}
