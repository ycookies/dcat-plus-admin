# dcat-plus-admin · Widget 组件完整指南

> 适用版本：dcat-plus/laravel-admin 3.x。本文覆盖 **Widget 基类机制 + 40+ 内置组件 API + 异步/交互组件 + 图表 + 自定义 Widget** 全流程。
> Widget 是可独立渲染的 UI 单元（卡片、弹窗、表格、图表……），实现 `Illuminate\Contracts\Support\Renderable`，`echo` 或传给 `Content::body()` 即输出。

---

## 目录

- [一、Widget 基类与渲染机制](#一widget-基类与渲染机制)
- [二、基类通用方法（所有 Widget 共享）](#二基类通用方法所有-widget-共享)
- [三、容器型 Widget（Card / Box / Modal / Tab / Dropdown / Collapse）](#三容器型-widget)
- [四、内容展示型 Widget](#四内容展示型-widget)
- [五、dcat-plus 新增 Widget](#五dcat-plus-新增-widget)
- [六、异步 / 交互型 Widget](#六异步--交互型-widget)
- [七、图表 Widget](#七图表-widget)
- [八、Metrics 数据统计卡片](#八metrics-数据统计卡片)
- [九、自定义 Widget](#九自定义-widget)
- [十、易错点与已知问题](#十易错点与已知问题)

---

## 一、Widget 基类与渲染机制

基类：`Dcat\Admin\Widgets\Widget`（抽象），`implements Renderable`，位于 `src/Widgets/Widget.php`。

```
echo $widget  →  __toString()  →  render()
                                  ├─ requireAssets()   // 从 static $js/$css 注入 Admin::js/css
                                  ├─ class(getElementClass(), true)
                                  ├─ html()  ─┬─ 有 $view: view($view, variables()) 渲染 Blade
                                  │           │           └─ <script> 被抽离到 $this->script
                                  │           └─ 无 $view: 子类重写返回内联 HTML
                                  └─ withScript()      // $this->script 推入 Admin::script()
```

### 核心设计点

1. **`make(...)` 静态工厂**：`Card::make('标题', $content)` ≡ `new Card(...)`。
2. **`__call` 魔术方法**：调用任意方法名 → 设置/获取 HTML 属性。`->title('x')` 等价于设 `title="x"` 属性；`->title()`（无参）取属性值。`class()`/`style()` 是特殊处理（追加语义）。
3. **`formatRenderable()`**：容器型 Widget 接收内容时，自动把 `LazyGrid` 包成 `LazyTable`、`LazyRenderable` 包成 `Lazy` —— 这就是 `Card::make($lazyTable)` 能直接异步渲染的原因。
4. **`$view` + `defaultVariables()` 模式**：子类设 `$view` 视图、重写 `defaultVariables()` 返回模板变量，基类自动渲染。`defaultVariables()` 里**必须返回 `'attributes' => $this->formatHtmlAttributes()`**，否则 `class()/id()/style()` 链不生效（见[第十节](#十易错点与已知问题)已知 Bug）。
5. **资源声明**：子类声明 `public static $js = [...]` / `$css = [...]`，渲染时自动 `Admin::js()/css()`。

### 三种使用方式

```php
// 1. echo / 字符串插值（最常用）
echo Card::make('标题', $content);

// 2. 传给 Content
return $content->body(Card::make(...))->row(Alert::make(...));

// 3. 嵌套（容器接收 Renderable/Closure）
Card::make('T', Tab::make()->add('页1', $a)->add('页2', $b));
```

---

## 二、基类通用方法（所有 Widget 共享）

| 方法 | 作用 |
|------|------|
| `static make(...$params): static` | 工厂方法 |
| `render(): string` | 渲染 HTML（触发资源+脚本） |
| `html(): string` | 返回 HTML（走视图或子类重写） |
| `class(string\|array $class, bool $append = false): $this` | 设/追加 CSS 类（append=true 追加） |
| `style(string $style, bool $append = true): $this` | 设/追加内联样式（`;` 连接） |
| `id(?string $id = null)` | 设 id；无参取 id |
| `<任意方法名>(?val)` | 设/取 HTML 属性（如 `->title('x')`、`->href('#')`） |
| `setHtmlAttribute($k,$v)` / `defaultHtmlAttribute($k,$v)` / `appendHtmlAttribute($k,$v)` | 底层属性操作 |
| `formatHtmlAttributes(): string` | 渲染成 `key="val"` 字符串（给视图用） |
| `options(array): $this` / `option($key,$val=null)` | 批量/点号取设选项 |
| `when($value, callable): $this\|mixed` | 条件链 `$w->when($cond, fn($w,$v)=>...)` |
| `view(string $view)` | 设置视图模板 |
| `runScript(bool = true): $this` | 是否输出 JS |
| `setElementClass(string)` / `getElementClass()` / `getElementSelector()` | CSS 作用域类名/选择器 |
| `static requireAssets()` | 注入 `$js/$css` 到 Admin |

> ⚠️ 通用 `class()/id()/style()` 链**只在 `defaultVariables()` 用了 `formatHtmlAttributes()` 的 Widget 上生效**。13 个 Widget 有 Bug（见第十节），链式失效。

---

## 三、容器型 Widget

### 3.1 Card（最常用）

`Dcat\Admin\Widgets\Card` · 视图 `admin::widgets.card` · `make($title='', $content=null)`（只传一个参数时作为 content）

| 方法 | 作用 |
|------|------|
| `title(string)` | 头部标题 |
| `content(string\|\Closure\|Renderable\|LazyRenderable)` | 主体；LazyGrid 自动 simple() |
| `footer(string)` | 底部 |
| `tool(string\|Renderable\|\Closure)` | 头部右上角工具按钮（可叠加） |
| `withHeaderBorder()` | 标题下加分割线 |
| `padding(string $css)` / `noPadding()` | 内联 padding |
| `outline($color='card-primary')` | 卡片轮廓色 |
| `setCardColor($color='card-primary')` | 整卡背景色 |
| `collapse()` / `remove()` | 显示折叠/关闭按钮 |

```php
Card::make('用户统计', '共 1,234 人')
    ->withHeaderBorder()
    ->tool('<a class="btn btn-sm btn-primary">详情</a>')
    ->footer('更新于 '.now()->toDateTimeString());

// 直接接收异步表格（自动 simple 模式）
Card::make('用户列表', UserTable::make());
```

### 3.2 Box（旧版盒子）

`Dcat\Admin\Widgets\Box` · 视图 `admin::widgets.box` · `make($title='', $content='')`

| 方法 | 作用 |
|------|------|
| `title()` / `content()` / `tool()` / `padding()` | 同 Card |
| `collapsable()` | 折叠按钮（`data-action="collapse"`） |
| `removable()` | 关闭按钮（`data-action="remove"`） |
| `solid()` | `box-solid` 实心样式 |
| `style(string\|array)` | **重写版**：加 `box-{style}` 类（如 `style('primary')`→`box-primary`） |

```php
Box::make('通知', $renderable)->solid()->collapsable()->removable();
```

### 3.3 Modal（模态窗）

`Dcat\Admin\Widgets\Modal` · 用 `InteractsWithRenderApi` trait · `make($title=null, $content=null)`

| 方法 | 作用 |
|------|------|
| `title($t)` / `body($c)` / `content($c)` / `footer($f)` | body 是 content 别名 |
| `button($html)` | 触发按钮 HTML（默认渲染只返回触发器，弹窗 HTML 注入页面底部） |
| `sm()` / `lg()` / `xl()` / `size(string)` | 尺寸（sm=300/lg=800/xl=1140 px） |
| `centered()` / `scrollable()` / `staticBackdrop()` | 居中/滚动/静态背景 |
| `delay(int $ms)` | 显示延迟（异步图表需设，避免渲染异常） |
| `join(bool=true)` | true: 触发器+弹窗拼一起返回；false（默认）: 弹窗注入底部 |
| `on($event,$script)` / `onShow/onShown/onHide/onHidden($script)` | Bootstrap 事件钩子 |

> Modal 的 `body()` 自动识别 `LazyGrid`（包非加载 LazyTable，show 时触发 `table:load`）和 `LazyRenderable`（延迟加载）。详见 [action-system.md](action-system.md) 第八节。

```php
Modal::make()
    ->lg()->title('编辑用户')
    ->body(EditUserForm::make()->payload(['id' => $id]))
    ->button('<button class="btn btn-primary">编辑</button>');
```

### 3.4 Tab（选项卡）

`Dcat\Admin\Widgets\Tab` · 视图 `admin::widgets.tab`

| 方法 | 作用 |
|------|------|
| `add($title, $content, $active=false, $id=null)` | 添加内容选项卡（active=默认选中） |
| `addLink($title, $href, $active=false)` | 添加链接型选项卡 |
| `tabId($id)` | 为下一个 add 预设 DOM id（窗格 `#tab_{id}` 可浏览器定位） |
| `icon($html)` | 给最后添加的选项卡加图标 |
| `dropdown(array $links)` | 下拉项（`[['名','url'],...]`） |
| `title(string)` | 右上角标题 |
| `padding(string)` / `noPadding()` | 内容 padding |
| `withCard()` | 包 `.card` + 轻量 padding |
| `vertical()` | 左侧垂直导航 |
| `theme(string='primary')` | 颜色主题 |
| `tabStyle($type)` | 原始 nav 类 |

> Tab 内置 PJAX 记忆：哈希同步，翻页/刷新后保持选中。常用于按状态分 tab 展示 Grid。

```php
Tab::make()
    ->add('活跃', $gridHtml, true)
    ->add('已禁用', $otherHtml)
    ->addLink('外部', 'https://example.com')
    ->withCard();
```

### 3.5 Dropdown（下拉菜单）

`Dcat\Admin\Widgets\Dropdown` · 视图 `admin::widgets.dropdown` · `make(array $options=[])`

| 方法 | 作用 |
|------|------|
| `options($options=[], $title=null)` | **重写版**：键值映射 + 可选分组标题 |
| `button(?string $text)` | 触发按钮文字 |
| `buttonClass()` / `buttonStyle()` | 按钮样式 |
| `direction('up'\|'down')` / `up()` / `down()` | 展开方向 |
| `menuRight()` | 右对齐 |
| `divider()` | 项间分隔符（或值用常量 `Dropdown::DIVIDER`） |
| `map(Closure)` | 选项渲染回调 `fn($v,$k)` |
| `click(?string $defaultLabel)` | 点击模式（`getButtonId()` 取按钮 id） |

```php
Dropdown::make(['edit' => '编辑', 'delete' => '删除'])
    ->button('操作')
    ->map(fn($v, $k) => "<a href='javascript:act(\"$k\")'>$v</a>");
```

### 3.6 Collapse（折叠面板）

`Dcat\Admin\Widgets\Collapse` · 视图 `admin::widgets.collapse` · 无参构造

```php
Collapse::make()
    ->add('第一章', '内容 A')
    ->add('第二章', '内容 B');
```

> ⚠️ Collapse 有 `formatAttributes()` Bug（见第十节），`class()/style()` 链不生效，用构造函数默认值。

---

## 四、内容展示型 Widget

### 4.1 Alert / Callout（提示/警告框）

`Dcat\Admin\Widgets\Alert` · `make($content='', $title=null, $style='danger')`
`Dcat\Admin\Widgets\Callout` · `make($content='', ?$title=null, ?$style=null)`（与 Alert 共享视图）

通用方法：`title()` / `content()` / `style($s)` / `removable(bool)` / `icon($i)`
样式快捷：`primary()` / `info()` / `success()` / `warning()` / `danger()`（Alert 会自动配图标；Callout 的 `light()`）

```php
Alert::make('保存成功', '提示')->success()->removable();
Callout::make('这是一条提示', '标题')->info();
```

### 4.2 Table（静态数据表格）

`Dcat\Admin\Widgets\Table` · `make($headers=[], $rows=false, $style=[])`

- `$rows=false` 时，`$headers` 视为关联行 → 渲染键值双列表
- 关联行会递归渲染成带左边框的嵌套表

```php
Table::make(['ID','名称'], [[1,'A'],[2,'B']])->withBorder();
Table::make(['name' => '张三', 'age' => 20]);  // 键值表
```

### 4.3 ListGroup / Timeline（列表/时间线）

`ListGroup::make()` · `.add($title,$content,$link)` · `vueAjax($apiurl,$param,$method,$headers)`（切 Vue 视图异步拉数据）
`Timeline::make()` · `.add($time_label,$title,$time,$content='')` · `.icons($strings)`（设最后项图标）

### 4.4 Code / Markdown / Terminal（代码/标记）

- `Code`（继承 Markdown）· `make($content='',$start=1,$end=1000)` · `.lang($l)`/`.javascript()`/`.section($file,$line,$context)`（读文件行范围）· 支持字符串/数组/对象/文件路径
- `Markdown::make($md=null)` · `.content($md)` · 内置 emoji/taskList/tex/flowChart 支持
- `Terminal::make($content=null)` · **静态** `Terminal::call('route:list')` 运行 Artisan 命令并展示 · `.dark()`/`.transparent()`

```php
Terminal::call('route:list')->dark();
Code::make("echo 'hi';")->javascript();
```

### 4.5 Descriptions（详情描述列表）

`Dcat\Admin\Widgets\Descriptions::make()` · `.field($label,$content='')` · `.dedicatedLine()`（最后字段占整行）· `.columns(int)` · `.card(bool)` · `.header($h)`/`.footer($f)` · `.shadow()` · `.setTips(bool)`

### 4.6 Tooltip（工具提示，非输出型）

`Dcat\Admin\Widgets\Tooltip` · `make($selector='')` · **不 echo，作为语句实例化**（`autoRender()` 自动挂载）

```php
Tooltip::make('.help-tip')->title('提示文本')->green()->top();
// 或
new Tooltip('.help-tip');
```
方法：`selector($s)` / `title()` / `background($color)` + `green/blue/red/purple()` / `maxWidth(int)` / `placement($p)` + `left/right/top/bottom()`

### 4.7 Radio / Checkbox / BtnGroup

- `Radio::make(?string $name, array $options=[], string $style='primary')` · `options()`/`check($opt)`/`disable($val)`/`size('sm'\|'lg')`/`inline(bool)`/`style('info'\|'primary'\|...)`
- `Checkbox`（继承 Radio）· 增加 `check($options)`/`checkAll($excepts=[])`
- `BtnGroup::make()` · `.add($btntxt,$icon='')`/`.vertical()`/`.styles($s)`/`.link($url)`

---

## 五、dcat-plus 新增 Widget

> 社区分支新增的展示型组件。**注意：下列多个组件有 `formatAttributes()` Bug（见第十节），`class()/id()/style()` 链可能失效。**

### 5.1 图卡 / 封面卡

- **CardWidget** · `make()` · `.add($img_src,$title='',$content='',$link)` — 图+标题+内容卡片
- **CoverCard** · `make()` · `.add($title,$content,$link)` · `.bg($img)`（最后项背景）· `.avatar($a)` · `.avatarCircle(bool)` — 带背景/头像的封面卡
- **InfoList** · `make()` · `.add($img_src,$title,$content,$link)` — 信息列表（镜像 CardWidget）

### 5.2 媒体 / 链接

- **MediaList** · `make()` · `.add($title,$content,$media_img,$link)` · `.imgCenter(bool)`/`.imgMaxWidth($w)`/`.rowNum($n)`/`.target($t)` — 媒体对象列表
- **Linkbox** · `make()` · `.add($icon,$title,$sub_title,$link,$bg='bg-info',$hot=false)` · `.groupTitle($t)` · `.target($t)` · `.hot(bool)`（最后项标热门）— 分组链接卡
- **MiniProgramBox** · `make($title='',$content=null)` · `.content(...)`（像 Card 处理 LazyGrid/LazyRenderable）— 小程序展示盒

### 5.3 定价 / 荣誉 / 跑马灯

- **PricingCard** · `make()` · `.columns($c=4)`/`.add($title,$money)`/`.li(array)`（功能列表）/`.active(bool)`/`.btnTxt($s)`/`.btnClick($payUrl)`（layer 支付弹窗）/`.head($s)`/`.footer($s)` — 定价方案网格
- **HonorWall**（继承 Swiper）· `make(array $honors=[])` · `.add($image,$title='',$url,$target)`/`.honors(array)`（批量）/`.slideWidth(int)`/`.visibleSlides(1\|3\|5)`/`.maxWidth(int)` — 荣誉/认证墙
- **MarqueeNotice**（继承 Swiper）· `make(array $notices=[])` · `.add($message,$url,$target)`/`.notices(array)` — 垂直跑马灯通知（3s 自动播放，悬停暂停）

### 5.4 轮播

- **Carousel** · `make()` · `.add($img_src,$title,$content,$link)`/`.addItems(array)`/`.interval(int)`(0 禁用)/`.vertical()`/`.horizontal()`/`.autoplay(bool)`/`.arrows(bool)` — 自定义轮播（非 Swiper）✅ 正确用 `formatHtmlAttributes()`
- **Swiper**（基类）· `make(array $slides=[])` · `.add($content)`/`.direction()`/`.loop()`/`.autoplay($delay=3000)`/`.pagination()`/`.navigation()` — 通用 Swiper 滑块 ✅

```php
Swiper::make(['<div>幻灯片 1</div>', '<div>幻灯片 2</div>'])
    ->loop()->autoplay(4000)->pagination()->navigation();
```

### 5.5 其他

- **DarkModeSwitcher** · `make(?bool $defaultDarkMode=null)` — 暗色模式切换按钮（implements Renderable，非 Widget）
- **Dump** · `make($content, ?string $padding=null)` · `.padding($p)`/`.maxWidth($w)` — 美化打印数组/对象/JSON

---

## 六、异步 / 交互型 Widget

### 6.1 Lazy / LazyTable（异步加载）

详见 [action-system.md](action-system.md) 第八节。

- **Lazy** · `make(?LazyRenderable $r, bool $load=true)` · `.load(bool)` — 异步渲染任意 LazyRenderable（图表、自定义视图）
- **LazyTable** · `make(?LazyRenderable $r, bool $load=true)` · `.from($r)`/`.simple(bool)`/`.load(bool)`/`.onLoad($js)` — 异步 Grid 表格，资源 `@grid-extension`

### 6.2 DialogForm / DialogTable（弹窗选择器）

- **DialogForm**（非 Widget）· `make(?string $title, $url)` · `.click($selector)`/`.url($url)`/`.width($w)`/`.height($h)`/`.forceRefresh()`/`.saved($js)`/`.success($js)`/`.error($js)` — layer.js iframe 表单弹窗。控制器内用静态 `DialogForm::prepare(Form $form)` 切弹窗模式渲染表单
- **DialogTable** · `make($title, ?LazyRenderable $table)` · `.from($r)`/`.button($b)`/`.width($w)`/`.maxmin(bool)`/`.resize(bool)`/`.onShown($js)`/`.onHidden($js)` — layer.js 表格选择弹窗

### 6.3 Tree（树形）

`Dcat\Admin\Widgets\Tree` · 视图 `admin::widgets.tree` · 基于 jstree · `make($nodes=[])`

| 方法 | 作用 |
|------|------|
| `nodes($data)` | 设置节点（数组/Arrayable） |
| `check($value)` / `checkAll()` | 选中节点 |
| `setIdColumn($name)` / `setTitleColumn($name)` / `setParentColumn($name)` | 自定义扁平节点的 id/text/parent 键（默认 `id/name/parent_id`） |

### 6.4 Form（工具表单）

`Dcat\Admin\Widgets\Form` · 详见 [action-system.md](action-system.md) 异步工具表单章节。核心：`form()`/`handle(array $input)`/`default()`/`payload()`/`confirm()`。

---

## 七、图表 Widget

### 7.1 ApexCharts\Chart

`Dcat\Admin\Widgets\ApexCharts\Chart` · 资源 `@apex-charts` · 用 `InteractsWithApi`（支持 API 重取） · `make($selector=null, $options=[])`

| 方法 | 作用 |
|------|------|
| `selector(?string)` | 容器选择器；设置时图表渲染到既有元素，留空则自动生成 div |
| `title(string\|array)` | 标题 |
| `series(array)` | 数据系列 `[['name'=>'销量','data'=>[30,40]]]` |
| `labels(array)` | 分类标签 |
| `colors(string\|array)` | 颜色 |
| `stroke($opts)` / `xaxis($opts)` / `yaxis($opts)` / `tooltip($opts)` / `fill($opts)` / `chart($opts)` / `dataLabels($opts\|bool)` | 各 ApexCharts 选项块 |
| `options(array)` | 批量合并选项 |

```php
\Dcat\Admin\Widgets\ApexCharts\Chart::make()
    ->series([['name' => '销量', 'data' => [30, 40, 35]]])
    ->labels(['1月', '2月', '3月']);
```

### 7.2 ChinaMap（中国地图，ECharts 下钻）

`Dcat\Admin\Widgets\ChinaMap` · 资源 `@echarts` + `@echarts-china-map` · 内联 HTML

`make()` 可传关联数组批量调方法：`ChinaMap::make(['title'=>'标题','height'=>500])`

链式方法：`width($w)` / `height(int)` / `title()` / `subtitle()` / `showLegend(bool)` / `legendTitle()` / `showBackButton(bool)` / `colorScale(array)`（默认 5 色渐变）/ `areaColor()` / `emphasisColor()` / `roam(bool)` / `valueSuffix()` / `data(array [{name,value}])` / `provinceData(array)`（省级数据，支持下钻）/ `selector(?string)`

### 7.3 Calendar（FullCalendar 日历）

`Dcat\Admin\Widgets\Calendar` · 资源 `@fullcalendar` · 视图 `admin::widgets.calendar` · `make()`

方法：`calendarId()` / `locale($v)`（默认 zh-cn）/ `timeZone()`（默认 Asia/Shanghai）/ `initialView($v)`（`dayGridMonth`/`timeGridWeek`/`timeGridDay`/`listWeek`）/ `eventItem(array)` / `addEvents($title,$description,$start,$end='')` / `.backgroundColor()/.borderColor()/.allDay()/.showModal()/.webUrl()`（修饰最后添加的事件）

---

## 八、Metrics 数据统计卡片

`Dcat\Admin\Widgets\Metrics\*` · 视图 `admin::widgets.metrics.card` · 用 `InteractsWithApi`（下拉/日期切换时 API 重取） · 资源 `@apex-charts`

### Card 基类

`Metrics\Card::make($title=null, $icon=null)` · 通用方法：

| 方法 | 作用 |
|------|------|
| `title()` / `subTitle()` / `icon()` | 头部字段 |
| `header($content)` / `content($content)` | 头部/主体内容 |
| `style(string)` | 颜色主题 |
| `dropdown(array $items)` | 头部下拉过滤器 |
| `dateRange($start='',$end='')` | 日期范围选择器（默认本周） |
| `height($value)` | 最小高度 |
| `useChart()` / `chart($opts)` / `chartOption($key,$val)` | 内嵌 ApexCharts（点号设选项） |
| `chartHeight(int)` / `chartLabels()` / `chartColors()` | 图表布局/标签/颜色 |
| `valueResult()` | API 响应返回 `[status,header,content,...chart]` |

### 子类

| 类 | 特点 | 特有方法 |
|----|------|---------|
| `Metrics\Line` | 面积火花线 | `chartStraight()`/`chartSmooth()` |
| `Metrics\Bar` | 柱状火花 | `chartBarColumnWidth($v)` |
| `Metrics\Donut` | 环形图 | `contentWidth($left,$right)` |
| `Metrics\RadialBar` | 径向条 | `footer($v)`/`contentWidth()`/`chartPullRight()` |
| `Metrics\Round` | 多系列径向条 | `chartRadialBarSize()`/`chartTotal($label,$num)` |
| `Metrics\SingleRound` | 单值环形 | （仅覆盖默认选项） |

> Metrics 卡片常配合 Controller 的 `ajax()` 方法实现：下拉/日期切换 → 请求 API → `valueResult()` 返回新数据 → 前端重渲染。详见旧文档 `3-数据统计卡片.md`。

---

## 九、自定义 Widget

### 步骤

1. **建类**：放 `app/Admin/Widgets/`，继承 `Dcat\Admin\Widgets\Widget`。
2. **选渲染策略**：
   - **视图型**：设 `protected $view`，重写 `defaultVariables()`（**务必用 `formatHtmlAttributes()`**）。
   - **内联型**：重写 `html()` 返回 HTML 字符串。
3. **构造函数**：收主数据，设 `id()`/`class()`/`setElementClass()`。
4. **内容方法**：链式返回 `$this`；Renderable 内容过 `$this->toString($content)` 或 `$this->formatRenderable($content)`（获 Lazy 支持）。
5. **资源**（可选）：声明 `public static $js/$css`，自动加载。
6. **JS**（可选）：设 `$this->script`；需自注入时构造函数调 `$this->autoRender()`。
7. **注册视图命名空间**（如用自定义 Blade）：服务提供者内 `view()->addNamespace('myapp', resource_path('views'))`，然后 `$view = 'myapp::widgets.my-widget'`。

### 最小骨架

```php
namespace App\Admin\Widgets;

use Dcat\Admin\Widgets\Widget;

class MyWidget extends Widget
{
    protected $view = 'myapp::widgets.my-widget';
    protected $title;

    public function __construct($title = '')
    {
        $this->title = $title;
        $this->id('myw-' . uniqid());
        $this->class('my-widget');
    }

    public function title($t)
    {
        $this->title = $t;
        return $this;
    }

    public function defaultVariables()
    {
        return [
            'title'      => $this->title,
            'attributes' => $this->formatHtmlAttributes(),  // ✅ 关键，勿写成 formatAttributes()
        ];
    }
}
```

---

## 十、易错点与已知问题

### 已知 Bug：`formatAttributes()` vs `formatHtmlAttributes()`

基类提供的是 **`formatHtmlAttributes()`**（渲染 HTML 属性字符串）。但以下 **13 个 Widget** 的 `defaultVariables()` 误调用了不存在的 `formatAttributes()`，导致 **`class()/id()/style()` 等通用链式方法静默失效**（属性不渲染到视图）：

`Collapse`、`BtnGroup`、`ListGroup`、`Timeline`、`CardWidget`、`CoverCard`、`MediaList`、`MiniProgramBox`、`Linkbox`、`InfoList`、`PricingCard`、`Descriptions`、`Calendar`

**影响**：对这些 Widget 调 `->class('foo')` / `->style('...')` 不生效。
**对策**：用构造函数默认值；或修复时把 `defaultVariables()` 里的 `formatAttributes()` 改成 `formatHtmlAttributes()`。

### 其他注意点

| 问题 | 说明 |
|------|------|
| Tooltip/Chart/ChinaMap 不 echo 就生效 | 它们构造时调 `autoRender()`，Content 组合时自注入。可 echo 也可直接 `new` |
| Modal 默认渲染只返回触发器 | 弹窗 HTML 注入页面底部。要拼接返回用 `->join(true)` |
| 异步图表放 Modal 要设 delay | `Modal::delay(300)`，否则图表渲染异常 |
| Box::style() 与通用 style() 不同 | Box 重写为加 `box-{style}` 类，不是内联 style |
| Dropdown::options() 与基类不同 | 重写为分组结构 `($options, $title)`，需键值映射 |
| DialogForm 不是 Widget | 用 `__destruct()` 输出，控制器内配合 `DialogForm::prepare()` |
| 类名含下划线影响异步 | 异步渲染类名 `\` 转 `_` 还原，类名勿含 `_` |

### 资源别名速查（`src/Layout/Asset.php`）

| 别名 | 用途 |
|------|------|
| `@apex-charts` | ApexCharts\Chart、Metrics\* |
| `@echarts` / `@echarts-china-map` | ChinaMap |
| `@fullcalendar` | Calendar |
| `@grid-extension` | LazyTable |
| `@admin/dcat/plugins/swiper/...` | Swiper / HonorWall / MarqueeNotice |

---

## 附：核心源码位置

| 关注点 | 文件 |
|--------|------|
| 基类 | `src/Widgets/Widget.php` |
| 属性/变量 trait | `src/Traits/{HasHtmlAttributes,HasVariables}.php` |
| 异步渲染 trait | `src/Traits/{InteractsWithRenderApi,InteractsWithApi}.php` |
| 容器型 | `src/Widgets/{Card,Box,Modal,Tab,Dropdown,Collapse}.php` |
| 内容型 | `src/Widgets/{Alert,Callout,Table,ListGroup,Timeline,Code,Markdown,Terminal,Descriptions,Tooltip,Radio,Checkbox,BtnGroup}.php` |
| 新增展示型 | `src/Widgets/{CardWidget,CoverCard,MediaList,Linkbox,MiniProgramBox,InfoList,PricingCard,HonorWall,MarqueeNotice,Carousel,Swiper,DarkModeSwitcher,Dump}.php` |
| 异步/交互 | `src/Widgets/{Lazy,LazyTable,DialogForm,DialogTable,Tree,Form}.php` |
| 图表 | `src/Widgets/ApexCharts/Chart.php`、`src/Widgets/{ChinaMap,Calendar}.php` |
| Metrics | `src/Widgets/Metrics/{Card,Line,Bar,Donut,RadialBar,Round,SingleRound}.php` |
| 资源别名 | `src/Layout/Asset.php` |
| Blade 视图 | `resources/views/widgets/*.blade.php`（`admin::widgets.xxx`） |
| 旧文档 | `public/dcat-admin-docs/10-页面组件/*.md` |
