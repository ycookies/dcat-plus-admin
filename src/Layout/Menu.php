<?php

namespace Dcat\Admin\Layout;

use Dcat\Admin\Admin;
use Dcat\Admin\Support\Helper;
use Illuminate\Support\Facades\Lang;

class Menu
{
    protected static $helperNodes = [
        [
            'id'        => 1,
            'title'     => 'Helpers',
            'icon'      => 'fa fa-keyboard-o',
            'uri'       => '',
            'parent_id' => 0,
        ],
        [
            'id'        => 2,
            'title'     => 'Extensions',
            'icon'      => '',
            'uri'       => 'auth/extensions',
            'parent_id' => 1,
        ],
        [
            'id'        => 3,
            'title'     => 'Scaffold',
            'icon'      => '',
            'uri'       => 'helpers/scaffold',
            'parent_id' => 1,
        ],
        [
            'id'        => 4,
            'title'     => 'Icons',
            'icon'      => '',
            'uri'       => 'helpers/icons',
            'parent_id' => 1,
        ],
    ];

    protected $view = 'admin::partials.menu';

    /**
     * 存储所有通过 add() 动态添加的菜单节点.
     * @var array
     */
    protected $addedNodes = []; // 每个 add() 调用存储为一个独立分组（二维数组）

    /**
     * register() 幂等标记（防止重复注册导致 helpers 菜单重复添加）.
     * @var bool
     */
    protected $registered = false;

    /**
     * getMergedNodes() 结果缓存（同一请求内菜单数据不变）.
     * @var array|null
     */
    protected $mergedNodes;

    public function register()
    {
        if ($this->registered) {
            return;
        }
        $this->registered = true;

        if (! admin_has_default_section(Admin::SECTION['LEFT_SIDEBAR_MENU'])) {
            admin_inject_default_section(Admin::SECTION['LEFT_SIDEBAR_MENU'], function () {
                $menuModel = config('admin.database.menu_model');

                return $this->toHtml((new $menuModel())->allNodes()->toArray());
            });
        }

        if (config('app.debug') && config('admin.helpers.enable', true)) {
            $this->add(static::$helperNodes, 20);
        }
    }

    /**
     * 增加菜单节点.
     *
     * @param  array  $nodes
     * @param  int  $priority
     * @return void
     */
    public function add(array $nodes = [], int $priority = 10)
    {
        // 存储为独立分组，避免不同 add() 调用的 ID 冲突
        $this->addedNodes[] = $nodes;

        admin_inject_section(Admin::SECTION['LEFT_SIDEBAR_MENU_BOTTOM'], function () use (&$nodes) {
            return $this->toHtml($nodes);
        }, true, $priority);
    }

    /**
     * 获取所有通过 add() 动态添加的菜单节点.
     *
     * @return array
     */
    public function getAddedNodes(): array
    {
        return $this->addedNodes;
    }

    /**
     * 获取合并后的全部菜单节点（数据库菜单 + add() 动态菜单，含 Helpers）.
     *
     * 【关键】分别重新编号每个来源的 ID，避免不同来源 ID 冲突（如 Helpers 硬编码 id 1..4 与数据库 id 撞车）；
     * getAddedNodes() 返回二维数组，每个 add() 调用是一组独立的节点，组内独立编号。
     * 结果在单例上缓存，同一请求内多次调用不会重复查询。
     *
     * @return array
     */
    public function getMergedNodes(): array
    {
        if ($this->mergedNodes !== null) {
            return $this->mergedNodes;
        }

        // 确保 register() 已执行（注册默认 section + Helpers）
        $this->register();

        // 数据库菜单
        $menuModel = config('admin.database.menu_model');
        $allNodes = (new $menuModel())->allNodes()->toArray();

        // 数据库节点：重新编号，ID 从 1 开始
        $globalId = 0;
        $dbIdMap = []; // old_id => new_id
        foreach ($allNodes as &$dbNode) {
            $globalId++;
            $dbIdMap[(int)$dbNode['id']] = $globalId;
            $dbNode['_old_parent_id'] = (int)$dbNode['parent_id'];
            $dbNode['id'] = $globalId;
        }
        unset($dbNode);
        // 更新数据库节点的 parent_id
        foreach ($allNodes as &$dbNode) {
            $oldPid = $dbNode['_old_parent_id'];
            $dbNode['parent_id'] = ($oldPid === 0) ? 0 : ($dbIdMap[$oldPid] ?? 0);
            unset($dbNode['_old_parent_id']);
        }
        unset($dbNode);

        // 动态节点：按组重新编号，每组独立的 ID 映射
        foreach ($this->getAddedNodes() as $group) {
            $groupOldToNew = []; // 组内的 old_id => new_id
            // 第一步：分配新 ID
            foreach ($group as $gNode) {
                $globalId++;
                $groupOldToNew[(int)$gNode['id']] = $globalId;
            }
            // 第二步：更新 id 和 parent_id
            foreach ($group as $gNode) {
                $oldPid = (int)$gNode['parent_id'];
                $gNode['id'] = $groupOldToNew[(int)$gNode['id']];
                $gNode['parent_id'] = ($oldPid === 0) ? 0 : ($groupOldToNew[$oldPid] ?? 0);
                $allNodes[] = $gNode;
            }
        }

        return $this->mergedNodes = $allNodes;
    }

