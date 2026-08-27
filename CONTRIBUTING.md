# Contributing to PustiSathi (পুষ্টিসাথী)

Thanks for taking a look. This is a small, hand-rolled PHP app (no
framework, no Composer packages) — the goal of any change here is to keep
it that way, not to introduce a dependency to solve a problem the standard
library already handles.

## Before you start

Read [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) and
[docs/DEVELOPMENT.md](docs/DEVELOPMENT.md) first — they cover the request
lifecycle, layer conventions, and the local dev setup in more detail than
this file will. [docs/FEATURES.md](docs/FEATURES.md)'s Known open items
section lists what's deliberately unbuilt (the nutritionist-patient
relationship, messaging) — if your change touches either, open an issue to
discuss direction before writing code, since whether to build those for
real or drop the dead schema is still an open decision.

## Local setup

```bash
cp .env.example .env
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"   # paste into APP_KEY
php database/migrate.php --fresh --seed
php -S 127.0.0.1:8000 -t public public/router-dev.php
```

See the root [README.md](README.md) for the Windows/PowerShell variant and
the seeded demo-account emails/passwords.

## Before opening a PR

- `php -l <file>` on every file you touched — cheap, catches typos that
  would otherwise only surface as a 500 on the one request path that hits
  them. CI runs this across the whole codebase on every push, but don't
  rely on CI to catch what a five-second local check would.
- `php tests/smoke.php` — covers `Crypto`, `Csrf`, `Validator`, and the
  `DietPlanEngine`/`ConditionRuleEngine` math. Not full coverage; it's the
  "did I just break something load-bearing" gate. If you touched any of
  those five classes, this must pass locally before you push.
- **Actually click through the feature you changed in a browser.**
  `php -l` and the smoke test both passing does not mean a view renders
  correctly or a form submits the way you expect — neither one loads a
  view. This has caught real bugs in this codebase's own history that
  static checks missed entirely.
- Follow the conventions in docs/DEVELOPMENT.md — controllers stay thin,
  every echoed value goes through `e()`, no inline `style=""` (the CSP has
  no `unsafe-inline`), raw `$_POST`/`$_GET` never touched directly.
- If you add a new seed file: **run it**, don't just read it. A bad SQL
  string is still PHP-valid, so `php -l` can't catch it — the seed-SQL bug
  this repo shipped for a while (Postgres syntax against a MySQL-only app)
  is exactly this failure mode, and it sat undetected until someone
  actually ran `--seed` against a real database.

## Commit messages

Explain *why*, not just *what* — this codebase's own git history is full
of comments explaining a non-obvious decision right next to the code, and
commit messages should carry the same habit. "Fix bug" is not useful on
its own; "fix X because Y broke under Z" is.

## What not to do

- Don't add a Composer dependency (or a JS bundler/framework) without
  discussing it first — zero third-party dependencies is a deliberate
  constraint of this whole workspace, not an oversight.
- Don't fabricate nutrition data. The seeded food/region set is a known
  placeholder (see docs/FEATURES.md) — expanding it with real sourced data
  is welcome; padding it with made-up figures to make a feature look more
  complete is not.
- Don't silently touch `nutritionist_patients`, `clinical_notes`, or
  `views/nutri/home.php` — see "Before you start" above.
