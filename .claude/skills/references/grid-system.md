# dcat-plus-admin Grid 完整参考

> 源码：`vendor/dcat-plus/laravel-admin/src/Grid.php` 及 `Grid/` 子目录。命名空间 `Dcat\Admin\Grid`。
> 任何未知列方法经 `Column::__call` 分发：先查注册的 displayer，再查 macro，否则尝试 `Illuminate\Support\Str`（字符串值）或 `Collection`（数组值）方法。

## 一、Grid 创建与基本用法

```php
use Dcat\Admin\Grid;

// 标准用法：Repository / Model / Builder 均可，底层自动包装为 Repository
Grid::make(new MemberUser(), function (Grid $grid) {
    $grid->column('id')->sortable();
    $grid->column('name', '名称');
    $grid->filter(function (Grid\Filter $filter) { /* ... */ });
    $grid->actions(function (Grid\Displayers\Actions $actions) { /* ... */ });
});

// 带预设查询/关联
Grid::make(User::with(['profile'])->where('active', 1), function (Grid $grid) { /* ... */ });
```

**Repository 自定义查询字段**（减少 SELECT *）：
```php
class Movie extends EloquentRepository {
    protected $eloquentClass = MovieModel::class;
    public function getGridColumns() {
        return [$this->getKeyName(), 'name', 'title', 'created_at']; // 只查这些列
    }
}
```

## 二、Grid 主类方法

### 列管理
| 方法 | 说明 |
|------|------|
| `column($name, $label='')` | 添加列；`$name` 支持点号关联 `profile.age`。字段名与 Grid 方法冲突时（如 `title`）必须用此方法 |
| `columns('a','b',...)` / `columns(['a'=>'A','b'=>'B'])` | 批量添加 |
| `allColumns()` | 获取所有列（含被隐藏/移除的） |
| `number($label='#')` | 添加从 1 起的行号列 |
| `prependColumn($field, $label)` | 在最前插入列 |
| `dropColumn($column)` | 移除列（可传 Column 实例或字段名） |
| `getColumnNames()` | 列名字段名数组 |
| `$grid->field` / `$grid->field('label')` | 魔术属性/方法等同于 `column()` |

### 查询模型 `$grid->model()` (Grid\Model)
`@mixin Builder`，支持链式 `where/orderBy/with/onlyTrashed/...`，未识别方法一律记为延迟查询。

| 方法 | 说明 |
|------|------|
| `where/orderBy/with/...` | 任意 Eloquent 链式调用，延迟执行 |
| `orderBy('profile.age')` | 关联排序（仅 hasOne/belongsTo） |
| `onlyTrashed()` | 软删除回收站数据 |
| `setConstraints(['k'=>'v'])` | 把参数附加到创建/编辑/分页 URL |
| `getConstraints()` | 取约束 |
| `usePaginate(bool)` / `simple(bool)` | 是否分页 / 简单分页（不查总数） |
| `getCurrentPage()` / `getPerPage()` | 当前页 / 每页数（不分页返回 null） |
| `getSort()` | 返回 `[$col, 'asc'\|'desc', $cast]`，无排序 `[null,null,null]` |
| `getQueries()` | 全部延迟查询的 unique Collection（header/footer 复用查询时用） |
| `filter()` | 返回 `Grid\Filter` 对象 |
| `makePaginator($total, $data, $url=null)` | 外部数据源构造分页器 |
| `resetOrderBy()` | 移除 orderBy 查询 |

### 表格样式与滚动
| 方法 | 说明 |
|------|------|
| `withBorder(true)` | 边框模式（自动关闭 tableCollapse） |
| `tableCollapse(true)` | 紧凑表格（默认开）。关闭传 `false` |
| `scrollbarX(true)` | 显示横向滚动条（默认不显示） |
| `scrollbar(true)` | 设 `table_scrollbar` 选项 |
| `tableHeaderFixed(true)` | 固定表头方式一：加 `fixed-header-table` 类，基于窗口 |
| `gridHeaderFixed(true)` | 固定表头方式二：容器限高滚动，加 `fixed-header-grid` 类 |
| `setTableWrapperStyle('max-height:680px;overflow-y:auto;')` | 自定义容器样式 |
| `addTableClass(['table-text-center'])` | 加 table css 类 |
| `columnLink(true)` | 列链接样式 |
| `setActionClass(Grid\Displayers\Actions::class)` | 切换行操作渲染：`Actions`（图标）/`DropdownActions`（下拉）/`ContextMenuActions`（右键）。配置项 `admin.grid.grid_action_class` |

