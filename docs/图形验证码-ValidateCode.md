# 图形验证码（ValidateCode）

`Dcat\Admin\Support\ValidateCode` 是 dcat-plus-admin 内置的 GD 图形验证码工具。它不依赖第三方验证码包，默认使用扩展包中的 `fonts/Elephant.ttf`，并且不会直接输出 HTTP header。

## 环境要求

PHP 必须启用 **GD** 和 **FreeType**。缺少扩展、字体文件不可读或图片编码失败时，工具会抛出明确的运行时异常；参数不合法时会抛出 `Dcat\Admin\Exception\InvalidArgumentException`。

## 最常用的控制器写法

```php
use Dcat\Admin\Admin;

public function captcha()
{
    // response() 会生成图片，并把验证码摘要写入当前 Session。
    return Admin::validateCode()->response();
}

public function login(Request $request)
{
    if (! Admin::validateCode()->check((string) $request->input('captcha'))) {
        return back()->withErrors(['captcha' => '验证码错误或已过期。']);
    }

    // 执行登录逻辑...
}
```

`check()` 默认是一次性校验：无论成功或失败，都会删除 Session 中的验证码，避免重放。默认有效期为 5 分钟。

## 单次调用覆盖配置

```php
return Admin::validateCode([
    'length' => 5,
    'width' => 150,
    'height' => 48,
    'line_count' => 3,
    'dot_count' => 16,
    'session_key' => 'admin.login_captcha',
    'ttl' => 180,
])->response();
```

`Admin::validateCode($options)` 每次都会返回一个独立实例，不会污染其他请求或其他验证码。

## 全局默认配置

在 `config/admin.php` 中配置 `admin.validate_code`：

```php
'validate_code' => [
    'length' => 4,
    'width' => 120,
    'height' => 42,
    // 验证码图片背景色，RGB 数组。
    'background' => [248, 250, 252],
    // 雪花干扰强度：1 默认，2-10 同时增强干扰线与随机像素点。
    'noise_level' => 1,
],
```

所有颜色均为 `[红, 绿, 蓝]`（每项为 `0-255`）。验证码长度限制为 `3-8`，宽度限制为 `80-600`，高度限制为 `32-180`，以避免不合理配置消耗过多资源。

noise_level 范围为 1-10：1 为默认强度，2-10 会按倍数同时增强随机干扰线和雪花点。字体、颜色、字符集及两类干扰的基础密度都使用扩展包内置的安全默认值，无须在应用配置中维护。

## 其他输出方式

```php
$captcha = Admin::validateCode(['length' => 6]);

// 原始 PNG 二进制，适合自行保存或交给其他响应层。
$png = $captcha->render();

// 可直接放入 <img src="..."> 的 data URI。
$dataUri = $captcha->dataUri();

// 手动控制 Session（render() 本身不会写入 Session）。
$captcha->store();
```

旧版 `doimg()` 仍保留，但现在返回 Laravel Response；请使用 `return $captcha->doimg()`，不要再直接调用后依赖它输出图片。

## 后台登录验证码

登录页已内置图形验证码开关。在 `config/admin.php` 设置后即可生效：

```php
'auth' => [
    // ...
    'captcha' => [
        'enable' => true,
        'session_key' => 'dcat.login_captcha',
        'options' => [
            'length' => 5,
            'width' => 130,
            'height' => 44,
        ],
    ],
],
```

开启后，登录页会显示验证码和刷新按钮，后台通过 `GET auth/captcha` 生成图片，并在登录时执行一次性 Session 校验。该端点限流为每分钟 30 次；关闭开关后，页面、端点和登录校验都会跳过验证码逻辑。
