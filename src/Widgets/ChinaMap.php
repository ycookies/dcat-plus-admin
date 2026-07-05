<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Admin;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Support\JavaScript;
use Illuminate\Support\Str;

/**
 * 中国地图组件（基于 ECharts）。
 *
 * 支持：
 *  - 全国 → 省份 两级下钻（点击省份进入，双击/返回按钮回退全国）
 *  - 左侧 legend-panel（区域明细列表，按数值降序，色块与数值）
 *  - 单色渐变色阶（legend 与 visualMap 共用同一套）
 *  - 关键参数全部可配置，带默认值
 *
 * 资产依赖：@echarts（echarts.min.js）、@echarts-china-map（china.js）。
 * 省份地图 JS 在下钻时按需从发布目录动态加载。
 *
 * 用法：
 *   echo \Dcat\Admin\Widgets\ChinaMap::make();
 *   echo (new ChinaMap())->data([...])->height(500)->showLegend(false);
 *
 * @see public/echarts-china-map/index.html  原始蓝本
 */
class ChinaMap extends Widget
{
    /**
     * 资产依赖（@echarts / @echarts-china-map 别名，在 Layout/Asset 注册）。
     *
     * @var array
     */
    public static $js = [
        '@echarts',
        '@echarts-china-map',
    ];

    /** @var string 唯一容器 id 前缀 */
    protected $idPrefix = 'china-map-';

    /** @var string|null 容器选择器（#xxx） */
    protected $containerSelector;

    /** @var bool 是否已渲染（防重复） */
    protected $built = false;

    // ============ 可配置参数（均带默认值）============

    /** @var string|int 地图主体区域宽度 */
    protected $width = '100%';

    /** @var int 地图主体区域高度（px） */
    protected $height = 620;

    /** @var bool 是否展示左侧 legend-panel（默认展示） */
    protected $showLegend = true;

    /** @var int legend-panel 宽度（px） */
    protected $legendWidth = 240;

    /** @var string legend-panel 标题 */
    protected $legendTitle = '区域明细';

    /** @var string 地图盒子标题 */
    protected $title = '各省份用户扩展分布';

    /** @var string 副标题 */
    protected $subtitle = '颜色越深表示数值越大 · 点击省份可查看下钻地图';

    /** @var bool 是否显示"返回全国"按钮 */
    protected $showBackButton = true;

    /** @var array 色阶数组（legend 与 visualMap 共用，浅→深） */
    protected $colorScale = ['#c7d8ff', '#7ea6ff', '#3b82f6', '#1d4ed8', '#0f1f6e'];

    /** @var string 无数据区域底色 */
    protected $areaColor = '#dbe4f0';

    /** @var string hover 高亮色 */
    protected $emphasisColor = '#fbbf24';

    /** @var bool 是否开启拖拽缩放 */
    protected $roam = false;

    /** @var string 数值单位/后缀（tooltip 用） */
    protected $valueSuffix = '';

    /** @var array 各省业务数据 [{name,value}] */
    protected $data = [];

    /** @var array 各省下钻市级数据 { 省名: [{name,value}] } */
    protected $provinceData = [];

