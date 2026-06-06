/**
 * 双栏菜单交互逻辑
 * - 左栏图标切换右栏面板
 * - 子菜单展开/折叠
 * - 刷新后自动恢复选中状态
 */
Dcat.ready(function () {
    var $wrapper = $('.two-col-menu-wrapper');
    if (!$wrapper.length) return;

    var $iconLinks = $wrapper.find('.icon-nav-link');
    var $panels = $wrapper.find('.detail-panel');
    var $detailLinks = $wrapper.find('.detail-nav-link');
    var $hasChildren = $wrapper.find('.has-children');

    // ========== 左栏图标点击 → 切换右栏面板 ==========
    $iconLinks.on('click', function () {
        var $this = $(this);
        var id = $this.data('id');

        // 高亮当前图标
        $iconLinks.removeClass('active');
        $this.addClass('active');

        // 显示对应面板
        $panels.removeClass('active');
        var $targetPanel = $wrapper.find('.detail-panel[data-panel-id="' + id + '"]');
        $targetPanel.addClass('active');

        // 保存选中状态
        saveMenuState(id, null);
    });

    // ========== 右栏子菜单点击 ==========
    $detailLinks.on('click', function (e) {
        var $this = $(this);
        var $parent = $this.closest('.detail-nav-item');

        // 如果是有子菜单的项，切换展开/折叠
        if ($parent.hasClass('has-children')) {
            e.preventDefault();
            $parent.toggleClass('menu-open');
            return;
        }

        // 普通菜单项点击 → 高亮
        $wrapper.find('.detail-nav-link').removeClass('active');
        $this.addClass('active');

        // 保存选中状态
        var panelId = $this.closest('.detail-panel').data('panel-id');
        var itemId = $this.data('id') || '';
        saveMenuState(panelId, itemId);
    });

    // ========== 自动恢复选中状态 ==========
    function autoActive() {
        // 优先检查服务端渲染的 active 状态
        var $serverActive = $iconLinks.filter('.active');
        if ($serverActive.length) {
            var activeId = $serverActive.data('id');
            $panels.removeClass('active');
            $wrapper.find('.detail-panel[data-panel-id="' + activeId + '"]').addClass('active');
            return;
        }

        // 回退到 localStorage
        var state = loadMenuState();
        if (!state || !state.parentId) return;

        // 激活图标
        $iconLinks.removeClass('active');
        var $targetIcon = $wrapper.find('.icon-nav-link[data-id="' + state.parentId + '"]');
        $targetIcon.addClass('active');

        // 显示面板
        $panels.removeClass('active');
        $wrapper.find('.detail-panel[data-panel-id="' + state.parentId + '"]').addClass('active');

        // 激活子菜单项
        if (state.subId) {
            var $targetLink = $wrapper.find('.detail-nav-link[data-id="' + state.subId + '"]');
            if ($targetLink.length) {
                $wrapper.find('.detail-nav-link').removeClass('active');
                $targetLink.addClass('active');

                // 展开父级
                $targetLink.parents('.has-children').addClass('menu-open');
            }
        }
    }

    // ========== 状态持久化 ==========
    function saveMenuState(parentId, subId) {
        try {
            localStorage.setItem('twoColMenuState', JSON.stringify({
                parentId: parentId,
                subId: subId
            }));
        } catch (e) {}
    }

    function loadMenuState() {
        try {
            return JSON.parse(localStorage.getItem('twoColMenuState'));
        } catch (e) {
            return null;
        }
    }

    // 初始化
    autoActive();
});