<?php

namespace Dcat\Admin\Grid\Concerns;

use Dcat\Admin\Contracts\Grid\ColumnSelectorStore;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Tools\ColumnSelector;
use Dcat\Admin\Support\Helper;
use Illuminate\Support\Collection;

trait CanHidesColumns
{
    /**
     * Default columns be hidden.
     *
     * @var array
     */
    public $hiddenColumns = [];

    /**
     * Conditional hidden columns.
     *
     * @var array|Closure
     */
    public $conditionalHiddenColumns = [];

    /**
     * @var ColumnSelectorStore
     */
    private $columnSelectorStorage;

    /**
     * Remove column selector on grid.
     *
     * @param  bool  $disable
     * @return $this|mixed
     */
    public function disableColumnSelector(bool $disable = true)
    {
        return $this->option('show_column_selector', ! $disable);
    }

    /**
     * @return bool
     */
    public function showColumnSelector(bool $show = true)
    {
        return $this->disableColumnSelector(! $show);
    }

    /**
     * @return bool
     */
    public function allowColumnSelector()
    {
        return $this->option('show_column_selector');
    }

    /**
     * @return string
     */
    public function renderColumnSelector()
    {
        if (! $this->allowColumnSelector()) {
            return '';
        }

        return (new ColumnSelector($this))->render();
    }

    /**
     * Setting default shown columns on grid.
     *
     * @param  array|string  $columns
     * @return $this
     */
    public function hideColumns($columns)
    {
        if (func_num_args()) {
            $columns = (array) $columns;
        } else {
            $columns = func_get_args();
        }

        $this->hiddenColumns = array_merge($this->hiddenColumns, $columns);

        return $this;
    }

    /**
     * Hide columns conditionally using Closure.
     *
     * @param  Closure|array  $columns
     * @return $this
     */
    public function hideColumnsWhen($columns)
    {
        $this->conditionalHiddenColumns[] = $columns;

        return $this;
    }

    /**
     * Get all hidden columns (including conditional ones).
     *
     * @return array
     */
    protected function getHiddenColumns(): array
    {
        $hidden = $this->hiddenColumns;

        // 处理条件隐藏列
        foreach ($this->conditionalHiddenColumns as $column) {
            if ($column instanceof \Closure) {
                $result = call_user_func($column, $this);
                if (is_array($result)) {
                    $hidden = array_merge($hidden, $result);
                } else {
                    $hidden[] = $result;
                }
            } else {
                if (is_array($column)) {
                    $hidden = array_merge($hidden, $column);
                } else {
                    $hidden[] = $column;
                }
            }
        }

        // 检查列对象的 isHidden() 状态，将隐藏的列加入到隐藏列列表
        if (isset($this->columns)) {
            foreach ($this->columns as $column) {
                if ($column->isHidden()) {
                    $hidden[] = $column->getName();
                }
            }
        }

        return array_values(array_filter(array_unique($hidden)));
    }

    /**
     * @return string
     */
    public function getColumnSelectorQueryName()
    {
        return $this->makeName(ColumnSelector::SELECT_COLUMN_NAME);
    }

    /**
     * Get visible columns from request query.
     *
     * @return array
     */
    public function getVisibleColumnsFromQuery()
    {
        $hiddenColumns = $this->getHiddenColumns();

        if (! $this->allowColumnSelector()) {
            // 列选择器禁用时，仍然需要应用 hideColumns() 的设置
            return array_values(array_diff(
                $this->getComplexHeaderNames() ?: $this->columnNames, $hiddenColumns
            ));
        }

        if (isset($this->visibleColumnsFromQuery)) {
            return $this->visibleColumnsFromQuery;
        }

        $columns = $input = Helper::array($this->request->get($this->getColumnSelectorQueryName()));

        if (! $input && ! $this->hasColumnSelectorRequestInput()) {
            $columns = $this->getVisibleColumnsFromStorage() ?: array_values(array_diff(
                $this->getComplexHeaderNames() ?: $this->columnNames, $hiddenColumns
            ));
        }

        $this->storeVisibleColumns($input);

        // 无论如何都要排除 hideColumns() 和 hideColumnsWhen() 设置的隐藏列
        $columns = array_values(array_diff($columns, $hiddenColumns));

        return $this->visibleColumnsFromQuery = $columns;
    }

