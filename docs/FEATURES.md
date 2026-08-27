# Features

## Email+password is the only auth, for every role

`/login` (`LoginController`) is the one screen every role signs in
through (patients, returning nutritionists, admin) — it looks the email
up and routes by whatever role is already on file, never guessing or
creating a new role for an existing account. A brand-new patient starts
at `/register`; a brand-new nutritionist instead starts at
`/nutri/register`, which collects a credentials/license text field up
front and creates the account as `nutritionist`/`pending` — approval is
never self-service (see below). Both registration flows are explicit
account creation, not find-or-create-on-first-login. Password reset
(`/forgot-password` → emailed link → `/reset-password/{token}`) is the
only account-recovery path — see docs/SECURITY.md for the token handling.

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
(`RateLimit`); anyone logged in (`Controller::isAuthenticated()`), any
role, has no cap.

## No billing — every feature is free behind a login

There is no subscription, no billing, no payment gateway anywhere in this
app; it's a hobby project. The diet plan (`/app/onboarding`, `/app/plan`
and its regeneration, `/app/dashboard`) sits behind `RequireAuth` only —
the same "any logged-in role" gate as the rest of `/app/*` — so a patient
gets full access the moment they register, nothing to activate or pay
for. Nutritionist access is gated the same way plus one extra check:
`RequireNutritionist` also requires `nutritionist_status = 'approved'`,
which only an admin can set (see docs/SECURITY.md's "Role / approval
gates"). An account that's never approved, or one an admin later
unapproves, simply can't reach `/nutri*` — there's no lapsed-subscription
concept to reason about, just "logged in" and, for nutritionists,
"approved."

(An earlier version of this app billed ৳2.78/day over mobile carrier
billing, gated by a `subscriptions.status` state machine. That entire
system — `SubscriptionService`, `MockGateway`/`DcbGateway`,
`RequireSubscription`, the `subscriptions`/`billing_events` tables — was
removed; see `database/migrations/017_drop_subscription_billing.sql`.)

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
middleware has already confirmed role (+ approval, for nutritionist —
see docs/SECURITY.md's "Role / approval gates"; there's no subscription
check anymore). Neither has a full CRUD panel, but both render live data:
`AdminController::home()` shows counts (patients, nutritionists, pending
approvals, food items, diet plans generated) pulled with one query;
`NutriController::home()` shows the nutritionist's own linked-patient
roster (see the next item — this one is a real feature, not a
placeholder).

## Known open items

- **The nutritionist↔patient relationship is built, in a minimal form.**
  `nutritionist_patients` and `clinical_notes` (from
  `008_nutritionist_patient.sql`) went unused for a while, but
  `NutriController` now reads and writes both: a patient generates an
  8-character share code from their dashboard
  (`DashboardController::shareCode()`, stored on
  `body_profiles.share_code`), hands it to their nutritionist, and the
  nutritionist enters it at `/nutri` (`NutriController::link()`) to create
  the link. `/nutri/patients/{id}` (`NutriController::patient()`) shows
  that patient's profile and a reverse-chronological `clinical_notes`
  thread; `/nutri/patients/{id}/notes` (`NutriController::addNote()`)
  appends to it. It's intentionally minimal — no real-time chat, no read
  receipts, no note-editing, no plan override/authoring flow tying into
  `diet_plans.created_by`/`source = 'nutritionist_authored'` (which the
  schema and `DietPlanEngine::persist()` support but nothing populates
  that way yet) — but "entirely unbuilt" is no longer an accurate
  description of it.
- **Messaging is 0% built at every layer.** No table, no controller, no
  route, no view, no mention in the schema at all. If patient↔nutritionist
  communication is wanted, it needs a schema addendum
  (`0NN_addendum_*.sql`) from scratch, not an extension of an existing
  half-built table.
- **`food_logs` (007) has a schema and no code.** No controller, service,
  or view reads or writes it. The home page's feature grid correctly lists
  "দৈনিক Food Log" under "শীঘ্রই আসছে."
- **The old mobile+OTP/carrier-billing system is gone, not stubbed.**
  An earlier version of this app authenticated via mobile+OTP and billed
  ৳2.78/day through direct-carrier-billing (`DcbGateway`/`MockGateway`,
  `SubscriptionService`, `OtpService`). All of it — code, routes,
  `subscriptions`/`billing_events`/`otp_requests` tables — was removed in
  favor of plain email+password auth with no billing at all (see
  `database/migrations/016_email_password_auth.sql` and
  `017_drop_subscription_billing.sql`). There is nothing left to finish
  here; this is a completed removal, not an open item.
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
- **`Repositories/` layer is only partially adopted** — see
  docs/DEVELOPMENT.md's Known gaps.
