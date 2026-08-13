# API 接口开发规范

> 本规则是团队**编码约定 + 基类能力速查**，适用于项目内所有应用 API（`app/Api/` 会员端）、管理端 API（`app/Admin/Api/`）和扩展包 API。
> 基类源码：`app/Api/Controllers/BaseApiController.php`（会员端）、`app/Admin/Api/Controllers/BaseApiController.php`（管理端）。两者结构一致，管理端多 `downImportTplFile`/`exportData`/`field` 及方法名差异（见末节对照）。
> 新增或修改接口时必须同步维护接口注释、响应结构和路由组织方式。

## 适用范围

- 所有会员端 / 管理端 API 控制器统一继承 `App\Api\Controllers\BaseApiController`（或管理端 `App\Admin\Api\Controllers\BaseApiController`），通过基类复用 `lists / show / store / update / destroy / batchDestroy` 等通用能力，子类只负责声明模型、校验规则和差异化逻辑。
- 所有控制器继承基类时，**基类 `$model` 属性是 `protected`，子类可直接 `$this->model` 访问**，但**禁止在方法里 `new` 第二个模型**造成职责混乱；跨模型操作应走关联或单独的服务类。

## 一、基类能力速查（写接口前必读）

基类是 `abstract`，子类**必须实现** `getValidationRules()`，否则实例化报错。

### 1.1 基类提供的 CRUD 方法

| 方法 | 签名 | 行为 | 子类如何用 |
|------|------|------|-----------|
| `lists` | `lists(Request $request)` | 分页查询。**仅对 `$model->getFillable()` 内字段做精确 `where`**；支持 `sort`/`order` 排序（见 1.3）。返回 `data.list` + `data.page_info` | `return parent::lists($request)` |
| `show` | `show(int $id)` | `findOrFail($id)`，返回 `data.info`。找不到抛 `ModelNotFoundException`→404 | `return parent::show($id)` |
| `store` | `store(Request $request)` | 调 `getValidationRules('store')` → `$request->validate()` → `create()`。**规则为空抛 `\Exception('数据校验规则不能为空')`** | `return parent::store($request)` |
| `update` | `update(Request $request, int $id)`（会员端）/ `updates(...)`（管理端） | 同 store，`findOrFail`+`update` | `return parent::update(...)` |
| `destroy` | `destroy(int $id)` | `findOrFail`+`delete` | `return parent::destroy($id)` |
| `batchDestroy` | `protected batchDestroy(Request $request)` | **带事务**，`whereIn('id',$ids)->delete()`，返回 `deleted_count` | `return parent::batchDestroy($request)` |

> **重要**：基类**没有** `batchUpdate` 方法，需在子类自行实现（见骨架示例）。

### 1.2 基类辅助方法

| 方法 | 签名 | 说明 |
|------|------|------|
| `returnData` | `returnData($code='',$status='',$data=[],$msg='')` | 统一响应。`$status==1`→`success`，否则→`error`；`$data` 非数组→强制 `error`；空数组→转空对象 `{}`；`$msg` 空时取 `config('errorCode.'.$code)` |
| `returnCode` | `returnCode($code=0,$status='success',$msg='ok')` | 仅返回数组（非 JsonResponse），少用 |
| `pageintes` | `pageintes($list,$pagesize=20,$Resource=null,$list_total='')` | 封装分页结构。`$list` 是 `LengthAwarePaginator` 时自动取 items/total/currentPage；`$Resource` 非空则覆盖 items（传 Resource collection）；`$list_total` 非空覆盖总数 |
| `getSortableFields` | `protected getSortableFields():array` | 默认 `[]`（=不限制，所有 fillable 可排序）。子类覆写返回白名单 |
| `getValidationRules` | `abstract protected getValidationRules(string $action):array` | **必须实现**。返回 `[规则数组, 消息数组]`，见 1.4 |
| `getFullFieldName` | `getFullFieldName($exclude=['id','created_at','updated_at','deleted_at'])` | 取表全部字段名（缓存），用于导出等 |

### 1.3 lists() 的真实过滤/排序语法（勿与文档旧版混淆）

基类 `lists()` 实际只支持以下 query 参数：

| 参数 | 说明 |
|------|------|
| `pageSize` | 每页条数，默认 `10` |
| `sort` | 排序字段。若子类覆写了 `getSortableFields()` 返回非空数组，则字段必须在白名单内才生效；返回空数组则**不校验，任意 fillable 字段可排序**（注意 SQL 注入风险，务必覆写白名单） |
| `order` | `asc`(默认) / `desc` |
| 其它 | **仅当字段名 ∈ `getFillable()` 时**做**精确等值** `where($key,$value)`。**不支持** `like`/`gt`/`in`/`between` 等运算符语法 |

