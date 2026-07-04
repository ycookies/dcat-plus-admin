# dcat-plus-admin 表单字段完整目录

## 输入类字段

| 方法 | 类名 | 说明 | 关键方法 |
|------|------|------|---------|
| text($col, $label) | Field\Text | 文本输入 | type(), same($field), minLength($len), maxLength($len), inputmask(), datalist(), prepend(), append(), small(), large() |
| textarea($col, $label) | Field\Textarea | 多行文本 | rows($n=5) |
| number($col, $label) | Field\Number | 数字 | min($v), max($v), default(0) |
| decimal($col, $label) | Field\Decimal | 小数(输入掩码) | Inputmask alias:'decimal' |
| currency($col, $label) | Field\Currency | 货币 | symbol('$'), digits() |
| rate($col, $label) | Field\Rate | 百分比 | 前置'%' |
| password($col, $label) | Field\Password | 密码 | type='password' |
| email($col, $label) | Field\Email | 邮箱 | rules: nullable\|email |
| url($col, $label) | Field\Url | 网址 | rules: nullable\|url |
| ip($col, $label) | Field\Ip | IP地址 | Inputmask alias:'ip' |
| mobile($col, $label) | Field\Mobile | 手机号 | Inputmask mask:'99999999999' |
| tel($col, $label) | Field\Tel | 电话 | type='tel' |
| hidden($col, $label) | Field\Hidden | 隐藏域 | default() 设置值 |
| color($col, $label) | Field\Color | 颜色选择 | hex(), rgb(), rgba() |
| slider($col, $label) | Field\Slider | 滑块 | options(['type'=>'single']) |

## 选择类字段

| 方法 | 类名 | 说明 | 关键方法 |
|------|------|------|---------|
| select($col, $label) | Field\Select | 下拉选择 | options(), groups(), model($class), ajax($url), config(), when(运算符,值,闭包), load($field,$url) |
| multipleSelect($col, $label) | Field\MultipleSelect | 多选下拉 | 同Select |
| checkbox($col, $label) | Field\Checkbox | 复选框 | style(), canCheckAll(), inline(), when() |
| radio($col, $label) | Field\Radio | 单选框 | style(), inline(), when() |
| switch($col, $label) | Field\SwitchField | 开关 | primary()/green()/red()/blue()/purple(), small()/large(), color() |
| tags($col, $label) | Field\Tags | 标签输入 | options(), pluck(), ajax() |
| listbox($col, $label) | Field\Listbox | 双列表选择 | settings() |
| autocomplete($col, $label) | Field\Autocomplete | 自动补全 | options(), ajax(), depends(), configs() |
| tree($col, $label) | Field\Tree | 树形选择 | nodes($data), expand(), rootParentId(), exceptParentNode() |
| timezone($col, $label) | Field\Timezone | 时区选择 | 自动填充PHP时区 |
| captcha() | Field\Captcha | 验证码 | 需 mews/captcha，无参数 |

## 日期时间类字段

| 方法 | 类名 | 说明 | 格式 |
|------|------|------|------|
| date($col, $label) | Field\Date | 日期 | YYYY-MM-DD |
| datetime($col, $label) | Field\Datetime | 日期时间 | YYYY-MM-DD HH:mm:ss |
| time($col, $label) | Field\Time | 时间 | HH:mm:ss |
| year($col, $label) | Field\Year | 年份 | YYYY |
| month($col, $label) | Field\Month | 月份 | MM |
| multiDate($col, $label) | Field\MultiDate | 多日期 | yyyy-mm-dd |
| dateRange($start, $end, $label) | Field\DateRange | 日期范围 | YYYY-MM-DD |
| datetimeRange($start, $end, $label) | Field\DatetimeRange | 日期时间范围 | YYYY-MM-DD HH:mm:ss |
| timeRange($start, $end, $label) | Field\TimeRange | 时间范围 | HH:mm:ss |
| range($start, $end, $label) | Field\Range | 纯输入范围 | 无日期选择器 |

## 上传类字段

| 方法 | 类名 | 说明 | 关键方法 |
|------|------|------|---------|
| file($col, $label) | Field\File | 文件上传 | accept(), maxSize(), chunked(), uniqueName(), dir(), disk(), retainable(), saveFullUrl(), override(), removable() |
| image($col, $label) | Field\Image | 图片上传 | 同File + dimensions(), ratio(), thumbnail(), resize()/fit()/crop() (Intervention) |
| multipleFile($col, $label) | Field\MultipleFile | 多文件 | 同File + sortable(), limit() |
| multipleImage($col, $label) | Field\MultipleImage | 多图片 | 同MultipleFile + ImageField方法 |

## 编辑器字段

| 方法 | 类名 | 说明 | 关键方法 |
|------|------|------|---------|
| editor($col, $label) | Field\Editor | TinyMCE | disk(), imageDirectory(), imageUrl(), height() |
| wangEditor($col, $label) | Field\WangEditor | WangEditor | 同Editor |
| markdown($col, $label) | Field\Markdown | Editor.md | htmlDecode(), height(), disk() |

