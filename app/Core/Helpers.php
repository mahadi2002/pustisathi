<?php
declare(strict_types=1);

/**
 * Global helper functions. Loaded once by bootstrap.php before anything else.
 */

if (!function_exists('e')) {
    /** The mandatory escape helper. Every echo of a variable goes through this. */
    function e(?string $v): string
    {
        return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    }
}

if (!function_exists('env')) {
    /** Read a value from the parsed .env. Never call getenv() elsewhere. */
    function env(string $key, mixed $default = null): mixed
    {
        return \App\Core\Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    /** config('app.debug') — dot path into config/*.php */
    function config(string $path, mixed $default = null): mixed
    {
        static $cache = [];
        $parts = explode('.', $path);
        $file  = array_shift($parts);

        if (!array_key_exists($file, $cache)) {
            $full = APP_ROOT . '/config/' . $file . '.php';
            $cache[$file] = is_file($full) ? require $full : [];
        }

        $value = $cache[$file];
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}

if (!function_exists('url')) {
    /** Absolute URL for a path within the app. */
    function url(string $path = '/'): string
    {
        $base = (string) config('app.url', '');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    /** Cache-busted asset URL. */
    function asset(string $path): string
    {
        $path = '/assets/' . ltrim($path, '/');
        $file = APP_ROOT . '/public' . $path;
        $ver  = is_file($file) ? (string) filemtime($file) : '1';
        return $path . '?v=' . $ver;
    }
}

if (!function_exists('bn_num')) {
    /**
     * Latin digits -> Bangla digits, for counts and dates. Prices (৳2.78)
     * and phone numbers stay in Latin digits — that's how people actually
     * read money and phone numbers here, regardless of the sentence's script.
     */
    function bn_num(int|float|string $n): string
    {
        return strtr((string) $n, [
            '0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪',
            '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯',
        ]);
    }
}

if (!function_exists('bn_date')) {
    /** Format a date/datetime string in Bangla digits, e.g. ০৫ আগস্ট ২০২৬ */
    function bn_date(?string $datetime, bool $withTime = false): string
    {
        if (!$datetime) {
            return '—';
        }
        $ts = strtotime($datetime);
        if ($ts === false) {
            return '—';
        }
        $months = [
            1 => 'জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
            'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর',
        ];
        $out = bn_num(date('d', $ts)) . ' ' . $months[(int) date('n', $ts)] . ' ' . bn_num(date('Y', $ts));
        if ($withTime) {
            $out .= ', ' . bn_num(date('h:i', $ts)) . ' ' . (date('a', $ts) === 'am' ? 'পূর্বাহ্ণ' : 'অপরাহ্ণ');
        }
        return $out;
    }
}

if (!function_exists('old')) {
    /** Re-populate a form field after a validation failure. */
    function old(string $key, string $default = ''): string
    {
        $old = \App\Core\Session::flashGet('_old') ?? [];
        return (string) ($old[$key] ?? $default);
    }
}

if (!function_exists('errors')) {
    /** Validation errors flashed from the previous request. */
    function errors(): array
    {
        return \App\Core\Session::flashGet('_errors') ?? [];
    }
}

if (!function_exists('error_for')) {
    function error_for(string $field): ?string
    {
        $all = errors();
        return isset($all[$field]) ? (string) $all[$field][0] : null;
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . e(\App\Core\Csrf::token()) . '">';
    }
}

if (!function_exists('str_excerpt')) {
    /** Multibyte-safe excerpt that does not cut a Bangla word in half. */
    function str_excerpt(?string $text, int $chars = 180): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $text)) ?? '');
        if (mb_strlen($text, 'UTF-8') <= $chars) {
            return $text;
        }
        $cut = mb_substr($text, 0, $chars, 'UTF-8');
        $sp  = mb_strrpos($cut, ' ', 0, 'UTF-8');
        return rtrim($sp !== false ? mb_substr($cut, 0, $sp, 'UTF-8') : $cut, ' ,।-') . '…';
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}]+/u', '-', $text) ?? '';
        return trim(substr($text, 0, 140), '-');
    }
}

if (!function_exists('pct_step')) {
    /**
     * Round a 0-100 value to the nearest 5 for the .w-pct-N utility classes
     * that drive the থালা (plate) meter — the CSP has no unsafe-inline, so a
     * dynamic percentage can never be written as style="width:N%".
     */
    function pct_step(float $value, float $max = 100.0): int
    {
        $pct = $max > 0 ? ($value / $max) * 100 : 0.0;
        $pct = max(0.0, min(100.0, $pct));
        return (int) (round($pct / 5) * 5);
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        if (!config('app.debug')) {
            http_response_code(500);
            exit;
        }
        header('Content-Type: text/plain; charset=utf-8');
        foreach ($vars as $v) {
            var_dump($v);
        }
        exit;
    }
}
