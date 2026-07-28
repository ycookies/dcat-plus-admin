<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Admin;
use Illuminate\Contracts\Support\Renderable;

class Tab extends Widget
{
    const TYPE_CONTENT = 1;
    const TYPE_LINK = 2;

    /**
     * @var string
     */
    protected $view = 'admin::widgets.tab';

    /**
     * 预设给下一个 add()/addLink() 的 tab 项 id（由 tabId() 设置，add 时消费并清空）。
     * 注意：这是单个 tab 项的 id，不要和 id()（容器外层 DOM id）混淆。
     *
     * @var string|int|null
     */
    protected $nextTabId;

    /**
     * @var array
     */
    protected $data = [
        'id'       => '',
        'title'    => '',
        'tabs'     => [],
        'dropDown' => [],
        'active'   => 0,
        'padding'  => null,
        'tabStyle' => '',
        'icon' => '',
    ];

    /**
     * 预设下一个 add()/addLink() 的 tab 项 id（链式调用，与 add() 的 $active 解耦）。
     *
     * 用法：$tab->tabId('all')->add('全员员工', $view);
     * 只作用于紧随其后的那一次 add，用完即清空。
     * 注意：这是给单个 tab 项设 id；设置/读取容器外层 DOM id 请用 id()。
     *
     * @param  string|int|null  $id  传 null 清除预设
     * @return $this
     */
    public function tabId($id = null)
    {
        $this->nextTabId = $id;

        return $this;
    }

    /**
     * Add a tab and its contents.
     *
     * @param  string  $title
     * @param  string|Renderable  $content
     * @param  bool  $active
     * @param  string|null  $id
     * @return $this
     */
    public function add($title, $content, $active = false, $id = null)
    {
        $index = count($this->data['tabs']);

        // tab 项 id 优先级：tabId() 链式预设 > add() 第4参数 > 序号
        $tabId = $this->nextTabId !== null ? $this->nextTabId : ($id !== null ? $id : $index);
        $this->nextTabId = null;

        $this->data['tabs'][] = [
            'id'      => $tabId,
            'title'   => $title,
            'content' => $this->toString($this->formatRenderable($content)),
            'type'    => static::TYPE_CONTENT,
        ];

        if ($active) {
            $this->data['active'] = $index;
        }

        return $this;
    }

    public function icon($icon)
    {
        $last = count($this->data['tabs']) - 1;
        if ($last >= 0) {
            $this->data['tabs'][$last]['icon'] = $icon;
        }

        return $this;
    }

    /**
     * Add a link on tab.
     *
     * @param  string  $title
     * @param  string  $href
     * @param  bool  $active
     * @return $this
     */
    public function addLink($title, $href, $active = false)
    {
        $index = count($this->data['tabs']);

        $tabId = $this->nextTabId !== null ? $this->nextTabId : $index;
        $this->nextTabId = null;

        $this->data['tabs'][] = [
            'id'      => $tabId,
            'title'   => $title,
            'href'    => $href,
            'type'    => static::TYPE_LINK,
        ];

        if ($active) {
            $this->data['active'] = $index;
        }

        return $this;
    }

    /**
     * Set tab content padding.
     *
     * @param  string  $padding
     */
    public function padding(string $padding)
    {
        $this->data['padding'] = 'padding:'.$padding;

        return $this;
    }

    public function noPadding()
    {
        return $this->padding('0');
    }

    /**
     * Set title.
     *
     * @param  string  $title
     */
    public function title($title = '')
    {
        $this->data['title'] = $title;

        return $this;
    }

    /**
     * Set drop-down items.
     *
     * @param  array  $links
     * @return $this
     */
    public function dropdown(array $links)
    {
        if (is_array($links[0])) {
            foreach ($links as $link) {
                call_user_func([$this, 'dropDown'], $link);
            }

            return $this;
        }

        $this->data['dropDown'][] = [
            'name' => $links[0],
            'href' => $links[1],
        ];

        return $this;
    }

    public function withCard()
    {
        return $this
            ->class('card', true)
            ->style('padding:.25rem .4rem .4rem');
    }

    public function vertical()
    {
        return $this
            ->class('nav-vertical d-block', true)
            ->style('padding:0!important;')
            ->tabStyle('nav-left flex-column');
    }

    public function theme(string $style = 'primary')
    {
        return $this
            ->class('nav-theme-'.$style, true)
            ->style('padding:0!important;');
    }