    public function __construct()
    {
        // 默认业务数据（沿用 index.html 蓝本的各省份虚拟数据）
        $this->data = [
            ['name' => '北京', 'value' => 1200], ['name' => '天津', 'value' => 320],
            ['name' => '上海', 'value' => 1850], ['name' => '重庆', 'value' => 280],
            ['name' => '河北', 'value' => 760], ['name' => '河南', 'value' => 680],
            ['name' => '云南', 'value' => 210], ['name' => '辽宁', 'value' => 540],
            ['name' => '黑龙江', 'value' => 420], ['name' => '湖南', 'value' => 720],
            ['name' => '安徽', 'value' => 650], ['name' => '山东', 'value' => 1320],
            ['name' => '新疆', 'value' => 180], ['name' => '江苏', 'value' => 1560],
            ['name' => '浙江', 'value' => 1480], ['name' => '江西', 'value' => 470],
            ['name' => '湖北', 'value' => 890], ['name' => '广西', 'value' => 530],
            ['name' => '甘肃', 'value' => 230], ['name' => '山西', 'value' => 390],
            ['name' => '内蒙古', 'value' => 360], ['name' => '陕西', 'value' => 610],
            ['name' => '吉林', 'value' => 310], ['name' => '福建', 'value' => 980],
            ['name' => '贵州', 'value' => 290], ['name' => '广东', 'value' => 3260],
            ['name' => '青海', 'value' => 90], ['name' => '西藏', 'value' => 40],
            ['name' => '四川', 'value' => 1180], ['name' => '宁夏', 'value' => 150],
            ['name' => '海南', 'value' => 260], ['name' => '台湾', 'value' => 0],
            ['name' => '香港', 'value' => 0], ['name' => '澳门', 'value' => 0],
        ];

        // 默认湖南省下钻示例数据（市级）
        $this->provinceData = [
            '湖南' => [
                ['name' => '长沙市', 'value' => 4200], ['name' => '株洲市', 'value' => 1680],
                ['name' => '湘潭市', 'value' => 1320], ['name' => '衡阳市', 'value' => 2150],
                ['name' => '邵阳市', 'value' => 1760], ['name' => '岳阳市', 'value' => 1980],
                ['name' => '常德市', 'value' => 1840], ['name' => '张家界市', 'value' => 540],
                ['name' => '益阳市', 'value' => 1230], ['name' => '郴州市', 'value' => 1560],
                ['name' => '永州市', 'value' => 1410], ['name' => '怀化市', 'value' => 1120],
                ['name' => '娄底市', 'value' => 1290], ['name' => '湘西土家族苗族自治州', 'value' => 680],
            ],
        ];
    }

    public static function make(...$params)
    {
        $map = new static();
        $options = $params[0] ?? [];

        if (is_array($options)) {
            foreach ($options as $k => $v) {
                if (method_exists($map, $k)) {
                    $map->{$k}($v);
                }
            }
        }

        return $map;
    }

    // ============ 链式 setter ============

    /** 地图主体区域宽度（如 '100%'、800、'800px'）。 */
    public function width($width)
    {
        $this->width = $width;

        return $this;
    }

    /** 地图主体高度（px）。 */
    public function height(int $height)
    {
        $this->height = $height;

        return $this;
    }

    /** 是否展示左侧 legend-panel（默认 true）。 */
    public function showLegend(bool $show = true)
    {
        $this->showLegend = $show;

        return $this;
    }

    /** legend-panel 宽度（px）。 */
    public function legendWidth(int $width)
    {
        $this->legendWidth = $width;

        return $this;
    }

    /** legend-panel 标题。 */
    public function legendTitle(string $title)
    {
        $this->legendTitle = $title;

        return $this;
    }

    /** 地图盒子标题。 */
    public function title(string $title)
    {
        $this->title = $title;

        return $this;
    }

    /** 副标题。 */
    public function subtitle(string $subtitle)
    {
        $this->subtitle = $subtitle;

        return $this;
    }

    /** 是否显示"返回全国"按钮（默认 true）。 */
    public function showBackButton(bool $show = true)
    {
        $this->showBackButton = $show;

        return $this;
    }

    /** 色阶数组（浅→深，legend 与 visualMap 共用）。 */
    public function colorScale(array $colors)
    {
        $this->colorScale = $colors;

        return $this;
    }

    /** 无数据区域底色。 */
    public function areaColor(string $color)
    {
        $this->areaColor = $color;

        return $this;
    }

    /** hover 高亮色。 */
    public function emphasisColor(string $color)
    {
        $this->emphasisColor = $color;

        return $this;
    }

    /** 是否开启拖拽缩放。 */
    public function roam(bool $roam = true)
    {
        $this->roam = $roam;

        return $this;
    }

    /** 数值单位/后缀（tooltip 中数值后追加，如 " 人"）。 */
    public function valueSuffix(string $suffix)
    {
        $this->valueSuffix = $suffix;

        return $this;
    }

    /** 各省业务数据 [{name,value}]。 */
    public function data(array $data)
    {
        $this->data = $data;

        return $this;
    }

