# dcat-plus-admin 表单 (Form) 完整参考

> 源码：`vendor/dcat-plus/laravel-admin/src/Form.php` 及 `Form/` 子目录。命名空间 `Dcat\Admin\Form`。
> 未知字段方法经 `Form::__call` → `findFieldClass($method)` 查 `$availableFields` 映射表，实例化对应 `Field` 子类并 `pushField`。
> **重要**：所有需传 JS 函数的配置项（`onSelect`/`setup`/`onchange` 等）必须用 `Dcat\Admin\Support\JavaScript::make($js)` 包装，写字符串会崩溃。heredoc 中的 `\n\t` 会被 PHP 解析为真控制字符，JS 代码须用 `\\n` 双反斜杠。

## 一、表单构建与基本用法

```php
use Dcat\Admin\Form;

// Repository / Model / Builder 均可
Form::make(new MemberUser(), function (Form $form) {
    $form->display('id', 'ID');
    $form->text('name', '名称')->rules('required');
    $form->datetime('created_at');
});

// 带预设关联
Form::make(User::with('profile'), function (Form $form) {
    $form->text('profile.age');   // 一对一字段用 点号
});

// 闭包内读取当前模型（编辑模式）
if ($form->model()->status == 1) { $form->text('rate'); }
```

**Repository 查询字段**（减少 SELECT *）：
```php
class Movie extends EloquentRepository {
    protected $eloquentClass = MovieModel::class;
    public function getFormColumns() { return [$this->getKeyName(), 'name', 'title']; }
}
```

## 二、Form 级常用方法

| 方法 | 说明 |
|------|------|
| `text/select/...($column, $label='')` | 添加字段，返回 Field 实例（链式） |
| `field($name)` | 取指定字段（Collection\|Field\|null） |
| `fields()` | 全部字段 Collection |
| `removeField($column)` | 移除字段 |
| `ignore($fields)` | 忽略字段不入库（string\|array） |
| `forgetIgnored($keys)` | 取消忽略 |
| `isCreating()` / `isEditing()` / `isDeleting()` | 判断当前模式 |
| `getKey()` | 当前 ID（创建页无效） |
| `model()` | 当前模型 Fluent（创建页无效，须在闭包内） |
| `input($key, $value=null)` | 获取/设置提交数据；`null` 返回全部 |
| `deleteInput($keys)` | 删除提交数据（`submitted`/`saving` 内有效） |
| `updates()` | 最终保存数组（仅 `saved` 内有效） |
| `action($url)` | 表单提交 action |
| `resource($slice=-2)` / `setResource($path)` | 资源 URL |
| `width($field=8, $label=2)` | 字段/标签宽度（1-12），影响所有字段 |
| `title($t)` / `view($v)` / `addVariables(array)` | 标题/视图/变量 |
| `confirm($title, $content=null)` | 提交确认弹窗（需 ajax） |
| `ajax(true)` / `validationErrorToastr(true)` | ajax 提交 / toastr 显示验证错误 |
| `wrap(Closure $closure)` | 用闭包包裹整个表单视图（如放进 Tab） |
| `if($condition)` | 条件分支，返回 `Form\Condition`，链 `then/else/now` |
| `block($width, Closure)` / `column($width, Closure)` / `row(Closure)` / `tab(...)` | 布局，见下 |
| `fieldset($title, Closure)` | 折叠分组，返回 `Field\Fieldset`，可 `->collapsed()` |

**Form 级开关**（均 `bool $…=true`，返回 `$this`）：
`disableHeader` `disableFooter` `disableSubmitButton` `disableResetButton` `disableViewCheck` `disableEditingCheck` `disableCreatingCheck` `defaultViewChecked` `defaultEditingChecked` `defaultCreatingChecked` `disableViewButton` `disableListButton` `disableDeleteButton`

## 三、字段基类通用方法（所有 Field 共享）

源码 `Form/Field.php`。值管线：`fill()` → `formatFieldData()` → `customFormat` 闭包 → 渲染用 `value()`（回退 `default()`）；提交时 `prepare()` 跑 `prepareInputValue()` 后依次执行 `saving()` 闭包（绑定行数据）。