> ⚠️ 基类定义了 `applyWhereCondition()`（支持 gt/gte/lt/lte/like/in/between）但 **`lists()` 从未调用它**。若需要模糊/范围查询，**不要**在 URL 用 `[like]`/`[gt]` 语法（无效），应在子类**覆写 `lists()`** 自行组装 query，或用专用接口。

```php
// 需要模糊搜索时——覆写 lists
public function index(Request $request)
{
    $pageSize = $request->input('pageSize', 10);
    $query = $this->model->query();
    if ($kw = $request->input('keyword')) {
        $query->where('name', 'like', "%{$kw}%");
    }
    $items = $query->paginate($pageSize);
    return $this->returnData(0, 1, $this->pageintes($items, $pageSize), 'ok');
}
```

### 1.4 getValidationRules() 返回结构（基类实际读取方式）

基类 `store`/`update` 这样读取规则：
```php
$validationRules = $this->getValidationRules($action);   // $action = 'store' | 'update'
$required     = $validationRules[0] ?? [];   // 规则数组
$required_msg = $validationRules[1] ?? [];   // 消息数组
if (empty($required)) throw new \Exception('数据校验规则不能为空');
```

即基类**按 `$action` 参数决定调用时机**，但**返回值结构固定为 `[rules, messages]` 二元组**（不是按 action 键分组的嵌套数组）。因此子类应根据传入的 `$action` 内部分支返回对应规则：

```php
protected function getValidationRules(string $action): array
{
    if ($action === 'store') {
        return [
            ['username' => 'required|string|max:50', 'password' => 'required|string|min:6'],
            ['username.required' => '用户名不能为空', 'password.required' => '密码不能为空'],
        ];
    }
    // update：若允许全字段为空（部分更新），返回带 optional 的规则
    return [
        ['username' => 'sometimes|string|max:50'],
        [],
    ];
}
```

> **要点**：`store` 调用时返回的规则数组**不能为空**，否则抛异常。`update` 若无需必填，用 `sometimes` 或返回非空但宽松的规则。

## 二、控制器组织

- 禁止把一个扩展包的全部接口长期堆在 `IndexController` 中。
- 按业务表或稳定业务边界拆分控制器，例如 `PondController`、`TicketProductController`、`OrderController`、`StatsController`。
- 一个控制器对应一个主模型：构造函数注入 `parent::__construct(new XxxModel())`。
- 探活接口可保留 `IndexController`，但只能承载扩展状态、模块说明等轻量信息。
- 会员端 API 与管理端 API 分开目录维护，不共用控制器。

## 三、标准控制器骨架（以 MemberUserController 为基准模板）

新增 CRUD 控制器时，**严格按以下结构编写**，方法顺序固定为：构造 → index → show → store → update → destroy → batchUpdate → batchDelete → getValidationRules。不要随意增删方法顺序，也不要把业务方法插在 CRUD 方法中间。

