# PustiSathi (পুষ্টিসাথী)

Bangla nutrition app: a free BMI/calorie estimate, then a personalized
daily diet plan matched to body profile, budget, and local food
availability — plus a patient↔nutritionist matching and messaging loop for
people who want an actual professional involved. Hobby project — no
billing, no subscription plans; every feature is free behind a plain
email+password account. Third app in this workspace's series (after
GardenBondhu, IELTS Master BD) — same architecture, re-skinned.

## Stack

- **PHP 8.2+, zero Composer packages.** Hand-rolled front controller,
  Router, Middleware pipeline, Controllers → Services → Repositories,
  plain PHP views.
- **MySQL 8 / MariaDB**, DB-backed sessions.
- **Vanilla JS**, no bundler, no framework.
- `app/Rules/ConditionRuleEngine.php` + `DietPlanEngine.php` are this app's
  bespoke "smart" feature — the one part of the architecture that's
  genuinely specific to the nutrition domain rather than copy-paste from
  the other apps in the series.

## Quick start

```bash
cp .env.example .env
php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"   # APP_KEY
php database/migrate.php --fresh --seed
php -S 127.0.0.1:8000 -t public public/router-dev.php
```

Open `http://127.0.0.1:8000`. `router-dev.php` (not `index.php`) is what
makes `php -S` fall through to static CSS/JS/images — Apache and nginx
handle that natively in production and never touch this file.

**Windows/PowerShell** needs two separate tabs — MySQL backgrounded, the
PHP server blocking its own tab (that's it actively serving, not stuck):

```powershell
Start-Process "C:\xampp\mysql\bin\mysqld.exe" -ArgumentList "--defaults-file=C:\xampp\mysql\bin\my.ini","--standalone"
C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public public/router-dev.php
```

**Stopping it:** `Ctrl+C` in the PHP server's tab. MySQL is left running on
purpose (shared infrastructure) — `C:\xampp\mysql\bin\mysqladmin.exe -u
root shutdown` if you want it down too.

### Logging in

Email + password for every role — `POST /register` for a new patient
account, `POST /nutri/register` for a new (pending-approval) nutritionist
account, `POST /login` for everyone, including admin. Demo accounts from
the seed:

- Admin: `admin@pustisathi.test` / `AdminDemo#2026`
- Nutritionist (pre-approved): `nutritionist@pustisathi.test` / `NutriDemo#2026`

## Docs

- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — request lifecycle, layer breakdown
- [docs/DATABASE.md](docs/DATABASE.md) — full schema reference
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — cPanel/shared-hosting deploy steps
- [docs/DEVELOPMENT.md](docs/DEVELOPMENT.md) — setup, conventions, testing
- [docs/FEATURES.md](docs/FEATURES.md) — what's built, what's a known gap
- [docs/SECURITY.md](docs/SECURITY.md) — auth, sessions, CSRF, PII handling

`php tests/smoke.php` is the load-bearing-logic gate — see
docs/DEVELOPMENT.md for what it covers.

## License

MIT — see [LICENSE](LICENSE).
