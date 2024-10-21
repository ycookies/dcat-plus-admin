<?php

use Dcat\Admin\Admin;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Symfony\Component\HttpFoundation\Response;

if (! function_exists('admin_setting')) {
    /**
     * 获取或保存配置参数.
     *
     * @param  string|array  $key
     * @param  mixed  $default
     * @return \Dcat\Admin\Support\Setting|mixed
     */
    function admin_setting($key = null, $default = null)
    {
        if ($key === null) {
            return app('admin.setting');
        }

        if (is_array($key)) {
            app('admin.setting')->save($key);

            return;
        }

        return app('admin.setting')->get($key, $default);
    }
}

if (! function_exists('admin_setting_array')) {
    /**
     * 获取配置参数并转化为数组格式.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return \Dcat\Admin\Support\Setting|mixed
     */
    function admin_setting_array(?string $key, $default = [])
    {
        return app('admin.setting')->getArray($key, $default);
    }
}

if (! function_exists('admin_extension_setting')) {
    /**
     * 获取扩展配置参数.
     *
     * @param  string  $extension
     * @param  string|array  $key
     * @param  mixed  $default
     * @return mixed
     */
    function admin_extension_setting($extension, $key = null, $default = null)
    {
        $extension = app($extension);

        if ($extension instanceof Dcat\Admin\Extend\ServiceProvider) {
            return $extension->config($key, $default);
        }
    }
}

if (! function_exists('admin_section')) {
    /**
     * Get the string contents of a section.
     *
     * @param  string  $section
     * @param  mixed  $default
     * @param  array  $options
     * @return mixed
     */
    function admin_section(string $section, $default = null, array $options = [])
    {
        return app('admin.sections')->yieldContent($section, $default, $options);
    }
}

if (! function_exists('admin_has_section')) {
    /**
     * Check if section exists.
     *
     * @param  string  $section
     * @return mixed
     */
    function admin_has_section(string $section)
    {
        return app('admin.sections')->hasSection($section);
    }
}

if (! function_exists('admin_inject_section')) {
    /**
     * Injecting content into a section.
     *
     * @param  string  $section
     * @param  mixed  $content
     * @param  bool  $append
     * @param  int  $priority
     */
    function admin_inject_section(string $section, $content = null, bool $append = true, int $priority = 10)
    {
        app('admin.sections')->inject($section, $content, $append, $priority);
    }
}

if (! function_exists('admin_inject_section_if')) {
    /**
     * Injecting content into a section.
     *
     * @param  mixed  $condition
     * @param  string  $section
     * @param  mixed  $content
     * @param  bool  $append
     * @param  int  $priority
     */
    function admin_inject_section_if($condition, $section, $content = null, bool $append = false, int $priority = 10)
    {
        if ($condition) {
            app('admin.sections')->inject($section, $content, $append, $priority);
        }
    }
}

if (! function_exists('admin_has_default_section')) {
    /**
     * Check if default section exists.
     *
     * @param  string  $section
     * @return mixed
     */
    function admin_has_default_section(string $section)
    {
        return app('admin.sections')->hasDefaultSection($section);
    }
}

if (! function_exists('admin_inject_default_section')) {
    /**
     * Injecting content into a section.
     *
     * @param  string  $section
     * @param  string|Renderable|Htmlable|callable  $content
     */
    function admin_inject_default_section(string $section, $content)
    {
        app('admin.sections')->injectDefault($section, $content);
    }
}

if (! function_exists('admin_trans_field')) {
    /**
     * Translate the field name.
     *
     * @param $field
     * @param  null  $locale
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    function admin_trans_field($field, $locale = null)
    {
        return app('admin.translator')->transField($field, $locale);
    }
}

if (! function_exists('admin_trans_label')) {
    /**
     * Translate the label.
     *
     * @param $label
     * @param  array  $replace
     * @param  null  $locale
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    function admin_trans_label($label = null, $replace = [], $locale = null)
    {
        return app('admin.translator')->transLabel($label, $replace, $locale);
    }
}

if (! function_exists('admin_trans_option')) {
    /**
     * Translate the field name.
     *
     * @param $field
     * @param  array  $replace
     * @param  null  $locale
     * @return array|\Illuminate\Contracts\Translation\Translator|null|string
     */
    function admin_trans_option($optionValue, $field, $replace = [], $locale = null)
    {
        $slug = admin_controller_slug();

        return admin_trans("{$slug}.options.{$field}.{$optionValue}", $replace, $locale);
    }
}