### 头部/脚部
| 方法 | 说明 |
|------|------|
| `header($content)` | 闭包/字符串/Renderable；闭包签名 `fn($collection, $grid)`，`$collection` 为当前页数据 |
| `footer($content)` | 同上 |
| `headerCenterStyle($width='400px')` | header 居中圆角样式 |
| `headerStyle($css)` | 自定义 header 容器 style |
| `wrap(Closure $closure)` | 用闭包包裹整个 grid 视图（如放进 Tab） |

header/footer 全量统计示例（复用 grid 查询条件）：
```php
$grid->header(function ($collection) use ($grid) {
    $query = Model::query();
    $grid->model()->getQueries()->unique()->each(function ($v) use (&$query) {
        if (in_array($v['method'], ['paginate','get','orderBy','orderByDesc'], true)) return;
        $query = call_user_func_array([$query, $v['method']], $v['arguments'] ?? []);
    });
    $total = $query->sum('amount');
    return "<div style='padding:10px'>总收入：{$total}</div>";
});
```

### 创建按钮
| 方法 | 说明 |
|------|------|
| `disableCreateButton(true)` / `showCreateButton(true)` | 关闭/显示 |
| `createMode('default'\|'dialog')` | 创建模式 |
| `enableDialogCreate()` | 弹窗创建表单 |
| `setDialogFormDimensions($w, $h)` | 弹窗宽高，默认 `['700px','670px']` |
| `setResource($path)` | 改创建/编辑按钮路由前缀（经 `admin_url`） |
| `getCreateUrl()` / `getEditUrl($key)` / `resource()` | URL 获取 |
| `urlWithConstraints($url)` | URL 附加约束 query |

### 行选择器 `rowSelector()` (Grid\Tools\RowSelector)
```php
$grid->rowSelector()
    ->style('primary')              // default/primary/success/info/danger/purple/inverse
    ->background('#fff')            // 选中行背景色
    ->click(true)                   // 点击行任意位置选中（默认 false）
    ->check(function ($row) { return $row->state === 1; })  // 默认选中（闭包 or _index 数组）
    ->disable(function ($row) { return $row->state === 0; })// 禁用选中
    ->idColumn('id')                // 选中值字段（默认主键）
    ->titleColumn('full_name');     // 选中标题字段（默认 name/title/username）
$grid->disableRowSelector(true) / showRowSelector(true)
```

### 分页 (HasPaginator)
| 方法 | 说明 |
|------|------|
| `paginate($perPage=20)` | 每页条数 |
| `simplePaginate(true)` | 简单分页（不查总数，性能好） |
| `perPages([10,20,30,50,100])` | 分页选择器选项，默认 `[10,20,30,50,100,200]` |
| `disablePerPages()` | 关闭分页选择器 |
| `disablePagination(true)` / `showPagination(true)` | 关闭/显示分页 |
| `setPaginatorClass($class)` | 自定义分页器类 |

### 工具栏 (HasTools / Tools)
| 方法 | 说明 |
|------|------|
| `tools(Closure\|AbstractTool\|string\|array)` | 左工具栏。闭包签名 `fn(Grid\Tools $tools)` |
| `rightTools(...)` | 右工具栏 |
| `disableToolbar(true)` / `showToolbar(true)` | |
| `disableRefreshButton(true)` / `showRefreshButton(true)` | |
| `disableBatchActions(true)` / `showBatchActions(true)` | |
| `disableBatchDelete(true)` / `showBatchDelete(true)` | |
| `batchActions(Closure\|BatchAction\|BatchAction[])` | 自定义批量操作 |
| `toolsWithOutline(true)` | 工具按钮 outline 样式（默认开）。单按钮关闭加 class `disable-outline` |

自定义工具栏按钮继承 `Grid\Tools\AbstractTool`（是 `Actions\Action` 子类），可定义 `title()`/`confirm()`/`handle(Request)`/`parameters()`/`script()`/`render()`。

### 筛选器开关
`disableFilter(true)`/`showFilter(true)`、`disableFilterButton(true)`/`showFilterButton(true)`、`expandFilter()`。

### 操作按钮开关 (HasActions)
`disableActions/showActions`、`disableEditButton/showEditButton`、`disableViewButton/showViewButton`、`disableDeleteButton/showDeleteButton`、`disableQuickEditButton/showQuickEditButton`。

