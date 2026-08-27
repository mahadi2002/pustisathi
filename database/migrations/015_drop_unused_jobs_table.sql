-- The `jobs` table (009_jobs.sql) was speculative queue infrastructure for
-- a cron that was never written — cron/charge_cycle.php (added later) polls
-- `subscriptions.next_charge_at` directly instead, matching the pattern
-- used by every other site in this workspace. Nothing has ever read or
-- written this table; drop it rather than carry dead schema forward.
DROP TABLE IF EXISTS jobs;