### 值与默认
| 方法 | 说明 |
|------|------|
| `value($v=null)` | 取/设值；getter 回退 `default()` |
| `default($v, $edit=false)` | 默认值；`$edit=true` 编辑页也生效 |
| `customFormat(Closure)` | 转换显示值（闭包内 `$this->字段` 取同行） |
| `saving(Closure)` | 转换待保存值（闭包绑定行数据） |
| `saveAsJson($opt=0)` / `saveAsString()` | 自动 json_encode / 字符串化（注册 saving） |
| `original()` | 编辑前原值 |
| `data(?array=null)` | 取/设整行数据 |
| `prepare($value)` | 最终处理（内部） |

### 显示与样式
| 方法 | 说明 |
|------|------|
| `label($l=null)` | 取/设标签 |
| `help($text)` | 提示信息 |
| `width($field=8, $label=2)` | 单字段宽度 |
| `horizontal(true)` | 水平布局 |
| `addElementClass($class, $normalize=false)` | 加 class（`false` 不加前缀） |
| `setElementClass($class, $normalize=true)` / `removeElementClass` / `setLabelClass` / `setFieldClass` / `setFormGroupClass` | class 设置 |
| `attribute($key, $val=null)` / `hasAttribute` / `getAttribute` | HTML 属性 |
| `defaultAttribute($key, $val)` | 仅当不存在时设置 |
| `placeholder($p=null)` | 占位符，默认 `"input {label}"` |
| `required($showStar=true)` | 必填（`rules('required')` + `*` 标记） |
| `disable(true)` / `readOnly(true)` / `autofocus(true)` | 禁用/只读/聚焦 |
| `pattern($regexp, $error=null)` | 正则 |
| `display(false)` | 不渲染 |
| `hideInDialog()` | 弹窗中隐藏 |
| `when($value, callable)` | 值为真时执行回调（通用版，区别于级联 when） |

### 验证（HasFieldValidator）
| 方法 | 说明 |
|------|------|
| `rules($rules=null, array $messages=[])` | Laravel 规则，支持 string\|Closure\|array |
| `creationRules(...)` | 仅创建时（覆盖 rules） |
| `updateRules(...)` | 仅更新时（覆盖 rules） |
| `removeRule($rule)` / `hasRule($rule)` | 移除/判断规则 |
| `validator(callable)` | 自定义验证器工厂 |
| `setValidationMessages($key, array)` | 消息，key: default/creation/update |
| `setClientValidationError($error, $key=null)` | 前端 `data-{key}-error` |

> 规则中的 `{{id}}` 会替换为当前模型主键。规则解析按上下文：创建用 `creationRules ?: rules`，编辑用 `updateRules ?: rules`。

## 四、输入类字段

| 方法 | 类 | 关键方法 |
|------|----|---------|
| `text($c,$l)` | Text | `type($t)` `same($field,$err=null)` `minLength($n,$err=null)` `maxLength($n,$err=null)` `inputmask($opts)` `datalist($entries)` `prepend($s)` `append($s)` `small()` `large()` |
| `textarea($c,$l)` | Textarea | `rows($n=5)` |
| `number($c,$l)` | Number (extends Text) | `min($v)` `max($v)`，默认 0，强制宽 140px |
| `decimal($c,$l)` | Decimal | Inputmask alias `decimal` |
| `currency($c,$l)` | Currency | `symbol('￥')` `digits($n)` |
| `rate($c,$l)` | Rate | 前置 % |
| `password($c,$l)` | Password | type=password |
| `email($c,$l)` / `url($c,$l)` / `ip($c,$l)` | Email/Url/Ip | rules 默认带验证 |
| `mobile($c,$l)` | Mobile | `options(['mask'=>'999 9999 9999'])` |
| `tel($c,$l)` | Tel | type=tel |
| `hidden($c,$l)` | Hidden | `default()` 设值 |
| `color($c,$l)` | Color | 颜色选择 |
| `slider($c,$l)` | Slider | `options(['max'=>100,'min'=>1,'step'=>1,'postfix'=>'年'])` |
| `autocomplete($c,$l)` | Autocomplete | `options([...])` `groups([[...]])` `ajax($url,$valueField='name',$groupField=null)` `configs([...])` `depends(['region','country'])`。服务端参数 `query` |

