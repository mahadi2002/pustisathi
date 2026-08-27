# Database

Schema source of truth: `database/migrations/*.sql`, applied in filename
(numeric) order by `database/migrate.php`. All tables use InnoDB defaults
(no explicit engine clause needed — that's MySQL's default) and
`utf8mb4`/`utf8mb4_unicode_ci`, set at database-creation time by
`migrate.php` itself (`CREATE DATABASE ... CHARACTER SET utf8mb4 COLLATE
utf8mb4_unicode_ci`).

All timestamps are stored in the connection's session time zone, which
`Db::pdo()` and `migrate.php` both set explicitly: `SET time_zone =
'+06:00'` (Asia/Dhaka) — unlike some sibling apps in this workspace, this
one does **not** store UTC and convert on display; `NOW()` in every query
already means Dhaka local time.

## Tables, grouped by migration file

- **001_users_auth** — `users`, `otp_requests` (dropped by 016), `sessions`,
  `rate_limits`
- **002_subscription_billing** — `subscriptions`, `billing_events` (both
  dropped by 017 — see below)
- **003_body_profile_location** — `regions`, `body_profiles`
- **004_food_database** — `food_items`, `food_availability`
- **005_condition_rules** — `condition_rules` (ships with its 4 rows as
  part of the migration itself, not a seed file — see below)
- **006_diet_plans** — `diet_plans`, `plan_meals`
- **007_food_log** — `food_logs` (schema exists; no controller/service
  reads or writes it yet — see docs/FEATURES.md)
- **008_nutritionist_patient** — `nutritionist_patients`, `clinical_notes`
  (schema exists; zero code touches either table — see docs/FEATURES.md)
- **009_jobs** — `jobs` (dropped again by 015 — see below)
- **010_addendum_sessions_nullable_user** — widens `sessions.user_id` to
  nullable
- **011_addendum_mobile_encrypted** — moves `users.mobile` to
  encrypted-at-rest + `mobile_hash` lookup (column itself dropped by 016)
- **012_addendum_subscription_grace** — adds `pending`/`grace` to
  `subscriptions.status` (table dropped by 017)
- **013_addendum_audit_log** — `audit_log`
- **014_addendum_otp_mobile_hash** — moves `otp_requests.mobile` to a
  blind-index hash (table dropped by 016)
- **015_drop_unused_jobs_table** — `DROP TABLE IF EXISTS jobs`
- **016_email_password_auth** — drops `users.mobile`/`mobile_hash`/
  `operator` and the `otp_requests` table; promotes `users.email`/
  `password_hash` (nullable since 001) to `NOT NULL` — the actual auth
  mechanism for every role now; adds `password_reset_tokens`
- **017_drop_subscription_billing** — `DROP TABLE IF EXISTS billing_events,
  subscriptions` — this app has no billing concept anymore (see
  docs/FEATURES.md)

## Notes worth knowing

- **`condition_rules` ships in a migration, not `database/seeds/`.**
  `DietPlanEngine`/`ConditionRuleEngine` depend on its four rows (diabetic,
  renal, cardiac, pregnancy) existing unconditionally on any environment
  that has run migrations — not only on one that also ran `--seed`. Every
  other piece of starter data (`food_items`, `regions`, the food↔region
  cross-product, demo accounts) lives in `database/seeds/` instead, and
  only runs when `migrate.php --seed` is passed explicitly.
- **Email+password is the only PII-adjacent auth data now.** `users.email`
  is plain `VARCHAR(191) UNIQUE NOT NULL` (it's the login identifier, not
  sensitive the way a medical condition is) and `users.password_hash` is a
  `password_hash()` output — never compared with `===`, always
  `password_verify()`. `body_profiles.medical_flags_enc` keeps the
  `Crypto::encrypt()` (AES-256-GCM, reversible, keyed from `APP_KEY`)
  treatment it always had. `password_reset_tokens.token_hash` uses
  `Crypto::blindIndex()` (HMAC-SHA256, one-way) — the same PII-lookup
  pattern `users.mobile_hash` used to use before 016 removed it, applied to
  a reset token instead of a phone number.
- **`food_items` has no unique constraint beyond its auto-increment `id`.**
  `regions` has `uq_region UNIQUE (district, upazila)` and
  `food_availability` has a composite `PRIMARY KEY (food_id, region_id)`,
  so both of those seed idempotently on a repeat `--seed` run
  (`INSERT IGNORE` genuinely no-ops on the second run). `food_items.sql`'s
  `INSERT IGNORE` does not — a second `--seed` run without `--fresh` first
  will insert the 35 rows again under new ids. This isn't new breakage from
  the MySQL syntax fix (see docs/DEVELOPMENT.md); `ON CONFLICT DO NOTHING`
  with no explicit conflict target wouldn't have deduped against this
  table's schema either. Give `food_items` a real natural key (or an
  admin-driven upsert path) before this table sees production traffic from
  outside a single `--fresh --seed` cycle.
- **`rate_limits`** is a fixed-window design: `bucket_key` (e.g.
  `login:id:<hash>`, `foods_search:ip:<hash>`) + `window_start` (floored to
  the bucket's own window size) is the unique key (`uq_bucket_window`),
  `count` increments via `INSERT ... ON DUPLICATE KEY UPDATE count = count
  + 1`. No `blocked_until` column — the block *is* the remaining time in
  the current window (`RateLimit::hit()` computes it from `window_start` +
  the caller-supplied window length).
- **`food_logs` and `nutritionist_patients`/`clinical_notes` are live
  schema with zero application code.** They migrate cleanly and are not
  going anywhere, but no controller, service, or view reads or writes them
  — see docs/FEATURES.md's Known gaps section before assuming either
  feature works.
- **Two DB users in production**: `DB_USER` (the runtime app connection,
  `App\Core\Db`) gets `GRANT SELECT, INSERT, UPDATE, DELETE` only, granted
  by `migrate.php` itself on every run. `DB_MIGRATE_USER` (DDL rights) is
  used only by `database/migrate.php`, never by the web app — see
  docs/DEPLOYMENT.md and docs/SECURITY.md.
