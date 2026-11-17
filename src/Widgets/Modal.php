<?php

namespace Dcat\Admin\Widgets;

use Closure;
use Dcat\Admin\Admin;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Form;
use Dcat\Admin\Grid\LazyRenderable as LazyGrid;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Traits\InteractsWithRenderApi;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Str;

class Modal extends Widget
{
    use InteractsWithRenderApi;

    /**
     * 弹窗引擎支持矩阵（modal_driver=layer 时）.
     *
     * 完整支持：title/content/body/button/footer/join、sm/lg/xl/size、
     *           viewOnly、staticBackdrop、delay、viewOnly+footer 组合
     * 事件桥接：onShow/onShown/onHide/onHidden/on(event) 在 layer 打开与
     *           关闭时机触发（target 指向内容根元素，存量脚本兼容）
     * 语义调整：centered —— layer 始终居中，视为 no-op（无效果无害）；
     *           scrollable —— 内容区超 80vh 自动内部滚动，始终生效；
     *           enlargedTransform —— BS4 专属入场动画，layer 下 no-op；
     *           sm/lg/xl 尺寸在 layer 下同时映射弹层高度（40%/70%/90%）
     */
    protected $target = 'modal';

    /**
     * @var string|Closure|Renderable
     */
    protected $title;

    /**
     * @var string|Closure|Renderable
     */
    protected $content;

    /**
     * @var string|Closure|Renderable
     */
    protected $footer;

    /**
     * @var string|Closure|Renderable
     */
    protected $button;

    /**
     * @var string
     */
    protected $size = '';

    /**
     * @var string
     */
    protected $centered = '';

    /**
     * @var string
     */
    protected $scrollable = '';
    /**
     * @var array
     */
    protected $events = [];

    /**
     * @var int
     */
    protected $delay = 10;

    /**
     * @var bool
     */
    protected $join = false;

    /**
     * @var bool
     */
    protected $static = false;

    /**
     * 异步表格的 LazyGrid（layer 驱动下走 DialogForm 通道）.
     *
     * @var LazyGrid
     */
    protected $lazyGrid;

    /**
     * 是否只读弹层（查看详情）：无提交/重置按钮.
     *
     * @var bool
     */
    protected $viewOnly = false;

    /**
     * 弹窗内工具表单成功自动关闭钩子是否已注册（进程级幂等）.
     *
     * @var bool
     */
    protected static $formAutoCloseHooked = false;

    /**
     * Modal constructor.
     *
     * @param  string|Closure|Renderable  $title
     * @param  string|Closure|Renderable|LazyRenderable  $content
     */
    public function __construct($title = null, $content = null)
    {
        $this->id('modal-'.Str::random(10));
        $this->title($title);
        $this->content($content);

        $this->class('modal fade');
    }

    /**
     * 设置弹窗垂直居中.
     *
     * @param  bool  $value
     * @return $this
     */
    public function centered(bool $value = true)
    {
        $this->centered = $value ? 'modal-dialog-centered' : '';

        return $this;
    }

    /**
     * 设置弹窗内容滚动.
     *
     * @param  bool  $value
     * @return $this
     */
    public function scrollable(bool $value = true)
    {
        $this->scrollable = $value ? 'modal-dialog-scrollable' : '';

        return $this;
    }

    /**
     * 设置弹窗为静态 只能手动关闭
     * @desc
     * @param bool $bool
     * @return $this
     */
    public function staticBackdrop($bool = true){
        $this->static = $bool;

        return $this;
    }
    
    // 启用放大动画
    public function enlargedTransform(){
        Admin::style(
            <<<CSS
    .modal.fade .modal-dialog {
    transform: scale(0.8);
    transition: transform 0.3s ease; 
}

.modal.show .modal-dialog {
    transform: scale(1); 
}
CSS
        );
        return $this;
    }
    

