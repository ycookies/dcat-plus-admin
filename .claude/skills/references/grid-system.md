# dcat-plus-admin Grid 完整目录

## Grid 主类方法

### 列管理
- column($name, $label = '') - 添加列
- columns($columns) - 批量添加列
- allColumns() - 获取所有列
- number($label) - 行号列
- prependColumn($field, $label) - 前插入列
- dropColumn($column) - 移除列
- getColumnNames() - 获取列名数组

### 模型
- model() - 获取Grid\Model
- setKeyName($name) / getKeyName()
- getCreateUrl() / getEditUrl($key)
- resource() / setResource($path)
- urlWithConstraints($url)

### 异步与轮询
- async(true) - 启用异步加载
- isAsyncRequest() - 判断异步请求
- polling($seconds=5) - 自动刷新

### 表格样式
- withBorder(true) - 边框表格
- tableCollapse(true) - 紧凑表格
- scrollbar(true) / scrollbarX(true)
- tableHeaderFixed(true) - 固定表头
- columnLink(true) - 列链接样式

### 头尾
- header($content) - Closure|string|Renderable
- footer($content)
- headerCenterStyle($width)
- headerStyle($type)

### 创建按钮
- disableCreateButton(true) / showCreateButton(true)
- createMode('default'|'dialog')
- enableDialogCreate()
- setDialogFormDimensions($w, $h)

### 行选择器
- rowSelector() - 获取RowSelector
- disableRowSelector(true) / showRowSelector(true)

### 分页
- paginate($perPage=20)
- simplePaginate(true)
- perPages([$n1,$n2])
- disablePagination(true)

### 工具栏
- tools(Closure) / rightTools(Closure)
- disableToolbar(true) / showToolbar(true)
- disableRefreshButton(true) / showRefreshButton(true)
- disableBatchActions(true) / showBatchActions(true)
- disableBatchDelete(true) / showBatchDelete(true)
- batchActions(Closure|BatchAction|BatchAction[])
- toolsWithOutline(true)

### 筛选器
- filter(Closure) / disableFilter(true) / expandFilter()
- disableFilterButton(true) / showFilterButton(true)

### 快速搜索
- quickSearch($cols) - string|array|Closure
- allowQuickSearch()

### 快捷创建
- quickCreate(Closure)

### 选择器
- selector(Closure)

### 导出导入
- export($driver) / import($driver)
- disableExporter() / disableImporter()

### 操作
- actions(Closure|array|RowAction)
- disableActions(true) / showActions(true)
- disableEditButton(true) / disableViewButton(true)
- disableDeleteButton(true) / disableQuickEditButton(true)

### 隐藏列
- hideColumns($cols) / hideColumnsWhen(Closure)
- disableColumnSelector(true) / showColumnSelector(true)

### 复杂表头
- combine($column, $columnNames, $label) - ComplexHeader

### 固定列
- fixColumns(int $head, int $tail=-1)

### 树面板
- treePanel(Closure, $disable=true) - 左树右表

### 汇总行
- disableSummarizers(true)
- disableSummarizerThisPage(true) / disableSummarizerAllPage(true)

### 事件
- listen(EventClass, Closure) / fire(Event)

## Column 显示器方法

| 方法 | 类 | 用法 |
|------|---|------|
| display(Closure) | 内置 | 自定义显示回调 |
| using(array) | 内置 | 值映射: [1=>'启用', 0=>'禁用'] |
| bold($color) | 内置 | 加粗+颜色 |
| dot(array) | 内置 | 彩色圆点: [1=>'success', 0=>'danger'] |
| prepend($val) / append($val) | 内置 | 前/后追加 |
| limit($len, $end) | Limit | 截断显示 |
| image($server, $w, $h) | Image | 图片显示 |
| label($style, $max) | Label | 标签徽章 |
| badge($style, $max) | Badge | 药丸徽章 |
| button($style) | Button | 按钮样式 |
| link($href, $target) | Link | 超链接 |
| progressBar($style, $size) | ProgressBar | 进度条 |
| bool(array) | 内置 | 布尔图标(勾/叉) |
| switch($color, $refresh) | SwitchDisplay | 行内开关切换 |
| switchGroup($cols, $color) | SwitchGroup | 多开关 |
| select($options, $refresh) | Select | 行内下拉 |
| radio($options) / checkbox($options) | Radio/Checkbox | 行内单选/多选 |
| expand(Closure) | Expand | 展开行 |
| modal($title, Closure) | Modal | 弹窗内容 |
| showTreeInDialog($nodes) | DialogTree | 弹窗树 |
| qrcode(Closure, $w, $h) | QRCode | 二维码 |
| downloadable($server) | Downloadable | 下载链接 |
| copyable() | Copyable | 点击复制 |
| orderable() | Orderable | 上下排序 |
| editable($opts) / input($opts) | Input | 行内编辑 |
| textarea($opts) | Textarea | 行内多行编辑 |
| table($titles) | Table | 嵌套表格 |
| date($fmt) / dateTime($fmt) / time($fmt) | 内置 | 格式化日期 |
| since($tz) | 内置 | "3小时前" |
| prefix($str) / suffix($str) | 内置 | 前缀/后缀 |
| view($blade) | 内置 | Blade视图渲染 |
| action(RowAction) | 内置 | 行操作按钮 |

