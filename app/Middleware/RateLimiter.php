<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Crypto;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Response;
use App\Exceptions\HttpException;

/**
 * IP-keyed limiter used as `rl:<bucket>` in the route table. `login` and
 * `password_reset` also key by the submitted email — IP-only throttling
 * would let someone brute-force one account from a pool of rotating IPs
 * untouched.
 */
final class RateLimiter implements Middleware
{
    private function limits(string $bucket): array
    {
        return match ($bucket) {
            'login'          => [5, 900],
            'register'       => [5, 3600],
            'password_reset' => [3, 3600],
            'foods_search'   => [(int) env('FOODS_SEARCH_DAILY_CAP_GUEST', 10), 86400],
            default          => [30, 60],
        };
    }

    public function __construct(private string $bucket)
    {
    }

    public function handle(Request $request, callable $next): Response
    {
        [$limit, $window] = $this->limits($this->bucket);

        $keys = ['ip:' . $request->ipHash()];
        if (in_array($this->bucket, ['login', 'password_reset'], true)) {
            $email = $request->str('email');
            if ($email !== '') {
                $keys[] = 'id:' . Crypto::blindIndex('email:' . strtolower($email));
            }
        }

        $retry = null;
        foreach ($keys as $key) {
            $wait = RateLimit::hit($this->bucket, $key, $limit, $window);
            if ($wait !== null) {
                $retry = max($retry ?? 0, $wait);
            }
        }

        if ($retry !== null) {
            throw new HttpException(429, 'অনেকবার চেষ্টা হয়েছে। ' . RateLimit::humanWait($retry) . ' পর আবার চেষ্টা করুন।', [
                'retry_after' => $retry,
            ]);
        }

        return $next();
    }
}