```php
<?php
namespace App\Api\Controllers;

use Illuminate\Http\Request;
use App\Models\MemberUser;
use Dedoc\Scramble\Attributes\Group;

#[Group('用户', '用户', 2)] // 参数：中文名、英文名、排序权重
class MemberUserController extends BaseApiController
{
    public function __construct()
    {
        parent::__construct(new MemberUser());
    }

    /** 
     * 获取列表 (接口名称)
     * 
     * 获取用户列表，支持分页，昵称查询 （接口描述）
     */
    public function index(Request $request)
    {
        return parent::lists($request);
    }

    /** 获取单条记录 */
    public function show($id)
    {
        // show / destroy 类无 body 入参方法，用 Validator::make 校验路由参数
        $msg = \Validator::make(['id' => $id], [
            'id' => ['required', 'integer', 'min:1', 'exists:' . $this->model->getTable() . ',id'],
        ], [
            'id.required' => 'ID不能为空',
            'id.integer'  => 'ID必须为整数',
            'id.exists'   => '此ID不存在',
        ]);

        if ($msg->fails()) {
            throw new \Dcat\Admin\Exception\ApiException($msg->errors()->first());
        }

        return parent::show($id);
    }

    /** 创建记录 */
    public function store(Request $request)
    {
        return parent::store($request);
    }

    /** 更新单个记录 */
    public function update(Request $request, int $id)
    {
        return parent::update($request, $id);
    }

    /** 删除单个记录 */
    public function destroy($id)
    {
        return parent::destroy($id);
    }

    /** 批量更新（基类无此方法，子类自行实现） */
    public function batchUpdate(Request $request)
    {
        // 有 body 入参的接口统一用 $request->validate()，规则与错误信息成对返回
        $request->validate([
            'ids'         => ['required', 'array', 'min:1'],
            'ids.*'       => ['integer', 'exists:' . $this->model->getTable() . ',id'],
            'updateData'  => ['required', 'array'],
        ], [
            'ids.required' => 'ids不能为空',
            // ...
        ]);

        $ids   = $request->input('ids');
        $data  = $request->input('updateData');
        $count = $this->model->query()->whereIn('id', $ids)->update($data);

        return $this->returnData(0, 1, ['updated_count' => $count], '批量更新成功');
    }

    /** 批量删除（走基类带事务实现） */
    public function batchDelete(Request $request)
    {
        $request->validate([
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:' . $this->model->getTable() . ',id'],
        ], [ /* 错误信息 */ ]);

        return parent::batchDestroy($request);
    }

    /**
     * 数据校验规则（store / update 复用，由 BaseApiController 自动调用）
     *
     * @param string $action 操作类型：store 创建、update 更新
     * @return array 返回 [规则数组, 错误信息数组]；store 规则不能为空
     */
    protected function getValidationRules(string $action): array
    {
        if ($action === 'store') {
            return [
                ['username' => 'required', 'password' => 'required'],
                ['username.required' => '用户名不能为空', 'password.required' => '密码不能为空'],
            ];
        }
        return [['username' => 'sometimes|string'], []];
    }
}
```

### 编写要点

- **构造函数**：只注入主模型，不在构造里做查询、IO 或副作用。
- **CRUD 方法**：能直接 `parent::` 复用的，方法体只写一行 `return parent::xxx(...)`，不要复制基类实现造成分叉。需要差异化（如自定义 Resource、附加过滤）时再覆写逻辑，并在注释里写清覆写原因。
- **校验入口分两类**：
  - 有 body / query 入参（`index`、`store`、`update`、`batchUpdate`、`batchDelete`）：用 `$request->validate()`。
  - 仅路由参数（`show($id)`、`destroy($id)`）：用 `\Validator::make()` 手动校验，失败时 `throw new \Dcat\Admin\Exception\ApiException($errors->first())`。
  - `store` / `update` 的字段规则统一收敛进 `getValidationRules()`，由基类调用，**不要**在方法内重复写一遍 `validate`。
- **批量操作**：`ids` 必须校验为非空数组并对每个元素做 `exists` 校验，防止越权操作他表数据。批量删除走 `parent::batchDestroy()`（含事务），不要自己手写不带事务的删除。
- **排序白名单**：基类 `getSortableFields()` 默认返回 `[]`（不限制）。**务必在子类覆写**返回白名单，否则 `lists()` 会把任意 `sort` 参数直接拼进 `orderBy`，存在风险。

## 四、Scramble 注释（接口文档生成）

> Dedoc Scramble 通过**静态分析控制器代码**自动生成 OpenAPI 文档：路径参数来自路由、请求体来自 `validate` 规则、响应来自 `return` 语句与 Resource。下面的注释约定用于补充/覆盖自动推断的结果。

### 4.1 接口标题与描述（方法 PHPDoc，必填）

`summary` 是 PHPDoc 第一行，`description` 是其余文本。**每个公开接口方法必须写**：

```php
/** 
 * 获取列表                         ← summary（接口名称）
 * 
 * 获取用户列表，支持分页，昵称查询   ← description（接口描述）
 */
public function index(Request $request)
```

- 不需要登录的接口在方法 PHPDoc 加 `@unauthenticated`（参考 `AuthController::login`）。

### 4.2 分组与排序（类级属性）

```php
use Dedoc\Scramble\Attributes\Group;

#[Group('用户', '用户', 2)]   // 参数：中文名、英文名、排序权重 weight（小的排前面）
class MemberUserController extends BaseApiController
```

- 不写 `Group` 时按控制器名自动分组；同名类跨命名空间时默认用 FQCN，用 `#[SchemaName]` 显式命名避免冲突。
- 多标签可用类 PHPDoc `@tags a, b`（UI 只显示第一个）。

### 4.3 请求体字段注释（写在 validate 规则数组里）

字段级注释**紧贴字段写在 `validate` 规则数组里**，Scramble 会读取生成请求体说明。**约定必填字段写 `@default` 示例值**：

