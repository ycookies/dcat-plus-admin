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
     * 异步表格内的弹窗表单（dialog create / quick edit 共用资源表单）保存成功后，
     * 响应携带 then:{action:'location',value:'资源列表页'}，handleJsonResponse
     * 会在 1 秒后整页跳转把用户拽离当前页 → 记录 DialogForm 来源（是否
     * async-table 内），命中的 location 指令改写为 refresh，复用 Dcat.reload
     * 拦截链（嵌套=关内层刷新外层异步表格；非嵌套=pjax 刷新当前页）。
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
    // 但移出到 body 的嵌套内层 modal 不在 pjax 容器内会残留，这里统一清理。
    // 同时清扫 layer 弹层的残留遮罩（viewOnly 弹窗 × 关闭是 hide 非 destroy，
    // 某些环境（iframe-tabs 顶层窗口等）会留下遮挡全页的遮罩，导致网站不可用）
    $(document).on('pjax:complete', function () {
        $('body > .modal[data-nested-moved]').remove();
        if (window.__dcatSweepLayerShades) {
            window.__dcatSweepLayerShades();
        }
    });

    // 全局清扫 layer 残留遮罩：遮罩对应的 layer 已关闭或隐藏时移除遮罩。
    // 同时处理顶层窗口（iframe-tabs 模式 layer 会开到 top）。
    window.__dcatSweepLayerShades = function () {
        var sweep = function (doc) {
            try {
                var shades = doc.querySelectorAll('[id^="layui-layer-shade"]');
                for (var i = 0; i < shades.length; i++) {
                    var idx = shades[i].id.replace('layui-layer-shade', '');
                    var layer = doc.getElementById('layui-layer' + idx);
                    var gone = ! layer || getComputedStyle(layer).display === 'none';
                    if (gone && shades[i].parentNode) {
                        shades[i].parentNode.removeChild(shades[i]);
                    }
                }
            } catch (e) {}
        };
        sweep(document);
        try {
            if (window.top && top.document && top.document !== document) {
                sweep(top.document);
            }
        } catch (e) {}
    };

    // 智能刷新拦截链（Dcat.reload 是表单/行操作 ->refresh() 与 grid 行操作
    // 删除/批量删除完成后的唯一出口）：
    // 1. 当前有嵌套内层弹窗打开时不整页刷新，改为只关内层并局部重载来源外层
    //    弹窗里的异步表格（async-table 触发 table:load）；
    // 2. 可见 modal 内含 async-table（dialog create / quick edit 保存后的刷新）
    //    → 只触发表格重载，弹窗保持打开，不整页刷新；
    // 3. 带 url 的定向重载（grid 行删除/批量删除完成后携带 data-redirect），
    //    若最近一次点击发生在 async-table 内（删除确认框 swal 中的点击延续
    //    上下文）→ 改写为该表格原地重载，不跳转到 data-redirect 指向的资源页；
    //    其余场景透传原生行为。
    // async-table 内点击由 document 捕获阶段监听记录（capture 不受业务
    // stopPropagation 影响）；上下文为点击位引用，无时间窗。
    (function () {
        var origReload = Dcat.reload.bind(Dcat);
        var asyncCtx = null;

        document.addEventListener('click', function (e) {
            if (! e.target || ! e.target.closest) {
                return;
            }
            // swal 中的点击（删除确认的"确认"）延续既有上下文，不更新也不清除
            if (e.target.closest('.swal2-container')) {
                return;
            }
            asyncCtx = e.target.closest('.async-table') || null;
        }, true);

        Dcat.reload = function (url) {
            // 带 url 的定向重载来自异步表格行删除/批量删除 → 表格原地重载
            if (url && asyncCtx && asyncCtx.isConnected) {
                var $ctxTable = jQuery(asyncCtx);
                asyncCtx = null;
                $ctxTable.trigger('table:load');
                return;
            }
            if (url) {
                return origReload(url);
            }
            // 嵌套内层：关内层 + 刷新来源外层的异步表格
            var $inner = $('.modal[data-nested-moved]:visible').first();
            if ($inner.length) {
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
                return;
            }
            // 异步表格弹窗：modal 内含 async-table（dialog create / quick edit
            // 保存后的刷新）→ 只触发表格重载，弹窗保持打开，不整页刷新
            var $modalTable = $('.modal.show').find('.async-table').first();
            if ($modalTable.length) {
                $modalTable.trigger('table:load');
                return;
            }
            return origReload(null);
        };
    })();

    // 异步表格内弹窗表单（dialog create / quick edit）保存后不跳转、只刷新：
    // 两者都走 Form::dialog（Dcat.DialogForm）加载资源表单，提交成功后响应携带
    // then:{action:'location',value:'资源列表页'}，handleJsonResponse 在 1 秒后
    // 整页跳转——绕过上面的 Dcat.reload 拦截链。这里在打开弹窗时记录来源是否
    // async-table，命中时把 location 指令改写为 refresh，回流到拦截链：
    // 嵌套=关内层并刷新外层异步表格；非嵌套（expand/独立异步表格）=pjax 刷新当前页。
    // window.__dcatDialogFormAsyncPatch 幂等；改写只对 10 秒内从 async-table
    // 打开的弹窗生效，页面级弹窗与普通表单的 location 跳转不受影响
    (function () {
        if (window.__dcatDialogFormAsyncPatch) {
            return;
        }
        window.__dcatDialogFormAsyncPatch = true;

        var flag = '__dcatFromAsyncTable';

        if (Dcat.DialogForm) {
            var origDialogForm = Dcat.DialogForm;
            Dcat.DialogForm = function (opts) {
                var inst = origDialogForm.apply(this, arguments);
                if (inst && inst.submit && !inst.__dcatAsyncFlag) {
                    inst.__dcatAsyncFlag = true;
                    var origSubmit = inst.submit.bind(inst);
                    inst.submit = function () {
                        // 记录触发按钮是否在异步表格内（展开行/独立异步表格同样命中）
                        window[flag] = inst.$target && inst.$target.closest('.async-table').length
                            ? Date.now() : 0;
                        return origSubmit();
                    };
                }
                return inst;
            };
        }

        if (Dcat.handleJsonResponse) {
            var origHandle = Dcat.handleJsonResponse.bind(Dcat);
            Dcat.handleJsonResponse = function (response) {
                try {
                    var recent = window[flag] && Date.now() - window[flag] < 10000;
                    var then = response && response.data && response.data.then;
                    if (recent && then && then.action === 'location') {
                        response.data.then = { action: 'refresh' };
                    }
                } catch (e) {}
                return origHandle(response);
            };
        }
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
