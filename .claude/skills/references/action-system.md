# dcat-plus-admin · Action 系统完整指南

> 适用版本：dcat-plus/laravel-admin 3.x。本文覆盖 **代码生成（`admin:action`）+ 自定义 Action 开发 API + 异步渲染** 全流程。
> Action 是"可点击、可触发 AJAX 后端逻辑"的按钮/链接单元；异步渲染类（lazy-table/lazy-form）是"懒加载渲染组件"，二者通过 `admin:action` 统一生成骨架。

---

## 目录

- [一、用命令生成骨架](#一用命令生成骨架)
- [二、Action 类型对照表](#二action-类型对照表)
- [三、处理型 Action 核心机制](#三处理型-action-核心机制)
- [四、Response：handle() 怎么返回](#四responsehandle-怎么返回)
- [五、可重写钩子一览](#五可重写钩子一览)
- [六、前端 JS 交互](#六前端-js-交互)
- [七、各容器注册 Action 的方式](#七各容器注册-action-的方式)
- [八、异步渲染（lazy-table / lazy-form）](#八异步渲染lazy-table--lazy-form)
- [九、完整实战示例](#九完整实战示例)
- [十、易错点与陷阱](#十易错点与陷阱)

---

## 一、用命令生成骨架

```bash
php artisan admin:action [action] [name] [--namespace=] [--base=] [--force]
```

| 参数 / 选项 | 必填 | 说明 |
|-------------|------|------|
| `action` | 否 | Action 类型，见[对照表](#二action-类型对照表)。不传则交互式选择 |
| `name` | 否 | 类名（PascalCase，如 `PublishPost`） |
| `--namespace=` | 否 | 完整命名空间。不传则按类型自动推断 |
| `--base=` | 否 | 应用目录（相对 `base_path()`），默认 `app` |
| `--force` | 否 | 类已存在时强制覆盖 |

```bash
# 全参数零交互（AI 首选）
php artisan admin:action lazy-form LoginUserForm
php artisan admin:action grid-row EditPost
php artisan admin:action default ClearCache --force

# 无参数 → 交互式四问
php artisan admin:action
```

> **关键**：只要传了 `action`（第一个位置参数）就是参数模式，namespace/base 自动用默认值、不再停下来询问。非法类型时报错并列出合法值。

---

## 二、Action 类型对照表

| 类型 key | 父类 | 默认命名空间 | 必须 | 注册入口 |
|----------|------|-------------|------|---------|
| `default` | `Dcat\Admin\Actions\Action` | `App\Admin\Actions` | `$title` + `handle()` | 页面/Widget 内直接 `new` 后输出 |
| `grid-row` | `Dcat\Admin\Grid\RowAction` | `App\Admin\Actions\Grid` | `$title` + `handle()` | `$grid->actions(...)` |
| `grid-batch` | `Dcat\Admin\Grid\BatchAction` | `App\Admin\Actions\Grid` | `$title` + `handle()` | `$grid->batchActions(...)` |
| `grid-tool` | `Dcat\Admin\Grid\Tools\AbstractTool` | `App\Admin\Actions\Grid` | `$title` + `handle()`/`href()` | `$grid->tools(...)` |
| `form-tool` | `Dcat\Admin\Form\AbstractTool` | `App\Admin\Actions\Form` | `$title` + `handle()`/`href()` | `$form->tools(...)` |
| `show-tool` | `Dcat\Admin\Show\AbstractTool` | `App\Admin\Actions\Show` | `$title` + `handle()`/`href()` | `$show->tools(...)` |
| `tree-row` | `Dcat\Admin\Tree\RowAction` | `App\Admin\Actions\Tree` | `$title` + `handle()` | `$tree->actions(...)` |
| `tree-tool` | `Dcat\Admin\Tree\AbstractTool` | `App\Admin\Actions\Tree` | `$title` + `handle()`/`href()` | `$tree->tools(...)` |
| `lazy-table` | `Dcat\Admin\Grid\LazyRenderable` | `App\Admin\Renderable` | `grid(): Grid` | `Modal::body()` / `LazyTable::make()` |
| `lazy-form` | `Dcat\Admin\Widgets\Form` + `implements LazyRenderable` + `use LazyWidget` | `App\Admin\Forms` | `handle(array)` + `form()` | `Modal::body(Form::make()->payload())` |

> 命名空间根 = `config('admin.route.namespace')` 去掉最后一段（`Controllers`）再拼 `Actions`/`Renderable`/`Forms`。默认 `App\Admin\Controllers` → `App\Admin`。

### 文件落点速查

```
app/Admin/
├── Actions/        # default / grid-* / form-tool / show-tool / tree-*
│   ├── Grid/       #   grid-batch · grid-row · grid-tool
│   ├── Form/       #   form-tool
│   ├── Show/       #   show-tool
│   └── Tree/       #   tree-row · tree-tool
├── Renderable/     # lazy-table（Grid\LazyRenderable）
└── Forms/          # lazy-form（Widgets\Form + LazyWidget）
```

---

## 三、处理型 Action 核心机制

> 适用：`default` 及所有 `grid-*`/`form-tool`/`show-tool`/`tree-*` 类型（即继承 `Actions\Action` 的）。
> `lazy-table` / `lazy-form` 不走这套，见[第八节](#八异步渲染lazy-table--lazy-form)。

### 3.1 继承链

```
Dcat\Admin\Actions\Action  (use HasActionHandler, HasAuthorization, HasHtmlAttributes)
├── Dcat\Admin\Grid\GridAction
│   ├── Grid\RowAction        (有 row() / getKey() 取行主键)
│   ├── Grid\BatchAction      (getKey() 返回选中 ID 数组)
│   └── Grid\Tools\AbstractTool(渲染成 <button>)
├── Dcat\Admin\Form\AbstractTool  (有 allowOnlyEditing/Creating)
├── Dcat\Admin\Show\AbstractTool
├── Dcat\Admin\Tree\RowAction
└── Dcat\Admin\Tree\AbstractTool
```

### 3.2 运行时流程

1. 点击按钮 → 前端 `action.js` 收集 `_action`(类名下划线化)、`_key`、`parameters()` 返回值，POST 到 `/dcat-api/action`
2. `HandleActionController::handle()` 反序列化类 → `setKey()` → 校验权限 → 调 `handle(Request $request)`
3. `handle()` 返回 `Response` → `send()` 输出 JSON → 前端按 `then`(redirect/refresh/script) 执行

### 3.3 handle() 签名

```php
use Illuminate\Http\Request;
use Dcat\Admin\Actions\Response;

public function handle(Request $request): Response
{
    $key = $this->getKey();   // RowAction: 当前行主键；BatchAction: 选中ID数组
    $params = $this->parameters(); // 或 $request->input(...)

    // 业务逻辑...

    return $this->response()->success('完成')->refresh();
}
```

- **RowAction**：`$this->getKey()` 取当前行主键，`$this->row()` 取整行模型。
- **BatchAction**：`$this->getKey()` 返回**选中行 ID 数组**（前端 `actionScript()` 自动注入），遍历处理即可。
- **AbstractTool**：通常无 key，靠 `parameters()` 传业务参数。

---

## 四、Response：handle() 怎么返回

`$this->response()` 返回 `Dcat\Admin\Actions\Response`（继承 `Http\JsonResponse`）。链式调用，**`then` 类（redirect/refresh/script）是覆盖语义，最后调的一个生效**。

| 方法 | 作用 |
|------|------|
| `success(?string $message)` | 成功提示（ Toastr 绿色） |
| `error(?string $message)` | 失败提示（中断 then） |
| `redirect(?string $url)` | 跳转 URL |
| `redirectToIntended(?string $url)` | 跳转到 intended URL |
| `refresh()` | 刷新当前页 |
| `script($js)` | 成功后执行任意 JS |
| `alert(bool $alert = true)` | 用 Swal 弹窗代替 Toastr |
| `withValidation(array $errors)` | 返回表单验证错误 |

```php
// 成功 + 刷新
return $this->response()->success('发布成功')->refresh();

// 成功 + 跳转
return $this->response()->success('完成')->redirect(admin_url('posts'));

// 成功 + 执行 JS（传 id 回前端）
return $this->response()
    ->success('已复制')
    ->script("Dcat.info('复制了 {$this->getKey()}')");

// 失败
return $this->response()->error('权限不足');

// Swal 大弹窗确认
return $this->response()->alert(true)->success('操作完成');
```

---

## 五、可重写钩子一览

所有处理型 Action 共享这些钩子（生成器 stub 已给出默认骨架）：

| 钩子 | 默认 | 作用 |
|------|------|------|
| `public $title` | `'Title'` | 按钮文字，必填 |
| `handle(Request): Response` | — | **业务逻辑入口，必填**（`href()` 型除外） |
| `confirm(): string\|array\|void` | `void`（无确认框） | 返回 `['确认标题','内容']` 弹确认框；返回字符串只做标题 |
| `parameters(): array` | `[]` | 附带到请求的额外参数（前端随 `_action` 提交） |
| `protected authorize($user): bool` | `true` | 权限校验，返回 false 则 `failedAuthorization()` |
| `href(): string` | 无 | **定义后变成纯链接**，不再触发 AJAX（`allowHandler=false`） |
| `protected html()` | button/link | 自定义按钮 HTML 结构 |
| `protected actionScript()` | 空函数 | 发起请求**前**的 JS 回调，return false 中断请求 |
| `protected resolverScript()` | 空函数 | 请求**成功**回调，return false 中断默认成功处理 |
| `protected rejectScript()` | 空函数 | 请求**出错**回调 |
| `protected handleHtmlResponse()` | `target.html(html)` | 处理接口返回 HTML 的回调 |

### RowAction / BatchAction 额外

| 方法 | 说明 |
|------|------|
| `getKey()` | RowAction: 当前行主键；BatchAction: 选中 ID 数组 |
| `row($key = null)` | RowAction 取行模型 / 行字段 |
| `resource()` | 当前 Grid 资源 URL |

### Form\AbstractTool 额外

| 方法 | 说明 |
|------|------|
| `static allowOnlyCreating(...)` | 仅新增页显示 |
| `static allowOnlyEditing(...)` | 仅编辑页显示 |

---

## 六、前端 JS 交互

### 6.1 三段 JS 钩子（PHP 重写 → 注入前端）

```php
class PublishPost extends RowAction
{
    // 发请求前：return false 可阻止请求
    protected function actionScript()
    {
        return <<<'JS'
function (data, target, action) {
    console.log('即将提交', data);
    // return false; // 取消
}
JS;
    }

    // 请求成功：return false 可阻止默认的成功处理（redirect/refresh/script）
    protected function resolverScript()
    {
        return <<<'JS'
function (target, results) {
    console.log('后端返回', results);
}
JS;
    }

    // 请求出错
    protected function rejectScript()
    {
        return <<<'JS'
function (target, results) {
    console.log('出错了', results);
}
JS;
    }
}
```

### 6.2 confirm() 确认框

```php
public function confirm()
{
    // 数组：[标题, 内容]
    return ['确认发布?', '发布后内容将对外可见'];
    // 字符串：仅标题
    // return '确认删除?';
}
```

### 6.3 parameters() 传额外参数

```php
protected function parameters()
{
    return [
        'scene' => 'publish',
        'flag'  => $this->row('status'),
    ];
}
// handle() 内：$request->input('scene') 或 $this->parameters()['scene']
```

---

## 七、各容器注册 Action 的方式

### 7.1 Grid（最常用）

```php
$grid->actions(function (Grid\Displayers\Actions $actions) {
    $actions->disableDelete();              // 关掉默认删除
    $actions->disableView();
    $actions->append(new \App\Admin\Actions\Grid\PublishPost());   // 加行操作
    $actions->prepend(new SomeAction());
    $actions->group(function ($g) {         // 下拉分组
        $g->append(new ActionA());
    }, 'feather icon-more-vertical', '更多操作');
});

// 或实例式
$grid->actions([new \App\Admin\Actions\Grid\EditPost()]);

// 顶部工具
$grid->tools(new \App\Admin\Actions\Grid\SyncData());
$grid->tools(function (Grid\Tools $tools) {
    $tools->append(new \App\Admin\Actions\Grid\ExportBtn());
});

// 批量操作（getKey() 返回选中ID数组）
$grid->batchActions(new \App\Admin\Actions\Grid\ApproveBatch());
$grid->tools(function (Grid\Tools $tools) {
    $tools->batch(function ($batch) {
        $batch->disableDelete();
        $batch->add(new \App\Admin\Actions\Grid\PublishBatch());
    });
});

// 默认按钮开关
$grid->disableEditButton();       $grid->showEditButton();
$grid->disableQuickEditButton();  $grid->showQuickEditButton();
$grid->disableViewButton();       $grid->showViewButton();
$grid->disableDeleteButton();     $grid->showDeleteButton();
$grid->disableActions();          $grid->showActions();
```

### 7.2 Form / Show

```php
// Form 顶部工具
$form->tools(new \App\Admin\Actions\Form\ExportBtn());
$form->tools(function (Form\Tools $tools) {
    $tools->append(new \App\Admin\Actions\Form\CustomTool());
});

// Show 详情页工具
$show->tools(new \App\Admin\Actions\Show\BackBtn());
$show->tools(function (Show\Tools $tools) {
    $tools->append(new \App\Admin\Actions\Show\CustomTool());
});
```

### 7.3 Tree

```php
$tree->actions(function (Tree\Actions $actions) {
    $actions->disableDelete();
    $actions->append(new \App\Admin\Actions\Tree\MoveUp());
});

$tree->tools(function (Tree\Tools $tools) {
    $tools->add(new \App\Admin\Actions\Tree\ExpandAll());
});
```

### 7.4 通用 Action（default）

放页面/Widget 内直接实例化（`__toString` 渲染成按钮）：

```php
return $content->body(new \App\Admin\Actions\ClearCache());
```

---

## 八、异步渲染（lazy-table / lazy-form）

> 这两类**不是 Action**，而是懒加载渲染组件，用 `Modal`/`Card`/`LazyTable` 承载。请求走 `/dcat-api/render` 或 `/dcat-api/form`，不走 `/dcat-api/action`。

### 8.1 lazy-table（异步表格）

继承 `Dcat\Admin\Grid\LazyRenderable`，实现 `grid(): Grid`：

```php
namespace App\Admin\Renderable;

use Dcat\Admin\Grid;
use Dcat\Admin\Grid\LazyRenderable;
use App\Models\User;

class UserTable extends LazyRenderable
{
    public function grid(): Grid
    {
        return Grid::make(new User(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('username');
            $grid->column('name');
            $grid->quickSearch(['id', 'username', 'name']);
            $grid->paginate(10);
            $grid->disableActions();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('username')->width(4);
                $filter->like('name')->width(4);
            });
        });
    }
}
```

**使用** —— Modal/Card/Box/Tab 接收渲染类实例时自动包 `LazyTable` 并启用 `simple()` 简化模式：

```php
// Modal 异步表格（最常用）
Modal::make()
    ->lg()
    ->title('选择用户')
    ->body(UserTable::make()->payload(['status' => 1]))  // payload 传参
    ->button('<button class="btn btn-primary">打开</button>');

// 不想用 simple 模式
LazyTable::make(UserTable::make(), true);
```

**simple 模式**（注入 Modal/Card 时自动启用）：去掉创建按钮、分页大小选择、批量删除、刷新按钮，filter 用简化容器，点击行选中。手动开关：`UserTable::make()->simple()`。

**payload 取参**：`$this->payload['status']` 或 `$this->status`（`__get` 从 payload 取）。

### 8.2 lazy-form（异步工具表单）

继承 `Dcat\Admin\Widgets\Form` + `implements LazyRenderable` + `use LazyWidget`：

```php
namespace App\Admin\Forms;

use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;

class UserProfile extends Form implements LazyRenderable
{
    use LazyWidget;

    // 注意签名是 array，不是 Request
    public function handle(array $input)
    {
        $id = $this->payload['id'] ?? null;          // 取异步创建时传入的参数
        $name = $input['name'];                      // 取表单提交值

        // 业务...

        return $this->response()->success('保存成功')->refresh();
    }

    public function form()
    {
        $this->text('name', '名称')->required();
        $this->email('email', '邮箱');
        $this->password('password', '密码');
    }

    public function default()
    {
        return ['name' => '默认名'];  // 表单默认值
    }
}
```

**使用**：

```php
Modal::make()
    ->lg()
    ->title('编辑用户')
    ->body(UserProfile::make()->payload(['id' => $id]))  // 传参
    ->button('编辑');
```

> Widget Form 与处理型 Action 的 `handle()` 签名不同：Action 是 `handle(Request)`，Widget Form 是 `handle(array $input)`（已 sanitize）。

### 8.3 payload 传递链路

```
创建：UserTable::make()->payload(['k'=>'v'])   → 存入 $this->payload
↓
生成 URL：payload 作为 query 附到 /dcat-api/render
↓
前端请求
↓
RenderableController: $renderable->payload($request->all())   → 请求参数全回灌 payload
↓
类内取值：$this->payload['k'] 或 $this->k
```

### 8.4 承载组件

| 组件 | 用法 |
|------|------|
| `Modal` | 自动识别 LazyRenderable/Grid\LazyRenderable，弹窗内懒渲染 |
| `Card`/`Box`/`Tab` | 直接 `Card::make($table)`，自动启 simple 模式 |
| `LazyTable::make($renderable, $load)` | 异步表格容器，`$load=false` 可手动触发 |
| `Lazy::make($renderable)` | 通用异步 HTML 容器（图表等） |

---

## 九、完整实战示例

### 9.1 行操作：发布文章（带确认 + 权限）

```php
// php artisan admin:action grid-row PublishPost
namespace App\Admin\Actions\Grid;

use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Actions\Response;
use Illuminate\Http\Request;
use App\Models\Post;

class PublishPost extends RowAction
{
    protected $title = '发布';

    public function handle(Request $request): Response
    {
        $post = Post::find($this->getKey());
        if (! $post) {
            return $this->response()->error('文章不存在');
        }
        $post->update(['status' => 1]);

        return $this->response()->success('已发布')->refresh();
    }

    public function confirm()
    {
        return ['确认发布?', '发布后对外可见'];
    }

    protected function authorize($user): bool
    {
        return $user->can('post.publish');
    }
}
```

挂载：`$grid->actions(function (Grid\Displayers\Actions $a) { $a->append(new PublishPost()); });`

### 9.2 批量操作：批量审批

```php
// php artisan admin:action grid-batch ApproveBatch
class ApproveBatch extends BatchAction
{
    protected $title = '批量审批';

    public function handle(Request $request): Response
    {
        $ids = $this->getKey();          // 选中 ID 数组
        Post::whereIn('id', $ids)->update(['status' => 1]);

        return $this->response()
            ->success('已审批 '.count($ids).' 条')
            ->refresh();
    }
}
```

挂载：`$grid->batchActions(new ApproveBatch());`

### 9.3 工具：导出按钮（链接型，href）

```php
// php artisan admin:action grid-tool ExportTool
class ExportTool extends \Dcat\Admin\Grid\Tools\AbstractTool
{
    protected $title = '导出';

    // 定义 href() 后变纯链接，不触发 AJAX
    protected function href()
    {
        return admin_url('posts/export'.request()->getQueryString());
    }
}
```

### 9.4 异步表单弹窗（编辑用户）

```php
// php artisan admin:action lazy-form EditUserForm
class EditUserForm extends Form implements LazyRenderable
{
    use LazyWidget;

    public function handle(array $input)
    {
        $id = $this->payload['id'];
        User::find($id)->update($input);
        return $this->response()->success('保存成功')->refresh();
    }

    public function form()
    {
        $this->text('name', '名称')->required();
        $this->email('email', '邮箱');
    }

    public function default()
    {
        $id = $this->payload['id'] ?? 0;
        return User::find($id)?->only(['name', 'email']) ?? [];
    }
}

// 挂在行操作里
$grid->actions(function (Grid\Displayers\Actions $a) {
    $a->append(
        Modal::make()
            ->lg()->title('编辑')
            ->body(EditUserForm::make()->payload(['id' => $a->getKey()]))
            ->button('<span class="mb-2 text-primary">编辑</span>')
    );
});
```

---

## 十、易错点与陷阱

| 现象 / 陷阱 | 原因 | 解决 |
|------|------|------|
| 点击没反应 / 404 | 类名含下划线（`_action` 反序列化用 `_` 当分隔符） | 类名只用字母数字，不含 `_` |
| 跳转/刷新/脚本都不生效 | `then` 类响应是**覆盖**语义 | redirect/refresh/script 只保留最后调用的一个 |
| 定义了 `href()` 后 handle 不执行 | `href()` 把 Action 变纯链接，`allowHandler=false` | 想触发 AJAX 就别定义 `href()` |
| BatchAction 的 getKey() 是数组 | 批量选中 ID 由前端注入 | `foreach`/`whereIn` 处理 |
| Widget Form 的 handle 收不到值 | 签名写成了 `handle(Request)` | 必须是 `handle(array $input)` |
| payload 取不到 | 拼错键名，或没经 `->payload()` 传 | 创建时 `::make()->payload(['k'=>'v'])`，类内 `$this->payload['k']` |
| `setupHtmlAttributes` vs `setUpHtmlAttributes` | Form/Show/Tree 的 AbstractTool 用无下划线版；Grid/基类用大写 U | 重写时注意对应类的实际方法名 |
| 权限 403 返回的是 JSON error 而非 abort | Action 重写了 `failedAuthorization()` 返回 `error(__('admin.deny'))` | 正常行为，前端会弹错误提示 |
| lazy-table 放 Modal 里样式简化了 | Modal/Card/Box 接收时自动启 simple 模式 | 不想要 simple 用 `LazyTable::make($table)` 显式包 |
| 生成的命名空间不对 | `config('admin.route.namespace')` 改过 | 生成时加 `--namespace=` |
| 类已存在报错 | 文件已存在 | 加 `--force` 覆盖 |

---

## 附：核心源码位置

| 关注点 | 文件 |
|--------|------|
| Action 基类 / 处理流程 | `src/Actions/Action.php`, `src/Actions/HasActionHandler.php` |
| 响应 | `src/Actions/Response.php`, `src/Http/JsonResponse.php` |
| 权限 | `src/Traits/HasAuthorization.php` |
| Grid Action | `src/Grid/{GridAction,RowAction,BatchAction}.php`, `src/Grid/Tools/AbstractTool.php` |
| Grid 注册 | `src/Grid/Concerns/{HasActions,HasTools}.php` |
| Form/Show/Tree Tool | `src/Form/AbstractTool.php`, `src/Show/AbstractTool.php`, `src/Tree/{RowAction,AbstractTool}.php` |
| 异步渲染 | `src/Grid/LazyRenderable.php`, `src/Support/LazyRenderable.php`, `src/Traits/LazyWidget.php` |
| 异步表单 | `src/Widgets/Form.php` |
| 承载组件 | `src/Widgets/{Lazy,LazyTable,Modal}.php` |
| 处理控制器 | `src/Http/Controllers/{HandleActionController,HandleFormController,RenderableController}.php` |
| 客户端 JS | `resources/assets/dcat/extra/action.js` |
| 生成 stub | `src/Console/stubs/actions/*.stub` |
