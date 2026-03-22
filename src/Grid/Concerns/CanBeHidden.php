<?php

namespace Dcat\Admin\Grid\Concerns;

use Closure;

trait CanBeHidden
{
    /**
     * Whether the column is hidden.
     *
     * @var bool|Closure
     */
    protected $isHidden = false;

    /**
     * Whether the column is visible.
     *
     * @var bool|Closure
     */
    protected $isVisible = true;

    /**
     * Set the column to be hidden.
     *
     * @param bool|Closure $condition
     * @return $this
     */
    public function hidden($condition = true)
    {
        $this->isHidden = $condition;

        return $this;
    }

    /**
     * Set the column to be visible.
     *
     * @param bool|Closure $condition
     * @return $this
     */
    public function visible($condition = true)
    {
        $this->isVisible = $condition;

        return $this;
    }

    /**
     * Check if the column is hidden.
     *
     * @return bool
     */
    public function isHidden(): bool
    {
        if ($this->evaluate($this->isHidden)) {
            return true;
        }

        return ! $this->evaluate($this->isVisible);
    }

    /**
     * Check if the column is visible.
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        return ! $this->isHidden();
    }

    /**
     * Evaluate a value or closure.
     *
     * @param mixed $value
     * @return mixed
     */
    protected function evaluate($value)
    {
        if ($value instanceof Closure) {
            return call_user_func($value, $this);
        }

        return $value;
    }
}
