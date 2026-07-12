<?php

namespace Dcat\Admin\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Conservative server-side sanitizer for content produced by the UEditor field.
 *
 * Client-side editor filters are not a security boundary: form values can always
 * be submitted directly. This sanitizer intentionally keeps a compact formatting
 * allowlist and removes executable markup and event attributes.
 */
class UeditorHtmlSanitizer
{
    protected const ALLOWED_TAGS = [
        'a', 'b', 'blockquote', 'br', 'code', 'del', 'div', 'em', 'h1', 'h2', 'h3',
        'h4', 'h5', 'h6', 'hr', 'i', 'img', 'li', 'ol', 'p', 'pre', 's', 'span',
        'strong', 'sub', 'sup', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'u', 'ul',
    ];

    protected const DROP_CONTENT_TAGS = [
        'base', 'embed', 'form', 'iframe', 'input', 'link', 'math', 'object', 'script',
        'select', 'style', 'svg', 'textarea', 'video', 'audio', 'source', 'track',
    ];

    protected const ALLOWED_ATTRIBUTES = [
        'a'     => ['href', 'rel', 'target', 'title'],
        'img'   => ['alt', 'height', 'src', 'title', 'width'],
        'ol'    => ['start'],
        'li'    => ['value'],
        'table' => ['border', 'cellpadding', 'cellspacing'],
        'td'    => ['colspan', 'rowspan'],
        'th'    => ['colspan', 'rowspan'],
    ];

    public static function sanitize($html): string
    {
        if (! is_string($html) || $html === '') {
            return (string) $html;
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $document->loadHTML(
            '<!DOCTYPE html><html><body><div id="ueditor-sanitizer-root">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('ueditor-sanitizer-root');
        if (! $root) {
            return '';
        }

        static::sanitizeChildren($root);

        return static::innerHtml($root);
    }

    protected static function sanitizeChildren(DOMNode $node): void
    {
        // Copy before iterating because nodes can be removed or unwrapped.
        $children = [];
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if (! $child instanceof DOMElement) {
                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, static::DROP_CONTENT_TAGS, true)) {
                $child->parentNode->removeChild($child);
                continue;
            }

            if (! in_array($tag, static::ALLOWED_TAGS, true)) {
                static::unwrap($child);
                continue;
            }

            static::sanitizeAttributes($child, $tag);
            static::sanitizeChildren($child);
        }
    }

    protected static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = static::ALLOWED_ATTRIBUTES[$tag] ?? [];
        $attributes = [];

        foreach ($element->attributes as $attribute) {
            $attributes[] = $attribute->name;
        }

        foreach ($attributes as $name) {
            $normalized = strtolower($name);
            if (! in_array($normalized, $allowed, true)) {
                $element->removeAttribute($name);
                continue;
            }

            $value = $element->getAttribute($name);
            if (in_array($normalized, ['href', 'src'], true) && ! static::isSafeUrl($value, $normalized === 'src')) {
                $element->removeAttribute($name);
                continue;
            }

            if (in_array($normalized, ['width', 'height', 'colspan', 'rowspan', 'border', 'cellpadding', 'cellspacing', 'start', 'value'], true)
                && ! ctype_digit($value)) {
                $element->removeAttribute($name);
            }
        }

        if ($tag === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    protected static function isSafeUrl(string $url, bool $isImage): bool
    {
        $url = preg_replace('/[\x00-\x20]+/', '', html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        if ($url === '' || substr($url, 0, 1) === '#' || substr($url, 0, 1) === '/') {
            return true;
        }

        if (! preg_match('/^([a-z][a-z0-9+.-]*):/i', $url, $matches)) {
            return true;
        }

        $allowed = $isImage ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];

        return in_array(strtolower($matches[1]), $allowed, true);
    }

    protected static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }

    protected static function innerHtml(DOMElement $element): string
    {
        $html = '';
        foreach ($element->childNodes as $child) {
            $html .= $element->ownerDocument->saveHTML($child);
        }

        return $html;
    }
}