**text 高级**：
```php
$form->text('age')->type('number')->attribute('min',10)->attribute('max',60);
$form->password('confirm')->same('password', '两次密码不一致');
$form->text('code')->inputmask(['mask' => '999-9999-9999']);
```

## 五、选择类字段

| 方法 | 类 | 关键方法 |
|------|----|---------|
| `select($c,$l)` | Select | `options(array\|Closure\|Model\|url)` `groups([[...]])` `model($class,$id='id',$text='name')` `ajax($url,$id='id',$text='text')` `config($k,$v)` `disableClearButton()` `placeholder()` `when(...)` `load/loads(...)` |
| `multipleSelect($c,$l)` | MultipleSelect (extends Select) | 同 Select，值强制数组 |
| `checkbox($c,$l)` | Checkbox (extends MultipleSelect) | `options()` `style($s)` `canCheckAll()` `inline(true)` `when('in'/'notIn',...)` |
| `radio($c,$l)` | Radio | `options()` `inline(true)` `style($s)` `when('=','>','<',...)` |
| `switch($c,$l)` | SwitchField | `primary()/green()/red()/blue()/purple()/yellow()` `color($c)` `secondary($c)` `small()/large()`。默认存 1/0 |
| `tags($c,$l)` | Tags | `options()` `pluck($visibleCol,$key='id')` `ajax($url,$id='id',$text='text')` |
| `listbox($c,$l)` | Listbox | `options()` `settings([...])` (bootstrap-duallistbox) |
| `tree($c,$l)` | Tree | 见「树形选择」 |
| `selectTable($c,$l)` | SelectTable | 见「弹窗选行」 |
| `multipleSelectTable($c,$l)` | MultipleSelectTable | 同 SelectTable + `max($n)` |
| `timezone($c,$l)` | Timezone | 自动填 PHP 时区 |
| `captcha()` | Captaptcha | 需 mews/captcha |

**select/ajax**：API 返回 `[{"id":9,"text":"xxx"}]`，分页接口返回 Laravel paginate 结构。
```php
$form->select('user_id')->options(User::class, 'id', 'name')->ajax('/api/users');
$form->multipleSelect('tags')->options(['php'=>'PHP','js'=>'JS'])
    ->saving(fn($v) => json_encode($v))
    ->customFormat(fn($v) => json_decode($v, true) ?: []);
```

## 六、日期时间类字段

均基于 moment.js + bootstrap-datetimepicker。格式用 moment 语法。

| 方法 | 类 | 默认格式 | 方法 |
|------|----|---------|------|
| `date($c,$l)` | Date (extends Text) | `YYYY-MM-DD` | `format($fmt)` |
| `datetime($c,$l)` | Datetime (extends Date) | `YYYY-MM-DD HH:mm:ss` | `format($fmt)` |
| `time($c,$l)` | Time | `HH:mm:ss` | |
| `year($c,$l)` | Year | `YYYY` | |
| `month($c,$l)` | Month | `YYYY-MM` | |
| `multiDate($c,$l)` | MultiDate | yyyy-mm-dd | 多日期 |
| `dateRange($s,$e,$l)` | DateRange | `YYYY-MM-DD` | `options([...])` |
| `datetimeRange($s,$e,$l)` | DatetimeRange | `YYYY-MM-DD HH:mm:ss` | |
| `timeRange($s,$e,$l)` | TimeRange | `HH:mm:ss` | |
| `range($s,$e,$l)` | Range | 纯输入范围 | 无日期选择器 |

## 七、上传类字段（WebUploader）

源码 `Field/File.php` + `UploadField` + `WebUploader` trait。

