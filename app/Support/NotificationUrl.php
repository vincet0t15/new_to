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
    public const SOURCE_KEY = 'from';

    public const SOURCE_VALUE = 'notification';

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

    /**
     * Mark a document link as coming from the notification inbox.
     *
     * The marker lets the document route offer a friendly fallback only when a
     * stale notification target is opened. A direct visit to the same URL keeps
     * its normal forbidden/not-found response.
     */
    public static function forNotificationOpen(mixed $url): string
    {
        $path = self::toLocalPath($url);
        $fragment = '';

        if (str_contains($path, '#')) {
            [$path, $fragment] = explode('#', $path, 2);
            $fragment = '#'.$fragment;
        }

        $separator = str_contains($path, '?') ? '&' : '?';

        return $path.$separator.self::SOURCE_KEY.'='.self::SOURCE_VALUE.$fragment;
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
