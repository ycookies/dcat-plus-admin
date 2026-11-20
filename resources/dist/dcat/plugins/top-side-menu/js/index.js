/**
 * 顶栏菜单交互逻辑（menu_style = top_side_menu：顶栏一级 + 左侧二级）
 * - 顶栏只展示 parent_id=0 的一级菜单，所有项均为纯切换链接（含无子级的一级，不导航）
 * - 点击顶栏项 → 切换左侧对应二级面板
 * - 左侧二级叶子点击 → 即时高亮 + 记录所属分组
 * - 每次 pjax 加载完成后按当前 pathname 重算激活状态（浏览器前进/后退同样正确）
 * - 激活态优先级：pathname 精确匹配（与 Menu::isActive 同源规则）> localStorage 记忆 > 第一组
 *
 * 本脚本位于 pjax 容器外（sidebar），每次整页加载只执行一次；
 * 事件绑定在持久 DOM（navbar/sidebar）上，跨 pjax 导航持续有效。
 */
Dcat.ready(function () {
    var $topBar = $('.ts-top-menu');
    var $panels = $('.ts-sidebar-panels');
    if (!$topBar.length || !$panels.length) return;

    var STORAGE_KEY = 'topSideMenuState';

    var $topItems = $topBar.find('.ts-top-item');

    // ========== 状态持久化（localStorage，two-col 同款容错写法） ==========
    function loadState() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
        } catch (e) {
            return {};
        }
    }

    function saveState(groupId) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify({groupId: groupId}));
        } catch (e) {}
    }

    // ========== 基础操作 ==========
    function $panelOf(id) {
        return $panels.find('.ts-panel[data-panel-id="' + id + '"]');
    }

    function activateGroup(id) {
        $topItems.removeClass('active');
        $topBar.find('.ts-top-item[data-id="' + id + '"]').addClass('active');

        $panels.find('.ts-panel').removeClass('active');
        $panelOf(id).addClass('active');
    }

    function activateLink($link) {
        $panels.find('a.nav-link').removeClass('active');
        $link.addClass('active');

        // 展开祖先节点
        $link.parents('li.has-treeview').addClass('menu-open');
    }

    // 链接 href → 站内路径（去协议/域名/查询串，trim 斜杠；javascript:、外链与 fragment 返回 ''）
    // 【注意】href="#" 的父项（partials.menu 的展开节点）在 DOM 内会解析成当前页路径，
    // 必须按 fragment 排除，否则每次都误命中所在面板
    function linkPath(href) {
        if (!href || href.charAt(0) === '#') return '';
        var a = document.createElement('a');
        a.href = href;
        if (a.protocol === 'javascript:') return '';

        return a.pathname.replace(/^\/+|\/+$/g, '');
    }

    // ========== 按当前 pathname 重算激活状态 ==========
    function syncByPath() {
        var current = location.pathname.replace(/^\/+|\/+$/g, '');
        var matchedLink = null;

        if (current) {
            // 左侧二级面板内的链接
            $panels.find('a.nav-link').each(function () {
                var p = linkPath(this.getAttribute('href'));
                if (!matchedLink && p && p === current) {
                    matchedLink = $(this);
                }
            });
        }

        if (matchedLink) {
            var groupId = matchedLink.closest('.ts-panel').data('panel-id');
            activateGroup(groupId);
            activateLink(matchedLink);
            saveState(groupId);
            return;
        }

        // 未命中（如工作台等不在菜单内的页面）：localStorage 记忆 > 第一组
        var state = loadState();
        if (state.groupId && $panelOf(state.groupId).length) {
            activateGroup(state.groupId);
        } else {
            var firstId = $topItems.first().data('id');
            if (firstId !== undefined) {
                activateGroup(firstId);
            }
        }
    }

    // ========== 顶栏点击：全部为纯切换（含无子级的一级，与 learnku 示例语义一致），不导航 ==========
    $topBar.on('click', 'a.ts-top-link', function (e) {
        var $link = $(this);

        e.preventDefault();

        var id = $link.data('id');
        if ($panelOf(id).length) {
            activateGroup(id);
            $panels.find('a.nav-link').removeClass('active');
            saveState(id);
        }
    });

    // ========== 左侧二级点击：即时高亮 + 记录分组（导航交给 pjax；父级折叠交给 AdminLTE treeview） ==========
    $panels.on('click', 'a.nav-link', function (e) {
        var href = this.getAttribute('href');
        if (!href || href === '#') return;

        var groupId = $(this).closest('.ts-panel').data('panel-id');
        activateLink($(this));
        saveState(groupId);
    });

    // ========== 初始 + 每次 pjax 完成后重算 ==========
    syncByPath();
    $(document).on('pjax:complete', function () {
        syncByPath();
    });
});