### 公共方法
| 方法 | 说明 |
|------|------|
| `accept($exts, $mimeTypes=null)` | 限制类型 `accept('jpg,png','image/*')` |
| `maxSize($kb)` | 单文件最大 KB，默认 10M，自动加 `max:` 规则 |
| `chunked(true)` / `chunkSize($kb)` | 分块上传，默认 5MB 自动开启 |
| `threads($n)` | 并发数，默认 3 |
| `url($server)` / `deleteUrl($url)` | 上传/删除接口 |
| `move($dir, $name=null)` / `dir($dir)` / `name($name\|Closure)` | 目录/文件名（闭包可） |
| `disk($disk)` | 存储驱动 |
| `uniqueName()` / `sequenceName()` | 随机名 `bin2hex(random_bytes(16))` / 序号名 |
| `retainable(true)` | 删记录时保留文件 |
| `saveFullUrl(true)` | 存完整 URL |
| `storagePermission($perm)` | 权限如 `777` |
| `removable(false)` | 禁用前端删除（覆盖上传） |
| `autoUpload(true)` | 选择即上传 |
| `downloadable()` | 下载功能 |
| `override(true)` | 覆盖同名 |
| `compress(true\|array)` | 压缩图片 |
| `autoSave(false)` | 自动存路径到库 |
| `options(array)` | 自定义 webuploader 配置 |
| `sortable()` | 多文件排序（multipleFile/multipleImage） |
| `limit($max)` | 多文件数量限制 |
| `on($event, $jsScript, $once=false)` | WebUploader 事件（脚本经 JavaScript::make） |
| `withFormData(array)` / `withDeleteData(array)` | 上传/删除额外表单数据 |

**compress 配置**：`width/height/quality(仅jpeg)/allowMagnify/crop/preserveHeaders/noCompressIfLarger/compressSize`

### 图片专用（Field\Image + ImageField，需 intervention/image）
```php
$form->image('avatar')->dimensions(['min_width'=>100,'max_width'=>300]);  // 像素校验
$form->image('cover')->ratio(16/9)->thumbnail('small', 300, 300);          // 缩略图
$form->image('img')->crop($w,$h,$x=null,$y=null)->resize($w,$h)->fit($w,$h);
$form->image('img')->insert($watermark, 'center')->text(...)->greyscale();
// thumbnail 批量: ->thumbnail(['small'=>[100,100],'large'=>[500,500]])
// 模型须 use Resizable，访问 $model->thumbnail('small','column')
```

> 多图/多文件注入数据接受 逗号字符串/json 字符串/array。文件字段不要在模型设访问器/修改器拼域名，改用 public 方法。

### 自定义上传接口（HasUploadedFile trait）
```php
use Dcat\Admin\Traits\HasUploadedFile;
class FileController {
    use HasUploadedFile;
    public function handle() {
        $disk = $this->disk('local');
        if ($this->isDeleteRequest()) return $this->deleteFileAndResponse($disk);
        $file = $this->file();
        $dir = 'my-images';
        $newName = $file->getClientOriginalName();
        $path = $dir.'/'.$newName;
        return $disk->putFileAs($dir, $file, $newName)
            ? $this->responseUploaded($path, $disk->url($path))
            : $this->responseErrorMessage('上传失败');
    }
}
// 路由: $router->any('users/files', 'FileController@handle');
```

## 八、编辑器字段

| 方法 | 类 | 说明 |
|------|----|------|
| `editor($c,$l)` | Editor | UEditor。`disk($d)` `imageDirectory($dir)` `imageUrl($url)` `languageUrl($lang)` `height($px)` `darkMode()` `options([...])` |
| `wangEditor($c,$l)` | WangEditor | wangEditor |
| `markdown($c,$l)` | Markdown | editor.md。`htmlDecode()` `height($px)` `disk($d)` `imageDirectory` `imageUrl` `options([...])` |

```php
$form->editor('content')->height(600)->options(['setup'=>JavaScript::make($js)])->disk('oss');
// 全局: Editor::resolving(fn($e) => $e->options([...])->disk('qiniu'));
```

## 九、特殊字段

