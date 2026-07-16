<?php

namespace Dcat\Admin\Models;

use Dcat\Admin\Admin;
use Illuminate\Support\Facades\Cache;

trait MenuCache
{
    protected $cacheKey = 'dcat-admin-menus-%d-%s';

    /**
     * Get an item from the cache, or execute the given Closure and store the result.
     *
     * @param  \Closure  $builder
     * @return mixed
     */
    protected function remember(\Closure $builder)
    {
        if (! $this->enableCache()) {
            return $builder();
        }

        return $this->getStore()->remember($this->getCacheKey(), null, $builder);
    }

    /**
     * @return bool|void
     */
    public function flushCache(?string $app = null)
    {
        if (! $this->enableCache()) {
            return;
        }

        return $this->getStore()->delete($this->getCacheKey($app));
    }

    /**
     * Flush every enabled application key when applications share menu tables.
     */
    public function flushAllCache()
    {
        if (! $this->enableCache()) {
            return;
        }

        $apps = array_unique(array_merge(
            [Admin::app()->getName(), \Dcat\Admin\Application::DEFAULT],
            array_keys(Admin::app()->getEnabledApps())
        ));

        foreach ($apps as $app) {
            // Different applications may use different bind_permission values
            // while sharing the same menu table, so clear both cache variants.
            $this->getStore()->delete(sprintf($this->cacheKey, 0, $app));
            $this->getStore()->delete(sprintf($this->cacheKey, 1, $app));
        }
    }

    /**
     * @return string
     */
    protected function getCacheKey(?string $app = null)
    {
        return sprintf($this->cacheKey, (int) static::withPermission(), $app ?: Admin::app()->getName());
    }

    /**
     * @return bool
     */
    public function enableCache()
    {
        return config('admin.menu.cache.enable');
    }

    /**
     * Get cache store.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function getStore()
    {
        return Cache::store(config('admin.menu.cache.store', 'file'));
    }
}
