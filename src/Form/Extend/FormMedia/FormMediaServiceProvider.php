<?php

namespace Dcat\Admin\Form\Extend\FormMedia;

use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Illuminate\Support\ServiceProvider;
use Dcat\Admin\Form\Extend\FormMedia\Form\Field;

/**
 * 表单媒体扩展
 *
 * @create 2020-11-25
 * @author deatil
 */
class FormMediaServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function boot()
    {
        /*// 设置别名
        if (! class_exists('LakeFormMedia')) {
            class_alias(__CLASS__, 'LakeFormMedia');
        }

        // 加载路由
        $this->app->booted(function () {
            $this->registerRoutes(__DIR__.'/../routes/admin.php');
        });
        
        // 视图
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lake-form-media');*/

        // 加载插件
        Admin::booting(function () {
            Form::extend('iconimg', Field\Iconimg::class);
            Form::extend('photo', Field\Photo::class);
            Form::extend('photos', Field\Photos::class);
            Form::extend('video', Field\Video::class);
            Form::extend('audio', Field\Audio::class);
            Form::extend('files', Field\Files::class);
        });

        // 加载语言包
        Admin::booting(function () {
            $script = "
            window.LakeFormMediaLang = {
                'empty': '" . admin_trans('form-media.js_empty') . "',
                'system_tip': '" . admin_trans('form-media.js_system_tip') . "',
                'remove_tip': '" . admin_trans('form-media.js_remove_tip') . "',
                'select_type': '" . admin_trans('form-media.js_select_type') . "',
                'page_render': '" . admin_trans('form-media.js_page_render') . "',
                'dir_not_empty': '" . admin_trans('form-media.js_dir_not_empty') . "',
                'create_dir_error': '" . admin_trans('form-media.js_create_dir_error') . "',
                'upload_error': '" . admin_trans('form-media.js_upload_error') . "',
                'selected_error': '" . admin_trans('form-media.js_selected_error') . "',
                'getdata_error': '" . admin_trans('form-media.js_getdata_error') . "',
                'preview_title': '" . admin_trans('form-media.js_preview_title') . "',
                'preview': '" . admin_trans('form-media.js_preview') . "',
                'remove': '" . admin_trans('form-media.js_remove') . "',
                'dragsort': '" . admin_trans('form-media.js_dragsort') . "',
                'copy_success': '" . admin_trans('form-media.js_copy_success') . "',
                'copy_error': '" . admin_trans('form-media.js_copy_error') . "',
            };
            ";
            Admin::script($script);
        });
    }
    
}