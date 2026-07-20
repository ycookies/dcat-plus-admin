<?php

namespace Dcat\Admin\Scaffold;

use Dcat\Admin\Support\Helper;
use Illuminate\Support\Facades\App;

class LangCreator
{
    protected $fields = [];

    public function __construct(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * 生成语言包.
     *
     * @param  string  $controller
     * @param  string  $title
     * @return string
     */
    public function create(string $controller, ?string $title, bool $force = false)
    {
        $controller = str_replace('Controller', '', class_basename($controller));

        $filename = $this->getLangPath($controller);
        if (is_file($filename) && ! $force) {
            return;
        }

        $title = $title ?: $controller;

        $content = [
            'labels' => [
                $controller => $title,
                Helper::slug($controller) => $title,
            ],
            'fields'  => [],
            'options' => [],
            'permissions' => [
                'resource' => [
                    'group' => $title,
                ],
                'description' => '',
                'actions' => [
                    'index'   => trans('admin.permission_action_index'),
                    'show'    => trans('admin.permission_action_show'),
                    'create'  => trans('admin.permission_action_create'),
                    'store'   => trans('admin.permission_action_store'),
                    'edit'    => trans('admin.permission_action_edit'),
                    'update'  => trans('admin.permission_action_update'),
                    'destroy' => trans('admin.permission_action_destroy'),
                    'import'  => trans('admin.permission_action_import'),
                    'export'  => trans('admin.permission_action_export'),
                ],
                'routes' => [],
            ],
        ];
        foreach ($this->fields as $field) {
            if (empty($field['name'])) {
                continue;
            }

            $content['fields'][$field['name']] = $field['translation'] ?: $field['name'];
        }

        $files = app('files');
        if (! $files->isDirectory(dirname($filename))) {
            $files->makeDirectory(dirname($filename), 0755, true);
        }

        if ($files->put($filename, Helper::exportArrayPhp($content))) {
            $files->chmod($filename, 0777);

            return $filename;
        }
    }

    /**
     * 获取语言包路径.
     *
     * @param  string  $controller
     * @return string
     */
    protected function getLangPath(string $controller)
    {
        $path = rtrim(app()->langPath(), '/').'/'.App::getLocale();

        return $path.'/'.Helper::slug($controller).'.php';
    }
}
