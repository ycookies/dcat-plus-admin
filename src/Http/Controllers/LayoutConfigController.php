<?php

namespace Dcat\Admin\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
/**
 * 布局配置控制器 - 处理导航栏可视化配置的保存
 */
class LayoutConfigController extends Controller
{
    /**
     * 保存布局配置
     */
    public function save(Request $request)
    {
        $data = $request->except(['_token']);

        // 读取已有配置，合并后再保存（避免部分更新时丢失其他配置）
        $existing = [];
        try {
            $existing = admin_setting_group('layout_config') ?: [];
        } catch (\Throwable) {}
        $data = array_merge($existing, $data);

        // 菜单模式处理：menu_style → horizontal_menu 映射
        $menuStyle = $data['menu_style'] ?? 'default_menu';
        $allowedStyles = ['default_menu', 'horizontal_menu', 'two_col_menu'];
        $data['menu_style'] = in_array($menuStyle, $allowedStyles) ? $menuStyle : 'default_menu';
        // 同步 horizontal_menu 布尔值（兼容旧逻辑）
        $data['horizontal_menu'] = ($data['menu_style'] === 'horizontal_menu');

        // 布尔字段处理
        $booleans = ['sidebar_collapsed', 'dark_mode_switch', 'full_screen', 'show_locale_switch', 'show_help', 'show_notification'];
        foreach ($booleans as $key) {
            $data[$key] = !empty($data[$key]);
        }

        // body_class 处理
        if (isset($data['body_class']) && is_array($data['body_class'])) {
            $data['body_class'] = array_values(array_filter($data['body_class']));
        }
        if (!isset($data['body_class'])) {
            $data['body_class'] = [];
        }

        // 字符串字段清理
        $strings = ['color', 'sidebar_style', 'navbar_color', 'home_url'];
        foreach ($strings as $key) {
            if (isset($data[$key])) {
                $data[$key] = trim($data[$key]);
            }
        }

        // 语言配置验证
        $allowedLocales = ['zh_CN', 'zh_TW', 'en'];
        if (isset($data['locale'])) {
            $data['locale'] = in_array($data['locale'], $allowedLocales) ? $data['locale'] : config('app.locale', 'zh_CN');
        }

        // iframe-tabs 活跃模式验证
        if (isset($data['active_mode'])) {
            $data['active_mode'] = in_array($data['active_mode'], ['default', 'iframe-tabs']) ? $data['active_mode'] : 'default';
        }

        // 保存到数据库（admin_settings 表，使用 layout_config 分组）
        admin_setting_group('layout_config', $data);

        return response()->json([
            'status'  => true,
            'message' => '布局配置已保存，页面即将刷新',
        ]);
    }
    /**
     * 清除所有缓存（路由、视图、应用缓存、配置）
     */
    public function clear(Request $request)
    {
        try {
            // 清除路由缓存
            Artisan::call('route:clear');
            // 清除视图缓存
            Artisan::call('view:clear');
            // 清除应用缓存
            Artisan::call('cache:clear');
            // 清除配置缓存
            Artisan::call('config:clear');

            return response()->json([
                'status'  => true,
                'message' => '缓存清理完成',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status'  => false,
                'message' => '缓存清理失败：' . $e->getMessage(),
            ]);
        }
    }
}