    /** 各省下钻市级数据 { 省名: [{name,value}] }。 */
    public function provinceData(array $data)
    {
        $this->provinceData = $data;

        return $this;
    }

    // ============ 渲染 ============

    /**
     * 容器选择器 getter/setter（仿 ApexCharts\Chart）。
     */
    public function selector(?string $selector = null)
    {
        if ($selector === null) {
            return $this->containerSelector;
        }

        $this->containerSelector = $selector;

        if ($selector && ! $this->built) {
            $this->autoRender();
        }

        return $this;
    }

    /**
     * 生成唯一 id。
     */
    protected function generateId(): string
    {
        return $this->idPrefix.Str::random(8);
    }

    public function render()
    {
        if ($this->built) {
            return;
        }
        $this->built = true;

        return parent::render();
    }

    public function html()
    {
        $hasSelector = $this->containerSelector ? true : false;

        if (! $hasSelector) {
            $id = $this->generateId();
            $this->selector('#'.$id);
        }

        $this->addScript();

        if ($hasSelector) {
            return;
        }

        return $this->renderHtml($id);
    }

    /**
     * 渲染 DOM 结构（盒子 header + legend-panel + 地图容器）。
     */
    protected function renderHtml(string $id): string
    {
        $widthStyle  = is_numeric($this->width) ? $this->width.'px' : $this->width;
        $heightStyle = $this->height.'px';
        $boxStyle    = 'background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,.08);overflow:hidden;border:1px solid #f0f0f0;';
        $headerStyle = 'padding:16px 20px;border-bottom:1px solid #f0f0f0;display:flex;align-items:center;justify-content:space-between;';
        $bodyStyle   = 'display:flex;align-items:stretch;';
        $mapStyle    = "flex:1;width:100%;height:{$heightStyle};min-width:0;";
        $legendStyle = "width:{$this->legendWidth}px;flex-shrink:0;border-right:1px solid #f0f0f0;padding:12px;overflow-y:auto;max-height:{$heightStyle};";

        $backBtn = $this->showBackButton
            ? '<button type="button" class="btn btn-sm btn-white" data-chinamap-back="'.$id.'" style="border:1px solid #d9d9d9;">返回全国</button>'
            : '';

        $legendHtml = $this->showLegend
            ? '<div class="chinamap-legend-panel" data-chinamap-legend="'.$id.'" style="'.$legendStyle.'">'
                .'<div class="chinamap-legend-title" style="font-size:13px;font-weight:600;color:#1f2937;margin:0 4px 10px;">'.e($this->legendTitle).'</div>'
              .'</div>'
            : '';

        return <<<HTML
<div class="chinamap-box" id="{$id}" style="width:{$widthStyle};{$boxStyle}" data-chinamap="{$id}">
    <div class="chinamap-header" style="{$headerStyle}">
        <div>
            <div class="chinamap-title" style="font-size:18px;font-weight:600;color:#1f2937;" data-chinamap-title="{$id}">{$this->title}</div>
            <div class="chinamap-sub" style="font-size:13px;color:#9ca3af;margin-top:4px;">{$this->subtitle}</div>
        </div>
        {$backBtn}
    </div>
    <div class="chinamap-body" style="{$bodyStyle}">
        {$legendHtml}
        <div class="chinamap-canvas" data-chinamap-canvas="{$id}" style="{$mapStyle}"></div>
    </div>
</div>
HTML;
    }