if (! function_exists('admin_trans')) {
    /**
     * Translate the given message.
     *
     * @param  string  $key
     * @param  array  $replace
     * @param  string  $locale
     * @return \Illuminate\Contracts\Translation\Translator|string|array|null
     */
    function admin_trans($key, $replace = [], $locale = null)
    {
        return app('admin.translator')->trans($key, $replace, $locale);
    }
}

if (! function_exists('admin_controller_slug')) {
    /**
     * @return string
     */
    function admin_controller_slug()
    {
        static $slug = [];

        $controller = admin_controller_name();

        return $slug[$controller] ?? ($slug[$controller] = Helper::slug($controller));
    }
}

if (! function_exists('admin_controller_name')) {
    /**
     * Get the class "basename" of the current controller.
     *
     * @return string
     */
    function admin_controller_name()
    {
        return Helper::getControllerName();
    }
}

if (! function_exists('admin_path')) {
    /**
     * Get admin path.
     *
     * @param  string  $path
     * @return string
     */
    function admin_path($path = '')
    {
        return ucfirst(config('admin.directory')).($path ? DIRECTORY_SEPARATOR.$path : $path);
    }
}

if (! function_exists('admin_url')) {
    /**
     * Get admin url.
     *
     * @param  string  $path
     * @param  mixed  $parameters
     * @param  bool  $secure
     * @return string
     */
    function admin_url($path = '', $parameters = [], $secure = null)
    {
        if (url()->isValidUrl($path)) {
            return $path;
        }

        $secure = $secure ?: (config('admin.https') || config('admin.secure'));

        return url(admin_base_path($path), $parameters, $secure);
    }
}

if (! function_exists('admin_base_path')) {
    /**
     * Get admin url.
     *
     * @param  string  $path
     * @return string
     */
    function admin_base_path($path = '')
    {
        $prefix = '/'.trim(config('admin.route.prefix'), '/');

        $prefix = ($prefix == '/') ? '' : $prefix;

        $path = trim($path, '/');

        if (is_null($path) || strlen($path) == 0) {
            return $prefix ?: '/';
        }

        return $prefix.'/'.$path;
    }
}

if (! function_exists('admin_toastr')) {
    /**
     * Flash a toastr message bag to session.
     *
     * @param  string  $message
     * @param  string  $type
     * @param  array  $options
     */
    function admin_toastr($message = '', $type = 'success', $options = [])
    {
        $toastr = new MessageBag(get_defined_vars());

        session()->flash('dcat-admin-toastr', $toastr);
    }
}

if (! function_exists('admin_success')) {
    /**
     * Flash a success message bag to session.
     *
     * @param  string  $title
     * @param  string  $message
     */
    function admin_success($title, $message = '')
    {
        admin_info($title, $message, 'success');
    }
}

if (! function_exists('admin_error')) {
    /**
     * Flash a error message bag to session.
     *
     * @param  string  $title
     * @param  string  $message
     */
    function admin_error($title, $message = '')
    {
        admin_info($title, $message, 'error');
    }
}

if (! function_exists('admin_warning')) {
    /**
     * Flash a warning message bag to session.
     *
     * @param  string  $title
     * @param  string  $message
     */
    function admin_warning($title, $message = '')
    {
        admin_info($title, $message, 'warning');
    }
}

if (! function_exists('admin_info')) {
    /**
     * Flash a message bag to session.
     *
     * @param  string  $title
     * @param  string  $message
     * @param  string  $type
     */
    function admin_info($title, $message = '', $type = 'info')
    {
        $message = new MessageBag(get_defined_vars());

        session()->flash($type, $message);
    }
}

if (! function_exists('admin_asset')) {
    /**
     * @param $path
     * @return string
     */
    function admin_asset($path)
    {
        return Admin::asset()->url($path);
    }
}