    /**
     * 设置弹窗尺寸.
     *
     * @param  string  $size
     * @return $this
     */
    public function size(string $size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * 设置弹窗尺寸为 sm 300px.
     *
     * @return $this
     */
    public function sm()
    {
        return $this->size('sm');
    }

    /**
     * 设置弹窗尺寸为 lg 800px.
     *
     * @return $this
     */
    public function lg()
    {
        return $this->size('lg');
    }

    /**
     * 设置弹窗尺寸为 xl 1140px.
     *
     * @return $this
     */
    public function xl()
    {
        return $this->size('xl');
    }

    /**
     * 设置loading效果延迟时间.
     *
     * @param  int  $delay
     * @return $this
     */
    public function delay(int $delay)
    {
        $this->delay = $delay;

        return $this;
    }

    /**
     * 设置按钮.
     *
     * @param  string|Closure|Renderable  $button
     * @return $this
     */
    public function button($button)
    {
        $this->button = $button;

        return $this;
    }

    /**
     * 设置弹窗标题.
     *
     * @param  string|Closure|Renderable  $title
     * @return $this
     */
    public function title($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * 设置弹窗内容.
     *
     * @param  string|Closure|Renderable|LazyRenderable  $content
     * @return $this
     */
    public function content($content)
    {
        if ($content instanceof LazyGrid) {
            // layer 驱动下保留 LazyGrid 原始引用（走 DialogForm 通道，
            // 服务端渲染完整 grid，分页/筛选天然可用）；bootstrap 驱动
            // 仍走 LazyTable 容器（table:load 链路）
            $this->lazyGrid = $content;
            // 异步表格是查看性质：强制 viewOnly（无提交/重置按钮），
            // 不受调用方遗漏设置影响
            $this->viewOnly(true);

            $content = $table =
                LazyTable::make()
                ->from($content)
                ->simple()
                ->load(false);

            $this->onShow("target.find('{$table->getElementSelector()}').trigger('table:load')");
        }

        if ($content instanceof LazyRenderable) {
            $this->setRenderable($content);
            // Widgets\Form 表单自带的提交/重置按钮在服务端隐藏
            //（DialogForm::is() 时 disableSubmitButton/ResetButton），
            // 提交由 DialogForm layer 底部按钮区驱动——与弹窗创建/
            // 快捷编辑的单一提交形态一致
        } else {
            $this->content = $content;
        }

        return $this;
    }

    /**
     * @param  string|Closure|Renderable|LazyRenderable  $content
     * @return $this
     */
    public function body($content)
    {
        return $this->content($content);
    }

    /**
     * 设置是否返回弹窗HTML.
     *
     * @param  bool  $value
     * @return $this
     */
    public function join(bool $value = true)
    {
        $this->join = $value;

        return $this;
    }

    /**
     * 设置弹窗底部内容.
     *
     * @param  string|Closure|Renderable|LazyRenderable  $footer
     * @return $this
     */
    public function footer($footer)
    {
        $this->footer = $footer;

        return $this;
    }

    /**
     * 监听弹窗事件.
     *
     * @param  string  $event
     * @param  string  $script
     * @return $this
     */
    public function on(string $event, string $script)
    {
        $this->events[] = compact('event', 'script');

        return $this;
    }

    /**
     * 监听弹窗显示事件.
     *
     * @param  string  $script
     * @return $this
     */
    public function onShow(string $script)
    {
        return $this->on('show.bs.modal', $script);
    }

    /**
     * 监听弹窗已显示事件.
     *
     * @param  string  $script
     * @return $this
     */
    public function onShown(string $script)
    {
        return $this->on('shown.bs.modal', $script);
    }

    /**
     * 监听弹窗隐藏事件.
     *
     * @param  string  $script
     * @return $this
     */
    public function onHide(string $script)
    {
        return $this->on('hide.bs.modal', $script);
    }

    /**
     * 监听弹窗已隐藏事件.
     *
     * @param  string  $script
     * @return $this
     */
    public function onHidden(string $script)
    {
        return $this->on('hidden.bs.modal', $script);
    }

    protected function addScript()
    {
        if (! $this->events) {
            return;
        }

        $script = '';

        foreach ($this->events as $v) {
            $script .= "target.on('{$v['event']}', function (event) {
                {$v['script']}
            });";
        }

        $this->script = <<<JS
(function () {
    var target = $('#{$this->id()}'), body = target.find('.modal-body');
    {$this->getRenderableScript()}
    {$script}
})();
JS;
    }

    protected function addLoadRenderableScript()
    {
        if (! $this->getRenderable()) {
            return;
        }

        $this->on('show.bs.modal', <<<JS
body.html('<div style="min-height:150px"></div>').loading();
        
setTimeout(function () {
    target.trigger('{$this->target}:load')
}, {$this->delay});
JS
        );
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        if ($this->useLayerDriver()) {
            return $this->renderLayerDriver();
        }

        $this->addLoadRenderableScript();
        $this->addScript();

        if ($this->join) {
            return $this->renderButton().parent::render();
        }

        Admin::html(parent::render());

        return $this->renderButton();
    }

