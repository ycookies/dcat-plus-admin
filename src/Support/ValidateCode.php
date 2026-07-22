<?php

namespace Dcat\Admin\Support;

use Dcat\Admin\Exception\InvalidArgumentException;
use Illuminate\Contracts\Session\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * 基于 GD 的图形验证码生成器。
 *
 * 使用 Admin::validateCode($options) 可获得已应用 admin.validate_code
 * 默认配置的实例。生成图片时不会直接输出 header，适用于 Laravel 控制器、
 * API 和需要嵌入 data URI 的场景。
 */
class ValidateCode
{
    /** @var array<string, mixed> */
    protected const DEFAULTS = [
        // 排除 0/O、1/I/l 等容易混淆的字符。
        'charset' => '23456789ABCDEFGHJKMNPQRSTUVWXYZ',
        'length' => 4,
        'width' => 120,
        'height' => 42,
        // null 时使用扩展包内置的 Elephant.ttf。
        'font' => null,
        // null 时根据验证码高度自动计算。
        'font_size' => null,
        'background' => [248, 250, 252],
        'text_colors' => [
            [30, 64, 175],
            [15, 118, 110],
            [126, 34, 206],
            [180, 83, 9],
        ],
        'line_color' => [203, 213, 225],
        'dot_color' => [148, 163, 184],
        // 雪花干扰强度：1 为默认强度，2-10 逐级增强。
        'noise_level' => 1,
        'line_count' => 5,
        'dot_count' => 28,
        // false 时校验忽略字符大小写。
        'case_sensitive' => false,
        // 图形输出后默认写入 Session 的键名和有效期（秒）。
        'session_key' => 'dcat.validate_code',
        'ttl' => 300,
        // PNG 0（最快）至 9（最小）。
        'png_compression' => 6,
    ];

    /** @var array<string, mixed> */
    protected array $options;

    protected ?string $code = null;

    /**
     * @param  array<string, mixed>  $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_replace(self::DEFAULTS, $options);
        $this->validateOptions();
    }

    /**
     * 返回应用新参数后的新实例，避免复用实例时污染已生成的验证码。
     *
     * @param  array<string, mixed>  $options
     */
    public function withOptions(array $options): static
    {
        return new static(array_replace($this->options, $options));
    }

    /**
     * 就地更新参数，并使当前验证码失效。
     *
     * @param  array<string, mixed>  $options
     */
    public function options(array $options): static
    {
        $this->options = array_replace($this->options, $options);
        $this->validateOptions();
        $this->code = null;

        return $this;
    }

    /**
     * 生成并返回 PNG 二进制数据，不会写入 Session 或输出 HTTP header。
     */
    public function render(): string
    {
        $this->ensureGdAvailable();
        $image = $this->createImage();

        try {
            $bufferLevel = ob_get_level();
            ob_start();

            try {
                $written = imagepng($image, null, $this->options['png_compression']);
                $contents = ob_get_clean();
            } catch (\Throwable $exception) {
                while (ob_get_level() > $bufferLevel) {
                    ob_end_clean();
                }

                throw $exception;
            }

            if (! $written || $contents === false) {
                throw new \RuntimeException('Unable to encode the validation code image as PNG.');
            }

            return $contents;
        } finally {
            imagedestroy($image);
        }
    }

