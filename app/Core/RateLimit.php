<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Fixed-window rate limiting on the `rate_limits` table. MySQL-backed on
 * purpose — no Redis assumption on shared hosting.
 *
 * Each call names its own limit/window rather than pulling from a shared
 * bucket table, because the actual limits are meant to live in .env
 * (OTP_RATE_LIMIT_PER_HOUR, FOODS_SEARCH_DAILY_CAP_GUEST, ...), not be
 * hardcoded here.
 */
final class RateLimit
{
    /**
     * Record one hit in the current fixed window for $bucket:$key.
     *
     * @return int|null seconds until the window resets, or null when allowed
     */
    public static function hit(string $bucket, string $key, int $limit, int $windowSeconds): ?int
    {
        $windowStart = self::windowStart($windowSeconds);
        $bucketKey   = $bucket . ':' . substr($key, 0, 140);

        Db::exec(
            'INSERT INTO rate_limits (bucket_key, window_start, count) VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE count = count + 1',
            [$bucketKey, $windowStart]
        );

        $count = (int) Db::value(
            'SELECT count FROM rate_limits WHERE bucket_key = ? AND window_start = ?',
            [$bucketKey, $windowStart]
        );

        if ($count > $limit) {
            return max(1, $windowSeconds - (time() - strtotime($windowStart)));
        }

        return null;
    }

    private static function windowStart(int $windowSeconds): string
    {
        $aligned = intdiv(time(), $windowSeconds) * $windowSeconds;
        return date('Y-m-d H:i:s', $aligned);
    }

    /** "৫৯ মিনিট" / "৪৫ সেকেন্ড" — turns a raw second count into rate-limit copy. */
    public static function humanWait(int $seconds): string
    {
        if ($seconds < 60) {
            return bn_num(max(1, $seconds)) . ' সেকেন্ড';
        }
        if ($seconds < 3600) {
            return bn_num((int) ceil($seconds / 60)) . ' মিনিট';
        }
        return bn_num((int) ceil($seconds / 3600)) . ' ঘণ্টা';
    }
}