```php
$request->validate([
    /**
     * 用户名
     * @default 杨光
     */
    'username' => 'required',
    /**
     * 登陆密码
     * @default 12345678
     */
    'password' => 'required',
], [ 
    /* 错误信息（规则与消息成对） */
    'username.required' => '用户名 不能为空',
    'password.required' => '密码 不能为空',
]);
```

字段注释支持的全部标注：

| 标注 | 作用 |
|------|------|
| 普通文本首行 | 字段描述（也可用 `// 单行注释` 紧贴字段） |
| `@var type` | 覆盖从规则推断的类型（如 `@var array{lat: float, long: float}`、`@var 'USD'\|'EUR'`），手动类型**总是优先** |
| `@example value` | 示例值，字符串或合法 JSON |
| `@default value` | 默认值 |
| `@query` | 把该字段标记为 query 参数（用于非 GET 请求中确属 query 的字段） |
| `@ignoreParam` | 该字段不出现在文档 |

### 4.4 Scramble 能识别的请求来源

请求参数除 `validate` 外，以下写法也会被自动识别为文档参数：

1. `$request->validate([...])` / `$this->validate(...)` / `Validator::make($request->all(), [...])`
2. `FormRequest` 的 `rules()` 方法
3. 对 `$request` 的方法调用：`integer/float/boolean/enum/query/string/str/get/input/post`（如 `$request->integer('per_page', 15)` 会被记为 integer 参数且默认 15）
4. 路由路径参数 `{id}` 自动识别（类型来自路由模型绑定的主键；`int`→integer，UUID→string+uuid format）

> **GET/DELETE/HEAD** 的参数记为 query；**其它方法**记为 body。`file` 规则会把 Content-Type 切到 `multipart/form-data`。

### 4.5 支持的验证规则（影响推断的类型/约束）

`required` `string` `bool/boolean` `number` `int/integer` `array` `in`/`Rule::in`(→enum) `nullable` `email` `uuid` `exists`/`Rule::exists` `min` `max` `Enum` `confirmed` `file` `image` `date` `date_format` `size` `between` `Rule::when` `Rule::unless` `regex`。

要点：
- `in` / `Rule::in` / `Enum` 会被渲染成 enum 枚举值列表。
- `exists` 会查库推断字段类型；表不存在时按 `id`/`*_id` 猜 int。**UUID 字段必须显式加 `uuid` 规则**（库列是 varchar 无法区分）。
- `regex` 转 JSON Schema `pattern`（ECMA-262 风味），含 lookbehind/回溯等 PCRE 特性的正则会被跳过。
- 规则数组内**只能用**路由参数、静态调用、全局函数、局部变量；避免在 `validate` 前声明再使用的复杂局部变量（会导致分析错误）。

### 4.6 手动标注参数属性（复杂场景）

当需要补充描述/默认/示例，或自动推断不准时，用 `*Parameter` 属性（作用于控制器方法）：

```php
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\BodyParameter;

#[QueryParameter('per_page', description: '每页条数', type: 'int', default: 10, example: 20)]
#[PathParameter('id', description: '用户ID', type: 'integer')]
public function index(Request $request) { /* ... */ }
```

- 默认行为是**合并**：属性文档覆盖自动推断的同名字段。设 `infer: false` 可完全忽略推断的类型。
- 可用 `format`（如 `uuid`、`date-time`）、`required` 参数。

### 4.7 响应错误码自动识别

Scramble 会自动为以下情况生成错误响应：

| 来源 | 状态码 |
|------|--------|
| `validate` 调用 | 422 |
| `authorize` / `AuthorizationException` | 403 |
| `AuthenticationException` | 401 |
| 路由模型绑定（`findOrFail`） | 404 |
| `abort(400, '...')` / `abort_if` / `abort_unless` | 按传入码 |
| 方法 PHPDoc `@throws SomeException` | 按异常映射 |

异常→状态码映射：`AuthenticationException`→401、`AuthorizationException`→403、`ValidationException`→422、`NotFoundHttpException`/`RecordsNotFoundException`→404、`HttpException`→取其 `$statusCode`（**必须是字面量整数**才能被识别）。本项目 `ApiException` 继承自 `HttpException`，其响应码可被识别。

### 4.8 其它常用 PHPDoc / 属性

