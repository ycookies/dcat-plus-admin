<?php

namespace Dcat\Admin\Form\Concerns;

use Closure;
use Dcat\Admin\Form\Tab;

trait HasTabs
{
    /**
     * @var Tab
     */
    protected $tab = null;

    /**
     * Use tab to split form.
     *
     * $id 不传时自动生成跨渲染稳定的 id（浏览器刷新后可按 URL hash 定位回原 tab），
     * 需要语义化 hash 时可显式传入；$active 仅决定服务端初始渲染的激活 tab，
     * 有 hash 时前端会以其为准，无需为了刷新记忆而设置。
     *
     * @param  string  $title
     * @param  Closure  $content
     * @param  bool  $active
     * @param  string|null  $id
     * @return $this
     */
    public function tab($title, Closure $content, $active = false, ?string $id = null)
    {
        $this->getTab()->append($title, $content, $active, $id);

        return $this;
    }

    public function hasTab()
    {
        return $this->tab ? true : false;
    }

    /**
     * Get Tab instance.
     *
     * @return Tab
     */
    public function getTab()
    {
        if (is_null($this->tab)) {
            $this->tab = new Tab($this);
        }

        return $this->tab;
    }
}
