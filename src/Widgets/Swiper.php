<?php

namespace Dcat\Admin\Widgets;

use InvalidArgumentException;

/**
 * Swiper 轮播组件。
 *
 * @see https://swiperjs.com
 */
class Swiper extends Widget
{
    /** @var array<string>|string */
    public static $css = [
        '@admin/dcat/plugins/swiper/swiper-bundle.min.css',
    ];

    /** @var array<string>|string */
    public static $js = [
        '@admin/dcat/plugins/swiper/swiper-bundle.min.js',
    ];

    /** @var string */
    protected $view = 'admin::widgets.swiper';

    /** @var array<int, mixed> */
    protected $slides = [];

    /** @var bool */
    protected $showPagination = false;

    /** @var bool */
    protected $showNavigation = false;

    /** @var array<string, mixed> */
    protected $defaultOptions = [
        'direction'       => 'horizontal',
        'slidesPerView'   => 1,
        'spaceBetween'    => 0,
        'speed'           => 400,
        'loop'            => false,
        'watchOverflow'   => true,
        'observer'        => true,
        'observeParents'  => true,
    ];

    /**
     * @param  array<int, mixed>  $slides
     */
    public function __construct(array $slides = [])
    {
        $this->id('swiper-'.uniqid());
        $this->setElementClass('dcat-swiper');
        $this->class('swiper', true);
        $this->addSlides($slides);
    }

    /**
     * 添加一个幻灯片内容，可传入字符串、Renderable 或 View。
     *
     * @param  mixed  $content
     * @return $this
     */
    public function add($content)
    {
        $this->slides[] = $content;

        return $this;
    }

    /**
     * 批量添加幻灯片内容。
     *
     * @param  array<int, mixed>  $slides
     * @return $this
     */
    public function addSlides(array $slides)
    {
        foreach ($slides as $slide) {
            $this->add($slide);
        }

        return $this;
    }

    /**
     * 设置 Swiper 滚动方向（horizontal 为左右，vertical 为上下）。
     *
     * @param  string  $direction
     * @return $this
     */
    public function direction(string $direction)
    {
        $direction = strtolower($direction);

        if (! in_array($direction, ['horizontal', 'vertical'], true)) {
            throw new InvalidArgumentException('Swiper direction must be horizontal or vertical.');
        }

        return $this->option('direction', $direction);
    }

    /**
     * 设置为左右滚动（默认）。
     *
     * @return $this
     */
    public function horizontal()
    {
        return $this->direction('horizontal');
    }

    /**
     * 设置为上下滚动。
     *
     * @return $this
     */
    public function vertical()
    {
        return $this->direction('vertical');
    }

    /**
     * 设置是否循环。
     *
     * @param  bool  $enabled
     * @return $this
     */
    public function loop(bool $enabled = true)
    {
        return $this->option('loop', $enabled);
    }

    /**
     * 设置自动播放间隔（毫秒）；传入 0 关闭自动播放。
     *
     * @param  int  $delay
     * @return $this
     */
    public function autoplay(int $delay = 3000)
    {
        return $this->option('autoplay', $delay > 0 ? [
            'delay'                => $delay,
            'disableOnInteraction' => false,
        ] : false);
    }

    /**
     * 显示分页器圆点。调用 options(['pagination' => [...]]) 可继续覆盖其参数。
     *
     * @param  bool  $show
     * @return $this
     */
    public function pagination(bool $show = true)
    {
        $this->showPagination = $show;

        return $this;
    }

    /**
     * 显示上一页、下一页切换按钮。调用 options(['navigation' => [...]]) 可继续覆盖其参数。
     *
     * @param  bool  $show
     * @return $this
     */
    public function navigation(bool $show = true)
    {
        $this->showNavigation = $show;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    protected function resolvedOptions(): array
    {
        $options = array_replace_recursive($this->defaultOptions, $this->options);

        if ($this->shouldRenderPagination()) {
            $pagination = is_array($options['pagination'] ?? null) ? $options['pagination'] : [];
            $options['pagination'] = array_replace([
                'el'        => '#'.$this->id.' .swiper-pagination',
                'clickable' => true,
            ], $pagination);
        }

        if ($this->shouldRenderNavigation()) {
            $navigation = is_array($options['navigation'] ?? null) ? $options['navigation'] : [];
            $options['navigation'] = array_replace([
                'nextEl' => '#'.$this->id.' .swiper-button-next',
                'prevEl' => '#'.$this->id.' .swiper-button-prev',
            ], $navigation);
        }

        return $options;
    }

    /**
     * @return bool
     */
    protected function shouldRenderPagination(): bool
    {
        return $this->showPagination || ! empty($this->options['pagination']);
    }

    /**
     * @return bool
     */
    protected function shouldRenderNavigation(): bool
    {
        return $this->showNavigation || ! empty($this->options['navigation']);
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables()
    {
        return [
            'id'         => $this->id,
            'slides'     => array_map([$this, 'toString'], $this->slides),
            'options'    => $this->resolvedOptions(),
            'pagination' => $this->shouldRenderPagination(),
            'navigation' => $this->shouldRenderNavigation(),
            'attributes' => $this->formatHtmlAttributes(),
        ];
    }
}