- `@operationId xxx` —— 覆盖自动生成的 operationId。
- `@requestMediaType multipart/form-data` —— 覆盖请求 Content-Type。
- `@status 201` / `@body User` —— 写在 `return` 语句上方，手动指定响应状态与类型。
- `@response LengthAwarePaginator<int, TodoItem>` / `@response AnonymousResourceCollection<TodoItemResource>` —— 手动标注分页器/匿名集合的类型。
- `#[Response(201, '描述', type: 'array{id: int}')]` —— 追加额外响应。
- `#[Header('X-Retry-After', '描述', type: 'int')]` —— 响应头文档。
- `#[IgnoreResponse(302)]` —— 移除某个被推断出的响应（如重定向），可重复，支持 `'30*'` 通配。
- `#[IgnoreParam('debug', 'query')]` —— 忽略某参数（可限定位置 query/path/header/cookie/body）。

### 4.9 列表过滤语法说明

列表过滤语法**以基类实际实现为准**（见 1.3）：`?pageSize=15&name=精确值&sort=created_at&order=desc`。模糊/范围查询需覆写 `lists()` 或开专用接口，并在 PHPDoc 中说明实际支持的 query 参数。

## 五、统一响应

- 所有 API 必须使用基类的 `returnData($code, $status, $data, $msg)` 返回，**禁止**裸写 `response()->json([...])`（仅登录签发 token 等基类覆盖不到的特例可例外，并在注释说明原因）。
- `returnData` 真实行为：
  - `$status == 1` → JSON `status` 为 `success`；**其它任何值** → `error`。所以统一传 `1/0`，基类自动映射。
  - `$data` **必须是数组**，否则强制 `status=error`；空数组自动转为空对象 `{}`。
  - `$msg` 为空时，`msg` 取 `config('errorCode.'.$code)`（错误码文案配置在 `config/errorCode.php`）。
- `returnData` 调用约定：成功 `returnData(0, 1, $data, 'ok')`；业务失败用对应业务 code + `0`。不要传字符串状态，统一用 `1/0`。
- 非分页成功响应：

```json
{ "code": 0, "status": "success", "msg": "ok", "data": {} }
```

- 分页成功响应（由 `pageintes()` 生成，禁止手拼）：

```json
{
  "code": 0, "status": "success", "msg": "ok",
  "data": {
    "list": [],
    "page_info": { "pagesize": 15, "page": 1, "total": 0 }
  }
}
```

- 详情成功响应（`show()` 返回）：

```json
{ "code": 0, "status": "success", "msg": "ok", "data": { "info": {} } }
```

- 异常响应（验证失败由 `ApiException` 统一抛出，格式由全局异常处理器保证）：

```json
{ "code": 422, "status": "error", "msg": "错误说明", "data": {} }
```

## 六、Resource（响应资源）

> Scramble 通过分析 Resource 的 `toArray()` 返回结构自动生成响应文档。**必须为每个 model 创建一个对应的 Resource 类**，列表/详情接口返回模型数据时一律走 Resource，禁止把模型直接塞进 `data`（仅内部或临时接口可例外）。

### 6.1 Resource 两种返回模式

本项目支持两种统一的 Resource 返回写法，**二选一保持整控制器一致**：

**模式 A —— Resource + `additional($this->returnCode())`（推荐，Scramble 文档友好）**

把统一响应信封（code/status/msg）通过 `additional` 合并到 Resource 顶层，Scramble 会完整识别 `data` 字段与附加字段：

```php
// 有分页的响应
$items = $query->paginate($pageSize);
return AdminHelpResource::collection($items)->additional($this->returnCode());

// 无分页的响应（详情）
$info = $this->model->query()->findOrFail($id);
return (new AdminHelpResource($info))->additional($this->returnCode());
```

- `returnCode()` 返回 `['code'=>0,'status'=>'success','msg'=>'ok']`，作为顶层附加字段。
- Scramble 会把 Resource 渲染成命名 schema 组件（可被其它 Resource `$ref` 复用），分页时自动识别 `data[]/links/meta` 结构。

**模式 B —— 基类 `returnData` + `pageintes`（统一信封严格）**

```php
return $this->returnData(0, 1, $this->pageintes($items, $pageSize, XxxResource::collection($items)), 'ok');
// 详情
return $this->returnData(0, 1, ['info' => new XxxResource($info)], 'ok');
```

> 区别：模式 A 信封字段来自 `returnCode()`；模式 B 来自 `returnData()`（空 data 转空对象、msg 取 errorCode 配置）。**Scramble 静态分析对模式 A 的结构识别更直接**（Resource collection + paginator），模式 B 需 Scramble 推断 `returnData` 内部结构。新接口推荐模式 A。

### 6.2 Resource 类命名与位置

- 命名按模型命名：`MemberUserResource`、`FishingPondResource`、`FishingOrderResource`、`AdminHelpResource`。
- 放 `app/Http/Resources/`。
- 类级用 `#[SchemaName('中文名')]` 显式命名（否则用类 basename；跨命名空间同名类会退化成 FQCN）。

