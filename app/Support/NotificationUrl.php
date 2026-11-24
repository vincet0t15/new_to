<?php

namespace App\Support;

/**
 * Normalizes stored notification links for in-app navigation.
 *
 * Queued notifications used to write absolute APP_URL links. When the app is
 * opened through another host, following those links from an Inertia visit
 * becomes a cross-origin redirect. Only the local path is needed in-app; email
 * still uses the absolute link.
 */
final class NotificationUrl
{
    public static function toLocalPath(mixed $url): string
    {
        $fallback = route('dashboard', [], false);

        if (! is_string($url) || trim($url) === '') {
            return $fallback;
        }

        $value = trim($url);

        if (str_starts_with($value, '//')) {
            return $fallback;
        }

        if (str_starts_with($value, '/')) {
            return self::safePath($value, $fallback);
        }

        $parts = parse_url($value);

        if ($parts === false || ! isset($parts['path']) || $parts['path'] === '') {
            return $fallback;
        }

        $scheme = $parts['scheme'] ?? null;

        if (is_string($scheme) && ! in_array(strtolower($scheme), ['http', 'https'], true)) {
            return $fallback;
        }

        $path = $parts['path'];

        if (isset($parts['query']) && $parts['query'] !== '') {
            $path .= '?'.$parts['query'];
        }

        return self::safePath($path, $fallback);
    }

    private static function safePath(string $path, string $fallback): string
    {
        if (! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return $fallback;
        }

        if (preg_match('/[\x00-\x1F\\\\]|%5c/i', $path) === 1) {
            return $fallback;
        }

        return $path;
    }
}
