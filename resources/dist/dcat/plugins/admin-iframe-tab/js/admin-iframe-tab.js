(function (window) {
    'use strict';

    var initialized = false;

    function hash(value) {
        var result = 0;
        var text = String(value || '');

        for (var i = 0; i < text.length; i += 1) {
            result = ((result << 5) - result) + text.charCodeAt(i);
            result |= 0;
        }

        return 'tab-' + Math.abs(result);
    }

    function normalizePath(path) {
        path = String(path || '/');

        if (path.length > 1 && path.charAt(path.length - 1) === '/') {
            return path.slice(0, -1);
        }

        return path;
    }

    function init(config) {
        if (initialized) {
            return;
        }

        var $ = window.jQuery;
        if (!$) {
            window.setTimeout(function () {
                init(config);
            }, 50);

            return;
        }

        initialized = true;

        var options = $.extend({
            adminPrefix: '/admin',
            shellUrl: '/admin/iframe-tabs',
            homeUrl: '/admin',
            homeTitle: '首页',
            queryKey: 'iframe_tab',
            cache: false,
            lazyLoad: true,
            userId: 0,
            forceTopPathKeywords: []
        }, config || {});

        options.adminPrefix = normalizePath(options.adminPrefix || '/');

        var $tabs = $('#admin-iframe-tab-tabs');
        var $panels = $('#admin-iframe-tab-panels');
        var tabs = [];
        var activeId = null;
        var storageKey = 'admin-iframe-tab:' + options.userId + ':' + options.adminPrefix;

        function toUrl(href) {
            try {
                return new URL(href, window.location.origin);
            } catch (error) {
                return null;
            }
        }

        function withIframeParam(href) {
            var url = toUrl(href);

            if (! url) {
                return href;
            }

            url.searchParams.set(options.queryKey, '1');
            url.searchParams.delete('_pjax');

            return url.toString();
        }

        function withoutIframeParam(href) {
            var url = toUrl(href);

            if (! url) {
                return href;
            }

            url.searchParams.delete(options.queryKey);
            url.searchParams.delete('_pjax');

            return url.toString();
        }

        function isSameOrigin(url) {
            return url && url.origin === window.location.origin;
        }

        function isAdminUrl(url) {
            if (! isSameOrigin(url)) {
                return false;
            }

            var prefix = options.adminPrefix;

            if (prefix === '/') {
                return true;
            }

            return url.pathname === prefix || url.pathname.indexOf(prefix + '/') === 0;
        }

        function isShellUrl(url) {
            var shellUrl = toUrl(options.shellUrl);

            return shellUrl && url.pathname === shellUrl.pathname;
        }

        function shouldOpenTop(url) {
            var path = url.pathname.replace(/^\/+/, '');

            return options.forceTopPathKeywords.some(function (keyword) {
                return path.indexOf(keyword) !== -1;
            });
        }

        function isTabbableLink(anchor) {
            var $anchor = $(anchor);
            var href = $anchor.attr('href');
            var target = String($anchor.attr('target') || '').toLowerCase();

            if (isMenuBranchLink(anchor)) {
                return false;
            }

            if (! href || href.charAt(0) === '#' || /^javascript:/i.test(href)) {
                return false;
            }

            if (/^(mailto|tel):/i.test(href) || target === '_blank' || $anchor.attr('download')) {
                return false;
            }

            var url = toUrl(href);

            if (! isAdminUrl(url) || isShellUrl(url) || shouldOpenTop(url)) {
                return false;
            }

            return true;
        }

        function isMenuBranchLink(anchor) {
            var $item = $(anchor).closest('li');

            if (! $item.length) {
                return false;
            }

            // Dcat 的多级菜单依赖父级 <a> 的 click 来展开/收起；这类分支菜单不能被 iframe tab 抢先拦截。
            return $item.children('ul, .dropdown-menu, .nav-treeview, .treeview-menu').length > 0
                || $item.hasClass('has-treeview')
                || $item.hasClass('dropdown')
                || $item.hasClass('dropdown-submenu')
                || $(anchor).attr('data-toggle') === 'dropdown'
                || $(anchor).attr('aria-expanded') !== undefined;
        }

        function titleFromAnchor(anchor) {
            var $anchor = $(anchor);
            var title = $anchor.attr('title') || $anchor.find('p').first().text() || $anchor.text();

            return $.trim(title).replace(/\s+/g, ' ') || options.homeTitle;
        }

        function tabIdFor(url) {
            return hash(withoutIframeParam(url));
        }

        function findTab(id) {
            return tabs.find(function (tab) {
                return tab.id === id;
            });
        }

        function saveTabs() {
            if (! options.cache) {
                return;
            }

            try {
                window.localStorage.setItem(storageKey, JSON.stringify({
                    activeId: activeId,
                    tabs: tabs
                }));
            } catch (error) {
                // localStorage 在隐私模式可能不可用，标签页仍保持当前会话可用。
            }
        }

        function restoreTabs() {
            if (! options.cache) {
                return false;
            }

            try {
                var raw = window.localStorage.getItem(storageKey);
                var payload = raw ? JSON.parse(raw) : null;

                if (! payload || ! Array.isArray(payload.tabs) || payload.tabs.length === 0) {
                    return false;
                }

                payload.tabs.forEach(function (tab) {
                    createTab(tab.title, tab.url, {
                        closeable: tab.closeable !== false,
                        activate: false
                    });
                });

                activateTab(payload.activeId || tabs[0].id);

                return true;
            } catch (error) {
                return false;
            }
        }

        var loadingHtml = [
            '<div class="admin-iframe-tab-loading">',
            '  <div class="admin-iframe-tab-loader">',
            '    <svg viewBox="0 0 80 80" xmlns="http://www.w3.org/2000/svg">',
            '      <defs>',
            '        <linearGradient id="admin-iframe-tab-grad" x1="0%" y1="0%" x2="100%" y2="100%">',
            '          <stop offset="0%" style="stop-color:#4966d6"/>',
            '          <stop offset="100%" style="stop-color:#b478ff"/>',
            '        </linearGradient>',
            '      </defs>',
            '      <circle class="track" cx="40" cy="40" r="36"/>',
            '      <circle class="arc" cx="40" cy="40" r="36"/>',
            '    </svg>',
            '    <img class="admin-iframe-tab-loader-logo" src="/vendor/dcat-admin/images/logo.png" alt="">',
            '  </div>',
            '</div>'
        ].join('');

        function showLoading($panel) {
            $panel.find('.admin-iframe-tab-loading').remove();
            $panel.append(loadingHtml);
        }

        function hideLoading($panel) {
            var $loading = $panel.find('.admin-iframe-tab-loading');
            if ($loading.length) {
                $loading.addClass('is-hidden');
                setTimeout(function () { $loading.remove(); }, 300);
            }
        }

        function bindFrameLoad(iframe, $panel) {
            $(iframe).on('load.iframeTab', function () {
                hideLoading($panel);

                var frame = this;
                var id = $panel.data('tab-id');

                try {
                    var frameUrl = frame.contentWindow.location.href;

                    if (shouldOpenTop(toUrl(frameUrl))) {
                        window.location.href = withoutIframeParam(frameUrl);
                        return;
                    }

                    var tab = findTab(id);
                    if (tab && tab.closeable) {
                        var frameTitle = frame.contentDocument.querySelector('.content-header h1 span');
                        if (frameTitle && $.trim(frameTitle.textContent)) {
                            updateTabTitle(id, frameTitle.textContent);
                        }
                    }
                } catch (error) {
                    // 跨源页面无法读取标题
                }
            });
        }

        function ensureFrame(tab) {
            var $panel = $('#' + tab.id + '-panel');
            var $frame = $panel.find('iframe');

            if ($frame.length) {
                return $frame;
            }

            showLoading($panel);

            var iframe = document.createElement('iframe');
            iframe.className = 'admin-iframe-tab-frame';
            iframe.title = tab.title || '';

            bindFrameLoad(iframe, $panel);

            $panel.empty().append(iframe);

            iframe.src = tab.url;

            return $(iframe);
        }

        function createTab(title, href, params) {
            params = $.extend({
                closeable: true,
                activate: true
            }, params || {});

            var url = withIframeParam(href);
            var id = tabIdFor(url);
            var existing = findTab(id);

            if (existing) {
                if (params.activate) {
                    activateTab(id);
                }

                return existing;
            }

            var tab = {
                id: id,
                title: title || options.homeTitle,
                url: url,
                closeable: params.closeable !== false
            };

            tabs.push(tab);

            var $tab = $('<button>', {
                type: 'button',
                class: 'admin-iframe-tab-item',
                id: id + '-tab',
                'data-tab-id': id,
                title: tab.title
            });

            $tab.append($('<span>', {
                class: 'admin-iframe-tab-title',
                text: tab.title
            }));

            if (tab.closeable) {
                $tab.append($('<span>', {
                    class: 'admin-iframe-tab-close',
                    title: '关闭'
                }).append($('<i>', {class: 'feather icon-x'})));
            }

            var $panel = $('<div>', {
                class: 'admin-iframe-tab-panel',
                id: id + '-panel',
                'data-tab-id': id
            });

            if (! options.lazyLoad || params.activate) {
                showLoading($panel);

                var iframe = document.createElement('iframe');
                iframe.className = 'admin-iframe-tab-frame';
                iframe.title = tab.title || '';

                bindFrameLoad(iframe, $panel);

                $panel.append(iframe);

                iframe.src = tab.url;
            } else {
                $panel.append($('<div>', {
                    class: 'admin-iframe-tab-empty',
                    text: '准备加载'
                }));
            }

            $tabs.append($tab);
            $panels.append($panel);

            if (params.activate) {
                activateTab(id);
            }

            saveTabs();

            return tab;
        }

        function activateTab(id) {
            var tab = findTab(id) || tabs[0];

            if (! tab) {
                return;
            }

            activeId = tab.id;

            $('.admin-iframe-tab-item').removeClass('active');
            $('.admin-iframe-tab-panel').removeClass('active');
            $('#' + tab.id + '-tab').addClass('active');
            $('#' + tab.id + '-panel').addClass('active');

            ensureFrame(tab);
            syncActiveMenu(tab.url);
            saveTabs();
        }

        function closeTab(id) {
            var tab = findTab(id);

            if (! tab || ! tab.closeable) {
                return;
            }

            var index = tabs.indexOf(tab);
            tabs.splice(index, 1);
            $('#' + tab.id + '-tab').remove();
            $('#' + tab.id + '-panel').remove();

            if (activeId === tab.id) {
                var next = tabs[index] || tabs[index - 1] || tabs[0];

                if (next) {
                    activateTab(next.id);
                }
            }

            saveTabs();
        }

        function closeOtherTabs() {
            tabs.slice().forEach(function (tab) {
                if (tab.id !== activeId && tab.closeable) {
                    closeTab(tab.id);
                }
            });
        }

        function closeAllTabs() {
            tabs.slice().forEach(function (tab) {
                if (tab.closeable) {
                    closeTab(tab.id);
                }
            });
        }

        function reloadActiveTab() {
            var tab = findTab(activeId);

            if (! tab) {
                return;
            }

            var $panel = $('#' + tab.id + '-panel');
            var $frame = $panel.find('iframe');

            if (! $frame.length) {
                ensureFrame(tab);
                return;
            }

            showLoading($panel);
            $frame.off('load.iframeTab');
            bindFrameLoad($frame[0], $panel);
            $frame.attr('src', $frame.attr('src'));
        }

        function openActiveInNewWindow() {
            var tab = findTab(activeId);

            if (tab) {
                window.open(withoutIframeParam(tab.url), '_blank');
            }
        }

        function updateTabTitle(id, title) {
            var tab = findTab(id);
            title = $.trim(title || '');

            if (! tab || ! title || title === tab.title) {
                return;
            }

            tab.title = title;
            $('#' + id + '-tab').attr('title', title).find('.admin-iframe-tab-title').text(title);
            saveTabs();
        }

        function syncActiveMenu(href) {
            var url = toUrl(href);

            if (! url) {
                return;
            }

            var targetPath = normalizePath(url.pathname);
            var $menu = $('.main-sidebar .nav-sidebar, .horizontal-menu .main-menu-content');

            if (! $menu.length) {
                return;
            }

            $menu.find('.nav-link.active').removeClass('active');
            $menu.find('.menu-open, .open').removeClass('menu-open open');

            var $active = $();

            $menu.find('a[href]').each(function () {
                var linkUrl = toUrl($(this).attr('href'));

                if (linkUrl && normalizePath(linkUrl.pathname) === targetPath) {
                    $active = $(this);

                    return false;
                }
            });

            if (! $active.length) {
                return;
            }

            $active.addClass('active');
            $active.parents('.has-treeview, .dropdown').addClass('menu-open open');
            $active.parents('.has-treeview, .dropdown').children('a').addClass('active');
            $active.parents('.dropdown-submenu').find('.nav-link').eq(0).addClass('active');
        }

        function bindEvents() {
            document.addEventListener('click', function (event) {
                var anchor = event.target.closest && event.target.closest('a[href]');

                if (! anchor || ! $(anchor).closest('.main-sidebar, .main-menu-content, .header-navbar').length) {
                    return;
                }

                if (! isTabbableLink(anchor)) {
                    return;
                }

                event.preventDefault();
                event.stopPropagation();

                if (event.stopImmediatePropagation) {
                    event.stopImmediatePropagation();
                }

                createTab(titleFromAnchor(anchor), $(anchor).attr('href'), {activate: true});
            }, true);

            $(document).on('click.adminIframeTab', '.admin-iframe-tab-item', function (event) {
                if ($(event.target).closest('.admin-iframe-tab-close').length) {
                    return;
                }

                activateTab($(this).data('tab-id'));
            });

            $(document).on('click.adminIframeTab', '.admin-iframe-tab-close', function (event) {
                event.preventDefault();
                event.stopPropagation();
                closeTab($(this).closest('.admin-iframe-tab-item').data('tab-id'));
            });

            $(document).on('dblclick.adminIframeTab', '.admin-iframe-tab-item', function () {
                closeTab($(this).data('tab-id'));
            });

            // 保留冒泡阶段兜底；真正防止 Dcat PJAX 抢先跳转的是上面的原生捕获监听。
            $(document).on('click.adminIframeTab', '.main-sidebar a[href], .main-menu-content a[href], .header-navbar a[href]', function (event) {
                if (! isTabbableLink(this)) {
                    return;
                }

                event.preventDefault();
                createTab(titleFromAnchor(this), $(this).attr('href'), {activate: true});
            });

            $(document).on('click.adminIframeTab', '[data-iframe-tab-action]', function () {
                var action = $(this).data('iframe-tab-action');

                if (action === 'reload') {
                    reloadActiveTab();
                } else if (action === 'close-other') {
                    closeOtherTabs();
                } else if (action === 'close-all') {
                    closeAllTabs();
                } else if (action === 'open-new') {
                    openActiveInNewWindow();
                }
            });

        }

        function boot() {
            try {
                bindEvents();

                if (! restoreTabs()) {
                    createTab(options.homeTitle, options.homeUrl, {
                        closeable: false,
                        activate: true
                    });
                }

                window.AdminIframeTabParent = {
                    openTab: function (href, title) {
                        return createTab(title || href, href, {activate: true});
                    },
                    closeActive: function () {
                        closeTab(activeId);
                    },
                    reloadActive: reloadActiveTab
                };
            } catch (error) {
                initialized = false;
                window.AdminIframeTabLastError = error;

                if (window.console && window.console.error) {
                    window.console.error('[AdminIframeTab] 初始化失败', error);
                }
            }
        }

        boot();
    }

    window.AdminIframeTab = {
        init: init
    };
}(window));