### 6.3 Resource 字段注释规范（重点）

`toArray()` **每个字段必须有字段注释**，说明字段含义；需要时用 `@var` 标注类型、`@example`/`@default`/`@format` 补充。参考 `app/Http/Resources/AdminHelpResource.php`：

```php
<?php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Dedoc\Scramble\Attributes\SchemaName;

#[SchemaName('后台帮助')]
class AdminHelpResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            /**
             * id
             * @var string
             */
            'id' => $this->id,
            /**
             * 分类ID
             * @var string
             */
            'category_id' => $this->category_id,
            /**
             * 标题
             * @var string
             */
            'title' => $this->title,
            /**
             * 内容
             * @var string
             */
            'content' => $this->content,
            /**
             * 链接
             * @var string
             */
            'link' => $this->link,
            /**
             * 链接目标
             * @var string
             */
            'link_target' => $this->link_target,
            /**
             * 排序
             * @var string
             */
            'sort' => $this->sort,
            /**
             * 是否启用
             * @var string
             */
            'is_active' => $this->is_active,
            /**
             * 创建时间
             * @var string
             */
            'created_at' => $this->created_at,
            /**
             * 更新时间
             * @var string
             */
            'updated_at' => $this->updated_at,
        ];
    }
}
```

字段注释支持的标注（与请求字段一致）：

| 标注 | 作用 |
|------|------|
| 首行普通文本 | 字段描述 |
| `@var type` | 覆盖推断的类型。Scramble 推断类型优先级：字段注释 `@var` > 模型 cast > 数据库列 |
| `@example value` | 示例值 |
| `@default value` | 默认值 |
| `@format date-time` / `@format date` | 格式（用于经 `->toDateTimeString()` 转换的时间字段） |

### 6.4 模型解析（类型推断）

Scramble 需要知道 Resource 对应哪个模型才能推断 `$this->字段` 的类型：

- **默认**：从 `App\Models` 命名空间按 Resource 名查（`TodoItemsResource`→`TodoItem`，自动转单数）。
- **找不到时所有字段退化为 `string`**。
- **显式指定**（模型不在 `App\Models` 或命名不匹配时必须加）：

```php
use App\Domains\Todo\Models\TodoItem;

/**
 * @property TodoItem $resource
 * 或 @property-read TodoItem $resource
 * 或 @mixin TodoItem
 */
class TodoItemResource extends JsonResource { ... }
```

### 6.5 关联、嵌套与可选字段

```php
return [
    'id' => $this->id,
    // 嵌套 Resource：只在该关联已加载时输出，文档仍会引用 UserResource schema
    'author' => UserResource::make($this->whenLoaded('author')),
    // 经访问器/方法计算的字段，用 @var 标类型
    /** @var array<string, ThreadResource> */
    'threads' => $this->threads->keyBy('name')->mapInto(ThreadResource::class),
];
```

- 嵌套 Resource 会被记为 schema `$ref` 引用（可复用组件）。
- `with()` 方法或 `additional()` 传入的顶层字段也会被文档化。
- 定义 `withResponse(Request, JsonResponse)` 方法可改响应状态码（如 `$response->setStatusCode(201)`），Scramble 会据此调整文档状态码。

### 6.6 分页响应

把 paginator 传给 `Resource::collection()` 时，Scramble 自动文档化 `data[]/links/meta` 结构。支持 `LengthAwarePaginator`(`paginate`)、`Paginator`(`simplePaginate`)、`CursorPaginator`(`cursorPaginate`)。如需自定义分页输出结构，在 Resource collection 类定义 `paginationInformation($request, $paginated, $default)` 方法。

## 七、路由

- 路由文件只负责 URL 到控制器方法的映射，不写业务逻辑。
- 公开接口（登录、探活）和需登录接口必须明确分组，需登录组挂对应中间件（如 `auth:memberapi`）。
- 不破坏已发布路径；确需改路径时必须在 README 或交接文档说明，并保留旧路径过渡。

## 八、会员端 / 管理端基类差异对照

