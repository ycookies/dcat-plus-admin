<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;
use Dcat\Admin\Support\Helper;
use Dcat\Admin\Support\UeditorConfig;
use Dcat\Admin\Support\UeditorHtmlSanitizer;
use Dcat\Admin\Support\UeditorUploadSignature;

/**
 * UEditor 富文本编辑器.
 *
 * @see https://ueditor.baidu.com
 */
class Editor extends Field
{
    /**
     * UEditor 前端配置。
     *
     * 未配置 admin.ueditor 时使用内置默认值；字段 options 的优先级最高。
     */
    protected $options = [];

    protected $disk;

    protected $imageUploadDirectory;

    /**
     * 设置文件上传存储磁盘.
     *
     * @param  string  $disk
     * @return $this
     */
    public function disk(string $disk)
    {
        $this->disk = $disk;

        return $this;
    }

    /**
     * 设置图片上传文件夹.
     *
     * @param  string  $dir
     * @return $this
     */
    public function imageDirectory(string $dir)
    {
        $this->imageUploadDirectory = $dir;

        return $this;
    }

    /**
     * 自定义上传接口（覆盖默认 serverUrl）.
     *
     * @param  string  $url
     * @return $this
     */
    public function imageUrl(string $url)
    {
        return $this->mergeOptions(['serverUrl' => $this->formatUrl(admin_url($url))]);
    }

    /**
     * 设置语言（zh-cn / en）.
     *
     * @param  string  $lang
     * @return $this
     */
    public function languageUrl(string $lang)
    {
        return $this->mergeOptions(['lang' => $lang]);
    }

    /**
     * 设置编辑器高度.
     *
     * @param  int  $height
     * @return $this
     */
    public function height(int $height)
    {
        return $this->mergeOptions(['initialFrameHeight' => $height]);
    }

    /**
     * 是否跟随 Dcat 后台的深色模式。
     *
     * @param  bool  $enabled
     * @return $this
     */
    public function darkMode(bool $enabled = true)
    {
        return $this->mergeOptions(['dcatDarkMode' => $enabled]);
    }

    /**
     * @return array
     */
    protected function formatOptions()
    {
        $config = UeditorConfig::all();

        $this->options = array_merge([
            'initialFrameHeight'    => $config['initial_frame_height'],
            'loadConfigFromServer'  => $config['load_config_from_server'],
            'elementPathEnabled'    => $config['element_path_enabled'],
            'autoHeightEnabled'     => $config['auto_height_enabled'],
            'toolbars'              => $config['toolbars'],
            // AI is opt-in because requests can send editor content to external providers.
            'toolbarShows'          => ['ai' => (bool) $config['enable_ai']],
            'shortcutMenuShows'     => ['ai' => (bool) $config['enable_ai']],
            // Use bundled emoticons instead of the default third-party HTTP endpoint.
            'emotionLocalization'   => (bool) $config['emotion_localization'],
            // Follow Dcat's body.dark-mode class, including the editor iframe and dialogs.
            'dcatDarkMode'          => (bool) $config['dark_mode'],
        ], $this->options);

        $locale = config('app.locale');
        $this->options['lang'] = $this->options['lang'] ?? ($locale === 'en' ? 'en' : 'zh-cn');

        $this->options['readonly'] = ! empty($this->attributes['readonly']) || ! empty($this->attributes['disabled']);

        if (empty($this->options['serverUrl'])) {
            $this->options['serverUrl'] = $this->defaultImageUploadUrl();
        }

        return $this->options;
    }

    /**
     * @return string
     */
    protected function defaultImageUploadUrl()
    {
        return $this->formatUrl(route(admin_api_route_name('ueditor.server.post')));
    }

    /**
     * @param  string  $url
     * @return string
     */
    protected function formatUrl(string $url)
    {
        $parameters = [];

        if ($this->disk || $this->imageUploadDirectory) {
            $expires = time() + (int) UeditorConfig::get('upload_token_ttl', 3600);

            $parameters['disk'] = $this->disk;
            $parameters['dir'] = $this->imageUploadDirectory;
            $parameters['expires'] = $expires;
            $parameters['signature'] = UeditorUploadSignature::make(
                $this->disk,
                $this->imageUploadDirectory,
                $expires
            );
        }

        return Helper::urlWithQuery($url, $parameters);
    }

    /**
     * Client-side editor filters can be bypassed with a direct form submission.
     */
    protected function prepareInputValue($value)
    {
        if (! UeditorConfig::get('sanitize_html', true)) {
            return $value;
        }

        return UeditorHtmlSanitizer::sanitize($value);
    }

    /**
     * @return string
     */
    public function render()
    {
        $this->addVariables([
            'options' => $this->formatOptions(),
            'homeUrl' => admin_asset('@admin/dcat/plugins/ueditor/'),
        ]);

        return parent::render();
    }
}
