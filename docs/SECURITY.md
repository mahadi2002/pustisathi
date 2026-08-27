# Security

## Auth

Mobile + OTP only, for every role including admin — there is no password
anywhere in this app (`users.password_hash` exists in the schema but no
code path ever writes or checks it). OTP codes are never stored in
plaintext: `OtpService::request()` hashes with `password_hash()`
(bcrypt/argon2i via PHP's default) and `OtpService::verify()` compares with
`password_verify()`. 5-minute TTL (`OTP_TTL_MIN`), capped attempts
(`OTP_MAX_ATTEMPTS`), rate-limited per mobile number (`OTP_RATE_LIMIT_PER_HOUR`,
keyed by the number's blind index so the limit survives the number itself
never being logged) and separately per IP (`RateLimiter` middleware's
`otp_request`/`otp_verify` buckets). `OtpService::DEV_CODE` (`123456`)
only ever verifies when `SUBSCRIPTION_GATEWAY=mock`, and `bootstrap.php`
refuses to boot with that gateway when `APP_ENV=production` — so this is a
dev/demo convenience, not a production backdoor.

`/admin` adds an optional IP allowlist on top of the same OTP auth
(`ADMIN_IP_ALLOWLIST` in `.env`, checked by `RequireAdmin` before the
session/role check) — defense in depth, not a replacement for the role
check.

## Sessions

DB-backed (`sessions` table via `App\Core\Session implements
SessionHandlerInterface`), never files — the mechanism that lets
`Session::revokeAllForUser()` kill every open session for an account
server-side, on that browser's *next* request rather than at next login.
Cookie flags: `HttpOnly`, `SameSite=Lax`, `Secure` when `SESSION_SECURE`
is true (the default). `session.use_strict_mode` and
`session.use_only_cookies` are both forced on. Regenerated on login
(`Session::login()` calls `regenerate()` before setting `user_id`, so the
pre-auth session id is never reused post-auth). `user_id` is nullable on
the `sessions` row itself (`010_addendum_sessions_nullable_user.sql`) —
required because CSRF protection and the OTP flow both need a session to
exist for a not-yet-authenticated visitor.

## Subscription / approval gates

`RequireSubscription`, `RequireNutritionist`, and
`Controller::isSubscribed()` are all the same code path underneath:
`SubscriptionService::hasAccess()`, a live `SELECT ... WHERE status IN
('active','grace')` query. No session flag, no cookie, no cached claim
ever decides access. `RequireNutritionist` additionally re-reads
`nutritionist_status` on every request — an approval that gets revoked
takes effect on the very next request to any `nutri`-gated route, same as
a lapsed subscription.

## CSRF

Token on every state-changing POST (`CsrfGuard` middleware, keyed off the
route table's `csrf` entry + `csrf_field()` in every form). `Csrf::check()`
uses `hash_equals()`, never `===`. A mismatch throws `HttpException(419)`
and rotates the token (`Csrf::rotate()`) so the immediate retry can
succeed instead of failing the same way twice.

## Rate limiting

`rate_limits` table (`App\Core\RateLimit`), fixed-window, `INSERT ... ON
DUPLICATE KEY UPDATE count = count + 1` keyed by `bucket_key` +
`window_start`. Buckets in active use: `otp_request`/`otp_verify` (IP-
keyed via `RateLimiter` middleware, and separately mobile-hash-keyed
inside `OtpService` itself — two independent limits, not one shared one),
`admin_login` (IP + submitted-email-hash — IP-only would let someone
brute-force one staff account from a pool of rotating IPs), and
`foods_search` (IP-keyed, guests only). No Redis assumption — this is
deliberately MySQL-backed so it works unmodified on shared hosting.

## Honeypot (`Honeypot` middleware)

CAPTCHA-free bot filtering on public forms (OTP request, nutritionist
registration): a hidden field styled off-screen (`hp_website`, real humans
never see or fill it) plus a minimum 2-second gap between when the form
rendered (`hp_ts`, a timestamp) and when the POST lands. Either trip
silently redirects instead of showing an error that would tell a bot what
gave it away.

## Content-Security-Policy

```
default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self';
font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self';
form-action 'self'; object-src 'none'
```

No `unsafe-inline` anywhere, applied both by the `SecurityHeaders`
middleware (every response, including error pages) and redundantly by
`public/.htaccess`'s `Header always set` block (belt-and-braces for the
case where PHP-level headers somehow don't fire). Concretely:

- Every dynamic "would normally be `style=""`" value is a pre-generated
  CSS utility class instead (`pct_step()` → `.w-pct-N` for the macro-bar
  widths).
- The 419 (session-expired) page's "go back" button is a real `<button>` +
  `app.js` event listener, not a `javascript:` href — the CSP's
  `script-src 'self'` (no `unsafe-inline`) blocks `javascript:` URLs from
  ever firing, since that scheme is script execution, not navigation.

## Input / output

- **SQL**: prepared statements everywhere (`Db::run()`),
  `PDO::ATTR_EMULATE_PREPARES` off. No string-built SQL, including
  dynamic `IN (...)` lists (`Db::placeholders()` builds the `?,?,?`
  positionally instead).
- **Output**: every echoed value goes through `e()`
  (`htmlspecialchars(..., ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5,
  'UTF-8')`). There is no rich-text/Markdown content type anywhere in this
  app, so there's no whitelist-renderer surface to worry about.
- **Validation**: `App\Core\Validator` — raw `$_POST`/`$_GET` is never
  used directly in a controller.

## PII handling

Two-part treatment, applied consistently:

- **`Crypto::encrypt()`** (AES-256-GCM, keyed from `APP_KEY`, random IV
  per call so ciphertext is never the same twice) for values the app
  sometimes needs back in plaintext: `users.mobile`,
  `body_profiles.medical_flags_enc`.
- **`Crypto::blindIndex()`** (HMAC-SHA256, one-way, same `APP_KEY`) for the
  column actually queried against: `users.mobile_hash`,
  `otp_requests.mobile_hash`, `audit_log.ip_hash`. Never reversible, never
  logged as a lookup that reveals the original value.

`Logger::scrub()` redacts a fixed key list (`mobile`, `phone`, `msisdn`,
`otp`, `code`, `password`, `password_hash`, `app_key`, `secret`, `token`,
`payload`, `medical_flags`, `medical_flags_enc`) recursively from any
context array passed to `Logger::*()`, as a belt-and-braces backstop —
callers should still never pass a secret in the first place.
`OtpService::request()`'s dev-only `otp` channel log
(`storage/logs/otp-*.log`) is the one deliberate exception, logging the
generated code and last-4-digits so local testing doesn't need real SMS —
and it only ever runs behind the same `SUBSCRIPTION_GATEWAY=mock` /
`APP_ENV≠production` conditions as `OtpService::DEV_CODE`.

## Audit log

`audit_log` (`App\Services\AuditLog::record()`) captures billing and
subscription-state-change events specifically
(`subscription_activated`, `subscription_charge_succeeded`,
`subscription_charge_failed`, `subscription_unsubscribed`) — not general
activity tracking. `ip_hash` rather than a raw IP, for the same reason
mobile numbers are hashed: no reason to keep something reversible around
that the app doesn't need back.

## Headers (`SecurityHeaders` middleware + `public/.htaccess`)

`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`,
`Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy`
denying geolocation/camera/microphone/payment, `Cross-Origin-Opener-Policy:
same-origin`, HSTS (skipped when `APP_ENV=local`). Applied globally,
including to error pages and uncaught-exception responses (see
docs/ARCHITECTURE.md's request-lifecycle step 7).

## Pre-launch checklist

See docs/DEPLOYMENT.md's go-live checklist for the full list —
`.env`/`storage/`/`database/migrate.php` unreachable via HTTP, fresh
`APP_KEY` (not copied from dev, and understand that rotating it after real
data exists requires re-encrypting every ciphertext column rather than
just editing `.env`), `ADMIN_IP_ALLOWLIST` set if `/admin` should be
IP-restricted, demo accounts never seeded in production
(`database/seeds/demo_accounts.php` already self-guards on `APP_ENV`, but
don't run `--seed` against production at all as the actual safeguard).
