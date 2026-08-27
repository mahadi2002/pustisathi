---
name: Feature request
about: Propose a new feature or a change to existing behavior
title: ''
labels: enhancement
assignees: ''
---

**What problem does this solve**

Describe the gap or pain point — not the solution yet.

**Proposed approach**

How you'd build it. If it touches the nutritionist-patient relationship
(`nutritionist_patients`/`clinical_notes` tables) or messaging, note that
explicitly — see [docs/FEATURES.md](../../docs/FEATURES.md)'s Known open
items, both are deliberately unbuilt pending a direction decision, and a
PR that starts building on top of that schema without that decision first
being made is likely to get redirected.

**Does this need a schema change**

If yes, sketch the migration (`database/migrations/0NN_addendum_*.sql`,
never an edit to an already-applied migration file).

**Alternatives considered**

Any other approach you thought about and why you didn't go with it.
