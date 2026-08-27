# Features

## Mobile+OTP is the only auth, for every role

No email/password anywhere in the app. `/subscribe` is the one screen
every role signs in through (patients, returning nutritionists, admin) —
`SubscribeController::verifyOtp()` looks the number up and routes by
whatever role is already on file, never guessing or creating a new role
for an existing number. A brand-new nutritionist instead starts at
`/nutri/register`, which collects a credentials/license text field up
front and creates the account as `nutritionist`/`pending` — approval is
never self-service (see below). `OtpService::DEV_CODE` (`123456`) verifies
against any number only when `SUBSCRIPTION_GATEWAY=mock`, which
`bootstrap.php` already refuses to run when `APP_ENV=production`.

## Free tier: BMI/BMR calculator + food search

`GET /calculator` — client-side only Mifflin-St Jeor BMR calculation
(`public/assets/js/app.js`), Asian BMI cutoffs (`bmi_category()`:
<18.5 Underweight, <23 Normal, <25 Overweight, else Obese — matches
Bangladeshi clinical practice, not the WHO general-population cutoffs).
Nothing is sent to the server or stored.

`GET /foods` — name search against `food_items` (`LIKE` on `name_bn`/
`name_en`), full page or JSON depending on `Accept` header (the same URL
backs both the no-JS form and the live-search JS). Guests are capped at
`FOODS_SEARCH_DAILY_CAP_GUEST` (default 10) searches/day, keyed by IP hash
(`RateLimit`); an active subscriber (`Controller::isSubscribed()`) has no
cap.

## The subscription: ৳2.78/day, no free tier for the diet plan itself

`subscriptions.status` state machine, driven by `SubscriptionService` and
the daily `cron/charge_cycle.php`:

```
pending --(first successful charge)--> active
active  --(missed charge)------------> grace   (one retry cycle, access stays on)
grace   --(missed charge again)------> expired (access lost)
grace   --(charge succeeds)----------> active
(pending|active|grace) --(user action)--> unsubscribed
```

There is no `pending`→`failed` retry loop distinct from grace — a
subscription that never had a first successful charge goes straight to
`failed` on that first miss (`SubscriptionService::recordChargeFailure()`'s
`match` — `'active' => 'grace', 'grace' => 'expired', default => 'failed'`).
`unsubscribe()` deliberately does **not** revoke the session — the account
and login stay usable (free calculator/food-search browsing, resubscribing
later); only `RequireSubscription`/`RequireNutritionist`-gated routes lose
access, on the very next request, since access is always a live DB read
(see docs/ARCHITECTURE.md).

`MockGateway::verifyOtp()` activates every non-admin account immediately
on first OTP verification — there's no separate "start subscription"
action. Nutritionist registration goes through the identical mechanism
(`NutriRegisterController::verifyOtp()` → `SubscriptionService::activate()`)
at the same price, gated by the same `RequireNutritionist` middleware that
also checks `nutritionist_status === 'approved'`.

## Diet plan generation (`app/Rules/DietPlanEngine.php`)

The one piece of real domain-specific logic in the app, and the reason a
body profile → dashboard flow exists at all.

1. **BMR** — Mifflin-St Jeor: `10×weight_kg + 6.25×height_cm − 5×age`,
   `+5` for male, `−161` for female, `−78` (the midpoint of the two, not a
   separate clinical formula) for `other`.
2. **TDEE** — BMR × an activity multiplier (`sedentary` 1.2 → `very_active`
   1.9, the standard Harris-Benedict/Mifflin activity-factor table).
   `targetKcal = round(BMR × multiplier)`.
3. **Macro split** — protein fixed at `1.2 g/kg` bodyweight; fat at 25% of
   `targetKcal` (÷9 kcal/g); carbs get whatever calories are left over
   (÷4 kcal/g), floored at 0 if protein+fat alone exceed the target.
4. **Condition restrictions** — `ConditionRuleEngine::restrictionsFor()`
   takes the profile's decrypted condition codes (diabetic/renal/cardiac/
   pregnancy, from `medical_flags_enc`) and returns the union of each
   condition's `restricted_tags` (foods carrying any of these tags are
   excluded outright) and `required_tags` (foods carrying one of these are
   preferred, not mandatory — see the required-tag-unmet log below).
5. **Food selection** — `eligibleFoods()` filters `food_items` by budget
   tier (`low`/`mid`/`high`, cumulative — a `high` budget can still pick
   `low`-tier foods) and, if the profile has a region, by
   `food_availability`. `assembleMeal()` then walks a fixed per-slot
   template (`MEAL_TEMPLATE`: breakfast/lunch/dinner each split across
   grain/protein/vegetable category groups with calorie shares that sum to
   1.0; snack is 100% fruit/dairy) and for each slot line, sorts the
   eligible pool by (has a required tag) → (not already used elsewhere in
   the plan) → (id, for determinism), picking the top result.
