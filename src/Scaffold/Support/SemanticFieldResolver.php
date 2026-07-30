<?php

namespace Dcat\Admin\Scaffold\Support;

/**
 * Resolves a database field into a conservative scaffold presentation type.
 *
 * Presentation directives may be stored in a column comment, for example:
 * `封面图 @scaffold:type=image` or
 * `发布状态 @scaffold:options={"0":"草稿","1":"已发布"}`.
 */
class SemanticFieldResolver
{
    public const TYPES = [
        'auto', 'text', 'long_text', 'integer', 'decimal', 'image', 'file',
        'url', 'email', 'phone', 'password', 'color', 'status', 'boolean',
        'date', 'datetime', 'time', 'json',
    ];

    /**
     * @param  array<string, mixed>  $field
     * @return array{type:string,label:string,options:array,source:string}
     */
    public static function resolve(array $field): array
    {
        $name = strtolower((string) ($field['name'] ?? ''));
        $comment = trim((string) ($field['comment'] ?? ''));
        $label = trim((string) ($field['translation'] ?? ''));
        $type = strtolower((string) ($field['source_type'] ?? $field['type'] ?? ''));
        $options = self::parseOptions($comment);
        if (! $options && ! empty($field['semantic_options'])) {
            $options = self::parseOptionJson((string) $field['semantic_options']);
        }
        $comment = self::stripDirectives($comment);

        if ($label === '') {
            $label = $comment !== '' ? $comment : (string) ($field['name'] ?? '');
        }

        $semanticType = self::normalizeType($field['semantic_type'] ?? null);
        if ($semanticType !== null && $semanticType !== 'auto') {
            return self::result($semanticType, $label, $options, 'manual');
        }

        $directiveType = self::parseTypeDirective((string) ($field['comment'] ?? ''));
        if ($directiveType !== null) {
            return self::result($directiveType, $label, $options, 'comment');
        }

        if ($options) {
            return self::result('status', $label, $options, 'comment-options');
        }

        if (preg_match('/\b(json|jsonb)\b/', $type)) {
            return self::result('json', $label, [], 'database-type');
        }
        if (preg_match('/\b(date)\b/', $type) && ! str_contains($type, 'datetime')) {
            return self::result('date', $label, [], 'database-type');
        }
        if (preg_match('/\b(datetime|timestamp|timestamptz)\b/', $type)) {
            return self::result('datetime', $label, [], 'database-type');
        }
        if (preg_match('/\b(time|timetz)\b/', $type)) {
            return self::result('time', $label, [], 'database-type');
        }
        if (preg_match('/\b(bool|boolean)\b/', $type)
            || in_array($type, ['boolean'], true)
            || preg_match('/\btinyint\s*\(\s*1\s*\)/', $type)) {
            return self::result('boolean', $label, [], 'database-type');
        }
        if (preg_match('/\b(decimal|numeric|float|double|real|money)\b/', $type)) {
            return self::result('decimal', $label, [], 'database-type');
        }
        if (preg_match('/\b(text|mediumtext|longtext|clob)\b/', $type)) {
            return self::result('long_text', $label, [], 'database-type');
        }

        if (self::matches($name, ['password', 'passwd', 'pwd'])) {
            return self::result('password', $label, [], 'field-name');
        }
        if (self::matches($name, ['email', 'e_mail'])) {
            return self::result('email', $label, [], 'field-name');
        }
        if (self::matches($name, ['mobile', 'phone', 'tel', 'telephone'])) {
            return self::result('phone', $label, [], 'field-name');
        }
        if (self::matches($name, ['url', 'website', 'homepage', 'link'])) {
            return self::result('url', $label, [], 'field-name');
        }
        if (self::matches($name, ['color', 'colour'])) {
            return self::result('color', $label, [], 'field-name');
        }
        if (self::matches($name, ['img', 'image', 'avatar', 'cover', 'banner', 'logo', 'thumbnail', 'photo', 'pic'])) {
            return self::result('image', $label, [], 'field-name');
        }
        if (self::matches($name, ['file', 'attachment', 'document'])) {
            return self::result('file', $label, [], 'field-name');
        }
        if (preg_match('/^(is|has)_/', $name) || in_array($name, ['enabled', 'visible', 'active'], true)) {
            if (preg_match('/\b(tinyint|tinyinteger|smallint|smallinteger|mediumint|mediuminteger|bigint|biginteger|unsignedtinyinteger|unsignedsmallinteger|unsignedmediuminteger|unsignedinteger|unsignedbiginteger|int|integer)\b/', $type)) {
                return self::result('boolean', $label, [], 'field-name');
            }
        }
        if (preg_match('/\b(tinyint|tinyinteger|smallint|smallinteger|mediumint|mediuminteger|bigint|biginteger|unsignedtinyinteger|unsignedsmallinteger|unsignedmediuminteger|unsignedinteger|unsignedbiginteger|int|integer|serial)\b/', $type)) {
            return self::result('integer', $label, [], 'database-type');
        }

        return self::result('text', $label, [], 'fallback');
    }

    public static function validFieldName(string $name): bool
    {
        return (bool) preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $name);
    }

    /**
     * Format a scalar or option map as safe PHP source.
     *
     * @param mixed $value
     */
    public static function export($value): string
    {
        return var_export($value, true);
    }

    /**
     * @param mixed $type
     */
    protected static function normalizeType($type): ?string
    {
        if (! is_string($type)) {
            return null;
        }

        $type = strtolower(trim($type));

        return in_array($type, self::TYPES, true) ? $type : null;
    }

    protected static function parseTypeDirective(string $comment): ?string
    {
        if (! preg_match('/@scaffold:type=([a-z_]+)/i', $comment, $matches)) {
            return null;
        }

        $type = self::normalizeType($matches[1]);

        return $type === 'auto' ? null : $type;
    }

    /**
     * @return array<int|string, int|string>
     */
    protected static function parseOptions(string $comment): array
    {
        if (! preg_match('/@scaffold:options=(\{.*?\})/s', $comment, $matches)) {
            return [];
        }

        return self::parseOptionJson($matches[1]);
    }

    /**
     * @return array<int|string, int|string>
     */
    protected static function parseOptionJson(string $json): array
    {
        try {
            $options = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            return [];
        }

        if (! is_array($options) || count($options) > 50) {
            return [];
        }

        foreach ($options as $key => $value) {
            if ((! is_int($key) && ! is_string($key)) || (! is_int($value) && ! is_string($value))) {
                return [];
            }
            if (mb_strlen((string) $key) > 64 || mb_strlen((string) $value) > 255) {
                return [];
            }
        }

        return $options;
    }

    protected static function stripDirectives(string $comment): string
    {
        $comment = preg_replace('/\s*@scaffold:type=[a-z_]+/i', '', $comment);
        $comment = preg_replace('/\s*@scaffold:options=\{.*?\}/s', '', (string) $comment);

        return trim((string) $comment);
    }

    /**
     * @param array<int, string> $terms
     */
    protected static function matches(string $name, array $terms): bool
    {
        foreach ($terms as $term) {
            if ($name === $term || str_ends_with($name, '_'.$term) || str_starts_with($name, $term.'_')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<int|string, int|string> $options
     * @return array{type:string,label:string,options:array,source:string}
     */
    protected static function result(string $type, string $label, array $options, string $source): array
    {
        return compact('type', 'label', 'options', 'source');
    }
}