## 列头筛选

```php
$grid->column('name')->filter();  // 默认Equal
$grid->column('name')->filter(Grid\Column\Filter\Like::make());
$grid->column('created_at')->filter(Grid\Column\Filter\Equal::make()->datetime());
$grid->column('amount')->filterByValue();  // 隐藏等值筛选
```

## 筛选器类型

| 方法 | 类 | SQL |
|------|---|-----|
| equal($col, $label) | Equal | = value |
| notEqual($col, $label) | NotEqual | != value |
| like($col, $label) | Like | LIKE %value% |
| ilike($col, $label) | Ilike | ILIKE %value% |
| startWith($col, $label) | StartWith | LIKE value% |
| endWith($col, $label) | EndWith | LIKE %value |
| gt($col, $label) | Gt | > value |
| lt($col, $label) | Lt | < value |
| ngt($col, $label) | Ngt | <= value |
| nlt($col, $label) | Nlt | >= value |
| between($col, $label) | Between | BETWEEN |
| in($col, $label) | In | IN (...) |
| notIn($col, $label) | NotIn | NOT IN (...) |
| where($col, Closure, $label) | Where | 自定义闭包 |
| whereBetween($col, Closure, $label) | WhereBetween | 自定义BETWEEN |
| date($col, $label) | Date | 日期 |
| day($col, $label) / month($col) / year($col) | Day/Month/Year | 部分 |
| group($col, $builder, $label) | Group | 分组筛选 |
| findInSet($col, $label) | FindInSet | FIND_IN_SET |
| hidden($name, $value) | Hidden | 隐藏传递 |
| newline() | Newline | 换行 |

### 筛选器模式
```php
$filter->panel();       // 面板模式
$filter->rightSide();   // 右侧滑出模式
```

### 筛选器展示器
```php
$filter->equal('col')->datetime();                     // 日期时间
$filter->like('col')->placeholder('Search...');        // 占位符
$filter->equal('col')->select([1=>'A', 2=>'B']);       // 下拉
$filter->equal('col')->multipleSelect([...]);           // 多选下拉
$filter->equal('col')->radio([...]);                    // 单选
$filter->equal('col')->checkbox([...]);                 // 复选
$filter->equal('col')->selectTable(...)->from(...);    // 弹窗选行
```

### Scope 筛选
```php
$filter->scope('recent', '最近')->where('created_at', '>', Carbon::now()->subDays(7));
$filter->scope('trashed', '回收站')->onlyTrashed();
```

## 行选择器 RowSelector
```php
$grid->rowSelector()
    ->style('primary')
    ->background('#fff')
    ->click(true)        // 点击行选中
    ->check([1,2,3])     // 默认选中
    ->disable([4,5])     // 禁用选中
    ->idColumn('id')
    ->titleColumn('name');
```

## 快捷创建 QuickCreate
```php
$grid->quickCreate(function($form) {
    $form->text('name');
    $form->select('status')->options([...]);
    $form->datetime('created_at');
});
```

## 事件系统
| 事件 | 触发 |
|------|------|
| Events\Fetching | 数据查询前 |
| Events\Fetched | 数据获取后 |
| Events\ApplyFilter | 筛选器应用时 |
| Events\ApplyQuickSearch | 快速搜索时 |
| Events\ApplySelector | 选择器应用时 |
| Events\Exporting | 导出时 |
