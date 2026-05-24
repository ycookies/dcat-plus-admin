# 🔒 dcat-plus/laravel-admin 安全审计报告

| 项目 | 详情 |
|------|------|
| **审计目标** | dcat-plus/laravel-admin 扩展包 |
| **审计环境** | PHP 8.3 / Laravel 13 |
| **审计范围** | `src/` 全部源码 |
| **审计时间** | 2026-05-24 |
| **审计方法** | 静态代码分析 |

---

## 📊 漏洞总览

| 严重程度 | 数量 | 漏洞编号 |
|---------|------|---------|
| 🔴 Critical | 4 | CVE-1 ~ CVE-4 |
| 🟠 High | 7 | VUL-5 ~ VUL-11 |
| 🟡 Medium | 6 | VUL-12 ~ VUL-17 |
| 🔵 Low | 4 | VUL-18 ~ VUL-21 |
| **合计** | **21** | |

---

## 🔴 Critical 严重漏洞

### CVE-1: 编辑器上传接口无文件类型验证 — 任意文件上传 (RCE)

**严重程度：** 🔴 Critical  
**攻击前提：** 已认证的管理员用户  
**漏洞类型：** CWE-434 Unrestricted Upload of File with Dangerous Type

**涉及文件：**
- `src/Http/Controllers/EditorMDController.php` (第 12-23 行)
- `src/Http/Controllers/TinymceController.php` (第 12-23 行)

**漏洞代码：**

```php
// EditorMDController.php
public function upload(Request $request)
{
    $file = $request->file('editormd-image-file');
    $dir = trim($request->input('dir'), '/');
    $disk = $this->disk();
    $newName = $this->generateNewName($file); // 仅用 uniqid 重命名，保留原始扩展名
    $disk->putFileAs($dir, $file, $newName);  // ❌ 无任何文件类型验证，直接存储！
    return ['success' => 1, 'url' => $disk->url("{$dir}/$newName")];
}

protected function generateNewName(UploadedFile $file)
{
    return uniqid(md5($file->getClientOriginalName())).'.'.$file->getClientOriginalExtension();
    // ❌ 保留原始扩展名，攻击者可上传 shell.php
}
```

**风险分析：** 两个编辑器上传接口完全没有文件类型、扩展名或 MIME 验证。攻击者可上传 `.php`、`.phtml`、`.php7`、`.phar` 等可执行文件，获取服务器控制权。

**修复建议：**

```php
// 1. 添加文件类型白名单验证
protected function validateFile(UploadedFile $file)
{
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'];
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];

    $ext = strtolower($file->getClientOriginalExtension());
    $mime = $file->getMimeType(); // 服务端 MIME 检测

    if (!in_array($ext, $allowedExts) || !in_array($mime, $allowedMimes)) {
        throw new \Exception('不支持的文件类型');
    }
}

// 2. 在 upload() 方法中调用验证
public function upload(Request $request)
{
    $file = $request->file('editormd-image-file');
    $this->validateFile($file); // ✅ 添加验证
    // ... 原有逻辑
}
```

---

### CVE-2: 任意存储磁盘选择

**严重程度：** 🔴 Critical  
**攻击前提：** 已认证的管理员用户  
**漏洞类型：** CWE-639 Authorization Bypass Through User-Controlled Key

**涉及文件：**
- `src/Http/Controllers/EditorMDController.php` (第 33-38 行)
- `src/Http/Controllers/TinymceController.php` (第 33-38 行)
- `src/Form/Extend/FormMedia/Controllers/FormMedia.php` (第 26-29, 72-75, 130-133 行)

**漏洞代码：**

```php
protected function disk()
{
    $disk = request()->input('disk') ?: config('admin.upload.disk'); // ❌ 用户可控磁盘！
    return Storage::disk($disk); // ❌ 可操作 filesystems.php 中配置的任意磁盘
}
```

**风险分析：** 攻击者可指定 `local` 磁盘写入 `storage/` 目录，可能覆盖 `.env` 文件或其他敏感文件；也可指定 `s3` 等云存储磁盘操作云端数据。

**修复建议：**

```php
protected function disk()
{
    return Storage::disk(config('admin.upload.disk')); // ✅ 只使用配置默认值
}
```

---

### CVE-3: 任意类实例化 — 潜在 RCE

**严重程度：** 🔴 Critical  
**攻击前提：** 已认证的管理员用户  
**漏洞类型：** CWE-94 Code Injection

