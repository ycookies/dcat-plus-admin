
let tpl = '<div class="dcat-loading d-flex items-center align-items-center justify-content-center pin" style="{style}">{svg}</div>',
    loading = '.dcat-loading',
    LOADING_SVG = [
        '<svg xmlns="http://www.w3.org/2000/svg" class="mx-auto block" style="width:{width};{svg_style}" viewBox="0 0 120 30" fill="{color}"><circle cx="15" cy="15" r="15"><animate attributeName="r" from="15" to="15" begin="0s" dur="0.8s" values="15;9;15" calcMode="linear" repeatCount="indefinite"/><animate attributeName="fill-opacity" from="1" to="1" begin="0s" dur="0.8s" values="1;.5;1" calcMode="linear" repeatCount="indefinite" /></circle><circle cx="60" cy="15" r="9" fill-opacity="0.3"><animate attributeName="r" from="9" to="9" begin="0s" dur="0.8s" values="9;15;9" calcMode="linear" repeatCount="indefinite" /><animate attributeName="fill-opacity" from="0.5" to="0.5" begin="0s" dur="0.8s" values=".5;1;.5" calcMode="linear" repeatCount="indefinite" /></circle><circle cx="105" cy="15" r="15"><animate attributeName="r" from="15" to="15" begin="0s" dur="0.8s" values="15;9;15" calcMode="linear" repeatCount="indefinite" /><animate attributeName="fill-opacity" from="1" to="1" begin="0s" dur="0.8s" values="1;.5;1" calcMode="linear" repeatCount="indefinite" /></circle></svg>',
    ];

class Loading {
    constructor(Dcat, options) {
        options = $.extend({
            container: Dcat.config.pjax_container_selector,
            zIndex: 100,
            width: '52px',
            color: Dcat.color.dark60,
            background: '#fff',
            style: '',
            svg: LOADING_SVG[0]
        }, options);

        let _this = this,
            defStyle = 'position:absolute;',
            content;

        _this.$container = $(options.container);

        content = $(
            tpl
                .replace('{svg}', options.svg)
                .replace('{color}', options.color)
                .replace('{color}', options.color)
                .replace('{width}', options.width)
                .replace('{style}', `${defStyle}background:${options.background};z-index:${options.zIndex};${options.style}`)
        );
        content.appendTo(_this.$container);
    }

    destroy() {
        this.$container.find(loading).remove();
    }
}

function destroyAll() {
    $(loading).remove();
}

function extend(Dcat) {
    // 全屏居中loading
    Dcat.loading = function (options) {
        if (options === false) {
            // 关闭loading
            return setTimeout(destroyAll, 70);
        }
        // 配置参数
        options = $.extend({
            zIndex: 999991014,
            width: '58px',
            shade: 'rgba(255, 255, 255, 0.1)',
            background: 'transparent',
            top: 200,
            svg: LOADING_SVG[0],
        }, options);

        var win = $(window),
            // 容器
            $container = $('<div class="dcat-loading" style="z-index:'+options.zIndex+';width:300px;position:fixed"></div>'),
            // 遮罩层直接沿用layer
            shadow = $('<div class="layui-layer-shade dcat-loading" style="z-index:'+(options.zIndex-2)+'; background-color:'+options.shade+'"></div>');

        $container.appendTo('body');

        if (options.shade) {
            shadow.appendTo('body');
        }

        function resize() {
            $container.css({
                left: (win.width() - 300)/2,
                top: (win.height() - options.top)/2
            });
        }
        // 自适应窗口大小
        win.on('resize', resize);
        resize();

        $container.loading(options);
    };

    // 给元素附加加载状态
    $.fn.loading = function (opt) {
        if (opt === false) {
            return $(this).find(loading).remove();
        }

        opt = opt || {};
        opt.container = $(this);

        return new Loading(Dcat, opt);
    };

    // 按钮加载状态。
    // 宿主解析（统一三点动画语义，底层根治）：
    //  1) 自身带 .btn / .layui-layer-btn0 / .layui-layer-btn1 → 自身即宿主
    //     （Form 提交、Pjax、DialogForm 提交按钮等既有调用，行为不变）；
    //  2) 自身无按钮类但内部有 → 动画施加到内部第一个真实按钮（Modal 按钮
    //     wrapper .dcat-wmodal-toggle 场景）：三点动画出现在按钮中间且按钮
    //     盒保留（与 enableDialogCreate 原生表现一致），不再把整个按钮挪进
    //     隐藏 div（旧实现按钮消失、布局塌陷）；
    //  3) 内部也无按钮（纯文本/任意 HTML）→ 追加模式：原内容原样保留，仅
    //     末尾追加小号 spinner，加载完摘除——零布局塌陷。
    //  状态标记（data-loading）一律写在宿主上；start/stop 双向对齐解析，
    //  对外壳调用 stop 自动下钻（DialogForm 持有的是外壳引用）。
    function findHost($el) {
        let btnClass = ['btn', 'layui-layer-btn0', 'layui-layer-btn1'];

        for (let i in btnClass) {
            if ($el.hasClass(btnClass[i])) {
                return $el;
            }
        }

        let $inner = $el.find('.btn, .layui-layer-btn0, .layui-layer-btn1').first();

        return $inner.length ? $inner : null;
    }

    $.fn.buttonLoading = function (start) {
        this.each(function () {
            let $this = $(this);

            if (start === false) {
                let loadingId = $this.attr('data-loading');

                if (! loadingId) {
                    // 外壳调用对齐：start 时状态写在了内部真实按钮上
                    let $inner = findHost($this);

                    if ($inner && $inner[0] !== $this[0]) {
                        $inner.buttonLoading(false);
                    }

                    return;
                }

                $this.find('.waves-ripple').remove();

                let $saved = $this.find('.' + loadingId);

                $this.removeClass('disabled btn-loading waves-effect')
                    .removeAttr('disabled')
                    .removeAttr('data-loading');

                if ($saved.length) {
                    // 替换模式（按钮宿主）：还原原始内容
                    $this.html($saved.html());
                } else {
                    // 追加模式（纯文本宿主）：仅摘除追加的 spinner
                    $this.find('.btn-loading-append').remove();
                }

                return;
            }

            let $host = findHost($this),
                $target = $host || $this;

            // 加载中防重入（状态标记在宿主上，外壳重复调用同样命中）
            if ($target.attr('data-loading')) {
                return;
            }

            let content = $target.html();

            let loadingId = 'ld-' + Dcat.helpers.random();

            let loading = $host
                // 按钮宿主：三点脉动动画（50px×11px SVG），按钮盒保留
                ? LOADING_SVG[0].replace('{color}', 'currentColor').replace('{width}', '50px;height:11px;')
                // 纯文本宿主：小号 spinner（追加模式，原文不动）
                : '<span class="btn-loading-append spinner-grow spinner-grow-sm" role="status" aria-hidden="true"></span>';

            $target.addClass('disabled btn-loading')
                .attr('disabled', true)
                .attr('data-loading', loadingId);

            if ($host) {
                // 替换模式：原内容存隐藏 div，恢复时还原
                $target.html(`<div class="${loadingId}" style="display:none">${content}</div>${loading}`);
            } else {
                // 追加模式：原文保留，末尾追加
                $target.append(loading);
            }
        });

        return this;
    }

}

export default extend
