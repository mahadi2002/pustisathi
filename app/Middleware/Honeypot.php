<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

/**
 * A CAPTCHA-free bot filter for public forms: a field named to look real
 * that a human never sees or fills, plus a minimum time between the form
 * rendering and the submit landing. Either trip and the request is quietly
 * dropped — no error that tells a bot what gave it away.
 */
final class Honeypot implements Middleware
{
    private const MIN_FILL_SECONDS = 2;

    public function handle(Request $request, callable $next): Response
    {
        if (!$request->isPost()) {
            return $next();
        }

        if ($request->str('hp_website') !== '') {
            return Response::redirect('/');
        }

        $renderedAt = $request->int('hp_ts', 0);
        if ($renderedAt <= 0 || (time() - $renderedAt) < self::MIN_FILL_SECONDS) {
            Session::notify('error', 'একটু ধীরে আবার চেষ্টা করুন।');
            return Response::redirect($this->safeReferer($request));
        }

        return $next();
    }

    /**
     * The Referer header is fully attacker-controlled on any non-browser
     * POST, so redirecting to it unchecked is an open redirect — only trust
     * it when it actually points back at this app.
     */
    private function safeReferer(Request $request): string
    {
        $referer = (string) ($request->header('referer', '') ?? '');
        $host    = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($referer !== '' && parse_url($referer, PHP_URL_HOST) === $host) {
            return $referer;
        }
        return '/';
    }
}