    /**
     * 推断当前请求激活的顶层菜单项 ID（当前 path 命中该项或其任意后代节点）.
     *
     * @param  array  $menus  buildNestedArray 处理后的顶层节点数组
     * @return int|null 无匹配时返回 null
     */
    public function inferActiveItemId(array $menus): ?int
    {
        foreach ($menus as $item) {
            if ($this->isActive($item)) {
                return (int)$item['id'];
            }
        }

        return null;
    }

    /**
     * 转化为HTML.
     *
     * @param  array  $nodes
     * @return string
     *
     * @throws \Throwable
     */
    public function toHtml($nodes)
    {
        $html = '';

        foreach (Helper::buildNestedArray($nodes) as $item) {
            $html .= $this->render($item);
        }

        return $html;
    }

    /**
     * 设置菜单视图.
     *
     * @param  string  $view
     * @return $this
     */
    public function view(string $view)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * 渲染视图.
     *
     * @param  array  $item
     * @return string
     */
    public function render($item)
    {
        return view($this->view, ['item' => &$item, 'builder' => $this])->render();
    }

    /**
     * 判断是否选中.
     *
     * @param  array  $item
     * @param  null|string  $path
     * @return bool
     */
    public function isActive($item, ?string $path = null)
    {
        if (empty($path)) {
            $path = request()->path();
        }

        if (empty($item['children'])) {
            if (empty($item['uri'])) {
                return false;
            }

            return trim($this->getPath($item['uri']), '/') == $path;
        }

        foreach ($item['children'] as $v) {
            if ($path == trim($this->getPath($v['uri']), '/')) {
                return true;
            }
            if (! empty($v['children'])) {
                if ($this->isActive($v, $path)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * 判断节点是否可见.
     *
     * @param  array  $item
     * @return bool
     */
    public function visible($item)
    {
        if (
            ! $this->checkPermission($item)
            || ! $this->checkExtension($item)
            || ! $this->userCanSeeMenu($item)
        ) {
            return false;
        }

        $show = $item['show'] ?? null;
        if ($show !== null && ! $show) {
            return false;
        }

        return true;
    }

    /**
     * 判断扩展是否启用.
     *
     * @param $item
     * @return bool
     */
    protected function checkExtension($item)
    {
        $extension = $item['extension'] ?? null;

        if (! $extension) {
            return true;
        }

        if (! $extension = Admin::extension($extension)) {
            return false;
        }

        return $extension->enabled();
    }

    /**
     * 判断用户.
     *
     * @param  array|\Dcat\Admin\Models\Menu  $item
     * @return bool
     */
    protected function userCanSeeMenu($item)
    {
        $user = Admin::user();

        if (! $user || ! method_exists($user, 'canSeeMenu')) {
            return true;
        }

        return $user->canSeeMenu($item);
    }

    /**
     * 判断权限.
     *
     * @param $item
     * @return bool
     */
    protected function checkPermission($item)
    {
        $permissionIds = $item['permission_id'] ?? null;
        $roles = array_column(Helper::array($item['roles'] ?? []), 'slug');
        $permissions = array_column(Helper::array($item['permissions'] ?? []), 'slug');

        if (! $permissionIds && ! $roles && ! $permissions) {
            return true;
        }

        $user = Admin::user();

        if (! $user || $user->visible($roles)) {
            return true;
        }

        foreach (array_merge(Helper::array($permissionIds), $permissions) as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  string  $text
     * @return string
     */
    public function translate($text)
    {
        $titleTranslation = 'menu.titles.'.trim(str_replace(' ', '_', strtolower($text)));

        if (Lang::has($titleTranslation)) {
            return __($titleTranslation);
        }

        return $text;
    }

    /**
     * @param  string  $uri
     * @return string
     */
    public function getPath($uri)
    {
        return $uri
            ? (url()->isValidUrl($uri) ? $uri : admin_base_path($uri))
            : $uri;
    }

    /**
     * @param  string  $uri
     * @return string
     */
    public function getUrl($uri)
    {
        return $uri ? admin_url($uri) : $uri;
    }
}
