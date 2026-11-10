<?php

namespace Dcat\Admin\Http\Middleware;

use Dcat\Admin\Admin;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Widgets\DarkModeSwitcher;
use Illuminate\Http\Request;

class Bootstrap
{
    public function handle(Request $request, \Closure $next)
    {
        $this->includeBootstrapFile();
        $this->addScript();
        $this->fireEvents();
        $this->setUpDarkMode();

        $response = $next($request);

        $this->storeCurrentUrl($request);

        return $response;
    }

    protected function setUpDarkMode()
    {
        // if (
        //     config('admin.layout.dark_mode_switch')
        //     && ! Helper::isAjaxRequest()
        //     && ! request()->routeIs(admin_api_route_name('*'))
        // ) {
        //     Admin::navbar()->right((new DarkModeSwitcher())->render());
        // }
    }

    protected function includeBootstrapFile()
    {
        if (is_file($bootstrap = admin_path('bootstrap.php'))) {
            require $bootstrap;
        }
    }

    protected function addScript()
    {
        $token = csrf_token();
        Admin::script("Dcat.token = \"$token\";");

        $this->addStackedModalScript();
    }

    /**
     * Bootstrap 4.3.1 嵌套 modal（modal 内再开 modal，如异步表格弹窗里的表单弹窗）支持
     *
     * 修复五个问题（均已在真实项目实测确诊）：
     * 1. z-index 固定 1050/1040 撑不起多层 → 按打开层数递增，backdrop 跟随到本层之下
     * 2. 嵌套内层 fade 动画的 transition 完成回调不触发 → _isTransitioning 永久卡
     *    true，内层弹窗关不掉（内层 dialog transform 为 none，transitionend/shown 均不
     *    触发）→ 嵌套内层自动去 fade
     * 3. [data-dismiss] 委托绑在每个 modal 元素上，内层关闭按钮点击冒泡到外层会误关
     *    外层 → 内层截断冒泡
     * 4. 内层 modal 的 show/hidden 事件冒泡到外层 modal 元素，Modal 的 onShow 绑定
     *    （show 时清空 body 重新异步加载）被误触 → 外层 body 重载 → 刚打开的内层
     *    modal 被连同销毁，表现为"一闪就关" → 猴补 $.fn.on 给 modal 四件套事件的
     *    无 selector 直接绑定自动加 e.target !== e.currentTarget 守卫
     * 5. 内层 modal 嵌在外层 .modal-body 内，被外层 z-index 的 stacking context 困
     *    住——内层遮罩(body 下)盖住整个外层上下文连同内层弹窗（变暗+拦截点击）→
     *    show 时移到 body 下脱离外层上下文，正常 modal 打开时清理移出成孤儿的旧内层
     *
     * 另含健壮性处理：show/hidden 事件冒泡去重；关内层后恢复 body.modal-open（BS4
     * 无打开计数，会误删导致外层背后页面可滚动）；hide 兜底清理 transition 回调丢
     * 失时的 backdrop 残留
     *
     * window.__dcatStackedModalPatch 幂等：PJAX/异步响应重复输出脚本时不会重复绑定
     */
    protected function addStackedModalScript()
    {
        Admin::script(<<<'JS'
if (!window.__dcatStackedModalPatch) {
    window.__dcatStackedModalPatch = true;

    // 死因4：猴补 $.fn.on，modal 事件直接绑定只处理 modal 自己触发的事件
    // （注意 jQuery 参数归一：.on(types, fn) 的 handler 落在 selector 形参位）
    (function () {
        var origOn = $.fn.on, MODAL_EVENTS = /^(show|shown|hide|hidden)\.bs\.modal(\s|$)/;
        $.fn.on = function (types, selector, data, fn) {
            if (typeof types === 'string') {
                var handler = null;
                if (typeof selector === 'function') {          // .on(types, fn)
                    handler = selector;
                } else if (typeof data === 'function') {       // .on(types, selector, fn)
                    handler = data;
                } else if (typeof fn === 'function') {         // .on(types, selector, data, fn)
                    handler = fn;
                }
                if (handler && (typeof selector === 'function' || selector === undefined)
                    && MODAL_EVENTS.test(types)) {
                    var wrapped = function (e) {
                        if (e.target !== e.currentTarget) return;
                        return handler.apply(this, arguments);
                    };
                    return origOn.call(this, types, wrapped);
                }
            }
            return origOn.apply(this, arguments);
        };
    })();

    $(document)
        .on('show.bs.modal', '.modal', function (e) {
            if (e.target !== this) return;
            if ($(this).parent().closest('.modal').length) {
                // 死因5：移到 body 下，脱离外层 stacking context
                // data-nested-from 记录来源外层 id：弹窗表单提交成功后可只关内层并局部刷新外层
                $(this).attr('data-nested-moved', '1');
                $(this).attr('data-nested-from', $(this).parent().closest('.modal').attr('id') || '');
                document.body.appendChild(this);
                // 死因2：嵌套内层去 fade，规避 transition 回调卡死
                $(this).removeClass('fade');
                // 死因3：内层截断 data-dismiss 冒泡，防误关外层
                $(this).off('click.nestedModalDismiss')
                    .on('click.nestedModalDismiss', '[data-dismiss="modal"]', function (ev) {
                        ev.stopPropagation();
                    });
            } else if (!$(this).attr('data-nested-moved')) {
                // 正常 modal 打开时：清理移出且已成孤儿的内层 modal
                $('body > .modal[data-nested-moved]').remove();
            }
            // 死因1：z-index 递增，backdrop 跟随到本层之下
            var zIndex = 1040 + 20 * ($('.modal:visible').length + 1);
            $(this).css('z-index', zIndex);
            setTimeout(function () {
                $('.modal-backdrop').last().css('z-index', zIndex - 1);
            }, 0);
        })
        .on('hide.bs.modal', '.modal', function (e) {
            if (e.target !== this) return;
            // 兜底：transition 回调丢失时 BS 不清 backdrop 也不 trigger hidden，强制收尾
            setTimeout(function () {
                if (!$('.modal').hasClass('show') && $('.modal:visible').length === 0) {
                    $('.modal-backdrop').remove();
                    document.body.classList.remove('modal-open');
                    $(document.body).css('padding-right', '');
                }
            }, 800);
        })
        .on('hidden.bs.modal', '.modal', function (e) {
            if (e.target !== this) return;
            var $visible = $('.modal:visible');
            if ($visible.length) {
                // 关内层后仍有外层打开：恢复 modal-open 并对齐剩余 backdrop
                document.body.classList.add('modal-open');
                var topZ = Math.max.apply(null, $visible.map(function () {
                    return parseInt($(this).css('z-index')) || 1050;
                }).get());
                $('.modal-backdrop').last().css('z-index', topZ - 1);
            }
        });

    // 弹窗表单提交成功后 Dcat.reload() 触发 pjax 重载：页面级 modal 随容器替换等效关闭，
    // 但移出到 body 的嵌套内层 modal 不在 pjax 容器内会残留，这里统一清理
    $(document).on('pjax:complete', function () {
        $('body > .modal[data-nested-moved]').remove();
    });

    // 嵌套内层弹窗提交成功的智能刷新：拦截 Dcat.reload（表单/行操作 ->refresh() 指令的
    // 唯一出口），当前有嵌套内层弹窗打开时不整页刷新，改为只关内层并局部重载来源外层
    // 弹窗里的异步表格（async-table 触发 table:load）。单层弹窗与普通页面提交不做任何
    // 改动（透传原生整页刷新）；带 url 的定向跳转也透传
    (function () {
        var origReload = Dcat.reload.bind(Dcat);
        Dcat.reload = function (url) {
            var $inner = $('.modal[data-nested-moved]:visible').first();
            if (!$inner.length || url) {
                return origReload(url || null);
            }
            var fromId = $inner.attr('data-nested-from');
            $inner.modal('hide');
            setTimeout(function () { $inner.remove(); }, 300);
            var $outer = fromId ? $('#' + fromId) : $();
            var $table = $outer.length ? $outer.find('.async-table').first() : $();
            if ($table.length) {
                $table.trigger('table:load');
            } else {
                origReload(null); // 找不到外层/表格时兜底整页刷新
            }
        };
    })();
}
JS
        );
    }

    protected function fireEvents()
    {
        Admin::callBooting();

        Admin::callBooted();
    }

    /**
     * @param  \Illuminate\Http\Request
     * @return void
     */
    protected function storeCurrentUrl(Request $request)
    {
        if (
            $request->method() === 'GET'
            && $request->route()
            && ! Helper::isAjaxRequest()
            && ! $this->prefetch($request)
        ) {
            Admin::addIgnoreQueryName(['_token', '_pjax']);

            Helper::setPreviousUrl(
                Helper::fullUrlWithoutQuery(Admin::getIgnoreQueryNames())
            );
        }
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    public function prefetch($request)
    {
        if (method_exists($request, 'prefetch')) {
            return $request->prefetch();
        }

        return strcasecmp($request->server->get('HTTP_X_MOZ'), 'prefetch') === 0 ||
            strcasecmp($request->headers->get('Purpose'), 'prefetch') === 0;
    }
}
