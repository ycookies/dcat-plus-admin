<?php

namespace Dcatplus\Admin\Form\Concerns;

use Closure;
use Dcatplus\Admin\Form\Layout;

trait HasLayout
{
    /**
     * @var Layout
     */
    protected $layout;

    /**
     * @param  int|float  $width
     * @param  Closure  $callback
     * @return $this
     */
    public function column($width, Closure $callback)
    {
        $this->layout()->onlyColumn($width, function () use ($callback) {
            $callback($this);
        });

        return $this;
    }

    /**
     * @return Layout
     */
    public function layout()
    {
        return $this->layout ?: ($this->layout = new Layout($this));
    }
}