### 异步与轮询
```php
$grid->async();                 // 启用异步渲染（toolbar 以下异步加载，适合多列多组件卡顿场景）
$grid->async(false);
$grid->isAsyncRequest();        // 是否当前异步请求
$grid->polling(5);              // 每 5 秒自动刷新（依赖 async）
```
> 多表格同页时无法使用 async。

### 其它
| 方法 | 说明 |
|------|------|
| `title($t)` / `description($d)` | 标题/描述 |
| `setName($name)` | 多 grid 同页时设不同名，避免 query 参数冲突（自动加前缀） |
| `show(true)` / `view($view)` | 是否渲染 / 自定义视图 |
| `option($key, $val)` / `option($key)` | 读写原始 options |
| `setKeyName($name)` / `getKeyName()` | 主键名（默认 `id`） |
| `fixColumns(int $head, int $tail=-1)` | 固定列，见下 |
| `combine($column, array $cols, $label=null)` | 复杂表头，见下 |
| `treePanel(Closure, $disable=true)` | 左树右表，见下 |

## 三、Column 列方法（HasHeader / HasDisplayers / CanFormatState）

### 表头与排序
| 方法 | 说明 |
|------|------|
| `sortable($columnName=null, $cast=null)` | 可排序。可指定排序字段与 MySQL CAST 类型（如 `'SIGNED'`） |
| `width('300px')` / `width('15%')` | 列宽 |
| `setHeaderAttributes(['style'=>'color:#5b69bc'])` | `<th>` 属性 |
| `setAttributes(['style'=>'font-size:14px'])` | `<td>` 属性 |
| `style('color:red')` | 内联 style |
| `help('提示', 'green', 'top')` | 表头提示图标。style: green/blue/red/purple；placement: top/left/right/bottom |
| `hide()` / `hidden($cond)` / `visible($cond)` | 隐藏/条件隐藏/条件显示，`$cond` 可为闭包 `fn($column)=>bool` |
| `filter(...)` / `filterByValue()` | 列头筛选，见「列过滤器」 |

### 值映射与状态格式化
| 方法 | 说明 |
|------|------|
| `display(Closure\|string)` | 自定义显示。闭包绑定当前行对象，签名 `fn($value, $column)`；可 `$this->字段` 访问同行其它字段。非闭包作为字面输出 |
| `using(array $map, $default=null)` | 值→文本映射 `[0=>'禁用',1=>'启用']` |
| `bool(array $map=[], $default=false)` | 布尔图标 ✓/✗，可传值映射 `['Y'=>true]` |
| `bold($color=null)` | 加粗，默认色 `Admin::color()->dark80()` |
| `dot(array $map, $default='default')` | 彩色圆点前缀，按原值取色 |
| `prepend($val)` / `append($val)` | 前/后追加。闭包签名 `fn($value, $original)` 绑定行 |
| `prefix($str)` / `suffix($str)` | 前缀/后缀（CanFormatState） |
| `explode($sep=',')` | 字符串分割成数组，便于后续 `label()` 等 |

**日期/时间（CanFormatState，底层 Carbon）**：
| 方法 | 默认格式 |
|------|---------|
| `date($fmt=null, $tz=null)` | `Y-m-d` |
| `dateTime($fmt=null, $tz=null)` | `Y-m-d H:i:s` |
| `time($fmt=null, $tz=null)` | `H:i:s` |
| `since($tz=null)` | "3小时前" (diffForHumans) |
| `timezone($tz)` | 设时区 |

类静态属性可改默认格式：`Grid::$defaultDateTimeDisplayFormat = 'Y-m-d H:i'`。

### 视图与字符串/集合透传
| 方法 | 说明 |
|------|------|
| `view('admin.fields.x')` | Blade 视图渲染，变量 `$model`(行)/`$value`(值)/`$name`(字段) |
| `limit($len=100, $end='...')` | 截断。字符串用 strLimit+展开收起；数组截前 N 项 |
| Str 透传 | `upper()` `lower()` `ucfirst()` `title()` `substr($s,$l)` `words($n,$end)` `camel()` `snake()` `studly()` `slug()` `ascii()` `finish($cap)` |
| Collection 透传 | `pluck('name')` `map(fn)` `implode('-')` 等（数组/Arrayable 值） |

链式混合：
```php
$grid->column('tags')->explode()->map('ucwords')->implode('-')->label();
```

