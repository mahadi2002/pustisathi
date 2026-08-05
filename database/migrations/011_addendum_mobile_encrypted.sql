-- ADDENDUM.
--
-- The mobile number is the one field here that's both sensitive and the
-- thing a subscriber logs in with, so it deserves the same treatment as any
-- other identity PII: encrypted at rest, looked up by a one-way hash instead
-- of the plaintext value. Ciphertext is never the same twice (random IV per
-- encryption), so the old UNIQUE constraint on the column itself has to go —
-- uniqueness now lives on the hash column instead.
ALTER TABLE users
    DROP INDEX mobile,
    MODIFY mobile VARBINARY(255) NULL,
    ADD COLUMN mobile_hash CHAR(64) NULL AFTER mobile,
    ADD UNIQUE KEY uq_users_mobile_hash (mobile_hash);
