# Security

## Auth

Email + password, for every role including admin — one shared `/login`
(`LoginController`), differentiated only by whatever `role` is already on
file for that email; there's no separate admin login screen. Passwords are
hashed with `password_hash()` (bcrypt via PHP's default) and checked with
`password_verify()`, never `===`. `LoginController::store()` always runs
`password_verify()` against *some* hash — a fixed dummy bcrypt hash when
the email doesn't exist at all — so a failed login takes the same amount
of work either way, and the error message is identical
("Email অথবা Password সঠিক নয়।") whether the email was wrong or the
password was: nothing about the response reveals which emails are
registered.

Registration is explicit and separate per role, never an
auto-create-on-first-login: `POST /register` (`RegisterController`) always
creates a `patient`; `POST /nutri/register` (`NutriRegisterController`)
always creates a `nutritionist` in `nutritionist_status = 'pending'` — see
"Role / approval gates" below for how that turns into actual access.
Both registration flows re-check `findByEmail()` before insert and also
catch the `users.email` `UNIQUE` constraint's MySQL 1062 error as a
race-safety net, since another request can register the same email
between the check and the insert.

**Password reset** (`PasswordResetController`): `/forgot-password` issues
a random token (`Crypto::randomToken(32)`) that only ever exists in the
emailed reset URL — `password_reset_tokens.token_hash` stores a one-way
HMAC of it (`Crypto::blindIndex()`), looked up by exact match and never
reversed back to the token. Tokens expire after 30 minutes
(`TOKEN_TTL_MIN`), are single-use (`used_at`), and every response on the
`/forgot-password` flow is identical regardless of whether the email has
an account — same anti-enumeration approach as login. A successful reset
also calls `Session::revokeAllForUser()`, so every other open session on
that account is killed, not just the one completing the reset.

`/admin` adds an optional IP allowlist on top of the same email+password
auth (`ADMIN_IP_ALLOWLIST` in `.env`, checked by `RequireAdmin` before the
session/role check) — defense in depth, not a replacement for the role
check.

## Sessions

DB-backed (`sessions` table via `App\Core\Session implements
SessionHandlerInterface`), never files — the mechanism that lets
`Session::revokeAllForUser()` kill every open session for an account
server-side, on that browser's *next* request rather than at next login
(used after a password reset, and when `RequireAuth`/`RequireAdmin` catch
a session whose account has since been soft-deleted). Cookie flags:
`HttpOnly`, `SameSite=Lax`, `Secure` when `SESSION_SECURE` is true (the
default). `session.use_strict_mode` and `session.use_only_cookies` are
both forced on. Regenerated on login (`Session::login()` calls
`regenerate()` before setting `user_id`, so the pre-auth session id is
never reused post-auth). `user_id` is nullable on the `sessions` row
itself (`010_addendum_sessions_nullable_user.sql`) — required because CSRF
protection needs a session to exist for a not-yet-authenticated visitor
(filling in a public form before ever logging in).

## Role / approval gates

No billing, no subscription — access is just being logged in, plus one
extra check for nutritionists:

- **`RequireAuth`** — any authenticated role (patient, nutritionist,
  admin) with a non-soft-deleted account. Gates `/app/*`.
- **`RequireNutritionist`** — `role = 'nutritionist'` **and**
  `nutritionist_status = 'approved'`, both re-read from the DB on every
  request, never cached in the session. Approval is never self-service —
  registering only reaches `pending`; an admin has to flip the status. An
  approval that gets revoked takes effect on the very next request to any
  `nutri`-gated route. Gates `/nutri*`.
- **`RequireAdmin`** — `role = 'admin'`, plus the optional IP allowlist
  described above. Gates `/admin`.

No session flag, cookie, or cached claim ever decides access — every gate
is a live `SELECT ... WHERE id = ?` against `users` on every request.

## CSRF

Token on every state-changing POST (`CsrfGuard` middleware, keyed off the
route table's `csrf` entry + `csrf_field()` in every form). `Csrf::check()`
uses `hash_equals()`, never `===`. A mismatch throws `HttpException(419)`
and rotates the token (`Csrf::rotate()`) so the immediate retry can
succeed instead of failing the same way twice.

## Rate limiting

`rate_limits` table (`App\Core\RateLimit`), fixed-window, `INSERT ... ON
DUPLICATE KEY UPDATE count = count + 1` keyed by `bucket_key` +
`window_start`. Buckets in active use (`RateLimiter` middleware's
`limits()`): `login` (5/15min) and `password_reset` (3/hour) are keyed
both by IP and by the submitted email's blind index — IP-only throttling
would let someone brute-force one account from a pool of rotating IPs
untouched; `register` (5/hour) and `foods_search` (guests only, default
`FOODS_SEARCH_DAILY_CAP_GUEST`/day) are IP-keyed only. No Redis
assumption — this is deliberately MySQL-backed so it works unmodified on
shared hosting.

## Honeypot (`Honeypot` middleware)

CAPTCHA-free bot filtering on public forms — patient registration,
nutritionist registration, and the forgot-password submit (every route
tagged `hp` in `app/routes.php`): a hidden field styled off-screen
(`hp_website`, real humans never see or fill it) plus a minimum 2-second
gap between when the form rendered (`hp_ts`, a timestamp) and when the
POST lands. Either trip silently redirects instead of showing an error
that would tell a bot what gave it away.

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
  sometimes needs back in plaintext — today that's just
  `body_profiles.medical_flags_enc`. (Mobile numbers used to get the same
  treatment; the `users.mobile`/`mobile_hash` columns were dropped
  entirely by `016_email_password_auth.sql` when auth moved to
  email+password.)
- **`Crypto::blindIndex()`** (HMAC-SHA256, one-way, same `APP_KEY`) for a
  value that's only ever looked up, never displayed back:
  `password_reset_tokens.token_hash`, the IP hash used for rate-limit keys
  (`Request::ipHash()`) and `audit_log.ip_hash`, and the email-hash half of
  the `login`/`password_reset` rate-limit keys. Never reversible, never
  logged as a lookup that reveals the original value. `users.email` itself
  is stored in plain text with a plain `UNIQUE` constraint — it's the
  login identifier, not sensitive the way a medical condition is, so no
  blind index is needed for it.

`Logger::scrub()` redacts a fixed key list (`mobile`, `phone`, `msisdn`,
`otp`, `code`, `password`, `password_hash`, `app_key`, `secret`, `token`,
`payload`, `medical_flags`, `medical_flags_enc`) recursively from any
context array passed to `Logger::*()`, as a belt-and-braces backstop —
callers should still never pass a secret in the first place. A few of
those keys (`mobile`, `otp`) are leftovers from the removed OTP system and
nothing currently logs under them, but the redaction stays cheap
insurance against a future context array reusing one of those names.

## Audit log

`app/Services/AuditLog.php` (`AuditLog::record()`) writes to the
`audit_log` table (`user_id`, `action`, `details_json`, `ip_hash`) when
called. It originally recorded billing/subscription-state-change events;
now that billing is gone, nothing in the codebase currently calls it —
the class and table are dormant, kept in case a future feature (e.g.
account changes, admin actions) needs an audit trail again rather than
rebuilding one from scratch.

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
