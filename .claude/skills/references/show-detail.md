# dcat-plus-admin 数据详情页 (Show) 完整参考

> 源码：`vendor/dcat-plus/laravel-admin/src/Show.php` 及 `Show/` 子目录。命名空间 `Dcat\Admin\Show`（字段 `Show\Field`，工具 `Show\Tools`，布局 `Show\Descriptions`/`Fieldset`/`Panel`/`Row`）。
> 未知字段方法经 `Show\Field::__call` 三级分发：扩展注册(`Show\Field::extend`) → macro → **collect/Str 助手**（数组值走 Collection 方法、字符串值走 Str 方法，如 `->limit(50)`、`->ucfirst()` 自动生效）。

## 一、创建与基本用法

```php
use Dcat\Admin\Show;

// 标准用法：$id 自动从路由解析；Repository / Model / 闭包均可
protected function detail($id)
{
    return Show::make($id, ForumPost::with(['user', 'tag', 'tags']), function (Show $show) {
        $show->field('id');
        $show->field('user.username', '作者');     // 点号关联
        $show->field('title', '标题');
        $show->field('content', '内容')->richText(); // 富文本自动识别渲染
        $show->field('status')->using([1 => '已发布', 0 => '草稿']);
    });
}

// 闭包内读取当前记录（编辑/详情页均可）
if ($show->model()->status == 1) { $show->field('reject_reason'); }

// 分步式 / 面板式容器见下文「布局」
```

## 二、Show 主类方法

| 方法 | 说明 |
|------|------|
| `field($name, $label='')` | 添加字段（`$name` 支持点号关联 `user.username`） |
| `fields(?array $fields)` | 全部字段 Collection；传字段名数组批量取字段 |
| `relation($name, $label, $builder=null)` | 一对多关联展示（内嵌表格，见下文） |
| `relations()` | 全部关联 Collection |
| `html($html)` / `divider()` / `newline()` | 自定义 HTML / 分割线 / 换行 |
| `row(Closure)` | 行布局：`$show->row(function (Show\Row $row) { $row->width(6)->field(...); ... })` |
| `card(Closure)` | Descriptions 卡片布局（见下文） |
| `fieldset(Closure)` | fieldset 分组布局（见下文） |
| `panel()->style('info')->title('标题')` | 面板样式容器；`panel` 后字段挂到 `$show->panel()->field(...)` |
| `tools(Closure)` | 工具栏（见下文「工具栏」） |
| `view($view)` | 自定义视图渲染 |
| `with([$var => $val])` | 传变量到视图 |
| `wrap(Closure)` | 包裹整个内容（如放进 Tab） |
| `disableListButton()` / `disableDeleteButton()` / `disableEditButton()` | 工具栏按钮开关 |
| `showQuickEdit($width, $height)` / `disableQuickEdit()` | 工具栏快捷编辑按钮（弹窗编辑） |
| `resource()` / `setResource($path)` | 资源路径（编辑/删除按钮 URL 依据） |

## 三、字段显示方法（Show\Field）

| 方法 | 说明 |
|------|------|
| `as(Closure)` | 自定义渲染闭包：`->as(fn($v) => strtoupper($v))` |
| `using(array $values, $default=null)` | 枚举映射：`->using([1 => '启用', 0 => '禁用'])` |
| `richText()` | **富文本自动识别渲染**：内容是 HTML（editor 编辑器保存）原样输出、是 Markdown（markdown 编辑器保存）交给 Markdown 组件渲染、纯文本安全兜底。同名字段混用两种编辑器时必用 |
| `unescape()` | HTML 原样输出（等价 `escape(false)`）；确知内容是 HTML 时用 |
| `escape(bool)` | 转义开关（默认转义） |
| `image($server='', $w=200, $h=200)` | 图片（多图自动展开，`data-action='preview-img'` 点击预览） |
| `file($server='', $download=true)` | 文件下载链接 |
| `link($href='', $target='_blank')` | 链接 |
| `label($style='primary')` / `dot($opts,$default)` / `badge($style='blue')` | 标签 / 圆点 / 徽章，常与 `using()` 链用 |
| `json()` | JSON 美化展示 |
| `prepend($v)` / `append($v)` | 前缀 / 后缀内容 |
| `explode($d=',')` | 分隔字符串拆分为多个标签 |
| `bold($color=null)` | 加粗着色 |
| `bool(array $map=[], $default=false)` | 布尔展示（默认 ✓/✗） |
| `view($view, $data=[])` | 自定义视图渲染字段 |
| `wrap(bool)` | 是否包 field 外框（`file()` 等内部已自动关闭） |
| `when($condition, Closure)` | 条件执行 |
| `width($field, $label=2)` | 内容/标签栅格宽度 |
| `dedicatedLine()` | 独占一行（配合 card/fieldset 多列布局） |
| `help($text)` | 字段提示 |
| `value($v)` | 直接设值（配合 `html()` 字段） |

