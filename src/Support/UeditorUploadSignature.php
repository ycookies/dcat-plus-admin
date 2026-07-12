<?php

namespace Dcat\Admin\Support;

use Dcat\Admin\Admin;

class UeditorUploadSignature
{
    /**
     * Create a short-lived signature that binds an upload target to its current admin user.
     */
    public static function make(?string $disk, ?string $directory, int $expires): string
    {
        return hash_hmac('sha256', static::payload($disk, $directory, $expires), config('app.key'));
    }

    /**
     * Verify that an upload target was produced by a server-rendered editor field.
     */
    public static function verify(?string $disk, ?string $directory, int $expires, ?string $signature): bool
    {
        return $signature
            && $expires >= time()
            && hash_equals(static::make($disk, $directory, $expires), $signature);
    }

    /**
     * Normalize a user-provided directory before signing or comparing it.
     */
    public static function normalizeDirectory(?string $directory): string
    {
        return trim((string) $directory, '/');
    }

    protected static function payload(?string $disk, ?string $directory, int $expires): string
    {
        $user = Admin::user();

        return implode('|', [
            (string) $disk,
            static::normalizeDirectory($directory),
            $expires,
            $user ? $user->getAuthIdentifier() : '',
        ]);
    }
}
