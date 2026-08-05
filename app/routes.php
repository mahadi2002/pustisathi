<?php
declare(strict_types=1);

/**
 * The route table. Format: [method, path, 'Controller@action', [middleware]].
 *
 * Middleware keys: csrf | guest | auth | sub | nutri | admin | hp | rl:<bucket>
 * SecurityHeaders is applied globally in public/index.php, not per route.
 *
 * There's no email/password login anywhere — /subscribe is the one
 * mobile+OTP screen every role signs in through, differentiated only by
 * whatever role is already on file for that number.
 *
 * Order matters: literal paths must precede {slug}/{id} patterns that would
 * also match.
 */
return [
    // -- Public (free) --------------------------------------------------
    ['GET',  '/',             'HomeController@index',       []],
    ['GET',  '/calculator',   'HomeController@calculator',  []],
    ['GET',  '/health',       'HealthController@index',     []],

    // -- Subscribe / login (mobile+OTP, every role) -----------------------
    ['GET',  '/subscribe',         'SubscribeController@form',       ['guest']],
    ['POST', '/subscribe/otp',     'SubscribeController@requestOtp', ['guest', 'csrf', 'hp', 'rl:otp_request']],
    ['POST', '/subscribe/verify',  'SubscribeController@verifyOtp',  ['guest', 'csrf', 'rl:otp_verify']],
    ['GET',  '/subscribe/status',  'SubscribeController@status',     ['guest']],
    ['POST', '/auth/logout',       'AuthController@logout',          ['auth', 'csrf']],

    // -- Nutritionist registration (mobile+OTP+credentials) ---------------
    ['GET',  '/nutri/register',        'NutriRegisterController@form',       ['guest']],
    ['POST', '/nutri/register/otp',    'NutriRegisterController@requestOtp', ['guest', 'csrf', 'hp', 'rl:otp_request']],
    ['POST', '/nutri/register/verify', 'NutriRegisterController@verifyOtp',  ['guest', 'csrf', 'rl:otp_verify']],

    // -- Nutritionist (approval + billing gated by RequireNutritionist) ---
    ['GET',  '/nutri', 'NutriController@home', ['nutri']],
];