`Show\Field::__call` 魔术：未识别方法自动作为 **collect/Str 助手**应用于值——`->limit(50)`、`->ucfirst()`、`->substr(0, 60)`、`->implode(',')`（数组值）直接可用，无需 as()。

### 富文本与内容识别（v3.0.10+）

```php
$show->field('content', '内容')->richText();   // 自动识别 HTML / Markdown 并渲染

// 底层能力（自定义页面）：
use Dcat\Admin\Support\ContentFormat;
ContentFormat::render($content);   // 返回可直接嵌入的 HTML
ContentFormat::detect($content);   // 'html' | 'markdown'
```

识别规则：块级标签开头或含 `</p>`/`<br`/`&nbsp;`（UEditor 指纹）判 HTML 原样渲染；`#` 标题/围栏代码/列表/链接判 Markdown 渲染；纯文本兜底。**混合内容（Markdown 内嵌 HTML 块）识别为 HTML**，确知格式时用 `unescape()` / `Markdown::make()` 显式指定。

## 四、关联关系

```php
// 一对多：详情页下方内嵌关联表格（需 Repository/模型 with 预加载）
$show->relation('comments', '评论', function (Grid $grid) {
    $grid->column('id')->sortable();
    $grid->column('content')->limit(30);
    $grid->resource(admin_url('blog/comments'));   // 行操作 URL 依据（异步表格同款要求）
    $grid->disableCreateButton();
});

// relation 闭包内 $grid->model() 已自动按关联键过滤

// 一对一：直接点号字段即可（无需 relation）
$show->field('user.username', '作者');
```

## 五、布局

### row 行布局

```php
$show->row(function (Show\Row $row) {
    $row->width(6)->field('name', '姓名');      // 半行
    $row->width(6)->field('age', '年龄');
    $row->field('description', '描述');          // 独占整行
});
```

### card 卡片布局（Descriptions）

```php
$show->card(function (Show\Descriptions $descrs) {
    $descrs->columns(3)                       // 列数（最高 6）
        ->card()                              // 卡片效果
        ->shadow()                            // 卡片阴影
        ->labelWidth('130px')
        ->labelJustifyContent('flex-end')     // 标题对齐（css justify-content 值）
        ->contentJustifyContent('center')     // 内容对齐
        ->contentWhiteSpace()                 // 内容换行
        ->footer('卡片底部')
        ->header('<div class="header-title">卡片头部</div>');

    $descrs->field('name')->help('提示');
    $descrs->field('img', '图片')->image('', 200, 200)->dedicatedLine(); // 独占一行
});
```

### fieldset 分组布局

```php
$show->fieldset(function (Show\Fieldset $field) {
    $field->columns(4)->shadow()->contentWhiteSpace();
    $field->title('基本信息');
    $field->field('name', '名称')->value('值');
    $field->field('custom', '自定义HTML')->html()->value('<h3>HTML内容</h3>');
    $field->field('video', '视频')->video('/a.mp4', '100%', 400)->dedicatedLine();
    $field->field('audio', '音频')->audio('/a.mp3');
});
```

## 六、工具栏 tools()

```php
$show->tools(function (Show\Tools $tools) {
    $tools->disableList();          // 隐藏返回列表按钮
    $tools->disableDelete();
    $tools->disableEdit();
    $tools->disableQuickEdit();
    $tools->showQuickEdit('700px', '600px');  // 快捷编辑按钮（可设弹窗尺寸）
    $tools->append(new MyTool());             // 自定义工具（Action）
    $tools->prepend('<div>HTML</div>');
});
```

## 七、陷阱与已知

- `Show\Field::__call` 未识别方法 = collect/Str 助手：**拼写错误的助手方法不报错、直接返回原值**（如 `->limt(50)`），排查字段"没效果"时先查方法名。
- `richText()` 遇 Markdown 内嵌 HTML 块识别为 HTML；确知格式用 `unescape()` / `Markdown::make()`。
- `relation()` 内嵌表格遵循**异步表格规则**：行操作/创建按钮需 `$grid->setResource()` 显式资源路径（见 grid-system.md「异步表格」节）。
- `image()` 的 `$server` 为空时走 `admin.upload.disk` 生成 URL；外链直接传完整 URL 值即可。
- 工具栏的编辑/删除按钮 URL 依据 `setResource()`/`resource()`（默认当前请求路径），自定义页面用 Show 时注意显式设置。
