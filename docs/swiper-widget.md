# Swiper 与跑马灯通知组件

`Dcat\Admin\Widgets\Swiper` 是基于包内 Swiper 资源的轮播组件；`Dcat\Admin\Widgets\MarqueeNotice` 在此基础上提供了适合后台公告的垂直跑马灯。

## 发布资源

首次安装或升级后请发布资源：

```bash
php artisan admin:publish --assets --force
```

组件会按需加载：

- `vendor/dcat-admin/dcat/plugins/swiper/swiper-bundle.min.css`
- `vendor/dcat-admin/dcat/plugins/swiper/swiper-bundle.min.js`

## Swiper

默认是**左右滚动**，单屏展示一项，不自动播放。

```php
use Dcat\Admin\Widgets\Swiper;

$swiper = Swiper::make([
    '<div class="p-4 bg-primary text-white">第一张内容</div>',
    '<div class="p-4 bg-success text-white">第二张内容</div>',
    '<div class="p-4 bg-warning text-white">第三张内容</div>',
]);

echo $swiper;
```

也可以链式添加：

```php
$swiper = (new Swiper())
    ->add('<img class="w-100" src="/images/banner-1.jpg" alt="Banner 1">')
    ->add('<img class="w-100" src="/images/banner-2.jpg" alt="Banner 2">');
```

### 滚动方向

```php
// 默认：左右滚动
$swiper->horizontal();

// 上下滚动
$swiper->vertical();

// 也可使用一个方向配置函数
$swiper->direction('vertical');
```

`direction()` 仅接受 `horizontal` 或 `vertical`，传入其它值会抛出 `InvalidArgumentException`。

### 覆盖 Swiper 参数

`options()` 可按需覆盖默认参数，嵌套数组会递归合并，因此只设置 `autoplay.delay` 时不会丢失默认的 `disableOnInteraction`。

```php
$swiper->options([
    'slidesPerView' => 3,
    'spaceBetween'  => 16,
    'loop'          => true,
    'autoplay'      => [
        'delay' => 4000,
    ],
]);
```

常用链式快捷方法：

```php
$swiper
    ->loop()
    ->autoplay(3000) // 毫秒；传 0 关闭
    ->pagination()   // 显示可点击的分页圆点
    ->navigation();  // 显示上一页、下一页按钮
```

`pagination()` 和 `navigation()` 会自动为当前实例创建独立的元素选择器，因此同页多实例不会互相影响。可继续用 `options()` 覆盖原生配置：

```php
$swiper
    ->pagination()
    ->navigation()
    ->options([
        'pagination' => ['type' => 'fraction'],
        'navigation' => ['hideOnClick' => true],
    ]);
```

