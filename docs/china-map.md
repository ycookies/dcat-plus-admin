# ChinaMap 中国地图组件

> 适用版本：dcat-plus-admin 内置，无需额外安装。

`Dcat\Admin\Widgets\ChinaMap` 是一个基于 [ECharts](https://echarts.apache.org/) 的中国地图可视化组件，封装了地图渲染、色阶、图例、省/市两级下钻等完整能力。参照框架内 `ApexCharts\Chart` 的集成方式，可作为独立、可复用的图表 Widget 在任何后台页面使用。

- **资产依赖**：`@echarts`（echarts.min.js）、`@echarts-china-map`（china.js + 34 省地图数据），随 `admin:publish` 自动发布
- **离线运行**：地图数据内置离线包，无需联网拉取
- **多实例友好**：自动生成唯一容器 id，同一页面可放多个地图互不干扰

---

## 一、快速开始

最简单的用法 —— 直接实例化即可（组件自带全国各省份虚拟数据）：

```php
use Dcat\Admin\Widgets\ChinaMap;

echo (new ChinaMap())->render();
```

或在控制器 `Content` 中：

```php
public function index(Content $content)
{
    return $content
        ->header('数据地图')
        ->body(new ChinaMap());
}
```

Widget 渲染时会自动注入 ECharts 资产与初始化脚本，**无需手动引入 JS**。

---

## 二、可配置参数

所有参数均带默认值，通过链式方法覆盖。每个方法都返回 `$this`，可串联调用。

| 方法 | 默认值 | 说明 |
|------|------|------|
| `width($w)` | `'100%'` | 地图主体宽度，接受 `'100%'`、`800`、`'800px'` |
| `height(int $h)` | `620` | 地图主体高度（px） |
| `showLegend(bool)` | `true` | **是否展示左侧 legend-panel（默认展示）** |
| `legendWidth(int $w)` | `240` | legend-panel 宽度（px） |
| `legendTitle(string)` | `'区域明细'` | legend-panel 标题 |
| `title(string)` | `'各省份用户扩展分布'` | 地图盒子主标题 |
| `subtitle(string)` | `'颜色越深表示数值越大 · 点击省份可查看下钻地图'` | 副标题 |
| `showBackButton(bool)` | `true` | 是否显示"返回全国"按钮 |
| `colorScale(array)` | `['#c7d8ff','#7ea6ff','#3b82f6','#1d4ed8','#0f1f6e']` | 色阶数组（浅→深，legend 与 visualMap 共用） |
| `areaColor(string)` | `'#dbe4f0'` | 无数据区域底色 |
| `emphasisColor(string)` | `'#fbbf24'` | 鼠标 hover 高亮色 |
| `roam(bool)` | `false` | 是否开启拖拽缩放 |
| `valueSuffix(string)` | `''` | 数值后缀（tooltip 中追加，如 `' 人'`） |
| `data(array)` | 全国各省份虚拟数据 | 业务数据，格式 `[['name' => '广东', 'value' => 3260], ...]` |
| `provinceData(array)` | 含湖南示例 | 各省下钻市级数据，格式 `['湖南' => [['name' => '长沙市', 'value' => 4200], ...]]` |

---

## 三、常用示例

### 3.1 自定义尺寸与数据

```php
$map = (new ChinaMap())
    ->title('各省份销售业绩')
    ->subtitle('单位：万元 · 点击省份查看明细')
    ->width('100%')
    ->height(500)
    ->valueSuffix(' 万元')
    ->data([
        ['name' => '广东', 'value' => 3260],
        ['name' => '江苏', 'value' => 1560],
        ['name' => '浙江', 'value' => 1480],
        // ...其余省份
    ]);

echo $map->render();
```

### 3.2 关闭左侧图例（仅地图）

```php
(new ChinaMap())->showLegend(false)->render();
```

`showLegend(false)` 后，左侧 legend-panel 不渲染，地图主体占满整个宽度。

### 3.3 配置省级下钻数据

```php
(new ChinaMap())
    ->provinceData([
        '湖南' => [
            ['name' => '长沙市', 'value' => 4200],
            ['name' => '株洲市', 'value' => 1680],
            // ...湖南其余市州
        ],
        '广东' => [
            ['name' => '广州市', 'value' => 5800],
            ['name' => '深圳市', 'value' => 7200],
        ],
    ])
    ->render();
```

> 用户点击某省份时，组件会动态加载该省的地图 JS（从已发布资产目录按需拉取），并使用 `provinceData` 中对应的市级数据渲染下钻地图。**未配置下钻数据的省份，点击后显示空白地图**。

### 3.4 自定义色阶与配色

```php
(new ChinaMap())
    ->colorScale(['#e0f2fe', '#0ea5e9', '#0369a1', '#0c4a6e'])  // 蓝色系 4 阶
    ->areaColor('#f1f5f9')      // 无数据底色
    ->emphasisColor('#f59e0b')  // hover 高亮（琥珀色）
    ->render();
```

### 3.5 批量传参（数组方式）

也可通过 `make()` 一次性传入配置数组（键名即方法名）：

```php
echo ChinaMap::make([
    'title'     => '用户分布',
    'height'    => 480,
    'showLegend'=> true,
    'data'      => $myData,
])->render();
```

### 3.6 在 Grid / 行布局中使用

```php
use Dcat\Admin\Layout\Row;

return $content->body(function (Row $row) {
    // 整行展示
    $row->column(12, new ChinaMap());
});
```

---

## 四、交互行为

组件默认开启以下交互（移植自原始蓝本）：

| 操作 | 行为 |
|------|------|
| **点击省份**（全国地图） | 下钻到该省份地图（加载省级地图数据） |
| **双击**（省份地图） | 返回全国地图 |
| **点击"返回全国"按钮** | 返回全国地图 |
| **鼠标 hover** | 高亮区域（`emphasisColor`）并显示 tooltip |
| **窗口 resize** | 地图自动重绘（实例级监听，多实例互不干扰） |

可通过 `showBackButton(false)` 关闭返回按钮、`roam(true)` 开启拖拽缩放。

---

## 五、工作原理（开发者参考）

### 5.1 资产体系

- 组件声明 `public static $js = ['@echarts', '@echarts-china-map']`
- Widget 基类 `render()` 时自动调 `requireAssets()` → `Admin::js()`，在页面注入对应的 `<script>` 标签
- 别名 `@echarts` / `@echarts-china-map` 定义在 `Dcat\Admin\Layout\Asset::$alias`
- 物理资产位于包内 `resources/dist/dcat/plugins/echarts-china-map/`，随 `dcat-admin-assets` 标签发布到 `public/vendor/dcat-admin/dcat/plugins/echarts-china-map/`

### 5.2 渲染流程

```
new ChinaMap() → render() → html()
                              ├─ generateId()  生成唯一 id（china-map-xxxx）
                              ├─ renderHtml()  输出盒子+图例+地图容器 DOM
                              └─ addScript()   把配置序列化进 JS heredoc → $this->script
                 → 基类 withScript() → Admin::script() → 页面 Dcat.ready() 执行
```

### 5.3 省 JS 动态加载

下钻时，组件通过 `Admin::asset()->get('@echarts-china-map')` 解析出 china.js 的浏览器 URL，推导出 province 目录，动态 `<script>` 加载对应省份 JS（如 `province/hunan.js`）。**这一步只发生在用户点击下钻时**，初始只加载 echarts + china.js，不预加载全部 34 省。

### 5.4 多实例隔离

每个 ChinaMap 实例生成独立的容器 id，所有 DOM 节点与 JS 配置都通过该 id（`data-chinamap="{id}"`）绑定，脚本以 IIFE 封装、resize 监听为实例级 —— 同一页面可安全放置多个 ChinaMap。

---

## 六、首次使用须知

```bash
# 1. 确保已发布资产（安装/升级框架后执行一次）
php artisan admin:publish --assets --force

# 2. 确认资产已发布到 public/
ls public/vendor/dcat-admin/dcat/plugins/echarts-china-map/
# 应含：echarts.min.js、map/china.js、map/province/*.js（34 个）
```

> ChinaMap 独立于 ApexCharts 版本切换（`apexcharts_version`），无论 v3 / v5 都可正常使用。

---

## 七、常见问题

**Q：地图不显示 / 控制台报 `echarts is not defined`？**
未发布资产。执行 `php artisan admin:publish --assets --force`，并确认 `public/vendor/dcat-admin/dcat/plugins/echarts-china-map/echarts.min.js` 存在。

**Q：点击省份后地图空白？**
两种可能：(1) 该省的地图 JS 未发布（检查 `province/` 目录）；(2) 该省未在 `provinceData()` 中配置市级数据。组件默认只为湖南配置了下钻示例数据，其它省份需自行补充。

**Q：如何关闭下钻，只做静态全国地图？**
目前下钻为内置行为。若需关闭，可在子类中覆写脚本逻辑，或通过 CSS/事件拦截。后续可考虑增加 `enableDrilldown` 配置项。

**Q：省份数据从哪来？名称必须用中文吗？**
`data` 中的 `name` 必须使用 ECharts 地图注册的中文名（如 `'广东'`、`'内蒙古'`），与地图区域一一对应才能正确上色。市级数据同理（如 `'长沙市'`）。

**Q：支持世界地图或其它国家吗？**
不支持。当前组件内置中国地图数据（全国 + 34 省/直辖市/特别行政区）。如需其它地图，需自行注册对应的 GeoJSON / JS 地图数据。
