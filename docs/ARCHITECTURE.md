# Architecture

## Request lifecycle

1. `public/index.php` — the only file the web server executes directly.
   Defines `APP_ROOT`, requires `app/bootstrap.php`.
2. `bootstrap.php` — PSR-4-ish autoloader (`App\` → `app/`), loads `.env`
   via `App\Core\Env`, sets the timezone (`Asia/Dhaka`) and UTF-8 internal
   encoding, runs startup guards (refuses to boot with `APP_DEBUG=true`
   while `APP_ENV=production`, refuses to boot at all if `APP_KEY` isn't a
   valid 32-byte base64 value), registers error/exception/shutdown
   handlers, and shares the one value every view needs (`appName`) via
   `View::share()`.
3. `Request::capture()` builds an immutable request object from
   superglobals — method (with `_method` POST-spoofing for PUT/PATCH/
   DELETE), path, query, body, files, headers.
4. `Session::start($request)` — DB-backed session (see below). No-ops on
   CLI (`PHP_SAPI === 'cli'`), since `database/migrate.php` also loads
   `bootstrap.php` but never touches sessions.
5. `Router::dispatch($request)` matches `app/routes.php` entries in order
   (`[method, path, 'Controller@action', [middleware]]`), runs the route's
   middleware pipeline, then dispatches to the controller.
6. Controllers stay thin: parse `$request->body`/`->query` through
   `Validator`, call at most one `Service`/`Rules` class, render a view or
   redirect. There is no `Repositories/` convention consistently applied
   here — `UserRepo` is the only repository class; everything else queries
   `Db::` directly from controllers, services, and rule classes. (Compare
   to sibling apps in this workspace, which push all SQL into
   `Repositories/` — this app didn't get that pass; see
   docs/DEVELOPMENT.md.)
7. `SecurityHeaders` middleware wraps every response — applied both inside
   the route pipeline (declared in `Router::MIDDLEWARE` as `sec`, though no
   route actually lists it) and unconditionally around whatever `$response`
   comes out of the try/catch in `public/index.php`, so error pages and
   uncaught-exception responses get the same headers as a normal 200.

## Things worth knowing before you touch them

- **Sessions live in MySQL** (`sessions` table), not files —
  `App\Core\Session` implements `SessionHandlerInterface` itself and calls
  `session_set_save_handler()`. This is what makes
  `Session::revokeAllForUser()` able to kill *every* open session for an
  account (used on password reset and when a soft-deleted account is caught
  mid-session by `RequireAuth`/`RequireAdmin`) — deleting the row means the
  next request from that browser has nothing to read, not "log out at next
  login."
- **One `users` table, one role column.** `role` is `patient` |
  `nutritionist` | `admin`; every role signs in through the exact same
  email+password flow at `/login`, differentiated only by whatever role is
  already on file for that email. Registration is explicit and separate per
  role: `POST /register` always creates a `patient`, `POST /nutri/register`
  always creates a `nutritionist` in `nutritionist_status='pending'` — no
  find-or-create-on-verify auto-account-creation, no silent role upgrade of
  an existing account.
- **No billing, no subscription — access is just being logged in.**
  `RequireAuth` (any role) and `RequireNutritionist` (role='nutritionist'
  AND `nutritionist_status='approved'`, re-read from the DB on every
  request) are the only access gates on `/app/*` and `/nutri`. The public
  calculator and food search are the only ungated surfaces, and food search
  itself is rate-limited for guests (`FOODS_SEARCH_DAILY_CAP_GUEST`) —
  unlimited for anyone logged in, regardless of role.
- **Password reset tokens follow the same PII-lookup pattern as everything
  else here.** The raw token only ever exists in the emailed URL;
  `password_reset_tokens.token_hash` is a one-way HMAC
  (`App\Core\Crypto::blindIndex()`), looked up by exact match — never
  reversed back to the token.
- **Medical flags are encrypted at rest** (`App\Core\Crypto`, AES-256-GCM
  keyed from `APP_KEY`) on `body_profiles.medical_flags_enc`. Email is
  stored in plain text (it's the login identifier, not sensitive PII the
  way a medical condition is) with a plain `UNIQUE` constraint — no blind
  index needed for it.
- **`DietPlanEngine` and `ConditionRuleEngine` (`app/Rules/`) are this
  app's one piece of real domain logic** — deterministic on purpose (same
  body profile in, same plan out), so "why did it pick this" always has a
  real answer. See the Diet plan generation section of docs/FEATURES.md for
  the actual algorithm, and `tests/smoke.php` for numeric test vectors
  against known inputs.
- **CSP has no `unsafe-inline`**, on scripts or styles. Dynamic width
  values (the macro-breakdown bars) go through the pre-generated
  `.w-pct-N` utility classes (`pct_step()` in `app/Core/Helpers.php`)
  instead of `style="width:…"`. `Logger::scrub()` redacts a fixed list of
  sensitive context keys (password, token, medical_flags, ...) before
  anything is written to `storage/logs/`.

## Layer breakdown

```
Controllers/   parse Request, call one Service/Rules class, return a Response
Services/      business rules: audit log, best-effort outbound email (Notifier)
Rules/         DietPlanEngine + ConditionRuleEngine — the domain-specific "smart" logic
Repositories/  UserRepo only — most other classes query Db:: directly (see above)
Middleware/    one class per route-table key (auth, nutri, admin, csrf, rl:*, hp, guest)
Core/          framework primitives: Router, Db, Session, Csrf, Validator, Crypto, Logger, ...
Exceptions/    HttpException (error-page short-circuit)
```

`app/routes.php` is the single source of truth for which middleware guards
which URL — the header comment there documents the middleware keys
(`csrf | guest | auth | nutri | admin | hp | rl:<bucket>`) and the
literal-before-`{slug}`/`{id}` ordering requirement.
