<?php

namespace Dcat\Admin\Support;

use Dcat\Admin\Admin;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Issues short-lived tokens for internal component routes.
 *
 * The token deliberately authorizes a framework capability rather than a URL.
 * It is bound to the current administrator and panel, so copied component URLs
 * cannot be reused by another account or another admin application.
 */
class InternalRouteToken
{
    public const PARAMETER = '_dcat_internal_token';

    public function issue(string $scope, array $claims = [], ?int $ttl = null): string
    {
        $userId = $this->userIdentifier();
        if ($userId === null) {
            return '';
        }

        $payload = [
            'v'      => 1,
            'uid'    => $userId,
            'app'    => $this->applicationName(),
            'scope'  => $scope,
            'exp'    => time() + max(60, $ttl ?: (int) config('admin.permission.internal.token_ttl', 3600)),
            'claims' => $claims,
        ];
        $encoded = $this->encode(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        return $encoded.'.'.$this->encode(hash_hmac('sha256', $encoded, $this->key(), true));
    }

    public function verify(Request $request, string $scope): ?array
    {
        $token = (string) ($request->input(static::PARAMETER) ?: $request->bearerToken());
        if ($token === '' || substr_count($token, '.') !== 1) {
            return null;
        }

        [$encoded, $signature] = explode('.', $token, 2);
        $expected = $this->encode(hash_hmac('sha256', $encoded, $this->key(), true));
        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $json = $this->decode($encoded);
        $payload = $json === null ? null : json_decode($json, true);
        $userId = $this->userIdentifier();

        if (
            ! is_array($payload)
            || $userId === null
            || (int) ($payload['exp'] ?? 0) < time()
            || (string) ($payload['uid'] ?? '') !== $userId
            || (string) ($payload['app'] ?? '') !== $this->applicationName()
            || ! hash_equals((string) ($payload['scope'] ?? ''), $scope)
        ) {
            return null;
        }

        return (array) ($payload['claims'] ?? []);
    }

    public function append(string $url, string $scope, array $claims = [], ?int $ttl = null): string
    {
        $token = $this->issue($scope, $claims, $ttl);
        if ($token === '') {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query([static::PARAMETER => $token]);
    }

    protected function key(): string
    {
        $key = (string) config('app.key');
        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);
            if ($decoded !== false && $decoded !== '') {
                return $decoded;
            }
        }

        if ($key === '') {
            throw new RuntimeException('APP_KEY is required to sign Dcat internal route tokens.');
        }

        return $key;
    }

    protected function userIdentifier(): ?string
    {
        $user = Admin::user();

        return $user ? (string) $user->getAuthIdentifier() : null;
    }

    protected function applicationName(): string
    {
        return (string) Admin::app()->getName();
    }

    protected function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    protected function decode(string $value): ?string
    {
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode(strtr($value, '-_', '+/'), true);

        return $decoded === false ? null : $decoded;
    }
}
