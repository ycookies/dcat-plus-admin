# DcatPlus Admin v2.0.23 发布：UEditor 安全接入、全链路深色模式与开发效率升级

> 发布建议：`v2.0.23`  
> Composer：`dcat-plus/laravel-admin`  
> 发布日期：2026-07-12

这次更新聚焦三件事：**把富文本编辑器真正做成可在生产环境使用的能力**、**补齐深色模式在编辑器与 iframe 多标签中的最后一公里**，以及**让常见后台页面的生成与展示更省心**。

如果你正在维护内容管理、帮助中心、运营后台或多页面管理系统，`v2.0.23` 会让上传、深色界面、多标签操作与 CRUD 建设更顺手。

## 一句话升级

```bash
composer require dcat-plus/laravel-admin:^2.0.23
php artisan admin:publish --assets --force
php artisan optimize:clear
```

> 本版本新增 UEditor 的发布资源与上传接口。升级后请重新发布资源；若项目使用了配置缓存，也请清理缓存。

---

## 一、`$form->editor()` 正式升级为 UEditor

富文本字段仍然使用熟悉的调用方式：

```php
$form->editor('content', '内容')
    ->height(460);
```

但底层已经切换为功能更完整的 UEditor：图片、视频、附件、表格、代码块、模板、表情等能力可直接按工具栏配置启用。

### 所有关键参数集中到 `admin.ueditor`

不再需要散落在控制器或前端脚本里改配置。上传磁盘、目录、大小、扩展名、MIME 白名单、编辑器高度、自动高度、工具栏、AI、表情本地化等参数，都提供了带注释的默认配置。

```php
// config/admin.php
'ueditor' => [
    'disk' => null,
    'directory' => [
        'image' => 'ueditor/images',
        'video' => 'ueditor/videos',
        'file'  => 'ueditor/files',
    ],
    'initial_frame_height' => 400,
    'dark_mode' => true,
    'toolbars' => [
        ['bold', 'italic', 'underline', '|', 'insertimage', 'insertvideo'],
    ],
],
```

工具栏支持按项目裁剪；字段级配置优先级更高：

```php
$form->editor('content', '内容')->options([
    'toolbars' => [
        ['bold', 'italic', 'underline', '|', 'insertimage'],
    ],
]);
```

### 富文本上传，默认按生产标准防护

本次不是只接了一个上传接口，而是补齐了上传链路的权限与边界控制：

- UEditor 配置和上传接口允许所有已登录后台角色使用，不再拆分角色级上传权限。
- POST 上传具备频率限制，默认每个账号每分钟 20 次。
- 图片、视频与附件分别支持大小、扩展名与 MIME 类型白名单。
- 字段级自定义磁盘/目录不再信任前端参数，改为绑定当前用户、磁盘、目录和过期时间的签名授权。
- 默认限制可使用的额外磁盘，避免任意写入。
- 保存富文本时默认执行服务端 HTML 净化，降低绕过浏览器端过滤造成存储型 XSS 的风险。
- AI 功能默认关闭；开启前请确认内容传输与密钥管理策略。

### 深色模式覆盖编辑器、编辑区和弹窗

UEditor 不只是外壳换色。`v2.0.23` 会让以下区域同步 Dcat 的 `dark-mode`：

- 工具栏、下拉框、选中/悬停按钮与字数状态栏
- 编辑区 iframe 内的正文、表格、代码块、输入框与滚动条
- 图片、视频、附件等功能弹窗的 iframe 内容

默认跟随后台深色模式；如某个字段不需要跟随，可单独关闭：

```php
$form->editor('content')->darkMode(false);
```

UEditor 发布的 CSS/JS 已完成压缩，减少生产环境的静态资源体积。

---

## 二、Carousel 组件：能轮播、能纵向、可多实例

`Dcat\Admin\Widgets\Carousel` 完成重构，解决旧实现中自动轮播和左右切换无响应的问题。

```php
use Dcat\Admin\Widgets\Carousel;

echo (new Carousel())
    ->interval(4)      // 停顿 4 秒；传 0 可关闭自动轮播
    ->vertical()       // 或 horizontal()，默认横向
    ->arrows(false)    // 隐藏左右切换箭头
    ->addItems([
        ['img_src' => '/images/banner-1.jpg', 'title' => '新品上线', 'content' => '欢迎体验'],
        ['img_src' => '/images/banner-2.jpg', 'title' => '活动预告', 'content' => '限时优惠'],
    ])
    ->render();
```

本次改进包括：

- 默认每 5 秒自动轮播，也可通过 `interval()`、`autoplay()` 控制。
- 支持水平/垂直滚动，支持隐藏切换箭头。
- 新增 `addItems()`，同时支持关联数组和与 `add()` 一致的位置数组。
- 每个实例使用独立 ID 与独立事件，不会互相抢占。
- 图片按容器宽度响应式缩放。

---

## 三、iframe Tabs：父页、标签栏、子页全部支持深色模式

iframe Tabs 模式过去存在一个常见割裂：后台已经切到深色模式，子页或顶部标签栏仍保持浅色。