if (! function_exists('admin_route')) {
    /**
     * 根据路由别名获取url.
     *
     * @param  string|null  $route
     * @param  array  $params
     * @param  bool  $absolute
     * @return string
     */
    function admin_route(?string $route, array $params = [], $absolute = true)
    {
        return Admin::app()->getRoute($route, $params, $absolute);
    }
}

if (! function_exists('admin_route_name')) {
    /**
     * 获取路由别名.
     *
     * @param  string|null  $route
     * @return string
     */
    function admin_route_name(?string $route)
    {
        return Admin::app()->getRoutePrefix().$route;
    }
}

if (! function_exists('admin_api_route_name')) {
    /**
     * 获取api的路由别名.
     *
     * @param  string  $route
     * @return string
     */
    function admin_api_route_name(?string $route = '')
    {
        return Admin::app()->getCurrentApiRoutePrefix().$route;
    }
}

if (! function_exists('admin_extension_path')) {
    /**
     * @param  string  $path
     * @return string
     */
    function admin_extension_path(string $path = '')
    {
        $dir = rtrim(config('admin.extension.dir'), '/') ?: base_path('dcat-admin-extensions');

        $path = ltrim($path, '/');

        return $path ? $dir.'/'.$path : $dir;
    }
}

if (! function_exists('admin_color')) {
    /**
     * @param  string|null  $color
     * @return string|\Dcat\Admin\Color
     */
    function admin_color(?string $color = null)
    {
        if ($color === null) {
            return Admin::color();
        }

        return Admin::color()->get($color);
    }
}

if (! function_exists('admin_view')) {
    /**
     * @param  string  $view
     * @param  array  $data
     * @return string
     *
     * @throws \Throwable
     */
    function admin_view($view, array $data = [])
    {
        return Admin::view($view, $data);
    }
}

if (! function_exists('admin_script')) {
    /**
     * @param  string  $js
     * @param  bool  $direct
     * @return void
     */
    function admin_script($script, bool $direct = false)
    {
        Admin::script($script, $direct);
    }
}

if (! function_exists('admin_style')) {
    /**
     * @param  string  $style
     * @return void
     */
    function admin_style($style)
    {
        Admin::style($style);
    }
}

if (! function_exists('admin_js')) {
    /**
     * @param  string|array  $js
     * @return void
     */
    function admin_js($js)
    {
        Admin::js($js);
    }
}

if (! function_exists('admin_css')) {
    /**
     * @param  string|array  $css
     * @return void
     */
    function admin_css($css)
    {
        Admin::css($css);
    }
}

if (! function_exists('admin_require_assets')) {
    /**
     * @param  string|array  $asset
     * @return void
     */
    function admin_require_assets($asset)
    {
        Admin::requireAssets($asset);
    }
}

if (! function_exists('admin_javascript')) {
    /**
     * 暂存JS代码，并使用唯一字符串代替.
     *
     * @param  string  $scripts
     * @return string
     */
    function admin_javascript(string $scripts)
    {
        return Dcat\Admin\Support\JavaScript::make($scripts);
    }
}

if (! function_exists('admin_javascript_json')) {
    /**
     * @param  array|object  $data
     * @return string
     */
    function admin_javascript_json($data)
    {
        return Dcat\Admin\Support\JavaScript::format($data);
    }
}

if (! function_exists('admin_exit')) {
    /**
     * 响应数据并中断后续逻辑.
     *
     * @param  Response|string|array  $response
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    function admin_exit($response = '')
    {
        Admin::exit($response);
    }
}

if (! function_exists('admin_redirect')) {
    /**
     * 跳转.
     *
     * @param  string  $to
     * @param  int  $statusCode
     * @param  Request  $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Routing\Redirector
     */
    function admin_redirect($to, int $statusCode = 302, Request $request = null)
    {
        return Helper::redirect($to, $statusCode, $request);
    }
}

if (! function_exists('format_byte')) {
    /**
     * 文件单位换算.
     *
     * @param $input
     * @param  int  $dec
     * @return string
     */
    function format_byte($input, $dec = 0)
    {
        $prefix_arr = ['B', 'KB', 'MB', 'GB', 'TB'];
        $value = round($input, $dec);
        $i = 0;
        while ($value > 1024) {
            $value /= 1024;
            $i++;
        }

        return round($value, $dec).$prefix_arr[$i];
    }
}
// 新增

