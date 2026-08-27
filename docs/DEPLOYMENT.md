# Deployment (cPanel / shared hosting)

1. **App code outside the web root** — e.g. `/home/USER/pustisathi/` (not
   web-accessible) vs. `/home/USER/public_html/` pointing at
   `pustisathi/public`. If the host can't point the docroot there, copy the
   contents of `public/` into `public_html/` and define `APP_ROOT`
   explicitly near the top of the copied `index.php` — the comment already
   in `public/index.php` shows the exact line
   (`define('APP_ROOT', '/home/USER/pustisathi');`).
2. **Fresh `.env` on the server itself** — never copy dev's `APP_KEY` over.
   Generate it with `php -r "echo base64_encode(random_bytes(32)),
   PHP_EOL;"`. `bootstrap.php` refuses to boot at all if `APP_KEY` isn't
   present or doesn't decode to exactly 32 bytes.
3. **Two MySQL users**, same reasoning as every sibling app in this
   workspace: the runtime app user (`DB_USER`) gets only `SELECT, INSERT,
   UPDATE, DELETE` (granted automatically by `migrate.php` on every run,
   see `database/migrate.php`'s `GRANT` statement); a separate
   `DB_MIGRATE_USER` with DDL rights is used only by
   `database/migrate.php`. A SQL-injection bug that somehow slips past
   prepared statements still can't `DROP`/`ALTER`/`CREATE` anything through
   the app's own connection.
4. `php database/migrate.php` (`--seed` only on a genuinely fresh install
   — **the demo accounts it creates are dev/local conveniences with fixed
   passwords; `database/seeds/demo_accounts.php` already refuses to run
   when `APP_ENV=production`**, but don't rely on that as your only
   safeguard — don't run `--seed` against a production database at all).
5. Outbound email for password resets (`App\Services\Notifier`) uses PHP's
   `mail()` — make sure the host's local MTA is actually configured to
   deliver, or resets will silently no-op (best-effort by design: a failed
   notification must never break the reset flow itself, but it's worth
   confirming mail delivery actually works before go-live).
6. `APP_ENV=production`, `APP_DEBUG=false`. **The app refuses to boot** if
   both are true at once (`app/bootstrap.php`'s startup guard) — fix the
   env config rather than working around it.
7. Point an uptime monitor at `GET /health` (runs a real `SELECT 1`
   against the DB via `HealthController`), not `/`.
8. Nightly `mysqldump --single-transaction | gzip` to off-server storage,
   and **test a restore at least once** before you need it for real.
9. Go-live checklist:
   - `.env` chmod 600, outside web root, not in git
   - `curl -I https://.../.env`, `.../storage/logs/app.log`, and
     `.../database/migrate.php` all 403/404 — `public/.htaccess` already
     blocks `.env`/`.log`/`.sql`/`.md`/`.json`/`.lock`/`.bak`/`.sh`/`.yml`
     by extension and denies directory listing, but verify it's actually
     in effect on the live host (mod_rewrite/mod_headers availability
     varies by shared-hosting provider)
   - Demo accounts never seeded (see step 4) — if they were ever seeded
     against a shared environment by mistake, change or delete those two
     accounts' passwords before go-live; there's no separate "force a
     password change" flow, just `/forgot-password` like any other account
   - Fresh `APP_KEY` (not copied from dev) — re-encrypting existing
     ciphertext under a new key is a real migration, not a config change,
     so get this right before the first real user record is written
   - CSP has no `unsafe-inline` (already true by default via
     `SecurityHeaders` middleware and the matching header block in
     `public/.htaccess` — don't add it)
   - `ADMIN_IP_ALLOWLIST` set if `/admin` should be restricted to known
     IPs, in addition to the `role = 'admin'` check
   - Manually verify, in a second browser, that revoking a session (e.g.
     via `Session::revokeAllForUser()` after a password reset or a
     soft-delete) actually logs that browser out on its *next* request, not
     just at next login
10. The one thing that doesn't survive scaling past one server as-is:
    `storage/uploads/` and `storage/cache/` are local-disk. Nothing in the
    current codebase actually writes to `storage/uploads/` yet. Sessions
    live in MySQL, so the web tier itself is safe to run behind a load
    balancer once the DB is shared.