如需使用更多参数，可直接传入 [Swiper 官方参数](https://swiperjs.com/swiper-api)；组件会将它们原样传给 `new Swiper()`。

## 跑马灯通知

`MarqueeNotice` 继承自 `Swiper`，默认配置为：垂直滚动、单条展示、3 秒自动播放、循环、鼠标悬停暂停、禁止拖拽。

```php
use Dcat\Admin\Widgets\MarqueeNotice;

$notice = (new MarqueeNotice())
    ->add('系统将于今晚 23:00 进行维护。', '/admin/system-notices/1')
    ->add('新版本 v2.0.23 已发布。', '/admin/releases/2', '_blank')
    ->add('请及时完善企业资料。');

echo $notice;
```

也支持批量传入：

```php
$notice = new MarqueeNotice([
    ['message' => '系统维护通知', 'url' => '/admin/system-notices/1'],
    ['message' => '查看最新版本说明', 'url' => '/admin/releases/2', 'target' => '_blank'],
    '第三条纯文本通知',
]);
```

通知内容会进行 HTML 转义；链接仅接受站内相对地址、锚点和 HTTP(S) 地址，避免把不可信内容作为可执行链接输出。

调整播放速度：

```php
$notice->options([
    'autoplay' => ['delay' => 5000],
]);
```

## 资质荣誉墙

`HonorWall` 同样继承自 `Swiper`，默认采用响应式舞台布局：中心卡片完整放大，两侧卡片逐级缩小叠放，并自带分页器与左右切换按钮。卡片、图片、标题字号和间距会根据容器宽度自动缩放。

```php
use Dcat\Admin\Widgets\HonorWall;

$honorWall = HonorWall::make([
    [
        'image' => '/storage/honors/iso9001.jpg',
        'title' => 'ISO 9001 质量管理体系认证',
        'url'   => '/admin/honors/1',
    ],
    [
        'image'  => '/storage/honors/software-copyright.jpg',
        'title'  => '计算机软件著作权登记证书',
        'url'    => '/admin/honors/2',
        'target' => '_blank',
    ],
]);

echo $honorWall;
```

默认最多展示 5 项，小屏自动收敛为中心项和相邻两项。`visibleSlides()` 接受 `1`、`3` 或 `5`；默认开启 `autoScale()`，如需固定卡片宽度可调用 `slideWidth()`。

`maxWidth()` 会同时约束卡片舞台、左右切换按钮和底部分页器；分页器位于正常布局流中，会随卡片高度和容器宽度同步移动。

两侧卡片的横向位移会根据容器宽度动态计算，默认预留阴影和内边距空间，最外层卡片不会被容器裁切。

五项布局采用非等距分布：中心项与左右相邻项的间距，比外层两项之间的间距大约三分之一，以突出中心荣誉。

```php
$honorWall = (new HonorWall())
    ->add('/storage/honors/iso9001.jpg', 'ISO 9001 质量管理体系认证')
    ->add('/storage/honors/copyright.jpg', '计算机软件著作权登记证书', '/admin/honors/2')
    ->visibleSlides(5)
    ->autoScale()
    ->maxWidth(1200)
    ->options([
        'speed' => 800,
        'autoplay' => ['delay' => 4000],
    ]);
```

批量数组项可使用 `image`、`title`、`url`、`target` 键，也可以使用位置数组 `[图片地址, 标题, 跳转地址, 打开方式]`。图片地址仅接受 HTTP(S)、站内绝对路径或常规相对路径；链接仅接受 HTTP(S)、站内相对地址和锚点，避免输出不可信协议。

## 完整后台页面示例

```php
<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Widgets\MarqueeNotice;
use Dcat\Admin\Widgets\Swiper;

class SwiperDemoController extends AdminController
{
    public function index(Content $content)
    {
        $swiper = (new Swiper())
            ->options([
                'loop' => true,
                'autoplay' => ['delay' => 3500],
                'spaceBetween' => 12,
            ])
            ->add('<div class="p-5 rounded bg-primary text-white"><h3>运营数据</h3><p class="mb-0">今日订单增长 18%</p></div>')
            ->add('<div class="p-5 rounded bg-success text-white"><h3>用户增长</h3><p class="mb-0">新增用户 1,286 人</p></div>')
            ->add('<div class="p-5 rounded bg-warning text-white"><h3>待办事项</h3><p class="mb-0">有 6 项审批等待处理</p></div>');

        $notice = new MarqueeNotice([
            ['message' => '系统将于今晚 23:00 进行维护。', 'url' => '/admin/system-notices/1'],
            ['message' => '请及时处理待审批事项。', 'url' => '/admin/tasks'],
            '欢迎使用 DcatPlus Admin。',
        ]);

        return $content
            ->header('Swiper 示例')
            ->description('轮播与跑马灯通知')
            ->body($notice.'<br>'.$swiper);
    }
}
```

在 `app/Admin/routes.php` 中注册：

```php
$router->get('examples/swiper', [SwiperDemoController::class, 'index']);
```

## 多实例与 PJAX

每个组件都会生成独立 ID，并通过 `data-swiper-initialized` 防止重复初始化。同一页面可以放置多个 Swiper 或多个跑马灯；在 Dcat PJAX 页面中重新渲染时也不会与既有实例冲突。
