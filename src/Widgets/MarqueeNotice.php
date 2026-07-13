<?php

namespace Dcat\Admin\Widgets;

/**
 * 基于 Swiper 的跑马灯通知组件。
 */
class MarqueeNotice extends Swiper
{
    /** @var array<string, mixed> */
    protected $defaultOptions = [
        'direction'       => 'vertical',
        'slidesPerView'   => 1,
        'spaceBetween'    => 0,
        'speed'           => 500,
        'loop'            => true,
        'watchOverflow'   => true,
        'observer'        => true,
        'observeParents'  => true,
        'allowTouchMove'  => false,
        'autoplay'        => [
            'delay'                => 3000,
            'disableOnInteraction' => false,
            'pauseOnMouseEnter'    => true,
        ],
    ];

    /**
     * @param  array<int, mixed>  $notices
     */
    public function __construct(array $notices = [])
    {
        parent::__construct();

        $this->class('dcat-marquee-notice', true);
        $this->notices($notices);
    }

    /**
     * 添加一条通知。
     *
     * @param  string  $message
     * @param  string|null  $url
     * @param  string  $target
     * @return $this
     */
    public function add($message, $url = null, string $target = '_self')
    {
        $message = htmlspecialchars((string) $message, ENT_QUOTES, 'UTF-8');
        $content = '<div class="dcat-marquee-notice__item"><i class="feather icon-bell"></i><span>'.$message.'</span></div>';

        if ($url && $this->isSafeUrl((string) $url)) {
            $target = $target === '_blank' ? '_blank' : '_self';
            $content = '<a class="dcat-marquee-notice__link" href="'.htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8').'" target="'.$target.'"'.($target === '_blank' ? ' rel="noopener noreferrer"' : '').'>'.$content.'</a>';
        }

        return parent::add($content);
    }

    /**
     * 批量添加通知。
     *
     * 每项可传字符串，或关联数组：
     * ['message' => '通知内容', 'url' => '/admin/xxx', 'target' => '_blank']。
     *
     * @param  array<int, mixed>  $notices
     * @return $this
     */
    public function notices(array $notices)
    {
        foreach ($notices as $notice) {
            if (is_array($notice)) {
                $this->add(
                    $notice['message'] ?? ($notice[0] ?? ''),
                    $notice['url'] ?? ($notice[1] ?? null),
                    $notice['target'] ?? ($notice[2] ?? '_self')
                );
            } else {
                $this->add($notice);
            }
        }

        return $this;
    }

    /**
     * 只允许站内相对地址、锚点或 HTTP(S) 地址，避免通知链接执行脚本。
     */
    protected function isSafeUrl(string $url): bool
    {
        return (bool) preg_match('~^(?:https?://|/|#)~i', $url);
    }
}