现在已经完成三层适配：

1. **标签栏宿主页**：标签容器、普通/激活标签、关闭按钮、工具栏、滚动条、加载层全部支持深色配色。
2. **子页面初始加载**：新打开的 Tab 会读取父页当前深色状态。
3. **运行时同步**：父页切换深色/浅色后，会向已打开和缓存的子 Tab 广播状态，子页即时调用 Dcat 的主题显示逻辑同步更新。

这意味着 iframe Tabs 不再是一个独立的“浅色孤岛”，也不需要刷新每个子页来追随主题。

---

## 四、新增 `admin:scaffold`：从数据表直接生成后台骨架

新增命令行脚手架可以基于数据表一次性生成模型、后台控制器、多语言文件、权限和菜单：

```bash
# 扫描默认连接的表并交互/批量生成
php artisan admin:scaffold

# 指定连接和表
php artisan admin:scaffold \
    --connection=pgsql \
    --table=products,product_categories \
    --menu-parent=0 \
    --role=1
```

新脚手架支持：

- MySQL/MariaDB、SQLite、PostgreSQL、SQL Server 的字段读取。
- 数据库连接、表前缀和 schema 的识别。
- 字段注释、主键、时间戳、软删除等模型信息生成。
- 自动创建/复用菜单和权限，并按需关联角色。
- `--force` 覆盖已有生成文件，便于重复迭代。

对于多数据库、表前缀或 PostgreSQL/SQL Server 项目，字段元数据读取也同步做了兼容性增强。

---

## 五、其它体验优化

- 帮助中心详情页按 HTML 呈现富文本内容，编辑帮助内容时默认隐藏 AI 入口。
- 菜单编辑时默认选中首个角色；保存成功后刷新当前页面，减少跳转打断。
- UEditor 的图片 URL、上传配置与资源根路径处理完成修正，避免出现错误前缀或上传插件无法读取后端配置的问题。

---

## 升级注意事项

### 1. 使用过 TinyMCE 专属 options 的项目需要迁移

`$form->editor()` 的方法名保持不变，但编辑器内核从 TinyMCE 升级为 UEditor。若项目传入了 TinyMCE 专属参数（例如 `images_upload_url`、`plugins`、`toolbar`），请迁移为 UEditor 的 `serverUrl`、`toolbars` 等配置。

### 2. UEditor 不需要单独分配上传权限

能够登录后台并访问业务编辑页面的角色，可以直接加载 UEditor 配置以及上传图片、视频和附件。上传接口继续经过 CSRF、请求限流、扩展名、MIME、大小限制和目录签名校验。

### 3. 检查历史富文本内容

默认启用了服务端 HTML 净化。建议先在测试环境编辑、保存一批历史内容，确认自定义标签、内嵌内容和样式是否符合你的业务预期；如有明确业务需要，再评估 `admin.ueditor.sanitize_html` 配置。

### 4. 重新发布资源并清理浏览器缓存

UEditor 与 iframe Tabs 都增加了静态资源。执行资源发布后，请在浏览器进行一次强制刷新。

```bash
php artisan admin:publish --assets --force
php artisan optimize:clear
```

---

## 完整更新清单

### 新功能

- ✨ 集成 UEditor 富文本编辑器与完整后端上传服务。
- ✨ 新增 `admin.ueditor` 集中配置与可定制工具栏。
- ✨ 新增 UEditor 编辑区/弹窗深色模式。
- ✨ 新增 Carousel 的自动轮播、垂直滚动、箭头控制和批量添加 API。
- ✨ 新增 `admin:scaffold` 数据库表脚手架命令。

### 安全与稳定性

- 🔒 UEditor 上传权限、限流、文件白名单、签名授权与服务端 HTML 净化。
- 🔒 禁止默认 AI 功能自动启用，避免无意的第三方内容传输。
- 🐛 修复 UEditor 后端配置、图片 URL 前缀和上传资源加载问题。
- 🐛 修复 Carousel 自动轮播、切换按钮与多实例冲突问题。
- 🐛 修复 iframe Tabs 的父子页深色模式同步问题。

### 体验优化

- 🎨 iframe Tabs 标签栏完整深色模式。
- 🎨 UEditor 工具栏、下拉框、状态栏、编辑区与弹窗统一深色视觉。
- 🛠️ 帮助中心富文本展示与菜单保存交互优化。
- 🛠️ 多数据库脚手架字段识别与表前缀/schema 兼容性优化。

---

## 感谢与反馈

`dcat-plus/laravel-admin` 是 Dcat Admin 的社区维护增强分支，持续面向现代 Laravel 项目补齐安全性、开发效率与后台体验。

- GitHub：[ycookies/dcat-plus-admin](https://github.com/ycookies/dcat-plus-admin)
- Composer：[dcat-plus/laravel-admin](https://packagist.org/packages/dcat-plus/laravel-admin)

如果这个版本对你的项目有帮助，欢迎 Star、提交 Issue 或分享你的使用场景。让我们一起把 Laravel 后台做得更安全、更好用。 🚀
