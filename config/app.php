<?php
declare(strict_types=1);

return [
    'name'     => env('APP_NAME', 'PustiSathi'),
    'env'      => env('APP_ENV', 'production'),
    'debug'    => env('APP_DEBUG', false) === true || env('APP_DEBUG') === 'true',
    'url'      => rtrim((string) env('APP_URL', ''), '/'),
    'timezone' => env('APP_TIMEZONE', 'Asia/Dhaka'),
    'locale'   => env('APP_LOCALE', 'bn'),
    'key'      => base64_decode((string) env('APP_KEY', ''), true) ?: '',

    'session' => [
        'cookie'       => env('SESSION_COOKIE_NAME', 'ps_sid'),
        'lifetime_min' => (int) env('SESSION_LIFETIME_MIN', 120),
        'secure'       => env('SESSION_SECURE', 'true') !== 'false',
    ],

    'otp' => [
        'length'          => (int) env('OTP_LENGTH', 6),
        'ttl'             => (int) env('OTP_TTL_MIN', 5) * 60,
        'max_attempts'    => (int) env('OTP_MAX_ATTEMPTS', 5),
        'rate_per_hour'   => (int) env('OTP_RATE_LIMIT_PER_HOUR', 3),
    ],

    'foods' => [
        'search_daily_cap_guest' => (int) env('FOODS_SEARCH_DAILY_CAP_GUEST', 10),
    ],

    // Empty = unrestricted. Comma-separated IPs in .env to lock /admin down further.
    'admin_ip_allowlist' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('ADMIN_IP_ALLOWLIST', ''))
    ))),

    'log_level' => env('LOG_LEVEL', 'info'),
];