6. **Portion sizing** — `grams = (mealKcal × slotShare / food's per_100g_kcal) × 100`,
   clamped to 20–400g so the math can never suggest an absurd serving size.
7. If a slot's winning food has none of the condition's required tags (the
   eligible pool for that category simply didn't contain one), the plan
   still gets built — "no food in stock beats no plan at all" — but
   `Logger::warning('diet_plan.required_tag_unmet', ...)` fires so this
   doesn't vanish silently. There's no UI surfacing of that warning today;
   it's log-only.
8. **Deterministic and idempotent for a given profile** — no randomness
   anywhere in the algorithm. Regenerating with an unchanged profile
   produces the same plan (`views/patient/plan.php` says exactly this to
   the user). `DietPlanEngine::generate()` archives the previous active
   plan (`status = 'archived'`) rather than deleting it — history exists in
   the table, but nothing in the UI lists past plans (see Known open
   items).

`tests/smoke.php` checks this math against known inputs — see
docs/DEVELOPMENT.md.

## Admin and nutritionist landing pages are real, not dead ends

Both `/admin` and `/nutri` are reached only after their respective
middleware has already confirmed role (+ approval + subscription, for
nutritionist). Neither has a CRUD panel yet, but both render live data
instead of a placeholder: `AdminController::home()` shows counts (patients,
nutritionists, pending approvals, active subscriptions, food items, diet
plans generated) pulled with one query; `NutriController::home()` is
currently just a welcome card (see Known open items — this one really is a
placeholder, unlike admin's).

## Known open items

- **The nutritionist↔patient relationship is entirely unbuilt.** The
  schema (`nutritionist_patients`, `clinical_notes`, from
  `008_nutritionist_patient.sql`) exists and migrates cleanly, but **zero
  application code reads or writes either table** — no repository, no
  service, no controller action, no route. `views/nutri/home.php` is a
  literal placeholder card ("Patient Roster ও Plan Editor শীঘ্রই আসছে…") and
  the home page's feature grid lists "Nutritionist Review" under
  "শীঘ্রই আসছে" (coming soon), not as a live feature. A nutritionist who
  registers, gets approved, and pays the subscription today lands on that
  placeholder with no way to see or do anything patient-related. Whether
  to build this for real (patient roster, plan override/authoring flow
  tying into `diet_plans.created_by`/`source = 'nutritionist_authored'`,
  which the schema and `DietPlanEngine::persist()` already support) or
  formally drop the dead schema is an open decision — not made in this
  pass, and this codebase should not be read as "almost done" on this
  feature. See the `POST /app/plan/regenerate`-adjacent `source` field for
  the one place the schema already anticipates nutritionist-authored plans
  without anything populating it that way today.
- **Messaging is 0% built at every layer.** No table, no controller, no
  route, no view, no mention in the schema at all. If patient↔nutritionist
  communication is wanted, it needs a schema addendum
  (`0NN_addendum_*.sql`) from scratch, not an extension of an existing
  half-built table.
- **`food_logs` (007) has a schema and no code.** No controller, service,
  or view reads or writes it. The home page's feature grid correctly lists
  "দৈনিক Food Log" under "শীঘ্রই আসছে."
- **`DcbGateway` (real direct-carrier-billing) is a stub.** Every method
  throws `GatewayNotConfiguredException` even once all five `DCB_*` env
  vars are present — the actual SDP/OTP API contract and request/response
  shapes haven't been integrated. `SUBSCRIPTION_GATEWAY=mock` is what every
  environment runs today, and `bootstrap.php` hard-blocks it in
  production.
- **Food/region data is a seed-only placeholder, not sourced content.**
  35 food items (hand-picked common Bangladeshi foods, reference nutrition
  figures, not from a licensed composition database — `data_source`
  defaults to `'seed_unverified'`), 8 regions (one representative
  district per division, not real upazila-level coverage), and a
  mechanical every-food×every-region "year_round" availability
  cross-product (`database/seeds/food_availability.php`'s own comment: "a
  placeholder so the diet engine's region filter has something to match
  against instead of finding nothing everywhere"). Real seasonal/regional
  variation and a verified nutrition source are both unresolved decisions
  — not something to silently expand with more hand-typed rows.
- **Plan history has no UI.** `DietPlanEngine::generate()` archives
  (doesn't delete) the previous plan on regeneration, so the data exists,
  but nothing lists past plans or lets a user compare them. Listed
  correctly as "শীঘ্রই আসছে" ("Plan History + PDF Export") on the home
  page.
- **BTRC operator prefix map unverified** — see docs/DEVELOPMENT.md.
- **`Repositories/` layer is only partially adopted** — see
  docs/DEVELOPMENT.md's Known gaps.
