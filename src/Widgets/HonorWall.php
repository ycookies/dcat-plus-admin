<?php

namespace Dcat\Admin\Widgets;

use InvalidArgumentException;

/**
 * 基于 Swiper 的资质荣誉墙组件。
 */
class HonorWall extends Swiper
{
    /** @var string */
    protected $view = 'admin::widgets.honor-wall';

    /** @var int */
    protected $slideWidth = 260;

    /** @var int */
    protected $maxWidth = 1200;

    /** @var bool */
    protected $autoScale = true;

    /** @var int */
    protected $visibleSlides = 5;

    /** @var array<string, mixed> */
    protected $defaultOptions = [
        'direction'             => 'horizontal',
        'slidesPerView'         => 1,
        'centeredSlides'        => true,
        'loop'                  => false,
        'speed'                 => 650,
        'spaceBetween'          => 0,
        'grabCursor'            => true,
        'effect'                => 'creative',
        'creativeEffect'        => [
            'limitProgress'     => 3,
            'progressMultiplier' => 1,
            'prev'              => [
                'translate' => ['-19%', 0, -180],
                'scale'     => 0.78,
                'opacity'   => 1,
            ],
            'next'              => [
                'translate' => ['19%', 0, -180],
                'scale'     => 0.78,
                'opacity'   => 1,
            ],
        ],
        'watchOverflow'         => true,
        'observer'              => true,
        'observeParents'        => true,
    ];

    /**
     * @param  array<int, mixed>  $honors
     */
    public function __construct(array $honors = [])
    {
        parent::__construct();

        $this->class('dcat-honor-wall', true);
        $this->pagination();
        $this->navigation();
        $this->honors($honors);
    }

    /**
     * 添加一个资质荣誉。
     *
     * @param  string  $image  图片地址
     * @param  string  $title  荣誉名称
     * @param  string|null  $url  点击跳转地址
     * @param  string  $target
     * @return $this
     */
    public function add($image, $title = '', $url = null, string $target = '_self')
    {
        $image = trim((string) $image);

        if (! $this->isSafeImageUrl($image)) {
            return $this;
        }

        $title = htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8');
        $image = htmlspecialchars($image, ENT_QUOTES, 'UTF-8');
        $content = '<div class="dcat-honor-wall__card"><div class="dcat-honor-wall__image"><img src="'.$image.'" alt="'.$title.'"></div><div class="dcat-honor-wall__title">'.$title.'</div></div>';

        if ($url && $this->isSafeLinkUrl((string) $url)) {
            $target = $target === '_blank' ? '_blank' : '_self';
            $content = '<a class="dcat-honor-wall__link" href="'.htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8').'" target="'.$target.'"'.($target === '_blank' ? ' rel="noopener noreferrer"' : '').'>'.$content.'</a>';
        }

        return parent::add($content);
    }

    /**
     * 批量添加荣誉项。
     *
     * 每项可传关联数组：
     * ['image' => '/storage/honor.jpg', 'title' => '荣誉名称', 'url' => '/admin/honors/1', 'target' => '_blank']，
     * 或位置数组：[图片地址, 荣誉名称, 跳转地址, 打开方式]。
     *
     * @param  array<int, mixed>  $honors
     * @return $this
     */
    public function honors(array $honors)
    {
        foreach ($honors as $honor) {
            if (is_array($honor)) {
                $this->add(
                    $honor['image'] ?? ($honor[0] ?? ''),
                    $honor['title'] ?? ($honor[1] ?? ''),
                    $honor['url'] ?? ($honor[2] ?? null),
                    $honor['target'] ?? ($honor[3] ?? '_self')
                );
            }
        }

        return $this;
    }

    /**
     * 设置单张荣誉卡片宽度，单位为 px。
     *
     * @param  int  $width
     * @return $this
     */
    public function slideWidth(int $width)
    {
        if ($width < 120 || $width > 600) {
            throw new InvalidArgumentException('Honor wall slide width must be between 120 and 600 pixels.');
        }

        $this->slideWidth = $width;
        $this->autoScale = false;

        return $this;
    }

    /**
     * 是否根据荣誉墙容器宽度自动计算卡片尺寸，默认开启。
     *
     * @param  bool  $enabled
     * @return $this
     */
    public function autoScale(bool $enabled = true)
    {
        $this->autoScale = $enabled;

        return $this;
    }

    /**
     * 设置桌面端最多显示的荣誉数量，推荐使用 3、5 或 7。
     *
     * @param  int  $count
     * @return $this
     */
    public function visibleSlides(int $count = 5)
    {
        if ($count < 1 || $count > 5 || $count % 2 === 0) {
            throw new InvalidArgumentException('Honor wall visible slides must be 1, 3 or 5.');
        }

        $this->visibleSlides = $count;

        return $this;
    }

    /**
     * 设置荣誉墙主体的最大宽度，单位为 px；组件会在父容器内自动居中。
     *
     * @param  int  $width
     * @return $this
     */
    public function maxWidth(int $width)
    {
        if ($width < 320 || $width > 1920) {
            throw new InvalidArgumentException('Honor wall max width must be between 320 and 1920 pixels.');
        }

        $this->maxWidth = $width;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function defaultVariables()
    {
        return array_merge(parent::defaultVariables(), [
            'slideWidth' => $this->slideWidth,
            'maxWidth'   => $this->maxWidth,
            'autoScale'  => $this->autoScale,
            'visibleSlides' => $this->visibleSlides,
        ]);
    }

    /**
     * 允许 HTTP(S)、站内绝对路径和常规相对图片路径，拒绝可执行协议。
     */
    protected function isSafeImageUrl(string $url): bool
    {
        return (bool) preg_match('~^(?:https?://|/(?!/)|(?:\./|\.\./)?[a-zA-Z0-9][a-zA-Z0-9_./?=&%#@+-]*)~', $url);
    }

    /**
     * 只允许站内相对地址、锚点或 HTTP(S) 地址。
     */
    protected function isSafeLinkUrl(string $url): bool
    {
        return (bool) preg_match('~^(?:https?://|/(?!/)|#)~i', $url);
    }
}