    protected function formatWithComplexHeaders(array $columns)
    {
        if (! $columns) {
            return $this->getComplexHeaders();
        }

        if (empty($this->getComplexHeaderNames())) {
            return $columns;
        }

        return $this->getComplexHeaders()
            ->map(function (Grid\ComplexHeader $header) use ($columns) {
                if (! in_array($header->getName(), $columns, true)) {
                    return;
                }

                return $header->getColumnNames() ?: $this->getName();
            })
            ->filter()
            ->flatten()
            ->toArray();
    }

    /**
     * @return mixed
     */
    public function getVisibleComplexHeaders()
    {
        $visible = $this->getVisibleColumnsFromQuery();

        if (empty($visible)) {
            return $this->getComplexHeaders();
        }

        array_push($visible, Grid\Column::SELECT_COLUMN_NAME, Grid\Column::ACTION_COLUMN_NAME);

        return optional($this->getComplexHeaders())->filter(function ($column) use ($visible) {
            return in_array($column->getName(), $visible);
        });
    }

    /**
     * Get all visible column instances.
     *
     * @return Collection|static
     */
    public function getVisibleColumns()
    {
        $hiddenColumns = $this->getHiddenColumns();

        // 获取可见列（已排除隐藏列）
        $visible = $this->getVisibleColumnsFromQuery();
        
        if (empty($visible)) {
            $visible = array_values(array_diff(
                $this->getComplexHeaderNames() ?: $this->columnNames, $hiddenColumns
            ));
        }

        if (! $this->allowColumnSelector()) {
            // 列选择器禁用时，直接根据可见列过滤
            return $this->columns->filter(function (Grid\Column $column) use ($visible) {
                return in_array($column->getName(), $visible);
            });
        }

        $visible = $this->formatWithComplexHeaders($visible);

        if (empty($visible)) {
            return $this->columns->filter(function (Grid\Column $column) use ($hiddenColumns) {
                return ! in_array($column->getName(), $hiddenColumns);
            });
        }

        array_push($visible, Grid\Column::SELECT_COLUMN_NAME, Grid\Column::ACTION_COLUMN_NAME);

        return $this->columns->filter(function (Grid\Column $column) use ($visible) {
            return in_array($column->getName(), $visible);
        });
    }

    /**
     * Get all visible column names.
     *
     * @return array
     */
    public function getVisibleColumnNames()
    {
        $hiddenColumns = $this->getHiddenColumns();

        // 获取可见列名（已排除隐藏列）
        $visible = $this->getVisibleColumnsFromQuery();
        
        if (empty($visible)) {
            $visible = array_values(array_diff(
                $this->getComplexHeaderNames() ?: $this->columnNames, $hiddenColumns
            ));
        }

        if (! $this->allowColumnSelector()) {
            // 列选择器禁用时，直接返回可见列名
            return collect($visible)->toArray();
        }

        $visible = $this->formatWithComplexHeaders($visible);

        if (empty($visible)) {
            return collect($this->columnNames)->filter(function ($column) use ($hiddenColumns) {
                return ! in_array($column, $hiddenColumns);
            })->toArray();
        }

        array_push($visible, Grid\Column::SELECT_COLUMN_NAME, Grid\Column::ACTION_COLUMN_NAME);

        return collect($this->columnNames)->filter(function ($column) use ($visible) {
            return in_array($column, $visible);
        })->toArray();
    }

    public function hasColumnSelectorRequestInput()
    {
        return $this->request->has($this->getColumnSelectorQueryName());
    }

    protected function storeVisibleColumns(array $input)
    {
        if (! $this->hasColumnSelectorRequestInput()) {
            return;
        }

        $this->getColumnSelectorStorage()->store($input);
    }

    protected function getVisibleColumnsFromStorage()
    {
        return $this->getColumnSelectorStorage()->get();
    }

    /**
     * @return ColumnSelectorStore
     */
    public function getColumnSelectorStorage()
    {
        return $this->columnSelectorStorage ?: ($this->columnSelectorStorage = $this->makeColumnSelectorStorage());
    }

    protected function makeColumnSelectorStorage()
    {
        $store = config('admin.grid.column_selector.store') ?: Grid\ColumnSelector\SessionStore::class;
        $params = (array) config('admin.grid.column_selector.store_params') ?: [];

        $storage = app($store, $params);

        $storage->setGrid($this);

        return $storage;
    }
}
