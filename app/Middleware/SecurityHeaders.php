<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

/**
 * Applied globally. No 'unsafe-inline' anywhere — dynamic styling goes
 * through server-computed utility classes (see pct_step() in Helpers.php),
 * never an inline style= attribute.
 */
final class SecurityHeaders implements Middleware
{
    private const CSP = "default-src 'self'; img-src 'self' data:; style-src 'self'; "
        . "script-src 'self'; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; "
        . "base-uri 'self'; form-action 'self'; object-src 'none'";

    public function handle(Request $request, callable $next): Response
    {
        $response = $next();

        $response
            ->withHeader('Content-Security-Policy', self::CSP)
            ->withHeader('X-Content-Type-Options', 'nosniff')
            ->withHeader('X-Frame-Options', 'DENY')
            ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->withHeader('Permissions-Policy', 'geolocation=(), camera=(), microphone=(), payment=()')
            ->withHeader('Cross-Origin-Opener-Policy', 'same-origin');

        if (config('app.env') !== 'local') {
            $response->withHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
