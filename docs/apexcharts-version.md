# ApexCharts 版本切换

> 适用版本：dcat-plus-admin 内置。

后台的图表组件（`Dcat\Admin\Widgets\ApexCharts\Chart`，以及基于它的 `Metrics\Card` 统计卡片）使用 [ApexCharts](https://apexcharts.com/) 渲染。框架内置两个版本，可通过配置项切换：

| 版本 | ApexCharts 版本 | 说明 |
|------|------|------|
| `v3` | 3.17.1 | **默认**，向后兼容，与历史版本行为一致 |
| `v5` | 5.16.0 | 新版，CSS 重写，性能与可访问性改进 |

---

## 一、切换版本

在 `config/admin.php` 中（或通过 `.env`）设置：

```php
// config/admin.php
'apexcharts_version' => env('ADMIN_APEXCHARTS_VERSION', 'v3'),
```

```bash
# .env
ADMIN_APEXCHARTS_VERSION=v5
```

切换后必须重新发布前端资产，让 v5 的 JS/CSS 复制到 `public/`：

```bash
php artisan admin:publish --assets --force
```

---

## 二、版本差异说明

经源码核查，v3 到 v5 的关键变化如下：

| 维度 | 说明 |
|------|------|
| 全局变量 | v5 仍挂 `window.ApexCharts`，与 v3 一致，`new ApexCharts(...)` 调用方式不变 |
| 渲染 API | `.render()` / `.destroy()` / `.updateOptions()` 等 API 完全兼容 |
| option 配置 | 本项目用到的 `chart.height`、`colors`、`labels`、`dataLabels`、`series`、`theme.mode` 等，v5 均保留 |
| **CSS（唯一硬性差异）** | **v5 重写了样式表，必须配套加载 `apexcharts.css`**。框架已在 v5 别名中自动包含 |

> 因此切换版本 **不需要修改任何控制器或 Widget 代码**，仅是资产层面的切换。

---

## 三、工作原理

框架在 `Dcat\Admin\Layout\Asset` 中维护资源别名。`@apex-charts` 别名在 Asset 实例化时根据配置动态决定指向：

- `v3`：`@admin/dcat/plugins/charts/apexcharts.min.js`
- `v5`：`@admin/dcat/plugins/charts/v5/apexcharts.min.js` + `apexcharts.css`

资产物理文件位于包内 `resources/dist/dcat/plugins/charts/`，两个版本共存：

```
resources/dist/dcat/plugins/charts/
├── apexcharts.{js,min.js,css}     # v3.17.1（默认）
└── v5/
    ├── apexcharts.{js,min.js}     # v5.16.0
    └── apexcharts.css
```

发布命令（`admin:publish --assets`）会把整个 `charts/` 目录（含 `v5/`）复制到 `public/vendor/dcat-admin/dcat/plugins/charts/`，前端按配置选用其一。

---

## 四、回滚

如 v5 出现问题，改回默认即可，无需清理已发布的 v5 文件：

```bash
# .env 中删除或改回
ADMIN_APEXCHARTS_VERSION=v3
# 或留空，默认就是 v3
```

切回 v3 无需重新发布资产（v3 文件始终存在），刷新页面即可。

---

## 五、常见问题

**Q：升级到 v5 后图表不显示 / 样式错乱？**
确认两件事：(1) 是否执行了 `php artisan admin:publish --assets --force`；(2) 浏览器是否清了缓存。v5 必须加载 `apexcharts.css`，未重新发布会导致 CSS 缺失。

**Q：v5 下暗色模式图表配色不对？**
本项目图表 option 默认未设置 `theme.mode`，与 v3 行为一致（保持现状，未额外做图表暗色适配）。如需图表跟随暗色，可在 Chart/Card 的 options 中手动设置 `['theme' => ['mode' => 'dark']]`。

**Q：只想让某个图表用 v5，其他用 v3？**
不支持按图表粒度切换，版本是全局配置。两个版本的资产都会发布到 `public/`，如有特殊需求可在自定义页面直接 `<script>` 引用 v5 文件。
