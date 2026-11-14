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
     * 统一走 DialogForm 通道（与 enableDialogCreate / quick edit 完全
     * 同款打开方式）：LazyGrid（异步表格）与 LazyRenderable（异步表单）
     * 都以 renderable->getUrl() 为内容源，layer 原生标题栏，_dialog_form_
     * 服务端最小化渲染，resolveHtml 执行响应内组件脚本。
     */
    protected function renderLayerDriver()
    {
        return $this->renderLayerDialogForm();
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
        $height = $this->layerHeight();

        // 异步表格：启用 simple 模式（与 LazyTable->simple() 对齐，
        // prepare 的自动适配据此生效）并取渲染 URL
        $url = null;
        if ($this->lazyGrid) {
            $this->lazyGrid->simple(true);
            $url = $this->lazyGrid->getUrl();
        } elseif ($this->getRenderable()) {
            $url = $this->getRenderable()->getUrl();
        }

        // 内容区加内边距，表格/表单不贴着弹窗边框；滚动条调细。
        // DialogForm（第三方类）layer 无 class 注入点，故规则对全局
        // .layui-layer-page 生效——后台所有 layer 内容区统一细滚动条，
        // 视觉一致性优于局部特例；padding 仅命中含 modal-content/grid
        // 的 wmodal 与 DialogForm 内容
        Admin::style(
            '.layui-layer-page .layui-layer-content{padding:20px;min-height:50vh;}'.
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
JS
        );
        }

        Form::dialog($title)
            ->url($url)
            ->click('.dcat-wmodal-toggle[data-modal-id='.$id.']')
            ->dimensions($width, $height)
            ->viewOnly($this->viewOnly);

        return $this->renderLayerButton();
    }

    protected function layerWidth()
    {
        $map = ['sm' => '300px', 'lg' => '800px', 'xl' => '1140px'];

        return $map[$this->size] ?? '520px';
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