### 条件显示 `if() / then() / else() / end()`
```php
$grid->column('status')
    ->if(fn($col) => $col->getValue() === 1)
        ->using([1=>'正常'])->label('success')
    ->else()
        ->using([0=>'禁用'])->label('danger')
    ->end()
    ->modal('详情', fn() => $this->note);  // end() 之后无条件方法恢复
```
> `if` 仅影响列显示，表头操作（sortable/filter/help）不可用。条件闭包绑定原行模型，`$this->字段` 可访问其它字段。

### 行内编辑
| 方法 | 说明 |
|------|------|
| `editable($refresh=false)` / `input($opts=[])` | 行内文本输入。`$opts['mask']` 输入掩码 |
| `textarea($opts=[])` | 行内多行，默认 `rows=5` |
| `switch($color='', $refresh=false)` | 行内开关。`$color` 可闭包 |
| `switchGroup($cols=[], $color='', $refresh=false)` | 多开关。`$cols` 为列名数组或 `[col=>label]`，可闭包 |
| `select($opts=[], $refresh=false)` | 行内下拉。`$opts` 可闭包 `fn() => User::pluck(...)` |
| `radio($opts=[], $refresh=false)` / `checkbox($opts=[], $refresh=false)` | 行内单选/多选 |

> 行内编辑列必须在 form 表单定义相同字段。`switchGroup` 默认存 0/1。所有方法第二参 `$refresh=true` 表示编辑后刷新页面。

## 四、Column 显示器（Displayer）完整表

通过 `$column->method(...)` 调用（`Column::__call` 分发）。颜色均可传别名 `success`/`danger` 或 `Admin::color()->xxx()` 或 hex `#222`。

| 方法 | 签名 | 说明 |
|------|------|------|
| `image` | `image($server='', $w=200, $h=200)` | 图片缩略图，点击预览。支持多值（数组/JSON）。URL 解析：合法 URL/data:image → `$server`+path → `Storage::disk(config('admin.upload.disk'))` |
| `label` | `label($style='primary', $max=null)` | 标签。`$style` 为别名或 `[值=>色]` 映射（含 `'default'` 兜底）；`$max` 截断数组 |
| `badge` | `badge($style='primary', $max=null)` | 药丸徽章，同 label |
| `button` | `button($style='primary')` | 按钮样式，`$style` 可数组多类 |
| `link` | `link($href='', $target='_blank', $view='')` | 超链接。`$href` 闭包 `fn($value)`；空则用列值 |
| `progressBar` | `progressBar($style='primary', $size='sm', $max=100)` | 进度条，值为百分比 |
| `icon` | `icon($size=14, $color=null)` | `<i class>` 图标，`fa-*` 自动补 `fa fa-fw` |
| `expand` | `expand($callbackOrButton=null)` | 展开行。闭包返回 HTML/Renderable；或 `LazyRenderable` 类名/实例异步加载（payload 自动注入 `key`）；字符串作按钮文字 |
| `modal` | `modal($title='', $callback=null)` | 模态框。链式 `title()` `xl()` `icon()`。`$callback` 同 expand |
| `showTreeInDialog` | `showTreeInDialog($callbackOrNodes=null)` | 弹窗 jstree。闭包内 `->nodes()/title()/area()/checkAll()/setIdColumn()/...` |
| `qrcode` | `qrcode($formatter=null, $w=150, $h=150)` | 二维码。`$formatter` 闭包转换内容 |
| `downloadable` | `downloadable($server='', $disk=null)` | 下载链接，支持多值 |
| `copyable` | `copyable()` | 点击复制图标 |
| `orderable` | `orderable()` | 上下拖拽排序。需模型 `ModelTree` 或 `SortableTrait+Sortable` |
| `table` | `table($titles=[])` | 嵌套表格（值为二维数组） |
| `tree` | `tree($showAll=false, $sortable=true)` | 树状表格（懒加载子节点）。模型须 `ModelTree` |

异步展开示例（LazyRenderable）：
```php
class UserPosts extends \Dcat\Admin\Support\LazyRenderable {
    public function render() { /* $this->key 为行 ID */ }
}
$grid->column('')->display('查看')->expand(UserPosts::class);
$grid->column('')->display('详情')->modal('文章', Post::make(['type'=>1]));
```

## 五、行操作 `$grid->actions(...)`

