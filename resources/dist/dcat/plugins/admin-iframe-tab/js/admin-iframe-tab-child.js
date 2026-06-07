(function (window) {
    'use strict';

    var initialized = false;

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

        initialized = true;

        var options = Object.assign({
            adminPrefix: '/admin',
            queryKey: 'iframe_tab',
            shellPath: '/admin/iframe-tabs',
            forceTopPathKeywords: []
        }, config || {});

        options.adminPrefix = normalizePath(options.adminPrefix || '/');
        options.shellPath = normalizePath(options.shellPath || '');

        function toUrl(href) {
            try {
                return new URL(href, window.location.origin);
            } catch (error) {
                return null;
            }
        }

        function isSameOrigin(url) {
            return url && url.origin === window.location.origin;
        }

        function isAdminUrl(url) {
            if (! isSameOrigin(url)) {
                return false;
            }

            if (options.adminPrefix === '/') {
                return true;
            }

            return url.pathname === options.adminPrefix || url.pathname.indexOf(options.adminPrefix + '/') === 0;
        }

        function shouldOpenTop(url) {
            var path = url.pathname.replace(/^\/+/, '');

            return options.forceTopPathKeywords.some(function (keyword) {
                return path.indexOf(keyword) !== -1;
            });
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

        function shouldDecorateLink(anchor) {
            var href = anchor.getAttribute('href') || '';
            var target = String(anchor.getAttribute('target') || '').toLowerCase();
            var url;

            if (! href || href.charAt(0) === '#' || /^javascript:/i.test(href)) {
                return false;
            }

            if (/^(mailto|tel):/i.test(href) || target === '_blank' || anchor.hasAttribute('download')) {
                return false;
            }

            url = toUrl(href);

            if (! isAdminUrl(url) || normalizePath(url.pathname) === options.shellPath) {
                return false;
            }

            return true;
        }

        document.addEventListener('click', function (event) {
            var anchor = event.target.closest && event.target.closest('a[href]');
            var href;
            var url;

            if (! anchor || ! shouldDecorateLink(anchor)) {
                return;
            }

            href = anchor.getAttribute('href');
            url = toUrl(href);

            if (shouldOpenTop(url)) {
                event.preventDefault();
                window.top.location.href = url.toString();

                return;
            }

            if (anchor.getAttribute('iframe-tab') === 'true' && window.parent.AdminIframeTabParent) {
                event.preventDefault();
                window.parent.AdminIframeTabParent.openTab(href, anchor.textContent.trim() || href);

                return;
            }

            anchor.setAttribute('href', withIframeParam(href));
        }, true);

        document.addEventListener('submit', function (event) {
            var form = event.target;
            var method = String(form.getAttribute('method') || 'get').toLowerCase();
            var action = form.getAttribute('action') || window.location.href;
            var url = toUrl(action);
            var input;

            if (method !== 'get' || ! isAdminUrl(url) || normalizePath(url.pathname) === options.shellPath) {
                return;
            }

            input = form.querySelector('input[name="' + options.queryKey + '"]');

            if (! input) {
                input = document.createElement('input');
                input.type = 'hidden';
                input.name = options.queryKey;
                form.appendChild(input);
            }

            input.value = '1';
        }, true);
    }

    window.AdminIframeTabChild = {
        init: init
    };
}(window));
