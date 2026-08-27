# Development

## Conventions

- Controllers stay thin — parse `$request->body`/`->query` through
  `App\Core\Validator`, call at most one `Service`/`Rules` class, return a
  `Response`. If a controller method is doing more than that, the logic
  belongs in a `Services/` or `Rules/` class.
- **Most SQL is called directly from controllers/services/rules via
  `App\Core\Db::`, not pushed into a `Repositories/` layer** — `UserRepo`
  is the only repository class in this app. This is a real inconsistency
  with the `Repositories/` convention some sibling apps in this workspace
  follow strictly; it wasn't cleaned up in this pass (see Known gaps
  below). Don't invent a partial repository layer for one new feature and
  leave the rest inline — either follow the existing (inline `Db::`)
  pattern for new code, or do the full extraction as its own change.
- Every echoed value goes through `e()`. Every dynamic "this would
  normally be an inline `style=""`" value goes through a pre-generated CSS
  utility class instead (`pct_step()` → `.w-pct-N` in
  `public/assets/css/app.css`) — the CSP (`script-src 'self'; style-src
  'self'`, no `unsafe-inline`) rejects both inline scripts and inline
  styles outright, so this isn't a style preference.
- Raw `$_POST`/`$_GET` is never read directly in a controller — always
  `App\Core\Validator::make($request->body, [...])`.
- Passwords are never logged, never stored except as a `password_hash()`
  output, and never compared with `===` — always `password_verify()`.
  Medical flags are the other thing that must never be logged or stored in
  plaintext — see docs/SECURITY.md's PII handling section and
  `Logger::scrub()`'s blocked-key list before adding a new field with
  either kind of data.

## Local dev

See the Quick start section of the root `README.md` for the full
copy-pasteable setup (including the Windows/PowerShell two-terminal
variant). Quick reference once `.env` and `APP_KEY` exist:

```bash
php database/migrate.php --fresh --seed
php -S 127.0.0.1:8000 -t public public/router-dev.php
```

- Demo accounts (`database/seeds/demo_accounts.php`): admin
  `admin@pustisathi.test` / `AdminDemo#2026`, nutritionist (pre-approved)
  `nutritionist@pustisathi.test` / `NutriDemo#2026` — only seeded when
  `APP_ENV` is not `production`.
- Rate-limiter reset while testing: `TRUNCATE TABLE rate_limits;`
- `php -l <file>` on every touched file before calling anything done —
  cheap, catches typos that would otherwise only surface as a 500 on the
  one request path that hits them.

## The seed SQL bug (fixed, but worth knowing why it happened)

`database/seeds/food_items.sql`, `regions.sql`, and `food_availability.php`
previously used `ON CONFLICT DO NOTHING` — Postgres/SQLite syntax — against
an app that has only ever connected via a hardcoded `mysql:` PDO DSN
(`App\Core\Db::pdo()`). Running the documented Quick Start
(`php database/migrate.php --fresh --seed`) threw a SQL syntax error on the
very first seed file. Both are fixed now — `INSERT IGNORE` for the "do
nothing on conflict" seeds, matching the correct-MySQL pattern already used
elsewhere in this codebase (`Session::write()`'s `ON DUPLICATE KEY UPDATE`,
`RateLimit::hit()`'s `ON DUPLICATE KEY UPDATE count = count + 1`).

If you add a new seed file: **run it**, don't just read it. `php -l`
cannot catch a bad SQL string — it's PHP-valid either way, and the syntax
error only surfaces when `PDO::exec()` actually sends it to MySQL. This bug
sat in multiple files simultaneously and would have surfaced for the first
person to follow the README's own Quick Start from a clean database.

## Testing

- `php tests/smoke.php` — `Crypto` AES-256-GCM round-trip and tamper
  rejection, `Csrf` token comparison, `Validator`'s rules, and
  (importantly, since this is the one piece of real domain logic in the
  app) numeric checks against `DietPlanEngine`'s BMR/TDEE/macro math and
  `ConditionRuleEngine`'s restricted/required tag output for each of the
  four seeded conditions. Requires a working DB connection (`.env`
  configured, migrations + `condition_rules` present) since
  `ConditionRuleEngine::restrictionsFor()` queries the `condition_rules`
  table directly rather than taking the rules as a parameter. Run after
  touching `Crypto`, `Csrf`, `Validator`, `DietPlanEngine`, or
  `ConditionRuleEngine`. Not full coverage — the "did I just break
  something load-bearing" gate, not a substitute for testing a real
  registration→onboarding→dashboard flow in a browser.
- **Actually drive changed features in a browser before calling anything
  done.** `php -l` and `tests/smoke.php` both passing does not mean the
  register→onboarding→plan-generation→dashboard flow renders correctly —
  neither one loads a view or exercises `View::render()`. Register a fresh
  account, fill in a body profile with at least one medical condition
  selected, and check that the dashboard's condition tips and macro bars
  actually reflect what `DietPlanEngine` computed.

## Known gaps to fill before treating this as "done"

- **The `Repositories/` layer is incomplete** — see Conventions above.
  `UserRepo` exists; nothing else does. Either finish the extraction (one
  repository per table/tight cluster, matching the pattern in this
  workspace's other apps) or drop the convention from this app's docs
  explicitly — leaving it half-adopted is worse than picking one and
  saying so.
- **`food_items` has no natural unique key** (see docs/DATABASE.md) — a
  repeat `--seed` run without `--fresh` duplicates the 35 seeded rows
  rather than no-oping. Worth fixing before this table sees real
  admin-driven inserts.
- **A native Bangla speaker should read every string in `views/`** before
  launch — the same flag raised independently by every sibling app in this
  workspace's build history, every time, without fail. Put it on the
  pre-launch checklist from day one instead of rediscovering it at the
  end.
- **Food/region data is a known placeholder** — 35 food items, 8 regions
  (one per division, district-level only), and a mechanical every-food×
  every-region "year_round" availability cross-product. `food_items.tags`
  are hand-assigned, not from a licensed nutrition database
  (`data_source` defaults to `'seed_unverified'`). Real sourcing is an
  open decision, not something to silently expand in a routine change —
  see docs/FEATURES.md.
- **The nutritionist-patient relationship and messaging are unbuilt** —
  see docs/FEATURES.md's Known open items for the full picture. Don't
  build against `nutritionist_patients`/`clinical_notes` or extend
  `views/nutri/home.php` without confirming that's actually the intended
  direction first (build for real vs. drop the dead schema is still an
  open decision).
