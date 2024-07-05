<?php

namespace Dcatplus\Admin\Grid\ColumnSelector;

use Dcatplus\Admin\Admin;
use Dcatplus\Admin\Contracts\Grid\ColumnSelectorStore;
use Dcatplus\Admin\Grid;

class SessionStore implements ColumnSelectorStore
{
    /**
     * @var Grid
     */
    protected $grid;

    public function setGrid(Grid $grid)
    {
        $this->grid = $grid;
    }

    public function store(array $input)
    {
        session()->put($this->getKey(), $input);
    }

    public function get()
    {
        return session()->get($this->getKey());
    }

    public function forget()
    {
        session()->remove($this->getKey());
    }

    protected function getKey()
    {
        return $this->grid->getName().'/'.request()->path().'/'.Admin::user()->getKey();
    }
}
