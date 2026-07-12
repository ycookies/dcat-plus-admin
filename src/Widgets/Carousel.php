<?php

namespace Dcat\Admin\Widgets;

use InvalidArgumentException;

class Carousel extends Widget
{
    /**
     * @var string
     */
    protected $view = 'admin::widgets.carousel';

    /**
     * @var array
     */
    protected $items = [];

    /**
     * 自动轮播间隔（秒）；0 表示关闭自动轮播。
     *
     * @var int
     */
    protected $interval = 5;

    /**
     * 轮播方向（horizontal / vertical）。
     *
     * @var string
     */
    protected $direction = 'horizontal';

    /**
     * 是否自动轮播。
     *
     * @var bool
     */
    protected $autoplay = true;

    /**
     * 是否显示左右切换箭头。
     *
     * @var bool
     */
    protected $showArrows = true;

    /**
     * Carousel constructor.
     */
    public function __construct()
    {
        $this->id('carousel-'.uniqid());
        $this->class('dcat-carousel box-group');
        $this->style('margin-bottom: 20px');
    }

    /**
     * 设置自动轮播间隔（秒）。传入 0 可关闭自动轮播。
     *
     * @param  int  $seconds
     * @return $this
     */
    public function interval(int $seconds)
    {
        $this->interval = max(0, $seconds);

        return $this;
    }

    /**
     * 设置轮播方向（horizontal / vertical）。
     *
     * @param  string  $direction
     * @return $this
     */
    public function direction(string $direction)
    {
        $direction = strtolower($direction);

        if (! in_array($direction, ['horizontal', 'vertical'], true)) {
            throw new InvalidArgumentException('Carousel direction must be horizontal or vertical.');
        }

        $this->direction = $direction;

        return $this;
    }

    /**
     * 设置为垂直滚动。
     *
     * @return $this
     */
    public function vertical()
    {
        return $this->direction('vertical');
    }

    /**
     * 设置为左右滚动。
     *
     * @return $this
     */
    public function horizontal()
    {
        return $this->direction('horizontal');
    }

    /**
     * 开启或关闭自动轮播。
     *
     * @param  bool  $autoplay
     * @return $this
     */
    public function autoplay(bool $autoplay = true)
    {
        $this->autoplay = $autoplay;

        return $this;
    }

    /**
     * 显示或隐藏左右切换箭头。
     *
     * @param  bool  $show
     * @return $this
     */
    public function arrows(bool $show = true)
    {
        $this->showArrows = $show;

        return $this;
    }

    /**
     * Add item.
     *
     * @param string $title
     * @param string $content
     *
     * @return $this
     */
    public function add($img_src, $title = '', $content = '', $link = 'javascript:void(0);')
    {
        $this->items[] = [
            'img_src' => $img_src,
            'title'   => $title,
            'content' => $content,
            'link'    => $link,
        ];

        return $this;
    }

    /**
     * 批量添加轮播项。
     *
     * 每项可使用关联数组：
     * ['img_src' => '...', 'title' => '...', 'content' => '...', 'link' => '...']，
     * 也可使用与 add 方法一致的位置数组：[img_src, title, content, link]。
     *
     * @param  array  $items
     * @return $this
     */
    public function addItems(array $items)
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $this->add(
                $item['img_src'] ?? ($item[0] ?? ''),
                $item['title'] ?? ($item[1] ?? ''),
                $item['content'] ?? ($item[2] ?? ''),
                $item['link'] ?? ($item[3] ?? 'javascript:void(0);')
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables()
    {
        return [
            'id'         => $this->id,
            'items'      => $this->items,
            'interval'   => $this->interval,
            'direction'  => $this->direction,
            'autoplay'   => $this->autoplay,
            'showArrows' => $this->showArrows,
            'attributes' => $this->formatHtmlAttributes(),
        ];
    }

}