**涉及文件：**
- `src/Http/Controllers/HandleActionController.php` (第 37-57 行)
- `src/Http/Controllers/HandleFormController.php` (第 94-114 行)
- `src/Http/Controllers/ValueController.php` (第 39-56 行)

**漏洞代码：**

```php
// HandleActionController.php
protected function resolveActionInstance(Request $request): Action
{
    $actionClass = str_replace('_', '\\', $request->input('_action')); // ❌ 用户可控类名
    if (! class_exists($actionClass)) { throw ... }
    $action = app($actionClass); // ❌ 可实例化任意已注册的类
    if (! method_exists($action, 'handle')) { throw ... }
    return $action;
}

// ValueController.php
protected function resolve(Request $request)
{
    $key = $request->input('_key'); // ❌ 用户可控类名
    if (! class_exists($key)) { throw ... }
    $instance = app($key); // ❌ 可实例化任意类
    if (! method_exists($instance, 'handle')) { throw ... }
    return $instance;
}

// HandleFormController.php
protected function resolveForm(Request $request)
{
    $formClass = $request->input(Form::REQUEST_NAME); // ❌ 用户可控类名
    if (! class_exists($formClass)) { throw ... }
    $form = app($formClass); // ❌ 可实例化任意类
    if (! method_exists($form, 'handle')) { throw ... }
    return $form;
}
```

**风险分析：** 攻击者可实例化应用中的任意具有 `handle()` 方法的类。Laravel 生态中有大量第三方包，其中很多类的 `handle()` 方法可能产生副作用（文件操作、命令执行等）。

**修复建议：**

```php
// 方案1: 白名单验证 — 类必须继承特定基类/实现特定接口
protected function resolveActionInstance(Request $request): Action
{
    $actionClass = str_replace('_', '\\', $request->input('_action'));

    if (! class_exists($actionClass)) { throw new AdminException("..."); }

    if (! is_subclass_of($actionClass, Action::class)) { // ✅ 验证类型
        throw new AdminException("Invalid action class.");
    }

    return app($actionClass);
}

// 方案2: 加密签名验证类名（推荐）
// 在渲染时对类名进行 HMAC 签名，请求时验证签名
```

---

### CVE-4: 默认使用客户端原始文件名 — 路径穿越 + 危险扩展名

**严重程度：** 🔴 Critical  
**攻击前提：** 已认证的管理员用户  
**漏洞类型：** CWE-22 Path Traversal / CWE-434 Unrestricted Upload

**涉及文件：**
- `src/Form/Field/UploadField.php` (第 120-139, 313-316 行)

**漏洞代码：**

```php
// 默认使用客户端文件名，无任何过滤
protected function getStoreName(UploadedFile $file)
{
    if ($this->useUniqueName) { return $this->generateUniqueName($file); }
    if ($this->useSequenceName) { return $this->generateSequenceName($file); }
    // ...
    return $file->getClientOriginalName(); // ❌ 客户端文件名，可含 ../ 和 .php
}

// 即使使用 uniqueName 也保留危险扩展名
protected function generateUniqueName(UploadedFile $file)
{
    return md5(uniqid()).'.'.$file->getClientOriginalExtension(); // ❌ 保留 .php
}
```

**修复建议：**

```php
// 危险扩展名黑名单
protected function getSafeExtension(UploadedFile $file): string
{
    $dangerous = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8',
        'phtml', 'pht', 'phar', 'inc', 'cgi', 'pl',
        'asp', 'aspx', 'jsp', 'py', 'sh', 'bash', 'bat',
        'hta', 'htaccess', 'svg', // SVG 可含 JS
    ];
    $ext = strtolower($file->getClientOriginalExtension());
    return in_array($ext, $dangerous) ? 'bin' : $ext;
}

protected function generateUniqueName(UploadedFile $file)
{
    return md5(uniqid()).'.'.$this->getSafeExtension($file); // ✅ 过滤危险扩展名
}

protected function getStoreName(UploadedFile $file)
{
    // ... 原有逻辑
    // 安全回退：使用哈希名 + 安全扩展名
    return md5($file->getContent().microtime(true)).'.'.$this->getSafeExtension($file);
}
```

---

## 🟠 High 高危漏洞

### VUL-5: SKU 删除接口路径穿越 — 可删除任意文件

**严重程度：** 🟠 High  
**涉及文件：** `src/Form/Extend/Sku/Controllers/UploadController.php` (第 33-48 行)