| 方法 | 类 | 说明 |
|------|----|------|
| `map($lat,$lng,$l)` | Map | 地图选点。`baidu()/amap()/google()/tencent()/yandex()` `height('300px')`。KEY 配置 `admin.map.keys.{provider}` |
| `icon($c,$l)` | Icon | FontAwesome Iconpicker |
| `display($c,$l)` | Display | 只读显示。`with(Closure)` 格式化 |
| `id($c,$l)` | Id | 简化 Display（主键） |
| `button($html)` | Button | `class()` `on($event,$js)` |
| `divider($title=null)` | Divide | 分割线（带标题） |
| `html($html,$l='')` | Html | 自定义 HTML（Htmlable/Renderable/`__toString`）。`plain()` |
| `sku($c,$l)` | SkuField | SKU 规格矩阵。`addColumn(array)`。自动加载 SkuAttribute |

## 十、复合字段（JSON/子表单）

| 方法 | 类 | 说明 |
|------|----|------|
| `embeds($c,$l,Closure)` | Embeds | 单字段 JSON 子表单（**不支持文件上传**）。需模型 `$casts=['extra'=>'json']` |
| `hasMany($c,$lOrCb,Closure)` | HasMany | 一对多关联。`mode('default'/'tab'/'table')` `useTab()` `useTable()` `disableCreate()` `disableDelete()` |
| `array($c,$lOrCb,Closure)` | ArrayField (extends HasMany) | 二维数组（非关联）。`saveAsJson()` |
| `table($c,$lOrCb,Closure)` | Table (extends ArrayField) | 表格视图二维数组 |
| `keyValue($c,$l)` | KeyValue | 键值对象 `{"k":"v"}`。`setKeyLabel()` `setValueLabel()` |
| `list($c,$l)` | ListField | 一维数组。`max($n)` `min($n)` |

```php
// embeds
$form->embeds('extra', '附加', function ($form) {
    $form->text('key1')->required();
    $form->datetime('key3');
    $form->dateRange('s','e','范围');
})->saving(fn($v) => json_encode($v));

// hasMany（需 Repository with 关联: new Painter(['paintings'])）
$form->hasMany('paintings', function (Form\NestedForm $form) {
    $form->text('title');
    $form->datetime('completed_at');
})->useTable();   // 表格模式

// keyValue
$form->keyValue('price')->default(['cny'=>'','usd'=>''])->setKeyLabel('币种')->setValueLabel('价格');

// table（单字段二维数组）
$form->table('extra', function ($table) {
    $table->text('key'); $table->text('value');
})->saving(fn($v) => json_encode($v));

// array（字段多时）
$form->array('items', function ($table) {
    $table->text('name'); $table->textarea('desc');
})->saveAsJson();
```

> `embeds` 子字段规则命名空间 `{column}.{sub}`；`hasMany` 为 `{column}.{idx}.{sub}`；`keyValue` 自动 `distinct` 到 `keys.*`。模型需配访问器/修改器 json_encode/array_values。

## 十一、弹窗选行 selectTable

渲染类须继承 `Dcat\Admin\Grid\LazyRenderable`，实现 `grid(): Grid`。
```php
$form->selectTable('user_id')
    ->title('选择用户')
    ->dialogWidth('50%')        // 默认 800px
    ->from(UserTable::make(['id' => $form->getKey()]))
    ->model(Administrator::class, 'id', 'name');   // 编辑回显，等同 options()->pluck()

$form->multipleSelectTable('user_ids')
    ->from(UserTable::make())
    ->max(10)                   // 多选最大数，默认不限
    ->model(User::class, 'id', 'name');
```
SelectTable 其它方法：`dialogMaxMin(bool)` `dialogResize(bool)` `onHide($js)` `btn($html)` `pluck($visible,$key='id')`。

## 十二、树形选择 tree

```php
$form->tree('permissions')
    ->nodes(Permission::all()->toArray())     // array|Arrayable|Closure，节点 {id,parent,text}
    ->customFormat(fn($v) => $v ? array_column($v, 'id') : [])  // 二维转ID
    ->setIdColumn('id')->setTitleColumn('title')->setParentColumn('parent_id')
    ->rootParentId(0)        // 默认 0
    ->expand(true)           // 默认展开，false 收缩
    ->exceptParentNode(true) // 默认不存父节点
    ->treeState(false);      // 允许单独选父节点（关闭 three_state）
```
默认字段映射 id/name/parent_id。`treeState(true)` 同时开启三态联动。