| 能力 | 会员端 `App\Api\Controllers\BaseApiController` | 管理端 `App\Admin\Api\Controllers\BaseApiController` |
|------|------|------|
| 列表 | `lists()` | `lists()` |
| 详情 | `show($id)` | `show($id)` |
| 创建 | `store()` | `store()` |
| 更新 | `update($request,$id)` | **`updates($request,$id)`**（方法名带 s） |
| 删除 | `destroy($id)` | `destroy($id)` |
| 批量删除 | `batchDestroy()`（protected，带事务） | `batchDestroy()`（protected，带事务） |
| 导入模板 | ❌ | `downImportTplFile()`（生成 xlsx，自动读表注释） |
| 数据导出 | ❌ | `exportData()`（protected，按 fillable + filters 导出 xlsx） |
| 字段元信息 | ❌ | `field()`（返回表字段+类型+注释） |
| 其余 | `returnData`/`pageintes`/`getSortableFields`/`getValidationRules`/`getFullFieldName`/`applyWhereCondition`/`returnCode` 均一致 | 同左 |

> 管理端控制器覆写更新方法时方法名是 `updates`，路由绑定要对应；会员端是 `update`。

## 九、AI 小程序接口交付文档

### 交付目标与时机

- 每次完成一个可供微信小程序使用的业务模块接口后，必须同步维护仓库根目录 `ai-delivery/` 下的交付文档；接口新增、字段变更、枚举变更、鉴权或业务规则变更时，必须在同一任务内更新对应文档。
- 交付文档的读者是接手小程序开发的 AI。目标是让其只阅读 `ai-delivery/` 即可理解业务场景、页面所需数据、接口契约、状态流转与枚举含义，**不得要求其通过阅读 Controller、Model 或迁移文件猜测接口行为**。
- 文档记录当前已实现且可调用的契约，不记录计划中、未发布或未经验证的接口；示例中的 URL、字段名、类型、必填项、默认值、枚举值必须与代码和实际响应一致。
- 新模块首次交付时创建以下三个文件；后续模块在现有文件内按业务域追加或更新。没有 `ai-delivery/` 目录时允许创建。小程序开发开始前必须先阅读这三个文件。

### 必交付文件

1. `ai-delivery/context.md`：业务上下文、实体关系、用户流程、页面数据依赖与鉴权边界。
2. `ai-delivery/api_list.md`：接口清单、请求与响应契约、调用顺序、异常处理和 JSON 示例。
3. `ai-delivery/enum_dictionary.md`：所有前端可见状态、类型、角色等枚举的值、文案、含义与可执行操作。

### `context.md` 模板

```md
# 业务上下文

## 模块概览
- 模块名称：协会活动报名
- 面向角色：游客、已登录会员、协会管理员
- 业务目标：会员查看活动并提交报名，管理员审核和管理报名。

## 核心实体
| 实体 | 主键 | 关键字段 | 与其他实体的关系 | 小程序用途 |
| --- | --- | --- | --- | --- |
| 活动 | `activity.id` | `title`、`signup_start_at`、`signup_end_at`、`status` | 一个活动有多个报名记录 | 活动列表、详情页 |
| 报名记录 | `registration.id` | `activity_id`、`member_id`、`status` | 属于一个活动和一个会员 | 我的报名、报名结果 |

## 用户流程
1. 用户进入活动列表，读取可报名活动。
2. 用户查看活动详情，根据报名状态决定是否展示报名按钮。
3. 已登录会员提交报名；接口返回报名记录和当前状态。
4. 用户在“我的报名”查看审核或取消状态。

## 页面与数据依赖
| 页面 / 组件 | 调用接口 | 必需数据 | 加载时机 | 空态 / 异常处理 |
| --- | --- | --- | --- | --- |
| 活动列表 | `GET /api/activities` | 标题、封面、报名时间、状态 | 页面进入与下拉刷新 | 空列表展示“暂无活动” |
| 活动详情 | `GET /api/activities/{id}` | 详情、剩余名额、当前用户报名状态 | 进入详情页 | 404 返回列表页 |

## 鉴权与业务边界
- `GET /api/activities`：无需登录。
- `POST /api/activities/{id}/registrations`：需要 `memberapi` 登录态。
- 同一会员不可重复报名；报名截止后不可创建；规则失败时以前端不可继续操作的业务错误返回。
```

### `api_list.md` 模板

