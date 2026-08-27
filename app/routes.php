<?php
declare(strict_types=1);

/**
 * The route table. Format: [method, path, 'Controller@action', [middleware]].
 *
 * Middleware keys: csrf | guest | auth | nutri | admin | hp | rl:<bucket>
 * SecurityHeaders is applied globally in public/index.php, not per route.
 *
 * `/login` is the one email+password screen every role signs in through,
 * differentiated only by whatever role is already on file for that email.
 * `/register` creates a patient account explicitly; `/nutri/register`
 * creates a pending nutritionist account explicitly — there's no
 * find-or-create-on-verify auto-account-creation anywhere anymore.
 *
 * Order matters: literal paths must precede {slug}/{id} patterns that would
 * also match.
 */
return [
    // -- Public (free) --------------------------------------------------
    ['GET',  '/',             'HomeController@index',       []],
    ['GET',  '/calculator',   'HomeController@calculator',  []],
    ['GET',  '/foods',        'FoodController@search',      []],
    ['GET',  '/health',       'HealthController@index',     []],

    // -- Auth: email + password, one shared login for every role ----------
    ['GET',  '/register',              'RegisterController@form',           ['guest']],
    ['POST', '/register',              'RegisterController@store',          ['guest', 'csrf', 'hp', 'rl:register']],
    ['GET',  '/login',                 'LoginController@form',              ['guest']],
    ['POST', '/login',                 'LoginController@store',             ['guest', 'csrf', 'rl:login']],
    ['POST', '/auth/logout',           'AuthController@logout',             ['auth', 'csrf']],
    ['GET',  '/forgot-password',       'PasswordResetController@forgotForm',   ['guest']],
    ['POST', '/forgot-password',       'PasswordResetController@forgotSubmit', ['guest', 'csrf', 'hp', 'rl:password_reset']],
    ['GET',  '/reset-password/{slug}', 'PasswordResetController@resetForm',    ['guest']],
    ['POST', '/reset-password/{slug}', 'PasswordResetController@resetSubmit',  ['guest', 'csrf', 'rl:password_reset']],

    // -- Nutritionist registration (email+password+credentials) -----------
    ['GET',  '/nutri/register', 'NutriRegisterController@form',  ['guest']],
    ['POST', '/nutri/register', 'NutriRegisterController@store', ['guest', 'csrf', 'hp', 'rl:register']],

    // -- Nutritionist (approval-gated by RequireNutritionist) -------------
    ['GET',  '/nutri',                     'NutriController@home',     ['nutri']],
    ['POST', '/nutri/link',                'NutriController@link',     ['nutri', 'csrf']],
    ['GET',  '/nutri/patients/{id}',       'NutriController@patient',  ['nutri']],
    ['POST', '/nutri/patients/{id}/notes', 'NutriController@addNote',  ['nutri', 'csrf']],

    // -- Admin (gated by RequireAdmin) -------------------------------------
    ['GET',  '/admin', 'AdminController@home', ['admin']],

    // -- Gated patient app --------------------------------------------------
    ['GET',  '/app/onboarding',      'OnboardingController@form',     ['auth']],
    ['POST', '/app/onboarding',      'OnboardingController@store',    ['auth', 'csrf']],
    ['GET',  '/app/dashboard',       'DashboardController@index',     ['auth']],
    ['GET',  '/app/plan',            'PlanController@show',           ['auth']],
    ['POST', '/app/plan/regenerate', 'PlanController@regenerate',     ['auth', 'csrf']],
    ['POST', '/app/share-code',      'DashboardController@shareCode', ['auth', 'csrf']],
];