## 十三、级联与远程联动

### 字段动态显示 `when()`（CanCascadeFields）
select/multipleSelect/radio/checkbox 支持。单选操作符：`=`(默认) `>` `<` `>=` `<=` `!=`；多选：`in`(默认) `notIn` `has`。
```php
$form->radio('type')
    ->when([1,4], function (Form $form) {       // = 可省略
        $form->text('field_a')->rules('required_if:type,1,4')->setLabelClass(['asterisk']);
    })
    ->when('>', 2, function (Form $form) { $form->text('field_b'); })
    ->options([1=>'A',2=>'B',3=>'C',4=>'D'])
    ->default(1);

$form->checkbox('nationality')
    ->when('notIn', 2, function (Form $form) { $form->text('passport'); })
    ->options([1=>'中国',2=>'外国']);
```
> 使用 when 后不能用 `required()`，改用 `required_if` + `setLabelClass(['asterisk'])`。同一表单不能有同名字段。

### 远程联动 `load/loads`（CanLoadFields）
select/multipleSelect/radio/checkbox/selectTable/multipleSelectTable 支持。当前值作 `q` 请求 API，返回 `[{id,text}]`。
```php
$form->select('province')->load('city_id', '/api/cities');          // 单字段
$form->select('province')->loads(['city_id','district_id'], ['/api/cities','/api/districts']); // 多字段
$form->autocomplete('addr')->ajax('/states','name')->depends(['region','country']);
// 请求: /states?query={query}&region={region}&country={country}
```

## 十四、表单布局

### 多列 column（Bootstrap 栅格，和≤12）
```php
$form->column(6, function (Form $form) {
    $form->text('name')->required();
});
$form->column(6, function (Form $form) {
    $form->image('avatar');
});
// column 内闭包参数可是 Form 或 BlockForm，支持嵌套
```

### 多行 row
```php
$form->row(function (Form\Row $form) {
    $form->width(4)->text('username')->required();  // width 设下一字段
    $form->width(3)->text('title');
    $form->horizontal();   // 行内水平布局
});
```

### 选项卡 tab
```php
$form->tab('基本', function (Form $form) {
    $form->text('username');
})->tab('详情', function (Form $form) {
    $form->image('avatar');
});
// 默认激活: $form->getTab()->active('标题2'); 或 activeByIndex(1);
// tab 内可嵌套 column/row
```

### 折叠分组 fieldset
```php
$form->fieldset('分组', function (Form $form) {
    $form->text('company');
})->collapsed();   // 默认收起
```

### 分块 block（字段多时）
```php
$form->block(8, function (Form\BlockForm $form) {
    $form->title('基本设置')->showFooter()->width(9,2);
    $form->column(6, fn($f) => $f->text('name'));
});
$form->block(4, function (Form\BlockForm $form) {
    $form->title('侧边');
    $form->next(function (Form\BlockForm $form) { $form->title('下一块'); });
});
```
> 布局可在 `hasMany`/`array` 内使用。`block`/`column`/`row`/`tab` 可互相嵌套。

## 十五、表单事件（HasEvents）

闭包**绑定到模型**（`$this` 是 Eloquent 模型），第一参是 `Form`，返回 `Response`/`JsonResponse` 中断流程。

| 方法 | 触发 | 回调签名 |
|------|------|---------|
| `creating(Closure)` | 新建页渲染（非提交） | `fn(Form $form)` |
| `editing(Closure)` | 编辑页渲染（非提交） | `fn(Form $form)` |
| `submitted(Closure)` | 提交前（验证/保存前） | `fn(Form $form)`，可 `input/deleteInput`，返回中断 |
| `saving(Closure)` | 保存前 | `fn(Form $form)`，可改 `$form->field=..` |
| `saved(Closure)` | 保存后（增改共用，新增 `$result` 是自增ID） | `fn(Form $form, $result)` |
| `deleting(Closure)` | 删除前 | `fn(Form $form)` |
| `deleted(Closure)` | 删除后 | `fn(Form $form, $result)` |
| `uploading(Closure)` | 上传前 | `fn(Form $form, $field, UploadedFile $file)` |
| `uploaded(Closure)` | 上传后 | `fn(Form $form, $field, $file, Response $response)` |
| `fileDeleting(Closure)` / `fileDeleted(Closure)` | 文件删除前/后 | `fn(Form $form, $field)` |