```php
public function delete(): JsonResponse
{
    $disk = config('admin.upload.disk');
    $path = request()->input('path'); // ❌ 用户完全可控路径
    if (!Storage::disk($disk)->exists($path)) { ... }
    Storage::disk($disk)->delete($path); // ❌ 可删除磁盘上任意文件
}
```

**修复建议：**

```php
public function delete(): JsonResponse
{
    $path = request()->input('path');
    $disk = Storage::disk(config('admin.upload.disk'));

    // ✅ 限制只能删除 sku 目录下的文件
    $fullPath = $disk->path($path);
    $allowedBase = $disk->path('sku');
    if (!str_starts_with(realpath(dirname($fullPath)), realpath($allowedBase))) {
        return response()->json(['code' => 403, 'message' => '非法路径']);
    }

    $disk->delete($path);
}
```

---

### VUL-6: SKU 上传接口无文件类型验证

**严重程度：** 🟠 High  
**涉及文件：** `src/Form/Extend/Sku/Controllers/UploadController.php` (第 16-26 行)

```php
public function store(): JsonResponse
{
    $file = request()->file('file');
    $path = Storage::disk($disk)->put('sku', $file); // ❌ 无文件类型验证
}
```

**修复建议：** 添加图片类型白名单验证（SKU 场景应只允许图片）。

---

### VUL-7: 登录无暴力破解防护

**严重程度：** 🟠 High  
**涉及文件：** `src/Http/Controllers/AuthController.php`

**问题：** 登录接口无速率限制、无账号锁定机制、无验证码。攻击者可无限次尝试密码。

**修复建议：**

```php
// 在路由中添加 throttle 中间件
Route::post('auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // 每分钟最多5次
```

---

### VUL-8: orderByRaw SQL 注入风险

**严重程度：** 🟠 High  
**涉及文件：** `src/Repositories/EloquentRepository.php` (第 235-263 行)

```php
protected function setOrderBy(Grid\Model $model, $column, $type, $cast)
{
    if ($isJsonColumn) {
        $column = "JSON_UNQUOTE(JSON_EXTRACT({$col}, '$.{$parts}'))";
    }
    if (!empty($cast)) {
        $model->addQuery('orderByRaw', ["CAST({$column} AS {$cast}) {$type}"]); // ❌
    }
    if ($isJsonColumn) {
        $model->addQuery('orderByRaw', ["{$column} {$type}"]); // ❌
    }
}
```

**修复建议：**

```php
protected function setOrderBy(Grid\Model $model, $column, $type, $cast)
{
    // ✅ 严格验证排序方向
    $type = in_array(strtolower($type), ['asc', 'desc']) ? $type : 'asc';
    // ✅ 验证列名只允许合法字符
    if (!preg_match('/^[a-zA-Z0-9_\-\.>]+$/', $column)) {
        return;
    }
    // ... 原有逻辑
}
```

---

### VUL-9: ScaffoldController 旧方法 SQL 注入

**严重程度：** 🟠 High  
**涉及文件：** `src/Http/Controllers/ScaffoldController.php` (第 858-919 行)

```php
protected function getDatabaseColumnsOld($db = null, $tb = null) {
    $sql = sprintf('SELECT * FROM information_schema.columns WHERE table_schema = "%s"', $value['database']);
    if ($tb) {
        $sql .= " AND TABLE_NAME = '{$p}{$tb}'"; // ❌ $tb 来自用户输入，字符串拼接
    }
    $tmp = DB::connection($connectName)->select($sql); // ❌ 直接执行
}
```

**修复建议：** 删除 `getDatabaseColumnsOld` 方法，使用已修复的 `getDatabaseColumns` 方法。

---

### VUL-10: 不安全的反序列化

**严重程度：** 🟠 High  
**涉及文件：** `src/Core/Util/CurlUtil.php` (第 245-264 行)

```php
public static function proxyRequest($proxy, $url, $param = [], $option = [])
{
    $content = self::getRaw($url);
    $content = @base64_decode($content);
    $content = @unserialize($content); // ❌ 不安全的反序列化！
    return $content;
}

public static function proxyCommon($proxy, $package)
{
    $content = self::getRaw($url);
    $content = @base64_decode($content);
    $content = @unserialize($content); // ❌ 不安全的反序列化！
    return $content;
}
```

**风险：** PHP Object Injection，如果远程服务器被攻陷或 URL 可控，可导致 RCE。

**修复建议：** 将 `unserialize()` 替换为 `json_decode()`。

---

