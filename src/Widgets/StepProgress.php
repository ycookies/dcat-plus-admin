<?php

namespace Dcat\Admin\Widgets;

use Dcat\Admin\Admin;
use Illuminate\Support\Arr;

class StepProgress extends Widget
{
    const STYLE_DOT = 'dot';
    const STYLE_CHECK = 'check';
    const STYLE_ARROW = 'arrow';

    /**
     * @var string
     */
    protected $view = 'admin::widgets.step-progress';

    /**
     * 步骤数据，每项：[num 序号(数据驱动，原样展示), title 标题, description 描述(悬浮 tips)]
     *
     * @var array
     */
    protected $steps = [];

    /**
     * 当前进行中的步骤（1-based 位置）
     *
     * @var int
     */
    protected $current = 1;

    /**
     * 展示风格：dot 圆点连线 / check 打勾 / arrow 箭头分段
     *
     * @var string
     */
    protected $style = self::STYLE_DOT;

    /**
     * @param  array  $steps  支持字符串数组（回退位置序号）或 [num, title, description] 数组
     * @param  int  $current
     * @param  string  $style
     */
    public function __construct(array $steps = [], int $current = 1, string $style = self::STYLE_DOT)
    {
        $this->id('step-progress-'.uniqid());

        if ($steps) {
            $this->steps($steps);
        }

        $this->current = max(1, $current);
        $this->style = in_array($style, [static::STYLE_DOT, static::STYLE_CHECK, static::STYLE_ARROW], true)
            ? $style : static::STYLE_DOT;

        // 主题色注入为 CSS 变量，跟随后台主题色与深色模式
        $primary = Admin::color()->get('primary', '#586cb1');
        $this->setHtmlAttribute('style', '--sp-primary: '.$primary.';--sp-primary-halo: '.$this->formatHalo($primary).';');
    }

    /**
     * 批量设置步骤.
     *
     * @param  array  $steps
     * @return $this
     */
    public function steps(array $steps)
    {
        $this->steps = array_merge($this->steps, array_values($steps));

        return $this;
    }

    /**
     * 流式添加一步；$num 不传时回退为位置序号.
     *
     * @param  string  $title
     * @param  string  $description  悬浮 tips 提示内容
     * @param  string|int  $num  序号，数据驱动原样展示
     * @return $this
     */
    public function add(string $title, string $description = '', $num = null)
    {
        $this->steps[] = ['num' => $num, 'title' => $title, 'description' => $description];

        return $this;
    }

    /**
     * 设置当前进行中的步骤（1-based 位置）.
     *
     * @param  int  $current
     * @return $this
     */
    public function current(int $current)
    {
        $this->current = max(1, $current);

        return $this;
    }

    /**
     * 设置展示风格：dot（默认）/ check / arrow.
     *
     * @param  string  $style
     * @return $this
     */
    public function style(string $style)
    {
        if (in_array($style, [static::STYLE_DOT, static::STYLE_CHECK, static::STYLE_ARROW], true)) {
            $this->style = $style;
        }

        return $this;
    }

    /**
     * 获取所有视图变量.
     *
     * @return array
     */
    public function defaultVariables()
    {
        $steps = [];
        foreach ($this->steps as $index => $step) {
            if (is_array($step)) {
                $num = Arr::get($step, 'num', $index + 1);
                $title = (string) Arr::get($step, 'title', '');
                $description = (string) Arr::get($step, 'description', '');
            } else {
                $num = $index + 1;
                $title = (string) $step;
                $description = '';
            }

            $position = $index + 1;
            $state = 'todo';
            if ($position < $this->current) {
                $state = 'done';
            } elseif ($position === $this->current) {
                $state = 'current';
            }

            $steps[] = compact('num', 'title', 'description', 'state');
        }

        return array_merge(parent::defaultVariables(), [
            'id'    => $this->getHtmlAttribute('id'),
            'steps' => $steps,
            'style' => $this->style,
        ]);
    }

    /**
     * 主题色 hex 转 rgba 光环色.
     *
     * @param  string  $hex
     * @param  float  $alpha
     * @return string
     */
    protected function formatHalo(string $hex, float $alpha = 0.15): string
    {
        $hex = ltrim(trim($hex), '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return 'rgba(92, 107, 192, '.$alpha.')';
        }

        [$r, $g, $b] = array_map('hexdec', str_split($hex, 2));

        return 'rgba('.$r.', '.$g.', '.$b.', '.$alpha.')';
    }
}