```php
$grid->actions(function (Grid\Displayers\Actions $actions) {
    $actions->disableDelete();
    $actions->disableEdit();
    $actions->disableQuickEdit();
    $actions->disableView();
    // 取数据
    $id    = $actions->getKey();
    $email = $actions->row->email;          // 行对象
    $arr   = $actions->row->toArray();
    // 追加自定义
    $actions->append('<a href="..."><i class="fa fa-eye"></i></a>');
    $actions->prepend(new CheckRow());       // 自定义 RowAction
    // 分组下拉
    $actions->group(function ($g) {
        $g->append(new MyAction($this->id));
    }, 'feather icon-more-vertical', '更多操作');
});
```

### 自定义行操作类（继承 `Dcat\Admin\Grid\RowAction`）
```php
class Restore extends RowAction {
    protected $title = '恢复';
    public function __construct(string $model = null) { $this->model = $model; } // 参数须默认值
    public function handle(Request $request) {
        $key = $this->getKey();
        $model = $request->get('model');
        $model::withTrashed()->findOrFail($key)->restore();
        return $this->response()->success('已恢复')->refresh();
    }
    public function confirm() { return ['确定恢复吗？']; }            // array|string|void
    public function parameters() { return ['model' => $this->model]; }
}
// 挂载
$grid->actions([new Restore(Post::class)]);
```
可用方法：`title()` `confirm()` `handle(Request)` `parameters()` `html()` `script()` `getKey()` `row`/`$this->row->字段` `modelClass()` `getColumnName()`。

## 六、批量操作 `$grid->batchActions(...)`

```php
$grid->batchActions(function (Grid\Tools\BatchActions $batch) {
    $batch->disableDelete();
    $batch->add(new ReleasePost('发布', 1));
    $batch->divider();
});
// 或数组（含 ActionDivider 分割线）
use Dcat\Admin\Grid\Tools\ActionDivider;
$grid->batchActions([new A(), new ActionDivider(), new B()]);
```

### 自定义批量操作类（继承 `Dcat\Admin\Grid\BatchAction`）
```php
class ReleasePost extends BatchAction {
    public function __construct($title = null, $action = 1) { /* 须默认值 */ }
    public function handle(Request $request) {
        $keys = (array) $this->getKey();    // 选中 ID 数组
        // ...
        return $this->response()->success('done')->refresh();
    }
    public function actionScript() {
        // JS 前置回调，return false 中断；获取选中 ID 用占位符
        return $this->getSelectedKeysScript();
    }
}
```

## 七、过滤器 `$grid->filter(...)`

### 布局与全局
```php
$grid->filter(function (Grid\Filter $filter) {
    $filter->panel();              // 面板模式（默认 rightSide 右侧滑出）
    $filter->expand();             // 默认展开；expand(false) 收起
    $filter->withoutInputBorder(); // 无边框输入
    $filter->style('padding:0');   // 或 padding(上,右,下,左) / noPadding()
    $filter->scope('trashed','回收站')->onlyTrashed();  // 规格范围切换
});
```

### 查询类型（全）
| 方法 | SQL |
|------|-----|
| `equal($c,$l)` | `= value` |
| `notEqual($c,$l)` | `!= value` |
| `like($c,$l)` | `LIKE %value%` |
| `ilike($c,$l)` | `ILIKE %value%` |
| `startWith($c,$l)` | `LIKE value%` |
| `endWith($c,$l)` | `LIKE %value` |
| `gt($c,$l)` / `lt` / `ngt`(`<`) / `nlt`(`>=`) | 比较 |
| `between($c,$l)` | BETWEEN |
| `in($c,$l)` / `notIn($c,$l)` | IN / NOT IN |
| `where($c, Closure, $l)` | 自定义闭包，`$this->input` 取输入 |
| `whereBetween($c, Closure, $l)` | 自定义 BETWEEN |
| `date/day/month/year($c,$l)` | DATE()/DAY()/... |
| `findInSet($c,$l)` | FIND_IN_SET(value, col) |
| `group($c, Closure, $l)` | 分组筛选（gt/lt/eq/like... 多操作符） |
| `hidden($name, $value)` | 隐藏传参 |
| `newline()` | 换行 |

`where` 自定义（含关联）：
```php
$filter->where('mobile', function ($q) {
    $q->whereHas('profile', function ($q) {
        $q->where('mobile', 'like', "%{$this->input}%");
    });
}, '手机号');
```

