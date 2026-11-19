<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;

/**
 * 时刻（小时）选择器：点击弹层、按时段分组（凌晨/上午/下午/晚上）平铺时刻小按钮。
 * 值 = 「HH:MM」（与 time 字段同格式，后端零改动）；选中回填触发框并高亮按钮，
 * 已有值（default/编辑回显）渲染时即高亮；支持清空（存空字符串）。
 *
 * 链式配置：
 *   ->step(10)     档位间隔（分钟），默认 60（整小时），可选 10/15/30 等（须能整除 60）
 *   ->hideSmallHours()  隐藏凌晨组（00:00~05:59 不进候选）
 *   ->placeholder('选择时间')  触发框空态文案
 *
 * 用法：$form->hourPicker('open_time', '开门时间')->default('09:00')->step(30)
 */
class HourPicker extends Field
{
    protected $view = 'admin::form.hour-picker';

    /**
     * 触发框空态文案（可用 ->placeholder() 覆写）.
     *
     * @var string
     */
    protected $placeholder = '选择时间';

    /**
     * 档位间隔（分钟）：60=整小时（默认）；可配 10/15/30 等，须能整除 60.
     *
     * @var int
     */
    protected int $stepMinutes = 60;

    /**
     * 是否显示凌晨组（00:00~05:59），默认显示.
     *
     * @var bool
     */
    protected bool $showSmallHours = true;

    /**
     * 隐藏凌晨组（0 点~5 点不进候选；选中值恰在凌晨时回显文本仍保留，仅按钮不出现）.
     */
    public function hideSmallHours(): static
    {
        $this->showSmallHours = false;

        return $this;
    }

    /**
     * 配置档位间隔（分钟），非法值回退整小时.
     */
    public function step(int $minutes): static
    {
        $this->stepMinutes = ($minutes >= 5 && $minutes <= 60 && 60 % $minutes === 0) ? $minutes : 60;

        return $this;
    }

    /**
     * 生成的候选时刻列表.
     */
    protected function slotOptions(): array
    {
        $slots = [];
        for ($minute = 0; $minute < 60 * 24; $minute += $this->stepMinutes) {
            $slots[] = sprintf('%02d:%02d', intdiv($minute, 60), $minute % 60);
        }

        return $slots;
    }

    /**
     * 传给 view 的变量（官方扩展字段手法：覆写 variables()）.
     */
    public function variables(): array
    {
        $slots = $this->slotOptions();
        if (! $this->showSmallHours) {
            $slots = array_values(array_filter($slots, fn ($s) => (int) substr($s, 0, 2) >= 6));
        }

        return parent::variables() + ['slots' => $slots];
    }

    public function render()
    {
        // Field 无元素 id 体系：自造唯一锚点经 attribute 烘进 view（column 净化 + uniqid 防同列多实例撞车）
        $domId = 'hp_'.preg_replace('/\W+/', '_', (string) $this->column()).'_'.uniqid();
        $this->attribute('data-hp-id', $domId);

        $emptyText = addslashes($this->placeholder());

        $this->script = <<<JS
(function () {
    var \$box = $('[data-hp-id="{$domId}"]');
    if (!\$box.length) return;
    var \$panel = \$box.find('.hp-panel');
    \$box.find('.hp-trigger').on('click', function (e) {
        e.preventDefault();
        \$panel.toggle();
    });
    \$box.find('.hp-btn').on('click', function () {
        var v = String($(this).data('h'));
        \$box.find('.hp-btn').removeClass('active');
        $(this).addClass('active');
        \$box.find('.hp-val').val(v);
        \$box.find('.hp-text').text(v);
        \$panel.hide();
    });
    \$box.find('.hp-clear').on('click', function () {
        \$box.find('.hp-val').val('');
        \$box.find('.hp-btn').removeClass('active');
        \$box.find('.hp-text').text('{$emptyText}');
        \$panel.hide();
    });
    $(document).on('click', function (e) {
        if (!$(e.target).closest('[data-hp-id="{$domId}"]').length) {
            \$panel.hide();
        }
    });
})();
JS;

        return parent::render();
    }
}
