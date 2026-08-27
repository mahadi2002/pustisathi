-- ADDENDUM.
--
-- The mobile number is the one field here that's both sensitive and the
-- thing a subscriber logs in with, so it deserves the same treatment as any
-- other identity PII: encrypted at rest, looked up by a one-way hash instead
-- of the plaintext value. Ciphertext is never the same twice (random IV per
-- encryption), so the old UNIQUE constraint on the column itself has to go —
-- uniqueness now lives on the hash column instead.
--
-- The column is blanked (not byte-reinterpreted) on the type change: old
-- plaintext digits are not valid ciphertext, and reinterpreting them as such
-- would just store garbage rather than a real encrypted value.
--
-- TEXT: App\Core\Crypto::encrypt() always returns a base64 string (never
-- raw bytes), so the column only ever holds printable ASCII.
ALTER TABLE users DROP INDEX uq_users_mobile;
UPDATE users SET mobile = NULL;
ALTER TABLE users MODIFY COLUMN mobile TEXT NULL;
ALTER TABLE users ADD COLUMN mobile_hash CHAR(64) NULL;
ALTER TABLE users ADD CONSTRAINT uq_users_mobile_hash UNIQUE (mobile_hash);