### VUL-11: SSL 证书验证全局禁用

**严重程度：** 🟠 High  
**涉及文件：** `src/Core/Util/CurlUtil.php` (第 199-201, 289-291, 332-334 行)

```php
if (strpos($url, 'https://') === 0) {
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ❌ 禁用 SSL 验证
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // ❌ 禁用 Host 验证
}
```

**风险：** 中间人攻击（MITM），攻击者可拦截/篡改 HTTPS 请求内容。

**修复建议：**

```php
// ✅ 启用 SSL 验证
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
```

---

## 🟡 Medium 中危漏洞

### VUL-12: FormMedia 文件管理器多安全问题

**严重程度：** 🟡 Medium  
**涉及文件：**
- `src/Form/Extend/FormMedia/Controllers/FormMedia.php`
- `src/Form/Extend/FormMedia/MediaManager.php`

**问题清单：**
1. 磁盘可由用户参数 `disk` 指定（同 CVE-2）
2. 路径 `path` 参数无穿越检查
3. `nametype=original` 模式使用客户端原始文件名（含危险扩展名）
4. `createFolder` 的 `name` 参数无过滤
5. 文件类型黑名单中不包含 `php`（`php` 在 `code` 类型白名单中，而非被拦截）

**修复建议：** 统一添加路径规范化、扩展名黑名单、磁盘参数锁定。

---

### VUL-13: 编辑器上传目录 `dir` 参数可控

**严重程度：** 🟡 Medium  
**涉及文件：**
- `EditorMDController.php` (第 15 行)
- `TinymceController.php` (第 15 行)

```php
$dir = trim($request->input('dir'), '/'); // ❌ 仅去除首尾斜杠，不防穿越
$disk->putFileAs($dir, $file, $newName);  // ❌ 可写到任意目录
```

**修复建议：** 对 `dir` 做白名单或规范化校验。

---

### VUL-14: Form 文件删除路径无白名单校验

**严重程度：** 🟡 Medium  
**涉及文件：** `src/Http/Controllers/HandleFormController.php` (第 75-86 行)

```php
public function destroyFile(Request $request)
{
    $field = $this->getField($request, $form);
    $field->deleteFile($request->key); // ❌ key 直接用于文件删除，无路径校验
}
```

---

### VUL-15: 分块上传临时文件竞争条件

**严重程度：** 🟡 Medium  
**涉及文件：** `src/Support/WebUploader.php` (第 135-158 行)

**问题：**
- `$this->_id` 来自用户输入 `$request->input('_id')`，无格式验证
- 临时文件名 `md5($file->getClientOriginalName())` 可预测
- `isComplete()` 检查与 `mergeChunks()` 操作非原子性

**修复建议：** 对 `_id` 添加格式验证（仅允许字母数字），使用 `random_bytes()` 生成临时文件名。

---

### VUL-16: Scaffold 控制器在 debug 模式下无权限限制

**严重程度：** 🟡 Medium  
**涉及文件：** `src/Http/Controllers/ScaffoldController.php` (第 69-71, 110-112 行)

```php
public function index(Content $content) {
    if (!config('app.debug')) {
        Permission::error(); // ❌ debug 模式下任何管理员都可用 Scaffold
    }
}
```

**风险：** Scaffold 可执行数据库迁移、修改路由文件、创建控制器——相当于代码执行。

**修复建议：** 添加额外的权限检查，不依赖 debug 模式。

---

### VUL-17: Scaffold 路由注入

**严重程度：** 🟡 Medium  
**涉及文件：** `src/Http/Controllers/ScaffoldController.php` (第 195-200 行)

```php
$route_path = $request->input('route_path');
$newRoutes = "\$router->resource('/" . $route_path . "'," . $controller_name . "::class)";
$this->addResourceRouteToAdminRoutes($newRoutes); // ❌ 写入路由文件，可注入代码
```

**修复建议：** 对 `route_path` 进行严格的正则白名单校验：`/^[a-zA-Z0-9_\-\/]+$/`

---

## 🔵 Low 低危漏洞

### VUL-18: 弱随机数用于文件命名

**涉及文件：**
- `src/Form/Field/UploadField.php` (第 315 行): `md5(uniqid())`
- `src/Form/Extend/FormMedia/MediaManager.php` (第 279 行): `mt_rand(10000, 99999)`

**修复建议：** 使用 `bin2hex(random_bytes(16))`。

---

### VUL-19: User-Agent 泄露服务器信息

