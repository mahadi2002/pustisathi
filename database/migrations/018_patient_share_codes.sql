-- ADDENDUM.
--
-- Lets a patient hand a nutritionist a short code instead of the
-- nutritionist needing to already know their account — the code lives on
-- body_profiles (one row per patient already) rather than a new table,
-- since it's a single nullable slot per patient, not a history.
--
-- NULL is intentionally allowed and NOT unique-violating in MySQL/InnoDB
-- (a unique index permits any number of NULLs) — most patients will never
-- generate a code, and that must not collide with each other.
ALTER TABLE body_profiles ADD COLUMN share_code CHAR(8) NULL;
CREATE UNIQUE INDEX uq_body_profiles_share_code ON body_profiles (share_code);
