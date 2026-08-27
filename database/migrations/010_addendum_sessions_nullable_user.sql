-- ADDENDUM.
--
-- The original schema had sessions.user_id as NOT NULL, but the OTP
-- subscribe flow and CSRF protection on public forms both need a session
-- row to exist for a visitor before they're authenticated — there's no
-- user to attach it to yet. Widening the column to nullable is the smallest
-- change that keeps DB-backed sessions working for anonymous visitors, and
-- it's a separate file rather than an edit to the original schema so the
-- change is easy to spot and review on its own.
ALTER TABLE sessions MODIFY COLUMN user_id BIGINT NULL;