## 特殊字段

| 方法 | 类名 | 说明 | 关键方法 |
|------|------|------|---------|
| map($lat, $lng, $label) | Field\Map | 地图选点 | baidu()/amap()/google()/tencent()/yandex(), height() |
| icon($col, $label) | Field\Icon | 图标选择 | FontAwesome Iconpicker |
| display($col, $label) | Field\Display | 只读显示 | with(Closure) |
| id($col, $label) | Field\Id | 主键显示 | 简化Display |
| button($html) | Field\Button | 按钮 | class(), on() |
| divider($title) | Field\Divide | 分割线 | 标题文字 |
| html($html, $label) | Field\Html | 自定义HTML | plain() |

## 复合字段

| 方法 | 类名 | 说明 | 关键方法 |
|------|------|------|---------|
| embeds($col, $label, Closure) | Field\Embeds | JSON子表单 | 闭包内嵌字段 |
| hasMany($col, $label, Closure) | Field\HasMany | 一对多 | mode('default'/'tab'/'table'), useTab(), useTable(), disableCreate(), disableDelete() |
| array($col, $label, Closure) | Field\ArrayField | 数组子表单 | 同HasMany(非关联) |
| table($col, $label, Closure) | Field\Table | 表格子表单 | 同ArrayField(表格视图) |
| keyValue($col, $label) | Field\KeyValue | 键值对 | setKeyLabel(), setValueLabel() |
| list($col, $label) | Field\ListField | 列表 | max(), min() |
| selectTable($col, $label) | Field\SelectTable | 弹窗选行 | from(LazyRenderable), pluck(), model(), dialogWidth(), max()(多选) |
| multipleSelectTable($col, $label) | Field\MultipleSelectTable | 弹窗多选 | 同SelectTable + max() |

## dcat-plus 扩展字段

| 方法 | 类名 | 说明 | 关键方法 |
|------|------|------|---------|
| sku($col, $label) | SkuField | SKU规格 | addColumn() |
| diyForm($col, $label) | DiyForm | 动态表单 | subComponentType(), addComponentType(), themeColor(), addPreviewHtml() |
| distpicker([$col1,$col2,$col3], $label) | Distpicker | 省市区 | autoselect() |
| iconimg($col, $label) | FormMedia\Iconimg | 图标图片 | uploadUrl(), disk(), path(), type(), limit(), nametype() |
| photo($col, $label) | FormMedia\Photo | 单图(媒体) | 同上, type='image', limit=1 |
| photos($col, $label) | FormMedia\Photos | 多图(媒体) | 同上, type='image', limit=9 |
| video($col, $label) | FormMedia\Video | 视频(媒体) | 同上, type='video' |
| audio($col, $label) | FormMedia\Audio | 音频(媒体) | 同上, type='audio' |
| files($col, $label) | FormMedia\Files | 多文件(媒体) | 同上, type='blend', limit=5 |

## 级联字段 (when运算符)

select/checkbox/radio 支持 when() 条件显示:
- `=`, `!=`, `>`, `<`, `>=`, `<=`, `in`, `notIn`, `has`

```php
$form->select('type')->options([...])->when('=', 1, function($form) {
    $form->text('field_a');
})->when('in', [2, 3], function($form) {
    $form->text('field_b');
});
```

## 远程联动 (load/loads)

select/checkbox/radio/selectTable 支持 load():
```php
$form->select('province')->load('city_id', '/api/cities');
$form->select('province')->loads(['city_id', 'district_id'], ['/api/cities', '/api/districts']);
```

## 文件上传通用方法 (WebUploader)

所有上传字段共享:
- accept($ext, $mimeTypes), maxSize($kb), chunked(true), chunkSize($kb)
- url($server), dir($dir), disk($disk), uniqueName(), sequenceName()
- removable(true=禁用删除), retainable(true=保留已删), saveFullUrl()
- compress(true), downloadable(), autoUpload()
- override(true=覆盖同名), limit($max=多文件)

## 表单事件回调

| 方法 | 触发时机 |
|------|---------|
| creating(Closure) | 新建页面访问时 |
| editing(Closure) | 编辑页面访问时 |
| submitted(Closure) | 表单提交后 |
| saving(Closure) | 保存前 |
| saved(Closure) | 保存后 |
| deleting(Closure) | 删除前 |
| deleted(Closure) | 删除后 |
| uploading(Closure) | 文件上传前 |
| uploaded(Closure) | 文件上传后 |
| fileDeleting(Closure) | 文件删除前 |
| fileDeleted(Closure) | 文件删除后 |

## 步骤表单

```php
$form->multipleSteps()
    ->add('步骤1', function($step) { $step->text('name'); })
    ->add('步骤2', function($step) { $step->textarea('desc'); })
    ->done('完成', function($done) { return view('done'); })
    ->remember();
```
