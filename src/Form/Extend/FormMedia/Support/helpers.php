<?php
namespace Dcat\Admin\Form\Extend\FormMedia;

use Lake\FormMedia\MediaManager;

if (! function_exists('lake_form_media_url')) {
    /**
     * 生成url链接
     *
     * @param  string  $url
     * @param  string  $disk
     *
     * @create 2020-11-26
     * @author deatil
     */
    function lake_form_media_url($url = '', $disk = '') {
        if (! empty($disk)) {
            $path = MediaManager::create()
                ->withDisk($disk)
                ->buildUrl($url);
        } else {
            $path = MediaManager::create()
                ->defaultDisk()
                ->buildUrl($url);
        }
        
        return $path;
    }
}

if (! function_exists('lake_form_media_urls')) {
    /**
     * 生成url链接列表
     *
     * @param  array<string, string>  $urls
     *
     * @create 2020-11-26
     * @author deatil
     */
    function lake_form_media_urls($urls = []) {
        $newUrls = [];
        foreach ($urls as $disk => $url) {
            if (is_string($disk)) {
                $newUrls[] = lake_form_media_url($url, $disk);
            } else {
                $newUrls[] = lake_form_media_url($url);
            }
        }
        
        return $newUrls;
    }
}
