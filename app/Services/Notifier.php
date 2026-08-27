<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;

/**
 * Best-effort outbound email. Uses PHP's mail() rather than pulling in an
 * SMTP library — every cPanel host this is meant to run on already has a
 * working local MTA wired up for it. If mail() fails, this quietly no-ops:
 * a notification failing must never take anything else down with it — the
 * password-reset flow itself never reveals whether the send succeeded.
 */
final class Notifier
{
    public static function passwordReset(string $to, string $resetUrl): void
    {
        if ($to === '') {
            return;
        }

        $subject = (string) config('app.name') . ' — Password Reset';
        $body    = "আপনার Password Reset করতে নিচের লিংকে যান (৩০ মিনিটের জন্য বৈধ):\n\n{$resetUrl}\n\n"
                 . "আপনি যদি এই Request না করে থাকেন, এই Email উপেক্ষা করুন।";
        $headers = 'From: no-reply@' . self::domain() . "\r\nContent-Type: text/plain; charset=UTF-8";

        try {
            if (!@mail($to, $subject, $body, $headers)) {
                Logger::warning('notifier.mail_failed', ['context' => 'password_reset']);
            }
        } catch (\Throwable $e) {
            Logger::warning('notifier.mail_exception', ['error' => $e->getMessage()]);
        }
    }

    private static function domain(): string
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);
        return is_string($host) && $host !== '' ? $host : 'localhost';
    }
}
