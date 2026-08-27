## What this changes and why

<!-- The "why" matters more than the "what" here — see CONTRIBUTING.md. -->

## How this was tested

- [ ] `php -l` on every changed file
- [ ] `php tests/smoke.php` passes (if you touched `Crypto`, `Csrf`,
      `Validator`, `DietPlanEngine`, or `ConditionRuleEngine`)
- [ ] Manually clicked through the changed flow in a browser — describe
      what you checked:

## Checklist

- [ ] No new Composer/npm dependency added (or, if one genuinely is
      needed, it's called out and justified below — zero third-party
      dependencies is a deliberate constraint of this app)
- [ ] No inline `style=""` or inline `<script>` added (CSP has no
      `unsafe-inline` — see docs/SECURITY.md)
- [ ] Every new echoed value goes through `e()`
- [ ] If a new seed file was added, it was actually run against a real
      database, not just read
- [ ] If this touches the nutritionist-patient relationship
      (`nutritionist_patients`/`clinical_notes`) or messaging, the
      direction was discussed in an issue first (see docs/FEATURES.md's
      Known open items)

## Screenshots (if this changes a view)