    /**
     * 是否使用 layer 弹窗引擎（默认）.
     *
     * BS4 modal 官方不支持多实例叠加（嵌套弹窗六死因的总根源），
     * layer 为自管理弹层系统：每层独立遮罩/独立销毁/天然多层叠加。
     * 遇到兼容问题可在 config admin.layout.modal_driver 切回 bootstrap。
     */
    protected function useLayerDriver()
    {
        return config('admin.layout.modal_driver', 'layer') !== 'bootstrap';
    }

    /**
     * 是否只读弹层（查看详情）：无提交/重置按钮.
     *
     * @param  bool  $value
     * @return $this
     */
    public function viewOnly(bool $value = true)
    {
        $this->viewOnly = $value;

        return $this;
    }

    /**
     * layer 引擎渲染：不输出 BS4 modal DOM，按钮改为 layer 触发器.
     *
     * 按内容类型分流：
     * - LazyRenderable（异步表单）/ LazyGrid（异步表格）→ DialogForm 通道，
     *   以 renderable->getUrl() 为内容源异步加载；
     * - 同步内容（纯 HTML 字符串 / Closure / 普通 Renderable 组件）→
     *   原生 layer 通道，内容随页面渲染进隐藏容器，点击整体搬入弹层。
     *   DialogForm 通道没有内容源 URL，同步内容走它会点击报错（url 为
     *   null）且无内容可加载。
     */
    protected function renderLayerDriver()
    {
        static::injectLayerFitScript();

        if ($this->getRenderable() || $this->lazyGrid) {
            return $this->renderLayerDialogForm();
        }

        return $this->renderLayerNative();
    }

    /**
     * 弹层超高封顶守护（进程级一次注入，窗口级幂等）.
     *
     * 语义（用户拍板）：显式指定高度的弹层（sm/lg/xl 档位、DialogForm
     * dimensions、配置 dialog_form_area）高度永不变化——开屏多大就多大；
     * 只有未指定高度（area height=auto，即「内容自适应」形态）的弹层才
     * 受本守护管辖：内容超高 90% 视口时封顶一次并转内部滚动（瞬时跳变，
     * 全局已禁 layer 过渡，无动画感），内容回落后交还 auto。
     * 无定时器轮询——两条通道都是内容就绪后才开层，开屏即终态高度；
     * MutationObserver 只服务后续内容变化（分页/筛选）。
     */
    public static function injectLayerFitScript()
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        // 统一弹窗配置注入 JS（admin.layout.dialog 单一事实源）：
        // patchLayerOpen 据此对缺省 shade 的弹层强制补齐全局默认
        $dialogDefaults = [
            'shade'        => (bool) config('admin.layout.dialog.shade', false),
            'destroyOnClose' => (bool) config('admin.layout.dialog.destroy_on_close', true),
        ];

        Admin::script('window.__dialogDefaults = '.json_encode($dialogDefaults).';');

