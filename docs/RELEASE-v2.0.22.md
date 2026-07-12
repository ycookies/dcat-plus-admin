# dcat-plus-admin v2.0.22 发布：ApexCharts v5 双版本 + 中国地图组件

> 发布日期：2026-07-05 ｜ 版本：`2.0.22` ｜ Composer：`dcat-plus/laravel-admin`

这个版本，我们给后台的可视化能力做了一次大升级：**ApexCharts 全面支持 v5 双版本无缝切换**，并带来了一个全新的、开箱即用的**中国地图组件（ChinaMap）**。无论是数据大屏、运营看板还是区域分析，现在都能用更少的代码做出更专业的图表。

## 一句话升级

```bash
composer require dcat-plus/laravel-admin:^2.0.22
php artisan admin:publish --assets --force
```

升级后**零改动、零回归**——所有现有图表行为与之前完全一致，新能力等你按需开启。

---

## 一、ApexCharts v5.16.0：双版本，你说了算

ApexCharts 是 dcat-plus-admin 内置的主力图表库。此前框架锁定在 v3.17.1，而官方已迭代到 v5，带来了大量新图表类型、性能改进和暗色体验优化。我们听到了社区"想用 v5 但怕升级踩坑"的声音，于是把选择权交给你：

### 一行配置切换版本

```php
// config/admin.php
'apexcharts_version' => env('ADMIN_APEXCHARTS_VERSION', 'v3'),
```

```bash
# .env 切到 v5
ADMIN_APEXCHARTS_VERSION=v5
php artisan admin:publish --assets --force
```

- **默认 v3**：完全向后兼容，现有项目升级后无任何感知。
- **切到 v5**：一行配置 + 重新发布资产即可，**无需改任何控制器或 Widget 代码**。

> 经我们逐项核查 v5 源码，本项目用到的全部 option API（`chart.height`、`colors`、`theme.mode`、`.render()/.destroy()` 等）在 v5 完全兼容。唯一差异是 v5 重写了 CSS——切换时框架自动为你加载配套样式表，你无需操心。

### v5 带来的 9 种全新图表类型

v3 不支持、v5 才有的高级图表，现在全部可用：

| 图表 | 用途 |
|------|------|
| **BoxPlot 箱线图** | 五数概括展示数据分布 |
| **Violin 小提琴图** | 箱线图 + 核密度，看集中与离散 |
| **Treemap 树图** | 矩形面积表达层级占比 |
| **Funnel 漏斗图** | 转化漏斗分析 |
| **RangeArea 范围面积图** | 区间波动（如温度预测带） |
| **RangeBar 范围条形图** | 甘特图/时间区间排期 |
| **Gauge 仪表盘** | 半圆指针式 KPI 达成度 |
| **Slope 坡图** | 两时间点多类别升降对比 |
| **Dumbbell 哑铃图** | 起止差值对比 |

加上原有的折线/柱状/饼图等 13 种基础类型，**合计 22 种图表**，覆盖了后台可视化的绝大多数场景。

📖 详见文档：[docs/apexcharts-version.md](apexcharts-version.md)

---

## 二、ChinaMap：开箱即用的中国地图组件

这是本次发布的**重头戏**。基于 ECharts 封装的 `Dcat\Admin\Widgets\ChinaMap`，把"做一个数据地图"这件事的门槛降到了一行代码。

### 最简用法

```php
use Dcat\Admin\Widgets\ChinaMap;

echo (new ChinaMap())->render();
```

组件内置全国各省份虚拟数据，开箱即看到一张带色阶、图例、可交互的中国地图。

### 自带能力

- 🗺️ **全国 → 省份两级下钻**：点击省份进入省级地图，双击或点"返回全国"回退
- 🎨 **单色渐变色阶**：legend 与 visualMap 共用同一套配色，深浅即数值
- 📋 **左侧 legend-panel（区域明细列表）**：按数值降序，名称+色块+数值，**可自由显隐（默认展示）**
- 📦 **离线地图数据**：echarts + china.js + 34 省地图全部内置，无需联网
- 🔁 **多实例友好**：自动生成唯一容器 id，同一页面放多个地图互不干扰

### 14 个可配置参数，全部带默认值

```php
(new ChinaMap())
    ->title('各省份销售业绩')
    ->height(500)
    ->valueSuffix(' 万元')
    ->colorScale(['#e0f2fe', '#0ea5e9', '#0369a1', '#0c4a6e'])
    ->showLegend(true)           // legend-panel 显隐
    ->provinceData([             // 省份下钻数据
        '广东' => [
            ['name' => '广州市', 'value' => 5800],
            ['name' => '深圳市', 'value' => 7200],
        ],
    ])
    ->render();
```

参数涵盖尺寸（`width/height`）、图例（`showLegend/legendWidth/legendTitle`）、标题、配色（`colorScale/areaColor/emphasisColor`）、交互（`roam/showBackButton`）、数据（`data/provinceData`）等，每个都有合理默认值，覆盖即可。

📖 详见开发者文档：[docs/china-map.md](china-map.md)

---

## 三、本次更新清单

### 新功能
- ✨ ApexCharts v5.16.0 双版本支持（`apexcharts_version` 配置，默认 v3）
- ✨ 新增 `Dcat\Admin\Widgets\ChinaMap` 中国地图组件
- ✨ 新增 v5 专属图表 9 种（BoxPlot / Violin / Treemap / Funnel / RangeArea / RangeBar / Gauge / Slope / Dumbbell）

### 优化与修复
- 🛠️ 操作日志相关优化（OperationLog 控制器 / 中间件 / 模型）
- 🐛 修复 PHP heredoc 内 `\n` 被解析为真换行导致脚本语法错误的问题

### 文档
- 📄 [ApexCharts 版本切换](apexcharts-version.md)
- 📄 [ChinaMap 组件使用指南](china-map.md)

---

## 四、升级指南

```bash
# 1. 升级到 2.0.22
composer require dcat-plus/laravel-admin:^2.0.22

# 2. 重新发布资产（必须）
php artisan admin:publish --assets --force

# 3.（可选）想用 v5 图表 / ChinaMap 时
# v5：在 .env 设置 ADMIN_APEXCHARTS_VERSION=v5
# ChinaMap：直接 use Dcat\Admin\Widgets\ChinaMap 即可
```

**向后兼容性**：本次升级对现有项目零侵入。
- 默认仍使用 ApexCharts v3.17.1，所有现有图表表现不变
- ChinaMap 是全新组件，不影响任何已有代码
- 若要回退 v5 到 v3，把 `.env` 改回 `v3`（或删除该行）刷新即可，无需重新发布资产

---

## 五、致谢与反馈

dcat-plus-admin 由杨光（ycookies）持续维护，是原 dcat/laravel-admin 的社区活跃分支。感谢每一位赞助者、提 issue 和 PR 的开发者。

- GitHub：https://github.com/ycookies/dcat-plus-admin
- 中文文档：https://www.dcat-admin.com/books/dcatplus-admin/
- 技术社区：https://forum.saishiyun.net/t/dcat-admin
- 作者微信：Q3664839（备注进开发交流群）

如果这个版本对你有帮助，欢迎 Star ⭐ 支持，也欢迎在社区分享你用 ChinaMap 做出的数据大屏！

---

**Happy coding with dcat-plus-admin v2.0.22! 🚀**