### 表单控件（链式修饰）
```php
$filter->equal('id')->placeholder('请输入');
$filter->equal('status')->select([0=>'禁用',1=>'启用']);
$filter->in('tag')->multipleSelect([...]);
$filter->between('created_at')->datetime()->width(5);
$filter->equal('amount')->decimal([])->toTimestamp();

// datetime 衍生
->datetime($opts) ->date() ->time() ->day() ->month() ->year()
// 输入格式
->url() ->email() ->integer() ->ip() ->mac() ->mobile() ->decimal([]) ->currency([]) ->percentage([]) ->inputmask([])
// 通用
->width(3)                 // 1-12 网格或 '250px'
->default($val)            // between 用 ['start'=>..,'end'=>..]
->ignore()                 // 提交时忽略此项
// 弹窗选行（渲染类继承 Grid\LazyRenderable）
->selectTable(UserTable::make())->title('选用户')->dialogWidth('50%')->model(User::class,'id','name')
->multipleSelectTable(...)->max(10)->model(...)
```

### 自定义过滤器
继承 `Grid\Filter\AbstractFilter`，实现 `condition($inputs)`（用 `buildCondition()`），`bootstrap.php` 注册 `Filter::extend('name', Class::class)`。

## 八、列过滤器（表头筛选图标）

```php
use Dcat\Admin\Grid\Column\Filter\Equal;
// 默认 Equal
$grid->column('name')->filter();
$grid->column('name')->filter(Equal::make()->placeholder('姓名'));
// 各类型（Grid\Column\Filter\*）
->filter(\Dcat\Admin\Grid\Column\Filter\Like::make())      // LIKE %x%
->filter(\Dcat\Admin\Grid\Column\Filter\StartWith::make())
->filter(\Dcat\Admin\Grid\Column\Filter\Gt::make())        // > / Lt / Ngt(<=) / Nlt(>=)
->filter(\Dcat\Admin\Grid\Column\Filter\In::make([0=>'A',1=>'B']))  // 多选
->filter(\Dcat\Admin\Grid\Column\Filter\Between::make())
// 日期链式
->filter(Equal::make()->datetime())        // 或 ->date()/->time()
->filter(Between::make()->datetime()->toTimestamp())
// 点击列值即筛选（隐藏表头图标）
->filterByValue();                         // = Equal::make()->valueFilter()->hide()
// 指定查询字段
->filter(Equal::make()->setColumnName('user.name'));   // 支持关联 / json->key
```

## 九、快捷搜索 `$grid->quickSearch(...)`

```php
$grid->quickSearch('title','desc','content');      // LIKE %query%
$grid->quickSearch(['user.name','content']);       // 关联（v1.7+）
$grid->quickSearch()->placeholder('搜索...')->width(20);
$grid->quickSearch()->auto(false);                 // 需回车提交（默认 1200ms 防抖自动）

// 自定义
$grid->quickSearch(function ($model, $query) {
    $model->where('title', $query)->orWhere('desc','like',"%{$query}%");
});
```

**无参 = 快捷语法 DSL**（空格 AND，`|` 前缀 OR，中文 label 可作字段名）：

| 语法 | 查询 |
|------|------|
| `title:foo` / `title:!foo` | `=` / `!=` |
| `rate:>10` `rate:>=10` `rate:<10` `rate:<=10` | 比较 |
| `status:(1,2,3)` / `status:!(1,2,3)` | whereIn / whereNotIn |
| `score:[1,10]` | whereBetween |
| `created_at:date,2019-06-08` | whereDate（另有 time/day/month/year） |
| `content:%Laud%` / `content:Laud%` | LIKE |
| `username:/song/` | REGEXP |
| `updated_at:"2019-06-08 09:57:45"` | 含空格用双引号 |

## 十、规格筛选器 `$grid->selector(...)`

类似电商规格选择，渲染为可点击的选项条。
```php
$grid->selector(function (Grid\Tools\Selector $selector) {
    $selector->select('brand', '品牌', [1=>'华为',2=>'小米']);      // 多选（默认）
    $selector->selectOne('status', '状态', [0=>'下架',1=>'上架']); // 单选
    // 自定义查询
    $selector->select('price', '价格', ['0-999','1000+'], function ($q, $value) {
        $q->whereBetween('price', [[0,999],[1000,999999]][current($value)]);
    });
    $selector->select('brand.id', '品牌', [...]); // 关联字段
});
```

## 十一、树状表格 / 树面板

**树状表格**（大数据量，分页/点击加载，不支持拖拽）：
```php
// 模型 use ModelTree（必要字段 id/parent_id/title，order 可选）
$grid->column('title')->tree();          // 默认懒加载子节点
$grid->column('title')->tree(true);      // 一次性加载全部
$grid->column('title')->tree(false,false);// 关闭排序
```
> 使用 `filter`/`column filter`/`quickSearch` 会取消「只查顶级」行为；`筛选器(scope)` 不受影响。