    public function tabStyle($type)
    {
        $this->data['tabStyle'] = $type;

        return $this;
    }

    /**
     * Render Tab.
     *
     * @return string
     */
    public function render()
    {
        // 容器 scope id：用于前端按容器隔离（多 Tab 互不影响）。调用方未设则自动生成。
        $scope = $this->getHtmlAttribute('id');
        if (! $scope) {
            $scope = 'tab-'.uniqid();
            $this->defaultHtmlAttribute('id', $scope);
        }

        $data = array_merge(
            $this->data,
            [
                'attributes' => $this->formatHtmlAttributes(),
                'scope'      => $scope,
            ]
        );

        $this->setupScript($scope);

        return view($this->view, $data)->render();
    }

    /**
     * Setup script.
     *
     * 按容器 scope 隔离，解决同页多个 Tab 互相干扰的问题；
     * 监听 pjax:loaded，在 Grid 翻页/筛选/排序后仍能根据 hash 恢复当前 Tab；
     * 切换 Tab 用 replaceState 更新 hash（避免与 PJAX 历史冲突）。
     *
     * tab 的 DOM id 直接用 tab_{id}（如 id('all') → tab_all），hash 刷新后稳定；
     * 多容器隔离靠"hash 归属判断"——只激活属于本容器 hashes 列表的 hash。
     *
     * @param  string  $scope
     */
    protected function setupScript($scope)
    {
        // 本容器下所有内容型 tab 的 hash 列表，用于判断地址栏 hash 是否归属本容器
        $hashes = [];
        foreach ($this->data['tabs'] as $tab) {
            if (($tab['type'] ?? static::TYPE_CONTENT) === static::TYPE_CONTENT) {
                $hashes[] = '#tab_'.$tab['id'];
            }
        }
        $hashesJson = json_encode($hashes);

        $script = <<<SCRIPT
(function () {
    var scope = '{$scope}',
        hashes = {$hashesJson},
        storeKey = 'dcat-tab-hash:' + scope,
        \$box = $('#' + scope);

    if (! \$box.length) return;

    // 根据地址栏 hash 激活本容器对应的 tab（hash 不归属本容器则不动，保证多 Tab 互不影响）
    function syncFromHash() {
        var hash = document.location.hash;
        if (hash && hashes.indexOf(hash) !== -1) {
            \$box.find('.nav-tabs a[href="' + hash + '"]').tab('show');
        }
    }

    // 切换 tab 时只更新 hash（replaceState 不污染浏览历史），并记忆到 sessionStorage
    \$box.find('.nav-tabs a[data-toggle="tab"]').off('shown.bs.tab.scope').on('shown.bs.tab.scope', function (e) {
        if (e.target && e.target.hash && hashes.indexOf(e.target.hash) !== -1) {
            history.replaceState(null, null, e.target.hash);
            try { sessionStorage.setItem(storeKey, e.target.hash); } catch (e) {}
        }
    });

    syncFromHash();

    // ---- Grid 翻页/筛选/排序走 PJAX，jquery-pjax 会用翻页链接的 URL（?page=2）覆盖
    // 地址栏，丢掉 #tab_x。这里在请求发出前记住当前 hash，完成后补回并激活。----
    \$(document).off('pjax:send.tab-' + scope).on('pjax:send.tab-' + scope, function () {
        var h = document.location.hash;
        if (h && hashes.indexOf(h) !== -1) {
            try { sessionStorage.setItem(storeKey, h); } catch (e) {}
        }
    });

    \$(document).off('pjax:loaded.tab-' + scope).on('pjax:loaded.tab-' + scope, function () {
        var remembered = null;
        try { remembered = sessionStorage.getItem(storeKey); } catch (e) {}
        // PJAX 用 fragment:'body' 重建了 DOM，这里重新选取容器，避免用到旧节点引用
        var \$fresh = \$('#' + scope);
        if (! \$fresh.length) return;
        // 地址栏还有有效 hash 则直接用；否则用记忆的 hash 补回地址栏并激活
        if (document.location.hash && hashes.indexOf(document.location.hash) !== -1) {
            \$fresh.find('.nav-tabs a[href="' + document.location.hash + '"]').tab('show');
        } else if (remembered && hashes.indexOf(remembered) !== -1) {
            history.replaceState(null, null, remembered);
            \$fresh.find('.nav-tabs a[href="' + remembered + '"]').tab('show');
        }
    });
})();
SCRIPT;
        Admin::script($script);
    }
}