    /**
     * 返回可直接在控制器中 return 的 PNG 响应。
     *
     * @param  bool  $store  是否把本次验证码写入当前会话
     */
    public function response(bool $store = true): Response
    {
        if ($store) {
            $this->store();
        }

        return response($this->render(), Response::HTTP_OK, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * 向后兼容旧的 doimg() 调用；现在请在控制器中使用 return doimg()。
     */
    public function doimg(): Response
    {
        return $this->response();
    }

    /**
     * 生成用于 img src 的 data URI。
     */
    public function dataUri(): string
    {
        return 'data:image/png;base64,'.base64_encode($this->render());
    }

    /**
     * 获取当前验证码；首次调用时会安全地生成一个新验证码。
     */
    public function getCode(): string
    {
        return $this->code();
    }

    /**
     * getCode() 的语义化别名。
     */
    public function code(): string
    {
        if ($this->code === null) {
            $this->code = $this->createCode();
        }

        return $this->normalized($this->code);
    }

    /**
     * 将验证码摘要写入 Session；不会保存明文验证码。
     */
    public function store(?Session $session = null, ?string $key = null): static
    {
        $session ??= $this->session();
        $key ??= $this->options['session_key'];

        $session->put($key, [
            'hash' => $this->hash($this->code()),
            'expires_at' => time() + $this->options['ttl'],
        ]);

        return $this;
    }

    /**
     * 校验输入验证码。无效、已过期和重复使用的验证码均返回 false。
     *
     * @param  bool  $forget  校验完成后是否立即从 Session 删除，默认 true
     */
    public function check(string $value, ?Session $session = null, ?string $key = null, bool $forget = true): bool
    {
        $session ??= $this->session();
        $key ??= $this->options['session_key'];
        $payload = $session->get($key);

        if ($forget) {
            $session->forget($key);
        }

        if (! is_array($payload) || ! isset($payload['hash'], $payload['expires_at']) || $payload['expires_at'] < time()) {
            return false;
        }

        return hash_equals((string) $payload['hash'], $this->hash($this->normalized($value)));
    }

    /**
     * 获取最终生效的配置，方便调试或封装自己的验证码端点。
     *
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    protected function createImage(): \GdImage
    {
        $width = $this->options['width'];
        $height = $this->options['height'];
        $image = imagecreatetruecolor($width, $height);

        if ($image === false) {
            throw new \RuntimeException('Unable to create the validation code image.');
        }

        $background = $this->color($image, $this->options['background']);
        imagefill($image, 0, 0, $background);

        $this->drawCode($image);
        // 干扰必须在文字之后绘制，才能真正遮挡字符轮廓并提高识别难度。
        $this->drawNoise($image, $this->options['noise_level']);

        return $image;
    }

    protected function drawNoise(\GdImage $image, int $level): void
    {
        $width = $this->options['width'];
        $height = $this->options['height'];
        $lineColor = $this->color($image, $this->options['line_color']);
        $dotColor = $this->color($image, $this->options['dot_color']);

        for ($index = 0, $count = $this->options['line_count'] * $level; $index < $count; $index++) {
            imageline(
                $image,
                random_int(0, $width - 1),
                random_int(0, $height - 1),
                random_int(0, $width - 1),
                random_int(0, $height - 1),
                $lineColor
            );
        }

        for ($index = 0, $count = $this->options['dot_count'] * $level; $index < $count; $index++) {
            $size = random_int(1, $level >= 5 ? 2 : 1);
            $x = random_int($size, $width - 1 - $size);
            $y = random_int($size, $height - 1 - $size);

            // 单像素在高分辨率屏幕上几乎不可见；绘制短十字让雪花干扰真实可见。
            imagefilledellipse($image, $x, $y, ($size * 2) + 1, ($size * 2) + 1, $dotColor);
            imageline($image, $x - $size, $y, $x + $size, $y, $dotColor);
            imageline($image, $x, $y - $size, $x, $y + $size, $dotColor);
        }
    }

    protected function drawCode(\GdImage $image): void
    {
        $font = $this->fontPath();
        $code = $this->code();
        $length = strlen($code);
        $fontSize = min($this->options['font_size'] ?? max(16, (int) floor($this->options['height'] * 0.58)), $this->options['height'] - 8);
        $slotWidth = $this->options['width'] / $length;
        $colors = $this->options['text_colors'];

        foreach (str_split($code) as $index => $character) {
            $color = $this->color($image, $colors[array_rand($colors)]);
            $x = (int) max(2, ($slotWidth * $index) + random_int(2, max(2, (int) floor($slotWidth * 0.18))));
            $y = (int) (($this->options['height'] + $fontSize) / 2) + random_int(-2, 3);
            $angle = random_int(-20, 20);

            if (imagettftext($image, $fontSize, $angle, $x, $y, $color, $font, $character) === false) {
                throw new \RuntimeException('Unable to render validation code text. Check the configured font file.');
            }
        }
    }

    protected function createCode(): string
    {
        $characters = $this->options['charset'];
        $last = strlen($characters) - 1;
        $code = '';

        for ($index = 0; $index < $this->options['length']; $index++) {
            $code .= $characters[random_int(0, $last)];
        }

        return $code;
    }

    protected function fontPath(): string
    {
        $font = $this->options['font'] ?: dirname(__DIR__, 2).'/fonts/Elephant.ttf';

        if (! is_string($font) || ! is_file($font) || ! is_readable($font)) {
            throw new \RuntimeException(sprintf('Validation code font is not readable: %s', (string) $font));
        }

        return $font;
    }

    protected function color(\GdImage $image, array $color): int
    {
        return imagecolorallocate($image, $color[0], $color[1], $color[2]);
    }

    protected function normalized(string $code): string
    {
        return $this->options['case_sensitive'] ? $code : strtoupper($code);
    }

    protected function hash(string $code): string
    {
        $key = (string) config('app.key', 'dcat-validate-code');

        return hash_hmac('sha256', $code, $key);
    }

    protected function session(): Session
    {
        try {
            $request = app('request');
        } catch (\Throwable) {
            throw new \RuntimeException('A Laravel request session is required to store or validate the validation code.');
        }

        if (! method_exists($request, 'session') || ! $request->hasSession()) {
            throw new \RuntimeException('A Laravel request session is required to store or validate the validation code.');
        }

        return $request->session();
    }

    protected function ensureGdAvailable(): void
    {
        foreach (['imagecreatetruecolor', 'imagepng', 'imagefilledellipse', 'imagettftext'] as $function) {
            if (! function_exists($function)) {
                throw new \RuntimeException('The GD extension with FreeType support is required to generate validation code images.');
            }
        }
    }

    protected function validateOptions(): void
    {
        foreach ([
            'length' => [3, 8],
            'width' => [80, 600],
            'height' => [32, 180],
            'noise_level' => [1, 10],
            'line_count' => [0, 20],
            'dot_count' => [0, 300],
            'ttl' => [60, 3600],
            'png_compression' => [0, 9],
        ] as $option => [$minimum, $maximum]) {
            if (filter_var($this->options[$option], FILTER_VALIDATE_INT) === false || $this->options[$option] < $minimum || $this->options[$option] > $maximum) {
                throw new InvalidArgumentException(sprintf('The validation code option [%s] must be an integer between %d and %d.', $option, $minimum, $maximum));
            }

            $this->options[$option] = (int) $this->options[$option];
        }

        if (! is_string($this->options['charset']) || ! preg_match('/^[\x21-\x7E]+$/', $this->options['charset']) || strlen($this->options['charset']) < $this->options['length']) {
            throw new InvalidArgumentException('The validation code charset must contain enough printable ASCII characters.');
        }

        if (! is_null($this->options['font_size']) && (! filter_var($this->options['font_size'], FILTER_VALIDATE_INT) || $this->options['font_size'] < 10 || $this->options['font_size'] > 96)) {
            throw new InvalidArgumentException('The validation code option [font_size] must be null or an integer between 10 and 96.');
        }

        if ($this->options['font_size'] !== null) {
            $this->options['font_size'] = (int) $this->options['font_size'];
        }

        if (! is_bool($this->options['case_sensitive'])) {
            throw new InvalidArgumentException('The validation code option [case_sensitive] must be boolean.');
        }

        if (! is_string($this->options['session_key']) || $this->options['session_key'] === '') {
            throw new InvalidArgumentException('The validation code option [session_key] must be a non-empty string.');
        }

        if (! is_null($this->options['font']) && ! is_string($this->options['font'])) {
            throw new InvalidArgumentException('The validation code option [font] must be a file path or null.');
        }

        $this->options['background'] = $this->validateColor($this->options['background'], 'background');
        $this->options['line_color'] = $this->validateColor($this->options['line_color'], 'line_color');
        $this->options['dot_color'] = $this->validateColor($this->options['dot_color'], 'dot_color');

        if (! is_array($this->options['text_colors']) || $this->options['text_colors'] === []) {
            throw new InvalidArgumentException('The validation code option [text_colors] must contain at least one RGB color.');
        }

        $this->options['text_colors'] = array_values(array_map(fn ($color) => $this->validateColor($color, 'text_colors'), $this->options['text_colors']));
    }

    /**
     * @param  mixed  $color
     * @return array{0: int, 1: int, 2: int}
     */
    protected function validateColor(mixed $color, string $option): array
    {
        if (! is_array($color) || count($color) !== 3) {
            throw new InvalidArgumentException(sprintf('The validation code option [%s] must be an RGB array.', $option));
        }

        $color = array_values($color);

        foreach ($color as $channel) {
            if (filter_var($channel, FILTER_VALIDATE_INT) === false || $channel < 0 || $channel > 255) {
                throw new InvalidArgumentException(sprintf('The validation code option [%s] contains an invalid RGB value.', $option));
            }
        }

        return [(int) $color[0], (int) $color[1], (int) $color[2]];
    }
}