**左树右表 `treePanel`**（独立 `Tree` 面板 + Grid）：
```php
$grid->treePanel(function () {
    return new \Dcat\Admin\Tree(new Category());
}, $disable = true);   // $disable=true 时关闭树上的增删改/拖拽/刷新，只读
```

## 十二、复杂表头 `$grid->combine(...)`

```php
$grid->combine('avgCost', ['avgMonthCost','avgQuarterCost','avgYearCost']);
$grid->combine('top', ['topCost','topVisit'])->style('color:#1867c0');
// 二级表头单独设样式
$grid->column('avgQuarterCost')->setHeaderAttributes(['style'=>'color:#5b69bc']);
```
- 第一参：一级表头字段名（label 自动翻译）；第二参：≥2 个二级字段名。

## 十三、固定列 `$grid->fixColumns(...)`

```php
$grid->fixColumns(2);     // 前 2 列固定，尾部固定最后 1 列
$grid->fixColumns(2, -2); // 前 2 后 2
```
> dcatplus 1.4.5+：不再自动 `resetActions()`，支持折叠。固定列会自动开启 `tableCollapse`。

## 十四、隐藏列与列选择器 (CanHidesColumns)

```php
$grid->showColumnSelector();                      // 显示列选择器（默认不显示）
$grid->hideColumns(['field1','field2']);          // 默认隐藏
$grid->hideColumnsWhen(function () { return request('simple'); }); // 条件隐藏
// 单列条件隐藏
$grid->column('type')->visible(fn() => false);
```
存储配置（`config/admin.php`）：`grid.column_selector.store` = `SessionStore::class` 或 `CacheStore::class`（含 `store_params`）。

## 十五、汇总行 (HasSummarizer)

```php
$grid->disableSummarizers(true);            // 关闭汇总
$grid->showSummarizerStatus(true);          // 显示汇总状态
// 注：disableSummarizerThisPage / disableSummarizerAllPage 为占位空实现
```

## 十六、快捷创建 `$grid->quickCreate(...)`

表头处内嵌表单快速创建（**每一项须在 form 表单定义同字段同类型**）。
```php
$grid->quickCreate(function (Grid\Tools\QuickCreate $create) {
    $create->text('name', '名称');
    $create->select('status')->options([0=>'禁用',1=>'启用']);
    $create->datetime('created_at');
    $create->action('auth/users');   // 提交地址（v1.4+）
    $create->method('POST');
});
// 可用：text hidden email ip url password mobile integer select multipleSelect tags datetime time date
```

## 十七、导出 / 导入

```php
// 导出（默认 Easy Excel，需 composer require dcat/easy-excel；默认不开启）
$grid->export();
$grid->export(['id'=>'ID','name'=>'名称']);           // 设列标题（数组当 titles）
$grid->export()->xlsx()->filename('用户')->titles([...]);
$grid->export()->csv();  // 或 ods()
$grid->export()->rows(function ($rows) { /* 处理每行 */ return $rows; });
$grid->export()->disableExportAll()->disableExportSelectedRow()->disableExportCurrentPage();

// 自定义 Exporter：继承 Grid\Exporters\AbstractExporter，实现 export()
$grid->export((new MyExporter())->filename('用户'));

// 导入（Since 1.3.0）
$grid->import();
```

## 十八、异步渲染

```php
$grid->async();            // 启用
$grid->async(false);       // 禁用
if ($grid->isAsyncRequest()) { /* 异步请求分支 */ }
```

## 十九、事件系统

```php
// 实例事件（$grid->listen）
$grid->listen(Grid\Events\Fetching::class, function ($grid) { /* 查询前 */ });
$grid->listen(Grid\Events\Fetched::class, function ($grid, Collection $rows) {
    $rows->transform(fn($r) => $r['name'] = $r['first'].' '.$r['last']);
});
$grid->listen(Grid\Events\ApplyFilter::class, function ($grid, array $conditions) {});
$grid->listen(Grid\Events\ApplyQuickSearch::class, function ($grid, $input) {});
$grid->listen(Grid\Events\ApplySelector::class, function ($grid, array $input) {});
$grid->listen(Grid\Events\Exporting::class, function ($grid) {});

// 全局（bootstrap.php）
Grid::resolving(function (Grid $grid) { /* 每个 grid 实例化时 */ });
Grid::resolving(function (Grid $grid) { /*...*/ }, true);   // 只监听一次
Grid::composing(function (Grid $grid) { /* 渲染前 */ });

// 行回调（取数据后）
$grid->rows(function (Collection $rows) {
    $rows->first()->setAttributes(['name'=>'...']);
});
```
> 全局禁用后单实例开启：`$grid->disableActions(false)`。

