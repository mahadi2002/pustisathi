# PustiSathi (পুষ্টিসাথী)

Bangla nutrition app: a free BMI/calorie estimate, then a personalized
daily diet plan matched to body profile, budget, and local food
availability — plus a patient↔nutritionist matching and messaging loop for
people who want an actual professional involved. Gated behind a ৳2.78/day
DCB micro-subscription for Robi & Airtel users, mobile+OTP only, no
passwords. Third app in this workspace's series (after GardenBondhu,
IELTS Master BD) — same architecture, re-skinned.

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

### Logging in without setting up real billing

The billing gateway defaults to a mock implementation (`SUBSCRIPTION_GATEWAY=mock`
in `.env`), and OTP login accepts the fixed dev code `123456` for any
number when `APP_ENV=local` — no real DCB credentials or SMS delivery
needed to test the app end to end. Demo accounts from the seed:

- Admin: mobile `01899999999`, OTP `123456` — no subscription required.
- Nutritionist: mobile `01812345678`, OTP `123456`.

## Docs

Not written yet for this app — `docs/`, `tests/smoke.php`, and `cron/`
exist as empty scaffolding but haven't been filled in, unlike the other
three apps in the series. Until then, `app/Rules/`, `app/Services/`, and
`database/migrations/` are the most direct way to see how it actually
works.

## License

Private — all rights reserved.
