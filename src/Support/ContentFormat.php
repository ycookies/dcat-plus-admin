<?php

namespace Dcat\Admin\Support;

use Dcat\Admin\Widgets\Markdown;

/**
 * 富文本内容格式识别与渲染。
 *
 * 后台同时存在 HTML 编辑器（Form\Field\Editor，UEditor）与 Markdown 编辑器
 * （Form\Field\Markdown），详情页预览同名字段的内容时，需要先识别格式再选择
 * 渲染方式：HTML 原样输出（等价 unescape），Markdown 交给 Markdown 组件渲染。
 *
 * 识别是启发式的：HTML 富文本编辑器保存的内容总是被块级标签（<p> 等）包裹
 * 并带有 &nbsp;、<br 等指纹，优先按 HTML 判断；混合内容（Markdown 内嵌
 * HTML 块）会误判为 HTML，确知格式时应直接使用 Markdown::make() 或 unescape()。
 */
class ContentFormat
{
    /** 内容是 HTML（富文本编辑器保存）。 */
    const HTML = 'html';

    /** 内容是 Markdown 文本。 */
    const MARKDOWN = 'markdown';

    /**
     * 结构性块级标签：内容以这些标签开头视为 HTML。
     */
    const BLOCK_TAGS = 'p|div|h[1-6]|ul|ol|li|dl|table|thead|tbody|tr|td|th|blockquote|pre|figure|figcaption|section|article|aside|header|footer|main|nav|hr|br|img|iframe|video|audio';

    /**
     * 识别内容格式。
     *
     * @param  mixed  $content
     * @return string static::HTML 或 static::MARKDOWN
     */
    public static function detect($content): string
    {
        $content = trim((string) $content);

        if ($content === '') {
            return static::MARKDOWN;
        }

        // HTML 注释不参与判断
        $body = trim((string) preg_replace('/<!--.*?-->/s', '', $content));

        // HTML 特征：块级标签开头、含块级闭合标签，或 UEditor 常见的 <br / &nbsp;
        if (preg_match('/^<('.static::BLOCK_TAGS.')(\s|>|\/>)/i', $body)
            || preg_match('#</(p|div|h[1-6]|ul|ol|li|dl|table|blockquote|pre|figure|section|article)>#i', $body)
            || str_contains($body, '<br')
            || str_contains($body, '&nbsp;')) {
            return static::HTML;
        }

        // Markdown 特征：标题、围栏代码、行首列表/引用、粗体、链接/图片、分隔线
        if (preg_match('/^#{1,6}\s+\S/m', $body)
            || preg_match('/^(```|~~~)/m', $body)
            || preg_match('/^(\*|-|\+|\d+\.)\s+\S/m', $body)
            || preg_match('/^>\s?\S/m', $body)
            || preg_match('/\*\*[^*\n]+\*\*/', $body)
            || preg_match('/!?\[[^\]\n]+\]\([^)\n]+\)/', $body)
            || preg_match('/^(-{3,}|\*{3,}|_{3,})\s*$/m', $body)) {
            return static::MARKDOWN;
        }

        // 兜底按 Markdown 渲染：纯文本经 Markdown 渲染视觉不变
        return static::MARKDOWN;
    }

    /**
     * 按识别结果渲染内容，可直接嵌入页面。
     *
     * @param  mixed  $content
     * @return string
     */
    public static function render($content): string
    {
        $content = (string) $content;

        if (trim($content) === '') {
            return '';
        }

        if (static::detect($content) === static::HTML) {
            return $content;
        }

        return Markdown::make($content)->render();
    }
}