```md
# 接口清单

## 通用约定
- Base URL：`/api`
- 鉴权：需登录接口在请求头携带 `Authorization: Bearer <token>`。
- 成功响应：`code` 为 `0`，`status` 为 `success`。
- 失败响应：以 `code`、`msg` 为准；前端不得只依据 HTTP 状态码判断业务结果。
- 分页响应：读取 `data.list` 与 `data.page_info`，不要假定接口返回全量数据。

## 活动列表
- **用途**：为活动列表页提供分页活动数据。
- **请求**：`GET /api/activities`
- **鉴权**：否

### Query 参数
| 字段 | 类型 | 必填 | 示例 | 说明 |
| --- | --- | --- | --- | --- |
| `page` | integer | 否 | `1` | 页码，默认值以接口实际行为为准 |
| `pagesize` | integer | 否 | `15` | 每页条数 |
| `status` | integer | 否 | `1` | 见枚举字典“活动状态” |

### 成功响应示例
```json
{
  "code": 0,
  "status": "success",
  "msg": "ok",
  "data": {
    "list": [
      {
        "id": 1,
        "title": "周末钓鱼活动",
        "cover_image": "https://example.com/activity.jpg",
        "signup_end_at": "2026-08-31 18:00:00",
        "status": 1
      }
    ],
    "page_info": {
      "pagesize": 15,
      "page": 1,
      "total": 1
    }
  }
}
```

### 字段说明
| 字段路径 | 类型 | 是否可能为空 | 含义 | 小程序处理建议 |
| --- | --- | --- | --- | --- |
| `data.list[].id` | integer | 否 | 活动 ID | 用于详情、报名等后续请求 |
| `data.list[].cover_image` | string | 是 | 活动封面图 URL | 为空时显示默认视觉样式，不得拼接存储路径 |
| `data.list[].status` | integer | 否 | 活动状态 | 使用枚举字典映射文案与样式 |

### 失败场景
| `code` / 场景 | `msg` 示例 | 前端处理 |
| --- | --- | --- |
| `422` 参数校验失败 | `页码必须为整数` | 保留页面并停止加载，可记录开发日志 |
| `401` 未登录 | `未登录` | 清理失效登录态并进入登录流程 |
```

- 每个接口必须按上述粒度记录：业务用途、请求方法和路径、鉴权要求、路径参数、Query 参数、Body 参数、成功响应 JSON、响应字段表、失败场景及小程序处理方式。
- `Body` 与响应 JSON 必须包含具有代表性的真实字段和值；密码、token、手机号、身份证号等敏感值必须使用脱敏或虚构示例，禁止写入真实数据。
- 接口存在前置调用、幂等约束、重复提交限制、状态前置条件、轮询或刷新要求时，必须明确写在接口章节中，不能只散落在业务描述中。
- 字段表必须使用完整路径（如 `data.list[].status`），标明类型、是否可空、业务含义和前端处理建议；时间格式、金额单位与图片 URL 是否完整可访问必须明确。

### `enum_dictionary.md` 模板

```md
# 枚举字典

## 活动状态
- 作用字段：`activity.status`、`data.list[].status`

| 值 | 文案 | 业务含义 | 小程序展示 / 可执行操作 |
| --- | --- | --- | --- |
| `0` | 草稿 | 仅管理员可见，未发布 | C 端不展示 |
| `1` | 报名中 | 用户可查看并在符合条件时提交报名 | 展示“立即报名” |
| `2` | 已结束 | 活动已结束 | 展示结束状态，不允许报名 |

## 报名状态
- 作用字段：`registration.status`、`data.registration.status`

| 值 | 文案 | 业务含义 | 小程序展示 / 可执行操作 |
| --- | --- | --- | --- |
| `0` | 待审核 | 报名已提交，等待审核 | 展示“审核中” |
| `1` | 已通过 | 报名有效 | 展示“报名成功” |
| `2` | 已拒绝 | 审核未通过 | 展示原因（如果响应提供） |
| `3` | 已取消 | 用户或管理员取消报名 | 不允许再次取消 |
```

- 所有暴露给小程序的整数、字符串枚举必须收录，包括状态、类型、角色、支付状态、审核状态和开关语义；禁止仅写“0/1 状态”而不说明业务含义。
- 一个枚举值的可用操作由服务端最终校验；文档需明确前端可展示的操作，但不得把前端隐藏按钮当作安全控制。

### 交付验收

- 完成接口开发后，先以实际路由、请求校验规则、Resource / 返回数据、业务异常和测试结果为依据核对文档；不得从需求描述反推未经实现的字段。
- 至少验证：接口路径与方法正确、登录要求正确、请求字段和必填规则正确、响应字段和嵌套路径正确、分页结构正确、枚举完整、失败场景可供小程序处理。
- 同一接口的 Scramble PHPDoc 与 `ai-delivery/api_list.md` 必须保持一致：Scramble 面向通用接口浏览，`ai-delivery/` 面向 AI 小程序快速开发；两者冲突时先修正代码契约，再同步修正文档。
- 在任务 README 的“关键文件清单”和“验证方式”中登记本次更新的 `ai-delivery/` 文件及核验结果，确保接口、文档与任务记录可追溯。