**涉及文件：** `src/Core/Util/CurlUtil.php` (第 348-349 行)

```php
$userAgent = 'dcat-plus/1.2.3 PHP/' . PHP_VERSION . ' OS/' . PHP_OS;
```

**修复建议：** 使用通用 User-Agent，不暴露版本信息。

---

### VUL-20: 数据库配置信息可能通过 Scaffold 泄露

**涉及文件：** `src/Http/Controllers/ScaffoldController.php`

Scaffold 页面展示数据库连接列表和表结构信息。

---

### VUL-21: Scaffold 控制器遗留 SQL 注入代码

**涉及文件：** `src/Http/Controllers/ScaffoldController.php` (第 858-919 行)

`getDatabaseColumnsOld` 方法仍存在 SQL 注入，应删除。

---

## 🛡️ 修复状态

### ✅ 已修复的漏洞：

| # | 漏洞编号 | 修复内容 | 修改文件 |
|---|---------|---------|---------|
| 1 | CVE-1 | 上传接口添加文件类型白名单验证（扩展名 + MIME） | `EditorMDController.php`, `TinymceController.php` |
| 2 | CVE-2 | 移除用户可控磁盘参数，锁定为配置默认值 | `EditorMDController.php`, `TinymceController.php`, `FormMedia.php` |
| 3 | CVE-3 | 类实例化添加 `is_subclass_of` 类型白名单验证 | `HandleActionController.php`, `HandleFormController.php`, `ValueController.php` |
| 4 | CVE-4 | 添加危险扩展名黑名单 `getSafeExtension()` + 文件名清理 `sanitizeFileName()` | `UploadField.php` |
| 5 | VUL-5/6 | SKU 上传添加图片类型验证；删除路径限制为 `sku/` 目录 | `Sku/Controllers/UploadController.php` |
| 6 | VUL-8 | `setOrderBy` 添加列名正则校验 + 排序方向白名单 + cast 类型白名单 | `EloquentRepository.php` |
| 7 | VUL-9/21 | 删除 `getDatabaseColumnsOld` SQL 注入遗留方法 | `ScaffoldController.php` |
| 8 | VUL-10 | `unserialize()` 替换为 `json_decode()` | `CurlUtil.php` |
| 9 | VUL-11 | SSL 验证全部启用 (`VERIFYPEER=true`, `VERIFYHOST=2`) | `CurlUtil.php` |
| 10 | VUL-12/13 | FormMedia 磁盘锁定 + 路径穿越防护 + 文件夹名清理 | `FormMedia.php` |
| 11 | VUL-15 | WebUploader `_id` 格式验证 + 随机临时文件名 | `WebUploader.php` |
| 12 | VUL-16 | Scaffold 添加超级管理员角色检查 | `ScaffoldController.php` |
| 13 | VUL-17 | `route_path` 添加正则白名单校验 | `ScaffoldController.php` |
| 14 | VUL-18 | 弱随机数替换为 `random_bytes()` | `UploadField.php`, `MediaManager.php` |
| 15 | VUL-19 | User-Agent 移除 PHP 版本和操作系统信息 | `CurlUtil.php` |

### ⚠️ 待手动处理的漏洞：

| # | 漏洞编号 | 建议 | 说明 |
|---|---------|------|------|
| 1 | VUL-7 | 登录接口添加 `throttle:5,1` 速率限制 | 需在路由文件中配置中间件 |
| 2 | VUL-14 | Form 文件删除路径添加白名单校验 | 需根据业务逻辑评估 |

### 🔧 新增安全防护方法汇总：

| 方法 | 位置 | 功能 |
|------|------|------|
| `getDangerousExtensions()` | UploadField, MediaManager | 危险扩展名黑名单（php/asp/jsp/sh 等） |
| `getSafeExtension()` | UploadField, MediaManager | 获取安全的文件扩展名，过滤危险类型 |
| `sanitizeFileName()` | UploadField, MediaManager | 清理文件名中的路径穿越和特殊字符 |
| `sanitizeId()` | WebUploader | 清理分块上传 `_id` 参数 |
| `sanitizePath()` | FormMedia | 清理路径穿越字符 |
| `sanitizeFolderName()` | FormMedia | 清理文件夹名称 |

---

## 📝 免责声明

本报告仅基于静态代码分析，未进行动态渗透测试。实际风险可能因部署环境、中间件配置、网络架构等因素有所不同。建议在修复后进行专业的渗透测试验证。

---

*报告生成时间：2026-05-24*