if (!function_exists('array_build')) {
    function array_build($array, callable $callback)
    {
        $results = [];

        foreach ($array as $key => $value) {
            list($innerKey, $innerValue) = call_user_func($callback, $key, $value);

            $results[$innerKey] = $innerValue;
        }

        return $results;
    }
}

if (!function_exists('starts_with')) {
    /**
     * 检查字符串是否以给定的子字符串开头
     *
     * 此函数接受一个字符串（$haystack）和一个或多个子字符串（$needles），并检查这些子字符串是否是原始字符串的前缀
     * 它将遍历子字符串数组，使用多字节字符串处理函数mb_strpos来判断是否有一个子字符串与原始字符串的开头匹配
     * 如果任何一个子字符串与原始字符串的开头匹配，则函数返回true；否则返回false
     *
     * @param string $haystack 原始字符串
     * @param mixed $needles 一个或多个要检查的子字符串，可以是字符串或字符串数组
     * @return bool 如果原始字符串以任何一个子字符串开头，则返回true；否则返回false
     */
    function starts_with($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '' && mb_strpos($haystack, $needle) === 0) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('array_get')) {
    /**
     * 从数组中安全地获取值
     *
     * @param array $array 要从中获取值的数组
     * @param string|null $key 要获取的键值，可以是嵌套键的字符串，用点分隔
     * @param mixed $default 如果键不存在时返回的默认值
     * @return mixed 返回键对应的值，如果键不存在，则返回默认值
     */
    function array_get($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return value($default);
            }

            $array = $array[$segment];
        }

        return $array;
    }
}


if (!function_exists('array_has')) {
    /**
     * 检查数组中是否存在指定的键。
     *
     * 此函数支持检查多级数组中是否存在指定的键。键可以是以点号分隔的字符串形式，用于指定多级数组的路径。
     *
     * @param array $array 要检查的数组
     * @param string $key 要检查的键，可以是多级键（使用点号分隔）
     * @return bool 如果数组中存在指定的键，则返回true，否则返回false
     */
    function array_has($array, $key)
    {
        if (empty($array) || is_null($key)) {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }
}

if (!function_exists('array_except')) {
    /**
     * 从数组中移除指定的键。
     *
     * 此函数用于从给定数组中移除一个或多个指定的键。它通过引用操作数组，
     * 因此不会返回一个新的数组，而是直接修改原始数组。这提供了一种便捷的方法
     * 来过滤掉不需要的数组元素。
     *
     * @param array $array 要处理的原始数组。
     * @param mixed $keys 单个键或键的数组，这些键将从数组中被移除。
     * @return array 返回处理后的数组，即移除指定键后的数组。
     */
    function array_except($array, $keys)
    {
        array_forget($array, $keys);

        return $array;
    }
}

if (!function_exists('array_forget')) {
    /**
     * 从数组中移除指定的键。
     *
     * 此函数用于从给定数组中移除一个或多个指定的键。它通过引用操作数组，
     * 因此不会返回一个新的数组，而是直接修改原始数组。这提供了一种便捷的方法
     * 来过滤掉不需要的数组元素。
     *
     * @param array $array 要处理的原始数组。
     * @param mixed $keys 单个键或键的数组，这些键将从数组中被移除。
     * @return array 返回处理后的数组，即移除指定键后的数组。
     */
    function array_forget(&$array, $keys)
    {
        $original = &$array;

        $keys = (array)$keys;

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            $parts = explode('.', $key);

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    $parts = [];
                }
            }

            unset($array[array_shift($parts)]);

            // clean up after each pass
            $array = &$original;
        }
    }
}
if (!function_exists('ends_with')) {
    /**
     * 检查字符串是否以指定的后缀结束
     *
     * @param string $haystack 被检查的字符串
     * @param mixed $needles 后缀字符串或后缀字符串数组
     * @return bool 如果字符串以任一后缀结束返回true，否则返回false
     */
    function ends_with($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ((string)$needle === mb_substr($haystack, -mb_strlen($needle))) {
                return true;
            }
        }
        return false;
    }
}