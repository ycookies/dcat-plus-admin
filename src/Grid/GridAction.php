<?php

namespace Dcatplus\Admin\Grid;

use Dcatplus\Admin\Actions\Action;
use Dcatplus\Admin\Grid;

/**
 * Class GridAction.
 */
abstract class GridAction extends Action
{
    /**
     * @var Grid
     */
    protected $parent;

    /**
     * @param  Grid  $grid
     * @return $this
     */
    public function setGrid(Grid $grid)
    {
        $this->parent = $grid;

        return $this;
    }

    /**
     * Get url path of current resource.
     *
     * @return string
     */
    public function resource()
    {
        return $this->parent->resource();
    }
}
