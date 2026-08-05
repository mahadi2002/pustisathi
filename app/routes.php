<?php
declare(strict_types=1);

/**
 * The route table. Format: [method, path, 'Controller@action', [middleware]].
 *
 * Middleware keys: csrf | guest | auth | sub | nutri | admin | hp | rl:<bucket>
 * SecurityHeaders is applied globally in public/index.php, not per route.
 *
 * Order matters: literal paths must precede {slug}/{id} patterns that would
 * also match.
 */
return [
    // -- Public (free) --------------------------------------------------
    ['GET',  '/',             'HomeController@index',       []],
    ['GET',  '/calculator',   'HomeController@calculator',  []],
    ['GET',  '/health',       'HealthController@index',     []],

    // -- Subscribe (OTP funnel) ------------------------------------------
    ['GET',  '/subscribe',         'SubscribeController@form',       ['guest']],
    ['POST', '/subscribe/otp',     'SubscribeController@requestOtp', ['guest', 'csrf', 'hp', 'rl:otp_request']],
    ['POST', '/subscribe/verify',  'SubscribeController@verifyOtp',  ['guest', 'csrf', 'rl:otp_verify']],
    ['GET',  '/subscribe/status',  'SubscribeController@status',     ['guest']],

    // -- Auth --------------------------------------------------------------
    ['GET',  '/auth/login',                    'AuthController@login',                ['guest']],
    ['POST', '/auth/login',                    'AuthController@login',                ['guest', 'csrf', 'rl:admin_login']],
    ['GET',  '/auth/register-nutritionist',    'AuthController@registerNutritionist', ['guest']],
    ['POST', '/auth/register-nutritionist',    'AuthController@registerNutritionist', ['guest', 'csrf', 'hp']],
    ['POST', '/auth/logout',                   'AuthController@logout',               ['auth', 'csrf']],
];
