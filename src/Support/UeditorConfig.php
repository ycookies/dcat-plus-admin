<?php

namespace Dcat\Admin\Support;

use Illuminate\Support\Arr;

/**
 * UEditor 的内置默认配置。
 *
 * 配置文件未发布、被精简或只配置了部分键时，编辑器与上传接口仍可使用。
 */
class UeditorConfig
{
    /**
     * 获取默认配置与 admin.ueditor 配置合并后的结果。
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $configured = config('admin.ueditor', []);
        $configured = is_array($configured) ? $configured : [];
        $config = array_replace_recursive(static::defaults(), $configured);

        // 工具栏是顺序数组，必须整体替换，不能与默认按钮按下标混合。
        if (array_key_exists('toolbars', $configured)) {
            $config['toolbars'] = (array) $configured['toolbars'];
        }

        return $config;
    }

    /**
     * 从合并后的配置中读取一个键。
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Arr::get(static::all(), $key, $default);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'disk'                    => null,
            'allowed_disks'           => [],
            'rate_limit'              => 20,
            'upload_token_ttl'        => 3600,
            'sanitize_html'           => true,
            'directory'               => [
                'image' => 'ueditor/images',
                'video' => 'ueditor/videos',
                'file'  => 'ueditor/files',
            ],
            'url_prefix'              => '',
            'load_config_from_server' => true,
            'initial_frame_height'    => 400,
            'element_path_enabled'    => false,
            'auto_height_enabled'     => true,
            'enable_ai'               => false,
            'emotion_localization'    => true,
            'dark_mode'               => true,
            'toolbars'                => [
                [
                    'fullscreen', 'source', '|', 'undo', 'redo', '|',
                    'bold', 'italic', 'underline', 'fontborder', 'strikethrough', 'superscript', 'subscript', '|',
                    'forecolor', 'backcolor', 'insertorderedlist', 'insertunorderedlist', '|',
                    'paragraph', 'fontfamily', 'fontsize', '|',
                    'justifyleft', 'justifycenter', 'justifyright', 'justifyjustify', '|',
                    'link', 'unlink', 'anchor', '|',
                    'simpleupload', 'insertimage', 'emotion', 'insertvideo', 'attachment', 'insertcode', '|',
                    'inserttable', 'deletetable', 'insertrow', 'deleterow', 'insertcol', 'deletecol', '|',
                    'preview', 'searchreplace',
                ],
            ],
            'image'                   => [
                'max_size'    => 2048000,
                'allow_files' => ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp'],
                'mime_types'  => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'],
            ],
            'video'                   => [
                'max_size'    => 102400000,
                'allow_files' => ['.mp4', '.avi', '.wmv', '.mov', '.flv', '.mkv', '.webm', '.m4v'],
                'mime_types'  => ['video/mp4', 'video/x-msvideo', 'video/x-ms-wmv', 'video/quicktime', 'video/x-flv', 'video/x-matroska', 'video/webm'],
            ],
            'file'                    => [
                'max_size'    => 51200000,
                'allow_files' => [
                    '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx', '.pdf',
                    '.zip', '.rar', '.7z', '.gz', '.tar', '.txt', '.csv', '.md',
                    '.mp3', '.wav', '.aac', '.flac', '.ogg',
                ],
                'mime_types' => [],
            ],
        ];
    }
}