    /**
     * 注入初始化脚本。把蓝本 index.html 的逻辑封装进 IIFE，参数从 PHP 注入。
     */
    protected function addScript()
    {
        $id = ltrim((string) $this->containerSelector, '#');

        // 省 JS 的浏览器可访问 URL：由 @echarts-china-map 别名（china.js）解析出完整 URL，再推导 province 目录。
        // 例：/vendor/dcat-admin/dcat/plugins/echarts-china-map/map/china.js
        //     → /vendor/dcat-admin/dcat/plugins/echarts-china-map/map/province/
        $chinaUrls = (array) Admin::asset()->get('@echarts-china-map', 'js');
        $chinaUrl  = $chinaUrls[0] ?? '';
        $provinceUrl = $chinaUrl ? rtrim(str_replace('china.js', 'province/', $chinaUrl), '/').'/' : '';

        $config = JavaScript::format([
            'boxId'          => $id,
            'canvasSel'      => '[data-chinamap-canvas="'.$id.'"]',
            'legendSel'      => '[data-chinamap-legend="'.$id.'"]',
            'titleSel'       => '[data-chinamap-title="'.$id.'"]',
            'backSel'        => '[data-chinamap-back="'.$id.'"]',
            'data'           => Helper::array($this->data, false),
            'provinceData'   => (object) $this->provinceData, // 强制 JS 对象
            'colorScale'     => $this->colorScale,
            'areaColor'      => $this->areaColor,
            'emphasisColor'  => $this->emphasisColor,
            'roam'           => $this->roam,
            'valueSuffix'    => $this->valueSuffix,
            'legendTitle'    => $this->legendTitle,
            'titleText'      => $this->title,
            'provinceUrl'    => $provinceUrl,
        ]);

        $this->script = $this->buildScript($config);
    }

