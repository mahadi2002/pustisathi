-- ADDENDUM.
--
-- otp_requests.mobile was left in plaintext when 011 encrypted users.mobile —
-- an oversight, not a deliberate exception. This table never needs the
-- plaintext back (OtpService only ever compares, never displays it), so a
-- one-way blind-index hash is enough here, no ciphertext/decrypt path needed.
DROP INDEX idx_mobile ON otp_requests;
ALTER TABLE otp_requests CHANGE COLUMN mobile mobile_hash CHAR(64) NOT NULL;
CREATE INDEX idx_mobile_hash ON otp_requests (mobile_hash);
