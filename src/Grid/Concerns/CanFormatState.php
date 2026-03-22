<?php

namespace Dcat\Admin\Grid\Concerns;

use Closure;
use Dcat\Admin\Grid;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

trait CanFormatState
{
    /**
     * 格式化状态的回调函数
     * @var Closure|null
     */
    protected ?Closure $formatStateUsing = null;

    /**
     * 字符限制数量
     * @var int|Closure|null
     */
    protected int | Closure | null $characterLimit = null;

    /**
     * 字符限制后的结尾字符
     * @var string|Closure|null
     */
    protected string | Closure | null $characterLimitEnd = null;

    /**
     * 单词限制数量
     * @var int|Closure|null
     */ 
    protected int | Closure | null $wordLimit = null;

    /**
     * 单词限制后的结尾字符
     * @var string|Closure|null
     */
    protected string | Closure | null $wordLimitEnd = null;

    /**
     * 前缀
     * @var string|Htmlable|Closure|null
     */
    protected string | Htmlable | Closure | null $prefix = null;

    /**
     * 后缀
     * @var string|Htmlable|Closure|null
     */
    protected string | Htmlable | Closure | null $suffix = null;

    /**
     * 时区设置
     * @var string|Closure|null
     */
    protected string | Closure | null $timezone = null;

    /**
     * 是否作为HTML渲染
     * @var bool|Closure
     */
    protected bool | Closure $isHtml = false;

    /**
     * 是否作为Markdown渲染
     * @var bool|Closure
     */
    protected bool | Closure $isMarkdown = false;

    /**
     * 是否为日期类型
     * @var bool
     */
    protected bool $isDate = false;

    /**
     * 是否为日期时间类型
     * @var bool
     */
    protected bool $isDateTime = false;

    /**
     * 是否为货币类型
     * @var bool
     */
    protected bool $isMoney = false;

    /**
     * 是否为数字类型
     * @var bool
     */
    protected bool $isNumeric = false;

    /**
     * 是否为时间类型
     * @var bool
     */
    protected bool $isTime = false;
    

    /**
     * 设置日期格式化
     * @param string|null $format 日期格式
     * @param string|null $timezone 时区
     * @return $this
     */
    public function date(?string $format = null, ?string $timezone = null): static
    {
        $this->isDate = true;

        $format ??= Grid::$defaultDateDisplayFormat;

        return $this->display(function ($value) use ($format,$timezone) {
            if (! $value) {
                return $value;
            }

            return Carbon::parse($value)
                ->setTimezone($timezone ?? config('app.timezone'))
                ->translatedFormat($format);
        });
    }

    /**
     * 设置日期时间格式化
     * @param string|null $format 日期时间格式
     * @param string|null $timezone 时区
     * @return $this
     */
    public function dateTime(?string $format = null, ?string $timezone = null): static
    {
        $this->isDateTime = true;

        $format ??= Grid::$defaultDateTimeDisplayFormat;

        $this->date($format, $timezone);

        return $this;
    }

    /**
     * 设置相对时间显示(例如:3小时前)
     * @param string|null $timezone 时区
     * @return $this
     */
    public function since(?string $timezone = null)
    {
        $this->isDateTime = true;


        return $this->display(function ($value) use ($timezone) {
            if (! $value) {
                return $value;
            }

            return Carbon::parse($value)
                ->setTimezone($timezone ?? config('app.timezone'))
                ->diffForHumans();
        });
    }
    public function time(?string $format = null, ?string $timezone = null): static
    {
        $this->isTime = true;

        $format ??= Grid::$defaultTimeDisplayFormat;

        $this->date($format, $timezone);

        return $this;
    }

    public function timezone(string | Closure | null $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function prefix(string | Htmlable | Closure | null $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function suffix(string | Htmlable | Closure | null $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }
}
