-- ADDENDUM.
--
-- otp_requests.mobile was left in plaintext when 011 encrypted users.mobile —
-- an oversight, not a deliberate exception. This table never needs the
-- plaintext back (OtpService only ever compares, never displays it), so a
-- one-way blind-index hash is enough here, no ciphertext/decrypt path needed.
ALTER TABLE otp_requests
    DROP INDEX idx_mobile,
    CHANGE COLUMN mobile mobile_hash CHAR(64) NOT NULL,
    ADD INDEX idx_mobile_hash (mobile_hash);