    /**
     * 地图初始化 JS（移植自 index.html，做组件化改造：按 boxId 隔离实例）。
     */
    protected function buildScript(string $config): string
    {
        return <<<JS
(function () {
    var cfg = {$config};

    // 省份拼音 → 中文名 映射（与 ECharts 官方 province/*.js 文件名对应）
    var PROVINCES_PNY = ['shanghai','hebei','shanxi','neimenggu','liaoning','jilin','heilongjiang','jiangsu','zhejiang','anhui','fujian','jiangxi','shandong','henan','hubei','hunan','guangdong','guangxi','hainan','sichuan','guizhou','yunnan','xizang','shanxi1','gansu','qinghai','ningxia','xinjiang','beijing','tianjin','chongqing','xianggang','aomen'];
    var PROVINCES_CN  = ['上海','河北','山西','内蒙古','辽宁','吉林','黑龙江','江苏','浙江','安徽','福建','江西','山东','河南','湖北','湖南','广东','广西','海南','四川','贵州','云南','西藏','陕西','甘肃','青海','宁夏','新疆','北京','天津','重庆','香港','澳门'];

    var canvasEl = document.querySelector(cfg.canvasSel);
    if (!canvasEl || typeof echarts === 'undefined') return;
    var chart = echarts.init(canvasEl);

    function hexToRgb(hex) {
        var h = String(hex).replace('#','');
        return [parseInt(h.substring(0,2),16), parseInt(h.substring(2,4),16), parseInt(h.substring(4,6),16)];
    }
    function getColorByValue(value, min, max) {
        if (value == null || isNaN(value)) value = 0;
        var ratio = max > min ? (value - min) / (max - min) : 0;
        ratio = Math.max(0, Math.min(1, ratio));
        var seg = (cfg.colorScale.length - 1) * ratio;
        var i = Math.floor(seg);
        if (i >= cfg.colorScale.length - 1) return cfg.colorScale[cfg.colorScale.length - 1];
        var t = seg - i, c1 = hexToRgb(cfg.colorScale[i]), c2 = hexToRgb(cfg.colorScale[i+1]);
        return 'rgb('+Math.round(c1[0]+(c2[0]-c1[0])*t)+','+Math.round(c1[1]+(c2[1]-c1[1])*t)+','+Math.round(c1[2]+(c2[2]-c1[2])*t)+')';
    }
    function dataMax(arr){ var m=0; for(var i=0;i<arr.length;i++){ if((arr[i].value||0)>m) m=arr[i].value||0; } return m; }

    function renderLegend(data, title, max) {
        var panel = document.querySelector(cfg.legendSel);
        if (!panel) return;
        var t = title || cfg.legendTitle;
        var sorted = data.slice().sort(function(a,b){ return (b.value||0)-(a.value||0); });
        var html = '<div style="font-size:13px;font-weight:600;color:#1f2937;margin:0 4px 10px;">'+t+'</div>';
        for (var i=0;i<sorted.length;i++) {
            var val = sorted[i].value||0;
            var color = getColorByValue(val, 0, max);
            html += '<div style="display:flex;align-items:center;padding:5px 4px;border-radius:4px;font-size:12px;color:#4b5563;">'
                + '<span style="width:12px;height:12px;border-radius:3px;margin-right:8px;flex-shrink:0;background:'+color+';border:1px solid rgba(0,0,0,.06);"></span>'
                + '<span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">'+sorted[i].name+'</span>'
                + '<span style="font-weight:600;color:#1f2937;margin-left:8px;">'+val+'</span>'
                + '</div>';
        }
        panel.innerHTML = html;
    }

    var labelFormatter = function (p) {
        var val = (p.value != null && !isNaN(p.value)) ? p.value : 0;
        // heredoc 内转义换行需用双反斜杠 \\n，否则被 PHP 解析为真换行
        return '{v|'+val+'}\\n{n|'+p.name+'}';
    };
    var labelRich = {
        v: { fontSize: 11, fontWeight: 'bold', color: '#1f2937', lineHeight: 14 },
        n: { fontSize: 9, color: '#6b7280', lineHeight: 12 }
    };

    function initEcharts(pName, cnName) {
        var isChina = pName === 'china';
        var seriesData = isChina ? cfg.data : (cfg.provinceData[pName] || []);
        var max = isChina ? dataMax(cfg.data) : dataMax(seriesData);
        if (max === 0) max = 3500;
        var legendTitle = isChina ? '各省份明细' : (cnName || pName)+' · 区域明细';

        var titleEl = document.querySelector(cfg.titleSel);
        if (titleEl) titleEl.textContent = isChina ? cfg.titleText : (cnName || pName)+' · 用户扩展分布';

        renderLegend(seriesData, legendTitle, max);

        var option = {
            tooltip: { trigger: 'item', formatter: function(p){
                var val = p.value==null||isNaN(p.value)?0:p.value;
                return p.name+'<br/>数值：<b>'+val+'</b>'+cfg.valueSuffix;
            }},
            visualMap: {
                type: 'continuous', min: 0, max: max, right: 10, bottom: 60,
                text: ['多','少'], textStyle: { color:'#6b7280', fontSize: 12 },
                calculable: true, itemHeight: 160, inRange: { color: cfg.colorScale }
            },
            series: [{
                name: cnName || pName, type: 'map', mapType: pName, roam: cfg.roam,
                itemStyle: { areaColor: cfg.areaColor, borderColor: '#fff', borderWidth: 1 },
                emphasis: {
                    label: { show:true, position:'inside', color:'#1f2937', fontSize:10, lineHeight:13, formatter: labelFormatter, rich: labelRich },
                    itemStyle: { areaColor: cfg.emphasisColor }
                },
                label: { show:true, position:'inside', color:'#1f2937', fontSize:10, lineHeight:13, formatter: labelFormatter, rich: labelRich },
                data: seriesData, top: '3%'
            }]
        };

        chart.setOption(option, true);
        chart.off('click'); chart.off('dblclick');

        if (isChina) {
            chart.on('click', function (param) {
                for (var i=0;i<PROVINCES_CN.length;i++) {
                    if (param.name === PROVINCES_CN[i]) { showProvince(PROVINCES_PNY[i], PROVINCES_CN[i]); break; }
                }
            });
        } else {
            chart.on('dblclick', function () { initEcharts('china','中国'); });
        }
    }

    function showProvince(pName, cnName) {
        loadScript('chinamap-province-'+cfg.boxId+'-'+pName, cfg.provinceUrl+pName+'.js', function () {
            initEcharts(cnName);
        });
    }
    function loadScript(scriptId, url, callback) {
        if (document.getElementById(scriptId)) { callback(); return; }
        var s = document.createElement('script');
        s.type = 'text/javascript';
        s.onload = function(){ callback(); };
        s.src = url; s.id = scriptId;
        document.getElementsByTagName('head')[0].appendChild(s);
    }

    var backBtn = document.querySelector(cfg.backSel);
    if (backBtn) backBtn.onclick = function(){ initEcharts('china','中国'); };

    initEcharts('china','中国');

    // 实例级 resize（避免多实例共享同一 resize 闭包）
    var rsz = function(){ chart.resize(); };
    window.addEventListener('resize', rsz);
})();
JS;
    }
}
