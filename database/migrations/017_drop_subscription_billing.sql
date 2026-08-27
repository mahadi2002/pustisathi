-- Subscription/billing removal — this is a hobby project now: no
-- billing, no plans, access is just being logged in (see
-- app/Middleware/RequireAuth.php). Additive drop, following
-- 015_drop_unused_jobs_table.sql's precedent rather than editing the
-- historical 002_subscription_billing.sql / 012_addendum_subscription_grace.sql.
-- billing_events has a FK to subscriptions, so it goes first.
DROP TABLE IF EXISTS billing_events;
DROP TABLE IF EXISTS subscriptions;