```php
$form->saving(function (Form $form) {
    $form->author_id = Admin::user()->id;     // 修改（需有 hidden 字段）
    $form->deleteInput('author_id');          // 删除
});
$form->saved(function (Form $form, $result) {
    if ($form->isCreating()) { $newId = $result; }
    $form->model()->update(['data' => 'new']); // 模型更新
    return $form->response()->success('保存成功')->redirect('auth/user');
});
```

**响应方法**（creating/editing 内不可用）：
`$form->response()->success/error(...)` `->redirect($url)` `->refresh()` `->refreshIf($cond)` `->redirectIf($cond,$url)`。

**自定义验证错误**（form 闭包或 submitted 内）：
```php
$form->responseValidationMessages('title', 'title格式错误');
$form->responseValidationMessages('content', ['错1','错2']);
```

## 十六、表单验证

### 后端（Laravel 验证）
```php
$form->text('title')->rules('required|min:3');
$form->text('title')->rules(function (Form $form) {
    return $form->model()->id ? '' : 'unique:users,email';
});
$form->text('code')->rules('required|regex:/^\d+$/', ['regex'=>'须全数字']);
$form->text('title')->creationRules('required');  // 仅创建
$form->text('title')->updateRules('required');    // 仅更新
```

### 前端（bootstrap-validator + H5）
```php
$form->text('title')->required();
$form->text('age')->type('number')->attribute('min',10)->attribute('max',60);
$form->text('title')->minLength(20)->maxLength(50);
$form->email('email');  $form->text('site')->type('url');
$form->password('confirm')->same('password');
```
自定义前端规则（macro + `Dcat.validator.extend`）见扩展章节。

## 十七、分步表单 multipleSteps

```php
$form->multipleSteps()
    ->remember()           // session 记忆，默认不开
    ->width('950px')       // 默认 1000px
    ->padding('30px 18px 30px')
    ->add('基本信息', function (Form\StepForm $step) {
        $step->text('name')->required()->maxLength(20);
        $step->radio('sex')->options(['未知','男','女'])->default(0);
        $step->leaving(<<<JS  return false; JS);  // 当前步骤离开回调
    })
    ->add('兴趣', function (Form\StepForm $step) { /* ... */ })
    ->leaving(<<<JS console.log('离开', args.index); JS)  // 全局离开回调
    ->shown(<<<JS console.log('显示', args.index); JS)     // 全局显示回调
    ->done(function (Form\DoneStep $done) {
        $newId = $done->getNewId();
        return view('admin::form.done-step', [...]);
    });
```
- `args` 字段：`index`(当前步) `event` `tab` `direction`(forward/backward) `form` `formArray` `getForm(i)` `getFormArray(i)`
- 行为：无 update 概念；点「下一步」后端验证该步；最后一步全量提交 `Form::store`
- 编辑：用 `$form->isEditing()` 判断走普通表单

## 十八、表单弹窗

### 数据表单页弹窗 Form::dialog
```php
Form::dialog('新增角色')
    ->click('.create-form')         // 绑定按钮选择器
    ->url('auth/roles/create')      // 创建页 URL（编辑用按钮 data-url 覆盖）
    ->width('700px')->height('650px')  // 默认 720x690，可百分比
    ->success('Dcat.reload()')      // 成功 JS，可用 response 变量
    ->error($js)->saved($js)->forceRefresh();
```

### 工具表单弹窗（Widgets\Form + Modal）
工具表单 `Dcat\Admin\Widgets\Form` 独立处理数据，无需路由：
```php
class Setting extends Form {
    public function handle(array $input) { return $this->response()->success('ok')->refresh(); }
    public function form() { $this->confirm('确定?'); $this->text('name')->required(); }
    public function default() { return ['name'=>'John']; }
}
// 生成: php artisan admin:form Setting
```
异步弹窗需 `implements LazyRenderable` + `use LazyWidget`，通过 `$this->payload['id']` 取参：
```php
Modal::make()->lg()->title('改密')->body(ResetPassword::make()->payload(['id'=>$id]))->button('改密');
```

