<?php

namespace Dcatplus\Admin\Support;

use Dcatplus\Admin\Admin;
use Dcatplus\Admin\Contracts\LazyRenderable as Renderable;
use Dcatplus\Admin\Traits\LazyWidget;

abstract class LazyRenderable implements Renderable
{
    use LazyWidget;

    protected static $js = [];

    protected static $css = [];

    public function __construct(array $payload = [])
    {
        $this->payload($payload);
    }

    public static function make(...$params)
    {
        return new static(...$params);
    }

    public static function requireAssets()
    {
        Admin::js(static::$js);
        Admin::css(static::$css);
    }

    public function __get($name)
    {
        return $this->payload[$name] ?? null;
    }
}