        Admin::script(<<<'JS'
if (! window.__wmodalFit) {
    window.__wmodalFit = true;
    (function () {
        // ① 包装 layer.open（幂等，当前窗口 + top 窗口——iframe-tabs 下
        // DialogForm 使用 top.window.layer）：area 高度为 auto 的 page 层，
        // layer 内部会「测量内容 → 把像素写死到 content.style.height」。
        // 该测量受内容节点布局状态影响（经历挂载/归位周期后的节点复用、
        // 异步内容加载中），第二次打开同一弹层时常测出虚高值（如 442px
        // 写死 → 底部大空白），且 layer 居中计算也基于错误高度（top 为负）。
        // 修法：开层后同步把 content 还原 auto，由内容自然撑开——开屏即
        // 终态、无修正动画；居中在首帧前按真实高度重算
        function patchLayerOpen(win) {
            if (! win || ! win.layer || win.__wmodalOpenPatched) { return; }
            win.__wmodalOpenPatched = true;
            var origOpen = win.layer.open;
            win.layer.open = function (opts) {
                try {
                    // 统一弹窗配置兜底（window.__dialogDefaults 由服务端
                    // 从 admin.layout.dialog 注入）：未显式声明 shade 的
                    // 弹层强制应用全局默认——layer 实例被重建时（如历史
                    // 版本的重复加载）会丢失 layer.config 全局配置，此
                    // 处按配置源补齐，保证三条链路遮罩行为一致
                    if (opts && opts.shade === undefined) {
                        opts.shade = (window.__dialogDefaults || {}).shade;
                    }
                } catch (e) {}
                var idx = origOpen.apply(this, arguments);
                try {
                    if (opts && opts.type === 1 && opts.area && opts.area[1] === 'auto') {
                        var el = document.getElementById('layui-layer' + idx);
                        if (el) {
                            var c = el.querySelector('.layui-layer-content');
                            if (c) { c.style.height = 'auto'; }
                            requestAnimationFrame(function () {
                                var h = el.getBoundingClientRect().height;
                                if (h > 0) {
                                    el.style.top = Math.max(0, Math.round((window.innerHeight - h) / 2)) + 'px';
                                }
                            });
                        }
                    }
                } catch (e) {}
                return idx;
            };
        }
        patchLayerOpen(window);
        try { patchLayerOpen(window.top); } catch (e) {}

        // ② 超高封顶守护：auto 层内容超 90% 视口时封顶一次转内部滚动；
        // 显式高度层（首见判定）永不干预
        var pending = null;
        function fit() {
            pending = null;
            var maxH = Math.round(window.innerHeight * 0.9);
            jQuery('.layui-layer.layui-layer-page').each(function () {
                var el = this, $l = jQuery(el);
                if ($l.css('display') === 'none') { return; }
                var auto = $l.data('wmodal-auto');
                if (auto == null) {
                    var inlineH = (el.style.height || '').trim();
                    auto = ! inlineH || inlineH === 'auto';
                    $l.data('wmodal-auto', auto);
                }
                if (! auto) { return; }
                var $title = $l.children('.layui-layer-title');
                var $btn = $l.children('.layui-layer-btn');
                var $content = $l.children('.layui-layer-content');
                if (! $content.length) { return; }
                var chrome = ($title.length ? $title.outerHeight() : 0)
                           + ($btn.length ? $btn.outerHeight(true) : 0);
                var c = $content[0], prev = c.style.height;
                c.style.height = 'auto';
                var need = c.scrollHeight + chrome + 4;
                c.style.height = prev;
                if (need >= maxH) {
                    // 超高：封顶 + 内容区内部滚动（瞬时，无过渡）
                    if (Math.abs($l.outerHeight() - maxH) <= 2) { return; }
                    $l.css('height', maxH + 'px');
                    c.style.height = (maxH - chrome - 2) + 'px';
                } else {
                    // 未超高：确保交还原生 auto（内容回落场景）
                    if ($l[0].style.height && $l[0].style.height !== 'auto') {
                        $l.css('height', 'auto');
                        c.style.height = '';
                    } else { return; }
                }
                // 垂直居中（用户拖动过则不再强制回中）
                var stored = $l.data('wmodal-top');
                if (stored == null || parseInt(el.style.top) === parseInt(stored)) {
                    var top = Math.max(0, Math.round((window.innerHeight - $l.outerHeight()) / 2));
                    el.style.top = top + 'px';
                    $l.data('wmodal-top', top);
                }
            });
        }
        function schedule() { if (pending == null) { pending = setTimeout(fit, 180); } }
        new MutationObserver(schedule).observe(document.body, { childList: true, subtree: true });
        jQuery(window).on('resize', schedule);
    })();
}
JS
        );
    }

    /**
     * 原生 layer 通道：同步内容（销毁式）.
     *
     * 原始内容存 <template>（浏览器不渲染、不可见，仅作模板源），每次
     * 点击 clone 全新节点开层，关闭随 layer 销毁——页面不残留任何弹窗
     * DOM，重复打开每次都是初始状态。内联 onclick 等静态属性随 clone
     * 保留。
     * 含 &lt;form&gt; 的内容（工具表单 Widgets\Form + handle 等）自动切换
     * 「隐藏容器」模式：容器随页面渲染进 DOM（页面加载时提交脚本才能
     * 绑定成功——template 内节点不在文档树，jQuery 选择器找不到），点击
     * 整体搬入弹层（元素身份不变，绑定全部有效），关闭归位隐藏容器
     * （与 BS4 modal show/hide 等价，状态延续）。
     * 高度默认自适应（同 BS4），显式 size 时用映射档位。
     * 事件桥：onShow/onShown → 打开时机、onHide/onHidden → 关闭时机
     * （target/body 指向内容根元素，存量脚本兼容）。
     */
    protected function renderLayerNative()
    {
        $id = $this->id();
        $title = json_encode(trim(strip_tags((string) Helper::render($this->title))));
        $width = json_encode($this->layerWidth());
        $height = json_encode($this->size ? $this->layerHeight() : 'auto');

        $content = Helper::render($this->content);
        if ($footer = Helper::render($this->footer)) {
            $content .= '<div class="modal-footer" style="border-top: 1px solid #ebebeb;">'.$footer.'</div>';
        }

        $showScript = '';
        $hideScript = '';
        foreach ($this->events as $v) {
            // 注意 'hidden.bs.modal' 不以 'hide' 开头（hidd≠hide），需并列匹配
            if (Str::startsWith($v['event'], 'show')) {
                $showScript .= $v['script']."\n";
            } elseif (Str::startsWith($v['event'], ['hide', 'hidden'])) {
                $hideScript .= $v['script']."\n";
            }
        }

        // 含 <form> → 容器模式（提交脚本页面加载时绑定，template 内节点
        // 不在文档树绑定会静默失败）；否则 → template clone 销毁式。
        // 容器关闭行为由统一配置 destroy_on_close 派生：
        //   true（默认）→ jQuery .detach() 摘出 DOM（页面零残留；detached
        //                 节点保留全部事件绑定与表单状态，重开自动挂载）
        //   false       → 归位隐藏（BS4 旧语义：节点留在页面 display:none）
        if (str_contains($content, '<form')) {
            $destroyOnClose = (bool) config('admin.layout.dialog.destroy_on_close', true);
            $closeLine = $destroyOnClose
                ? "\$box.detach();"                    // 摘出 DOM：零残留，绑定/状态存于内存
                : "\$box.appendTo('body').hide();";    // 归位隐藏：BS4 旧语义
            $box = '<div id="wmodal-box-'.$id.'" style="display:none">'.$content.'</div>';
            if ($this->join) {
                $boxHtml = $box;
            } else {
                Admin::html($box);
                $boxHtml = '';
            }

            Admin::script(<<<JS
// IIFE 隔离：同页多个容器模式 Modal 的脚本合并进同一 <script>，
// var 顶层声明会互相覆盖（A 的缓存被 B 命中 → 显示 A 的内容）
(function () {
    var \$boxCache = null;
    \$(document).off('click.wmodal-native-{$id}').on('click.wmodal-native-{$id}', '.dcat-wmodal-toggle[data-modal-id={$id}]', function () {
        var \$box = \$boxCache || jQuery('#wmodal-box-{$id}');
        \$boxCache = \$box;
        if (! \$box.length) { return; }
        // detach 后的节点需先挂回文档再交 layer（layer 要求 content 在文档中）
        if (! \$box.parent().length) { \$box.appendTo('body'); }
        layer.open({
            type: 1,
            title: {$title},
            // 移动端宽度自适应：屏幕或视口 ≤800px（与 DialogForm 通道同款
            // 判定阈值）时宽度收窄为满屏，避免桌面 px 宽度溢出手机视口；
            // 高度沿用既有语义（auto 自适应/百分比恒定）不受影响
            area: (function () {
                var a = [{$width}, {$height}];
                var small = Math.min(window.screen ? window.screen.width : 9999, document.documentElement.clientWidth) <= 800;
                if (small) { a[0] = '100%'; }
                return a;
            })(),
            content: \$box.show(),
            success: function (layero) {
                var target = \$box, body = \$box;
                {$showScript}
            },
            end: function () {
                // destroy_on_close：detach=摘出 DOM（零残留，绑定与状态保留在
                // 内存，重开自动挂载）；hide=归位隐藏（节点留页面）
                {$closeLine}
                var target = \$box, body = \$box;
                {$hideScript}
            }
        });
    });
})();
JS
            );

            return $boxHtml.$this->renderLayerButton();
        }

        // 销毁式：内容存 <template>（单根 wrapper 包裹，template 不被
        // 浏览器渲染、不进可视 DOM），点击 clone 全新节点开层，关闭随
        // layer 销毁——页面零残留、每次打开都是初始状态。clone 节点需
        // 先入 DOM（瞬态载体）再交给 layer，否则 detached 节点开层异常
        $tpl = '<template id="wmodal-tpl-'.$id.'"><div class="wmodal-tpl-wrap" style="display:none">'.$content.'</div></template>';
        if ($this->join) {
            $tplHtml = $tpl;
        } else {
            Admin::html($tpl);
            $tplHtml = '';
        }

        Admin::script(<<<JS
\$(document).off('click.wmodal-native-{$id}').on('click.wmodal-native-{$id}', '.dcat-wmodal-toggle[data-modal-id={$id}]', function () {
    var tpl = document.getElementById('wmodal-tpl-{$id}');
    if (! tpl) { return; }
    // 每次全新 clone；先以隐藏态入 DOM（layer 要求 content 节点在文档中），
    // 关闭随 layer 销毁，不在页面留下任何痕迹
    var node = tpl.content.firstElementChild.cloneNode(true);
    var \$box = jQuery(node).appendTo('body');
    layer.open({
        type: 1,
        title: {$title},
        // 移动端宽度自适应（同上：屏幕/视口 ≤800px 收窄为满屏）
        area: (function () {
            var a = [{$width}, {$height}];
            var small = Math.min(window.screen ? window.screen.width : 9999, document.documentElement.clientWidth) <= 800;
            if (small) { a[0] = '100%'; }
            return a;
        })(),
        content: \$box.show(),
        success: function (layero) {
            var target = \$box, body = \$box;
            {$showScript}
        },
        end: function () {
            // 销毁式：layer 关闭时可能把内容节点放回 body，显式移除——
            // 页面零残留
            \$box.remove();
            var target = \$box, body = \$box;
            {$hideScript}
        }
    });
});
JS
        );
        // 自动关闭所在弹层（一次性包装 handleJsonResponse，幂等防重复注册）。
        // 用 form[action*=dcat-api/form] 精准识别工具表单——表格弹窗的筛选
        // 表单 action 不是该端点，不会误关；DialogForm 通道的表单不走
        // handleJsonResponse（success 语句 return false 中断），互不影响。
        // 注意：不能用 Dcat.Form.submitted——Dcat.Form 只是 new Form 的包装
        // 函数，类静态方法未透传到全局，调用即抛 undefined
        if (! static::$formAutoCloseHooked) {
            static::$formAutoCloseHooked = true;

            Admin::script(<<<'JS'
if (! window.__wmodalFormAutoClose) {
    window.__wmodalFormAutoClose = true;
    var __origHjr = Dcat.handleJsonResponse;
    Dcat.handleJsonResponse = function (response) {
        var r = __origHjr.apply(this, arguments);
        try {
            if (response && response.status) {
                var layers = jQuery('.layui-layer:visible').toArray().reverse();
                for (var i = 0; i < layers.length; i++) {
                    if (jQuery(layers[i]).find('form[action*="dcat-api/form"]').length) {
                        window.layer.close(Number(String(layers[i].id).replace('layui-layer', '')));
                        break;
                    }
                }
            }
        } catch (e) {}
        return r;
    };
}
JS
            );
        }

        return $tplHtml.$this->renderLayerButton();
    }

    /**
     * DialogForm 通道：所有 renderable 内容统一走此通道.
     *
     * 复用 Form::dialog 成熟机制（同 enableDialogCreate / quick edit）：
     * 点击按钮 → layer 打开 → 请求 data-url（renderable->getUrl()）→
     * assets.resolveHtml 执行响应内组件脚本。viewOnly：无提交/重置
     * 按钮，× 或遮罩关闭即销毁。
     */
    protected function renderLayerDialogForm()
    {
        $id = $this->id();
        $title = trim(strip_tags((string) Helper::render($this->title)));
        $width = $this->layerWidth();
        // 未指定尺寸 → 高度 auto：DialogForm 是内容就绪后才开层，
        // 开屏即终态高度（无二次调整）；显式尺寸原样固定、绝不缩放
        $height = $this->size ? $this->layerHeight() : 'auto';

        // 异步表格：启用 simple 模式（与 LazyTable->simple() 对齐，
        // prepare 的自动适配据此生效）并取渲染 URL
        $url = null;
        if ($this->lazyGrid) {
            $this->lazyGrid->simple(true);
            $url = $this->lazyGrid->getUrl();
        } elseif ($this->getRenderable()) {
            $url = $this->getRenderable()->getUrl();
        }

        // 内容区加内边距，表格/表单不贴着弹窗边框；内容超出弹层上限时
        // 内部滚动（overflow:auto 配合高度自适应 fitter）；滚动条调细。
        // DialogForm（第三方类）layer 无 class 注入点，故规则对全局
        // .layui-layer-page 生效——后台所有 layer 内容区统一细滚动条，
        // 视觉一致性优于局部特例
        Admin::style(
            // 禁用 layer 开场动画与过渡：bounceIn 的 fill-mode:both 会在
            // 弹层隐藏/中断时把半程 scale 永久定格（内联样式无法覆盖，
            // 高度自适应测量随之失真），且动画期间测量高度不可靠
            '.layui-layer.layer-anim{animation:none!important;-webkit-animation:none!important}'.
            '.layui-layer{transition:none!important}'.
            '.layui-layer-page .layui-layer-content{padding:20px;overflow:auto;}'.
            '.layui-layer-page .layui-layer-content::-webkit-scrollbar{width:5px;height:5px;}'.
            '.layui-layer-page .layui-layer-content::-webkit-scrollbar-thumb{background-color:#c1c5cd;border-radius:3px;}'.
            '.layui-layer-page .layui-layer-content::-webkit-scrollbar-track{background:transparent;}'
        );

        // 异步表格（LazyGrid）接管：DialogForm 加载 grid 后，把 layer 内的
        // grid 容器注册为 AsyncTable（data-current 由接管逻辑补写）——
        // 分页/排序/搜索/筛选链接 ajax 化，删除后原地重载（拦截链），
        // 全部复用 LazyTable 同款机制。注册后立即触发一次 table:load：
        // AsyncTable.load 会 ajax 拉取 data-current 渲染内容并 bind()
        // 全部链接（与 LazyTable 首次加载一致，搜索/分页从此 ajax 化）。
        // 注意：takeover 找到容器后先手动接管现有 form 的 submit（防
        // load 返回前的点击窗口期导航），load 完成后 bind 会重新接管
        if ($this->lazyGrid) {
            // AsyncTable 定义在 grid-extend.js，仅 LazyTable 页面按需加载；
            // DialogForm 通道必须显式引入，否则 Dcat.grid.AsyncTable 不存在
            Admin::js('@grid-extension');
            $gridUrl = json_encode($url);
            Admin::script(<<<JS
$(document).off('click.wmodal-grid-{$id}').on('click.wmodal-grid-{$id}', '.dcat-wmodal-toggle[data-modal-id={$id}]', function () {
    // DialogForm 在自身点击监听中加载内容；等其 layer 出现后接管
    var tries = 0;
    (function takeover() {
        var layers = document.querySelectorAll('.layui-layer');
        for (var i = 0; i < layers.length; i++) {
            var grid = layers[i].querySelector('.simple-grid, .dcat-grid-con');
            if (grid && ! grid.hasAttribute('data-current')) {
                var \$g = jQuery(grid);
                \$g.attr('data-current', {$gridUrl});
                Dcat.grid.AsyncTable({ container: \$g });
                // 竞态防护：接管瞬间先接管容器内已有 form 的 submit
                \$g.find('form').on('submit', function () {
                    var \$f = jQuery(this);
                    \$g.attr('data-current', \$f.attr('action') + '&' + \$f.serialize());
                    \$g.trigger('table:load');
                    return false;
                });
                \$g.trigger('table:load');
                return;
            }
        }
        if (++tries < 20) { setTimeout(takeover, 200); }
    })();
});
JS, true
        );
        }

        Form::dialog($title)
            ->url($url)
            ->click('.dcat-wmodal-toggle[data-modal-id='.$id.']')
            ->dimensions($width, $height)
            ->viewOnly($this->viewOnly)
            ->forceRefresh()
            // DialogForm 提交走 Dcat.Form({redirect:!1})，其响应链会先删
            // data.then 再 handleJsonResponse → 表单 handle 里的 ->refresh()
            // / ->download() / ->location() 等指令全部失效。
            // 而本回调（DialogForm 的 after）恰好在该删除之前执行，此时
            // response.data.then 完整 → 在此按服务端指令分发，并 return
            // false 中断原链（避免 toastr 弹两次、Dcat.reload 被拦截链
            // 改写为原地刷表格）：
            //   download  → window.open 新窗口下载（与 Ajax.js 原生一致）
            //   redirect  → 整页跳转（不走 Dcat.reload，防拦截链改写）
            //   location  → 有值跳转/无值重载（原生留 1s 给 toastr 展示）
            //   script    → eval 执行
            //   refresh   → 重载整页（->refresh() 的原始语义）
            //   无指令    → 仅提示+关弹窗，不刷新（与普通表单原生语义
            //               一致：success 不链式指令时不做任何页面动作）
            // 注意：success/error/saved 的内容会被 DialogForm 包进
            // function (success, response) { ... } 函数体，必须是「语句」
            // 而非函数定义（函数定义只会声明不会执行，静默失效）
            ->success(<<<'JS'
// 提示展示：与 Ajax.js handleJsonResponse 完全一致——alert() 用
// SweetAlert（detail 为正文），否则 toastr（timeout 秒数可调）
var wdata = response.data || {};
var wmsg = wdata.message;
if (typeof wmsg === 'string' && wmsg) {
    var wtype = wdata.type || 'success';
    if (wdata.alert) {
        Dcat.swal[wtype](wmsg, wdata.detail);
    } else {
        Dcat[wtype](wmsg, null, wdata.timeout ? {timeOut: wdata.timeout * 1000} : {});
    }
}
var wthen = wdata.then;
switch (wthen && wthen.action) {
    case 'download':
        window.open(wthen.value, '_blank');
        break;
    case 'redirect':
        window.location = wthen.value;
        break;
    case 'location':
        setTimeout(function () {
            if (wthen.value) { window.location = wthen.value; }
            else { window.location.reload(); }
        }, 1000);
        break;
    case 'script':
        (function () { eval(wthen.value); })();
        break;
    case 'refresh':
        window.location.reload();
        break;
}
return false;
JS
            );

        return $this->renderLayerButton();
    }

    protected function layerWidth()
    {
        $map = ['sm' => '300px', 'lg' => '800px', 'xl' => '1140px'];

        if (isset($map[$this->size])) {
            return $map[$this->size];
        }

        // size(string) 支持任意自定义值（'600px'/'80%'），非档位键不再丢弃
        if ($this->size && preg_match('/^\d+(\.\d+)?(px|%|vw|vh)$/', $this->size)) {
            return $this->size;
        }

        return config('admin.layout.dialog.width') ?: '520px';
    }

    /**
     * 弹层高度：跟随宽度档位（sm 小内容配矮窗，xl 大内容配高窗）；
     * 尺寸为空（未调 sm/lg/xl）时用 grid 的 dialog_form_area 高度
     * （与 enableDialogCreate 默认 670px 对齐）.
     *
     * @return string
     */
    protected function layerHeight()
    {
        $map = [
            'sm' => '40%',
            'lg' => '70%',
            'xl' => '90%',
        ];

        if (isset($map[$this->size])) {
            return $map[$this->size];
        }

        $gridArea = $this->grid ? ($this->grid->option('dialog_form_area')[1] ?? null) : null;

        return $gridArea ?: '670px';
    }

    protected function renderTitle()
    {
        return Helper::render($this->title);
    }

    protected function renderContent()
    {
        return Helper::render($this->content);
    }

    protected function renderFooter()
    {
        $footer = Helper::render($this->footer);

        if (! $footer) {
            return;
        }

        return <<<HTML
<div class="modal-footer">{$footer}</div>
HTML;
    }

    protected function renderLayerButton()
    {
        if (! $this->button) {
            return;
        }

        $button = Helper::render($this->button);

        // 如果没有HTML标签则添加一个 a 标签
        if (! preg_match('/(\<\/[\d\w]+\s*\>+)/i', $button)) {
            $button = "<a href=\"javascript:void(0)\">{$button}</a>";
        }

        return <<<HTML
<span style="cursor: pointer" class="dcat-wmodal-toggle" data-modal-id="{$this->id()}">{$button}</span>
HTML;
    }

    protected function renderButton()
    {
        if (! $this->button) {
            return;
        }

        $button = Helper::render($this->button);

        // 如果没有HTML标签则添加一个 a 标签
        if (! preg_match('/(\<\/[\d\w]+\s*\>+)/i', $button)) {
            $button = "<a href=\"javascript:void(0)\">{$button}</a>";
        }

        return <<<HTML
<span style="cursor: pointer" data-toggle="modal" data-target="#{$this->id()}">{$button}</span>
HTML;
    }
}