## 十九、字段扩展

### 注册扩展
```php
// bootstrap.php
Form::extend('editor', WangEditor::class);     // 替换/新增字段类型
Form::alias('editor', 'rich');                 // 别名 $form->rich(...)
```

### 扩展类继承 `Form\Field`
```php
class WangEditor extends Field {
    protected $view = 'admin.wang-editor';
    protected function prepareInputValue($value) { return (string) $value; }  // 类似修改器
    protected function formatFieldData($data) { /* 类似访问器，customFormat 前 */ }
}
```
视图模板变量：`$viewClass['form-group'/'label'/'field']` `$label` `$name` `$column` `$value` `$selector` `$attributes` `$class` `$placeholder`。

### 动态字段 JS（兼容 hasMany/array）必须用 `Dcat.init`：
```html
<script require="@wang-editor" init="{!! $selector !!}">
    var editor = new E('#' + id);   // id 自动生成
    editor.config.onchange = function (html) {
        $this.parents('.form-field').find('input[type=hidden]').val(html);
    };
    editor.create();
</script>
```

### 资源别名
```php
Admin::asset()->alias('@wang-editor', ['js'=>['https://cdn.jsdelivr.net/npm/wangeditor@4/dist/wangEditor.min.js']]);
```

## 二十、初始化（全局设置）

```php
// bootstrap.php
Form::resolving(function (Form $form) {           // 每个 Form 实例化
    $form->disableEditingCheck()->disableViewCheck()->disableCreatingCheck();
    $form->tools(function (Form\Tools $tools) {
        $tools->disableDelete()->disableView()->disableList();
    });
});
Form::composing(function (Form $form) { /* render 前 */ });
```
单实例恢复：`$form->disableEditingCheck(false)`。

## 二十一、字段翻译

- 文件：`lang/{语言}/{控制器-中划线}.php` → `['fields'=>['name'=>'名称']]`
- 改名：`protected $translation='user1';` 或 `Admin::translation('user1');`
- 公共字段：`lang/{语言}/global.php` 的 `fields`

## 二十二、关联关系速查

| 关系 | 用法 |
|------|------|
| 一对一 hasOne/belongsTo | `Form::make(User::with('profile'))` → `$form->text('profile.age')` |
| 一对多 hasMany | Repository with 关联 → `$form->hasMany('paintings', fn(NestedForm $f)=>...)` |
| 多对多 belongsToMany | `$form->tree('permissions')->nodes(...)->customFormat(fn($v)=>array_column($v,'id'))` |
| 内嵌 JSON | `$form->embeds('extra', fn($f)=>...)`（模型 `$casts=['extra'=>'json']`） |

> 关联字段名 v2.0.21-beta 起支持驼峰，之前须下划线（`user_profile.postcode`）。

## 二十三、完整控制器示例

```php
use Dcat\Admin\Form;

protected function form()
{
    return Form::make(new MemberUser(['profile']), function (Form $form) {
        $form->display('id', 'ID');
        $form->text('username')->required()->rules('unique:member_users,username,{{id}}');
        $form->email('email')->rules('nullable|email');
        $form->image('avatar')->disk('admin')->uniqueName();
        $form->password('password')->rules('required|string|min:6')->value('');
        $form->switch('status')->default(1);
        $form->multipleSelect('roles')->options(Role::pluck('name','id'))
            ->saving(fn($v) => json_encode($v))
            ->customFormat(fn($v) => json_decode($v, true) ?: []);
        $form->text('profile.nickname');

        $form->ignore(['password_confirm']);
        $form->width(9, 2);
        $form->disableCreatingCheck();
        $form->tools(fn(Form\Tools $t) => $t->disableView());

        $form->saving(function (Form $form) {
            if ($form->password && $form->password !== '') {
                $form->password = bcrypt($form->password);
            } else {
                $form->deleteInput('password');
            }
        });
    });
}
```