## 二十、行 Row 对象

```php
$row->getKey();            // 主键值
$row->model();             // Fluent|Model 原始数据
$row->name;                // 取字段（__get）
$row->setAttributes([...]); // <tr> 属性
$row->style('color:red');  // <tr> style
$row->column('field');     // 取已渲染列值
$row->toArray();
```

## 二十一、字段翻译

- 文件：`lang/{语言}/{控制器名-中划线}.php`，结构 `['fields'=>['name'=>'名称',...]]`
- 自动：`$grid->name()` 自动读翻译；显式 `admin_trans_field('name')`
- 改文件名：`protected $translation='user1';` 或 `Admin::translation('user1');`
- 公共字段放 `lang/{语言}/global.php` 的 `fields`

## 二十二、扩展列功能

**扩展显示方法**（`app/Admin/bootstrap.php`）：
```php
// 匿名函数
Grid\Column::extend('color', function ($value, $color) {
    return "<span style='color:$color'>$value</span>";
});
// 类（继承 Grid\Displayers\AbstractDisplayer）
Grid\Column::extend('popover', Popover::class);
// class Popover extends AbstractDisplayer { public function display($placement='left') { $this->value/$this->row/$this->getKey()/$this->resource(); } }
```
**扩展表头**（macro）：
```php
Grid\Column::macro('myHeader', function ($p1, $p2=null) {
    return $this->addHeader(new MyHeader($this, $p1, $p2)); // 须实现 Renderable
});
```
> 扩展后 `php artisan admin:ide-helper` 生成 IDE 提示。

## 二十三、多 Grid 同页

```php
$grid->setName('name1');   // 所有 query 前缀加 name1_：page/per_page/_sort/_columns_/_search_/_selector/_scope_/_parent_id_ 等
```

## 二十四、完整控制器示例

```php
use Dcat\Admin\Grid;

protected function grid()
{
    return Grid::make(new MemberUser(), function (Grid $grid) {
        $grid->column('id')->sortable();
        $grid->column('avatar','头像')->image('', 44, 44);
        $grid->column('username');
        $grid->column('status')->using(MemberUser::$status_arr)->label([0=>'danger',1=>'success']);

        $grid->model()->orderBy('id', 'desc');
        $grid->tableCollapse()->addTableClass(['table-text-center']);
        $grid->enableDialogCreate()->setDialogFormDimensions('50%','50%');
        $grid->quickSearch(['username','phone','email'])->placeholder('用户名/手机/邮箱');
        $grid->setActionClass(Grid\Displayers\Actions::class);
        $grid->fixColumns(3);

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
            $actions->append('<a href="'.url('/view/'.$actions->getKey()).'">查看</a>');
        });

        $grid->filter(function (Grid\Filter $filter) {
            $filter->panel();
            $filter->expand(false);
            $filter->like('username')->placeholder('用户名');
            $filter->equal('status')->select(MemberUser::$status_arr);
            $filter->between('created_at')->datetime()->width(5);
        });
    });
}
```

## 配置项速查（`config/admin.php` → `grid`）

| 键 | 默认 | 说明 |
|----|------|------|
| `grid_action_class` | `Displayers\Actions` | 行操作渲染类 |
| `batch_action_class` | `BatchActions` | 批量操作类 |
| `paginator_class` | `Tools\Paginator` | 分页器类 |
| `actions.batch_delete` | `BatchDelete` | 批量删除操作类 |
| `actions.view/edit/quick_edit/delete` | 对应类 | 各默认行操作类 |
| `column_selector.store` | `SessionStore` | 列选择器存储 |
| `column_selector.store_params` | `[]` | 存储参数 |

## option() 内部键

`pagination` `filter` `actions` `quick_edit_button` `edit_button` `view_button` `delete_button` `row_selector` `create_button` `bordered` `table_collapse` `toolbar` `create_mode` `dialog_form_area` `table_class` `scrollbar_x` `actions_class` `batch_actions_class` `paginator_class` `summarizers` `column_link` `table_wrapper_style` `show_column_selector` `headerStyle`
