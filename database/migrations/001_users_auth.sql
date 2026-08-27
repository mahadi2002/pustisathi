-- Accounts (one table, one role column — patient/nutritionist/admin all
-- live here), OTP requests, DB-backed sessions, and rate-limit buckets.
CREATE TABLE users (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    role                TEXT NOT NULL DEFAULT 'patient' CHECK (role IN ('patient','nutritionist','admin')),
    mobile              VARCHAR(15) NULL,           -- 01XXXXXXXXX, patients
    email               VARCHAR(191) UNIQUE NULL,   -- nutritionist/admin
    password_hash       VARCHAR(255) NULL,          -- staff only; patients auth via OTP
    operator            TEXT NULL CHECK (operator IN ('robi','airtel','unknown')),
    nutritionist_status TEXT NULL CHECK (nutritionist_status IN ('pending','approved','rejected')),
    nutritionist_creds  VARCHAR(255) NULL,          -- credential/license text, admin-verified
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP NULL,
    CONSTRAINT uq_users_mobile UNIQUE (mobile)
);

CREATE TABLE otp_requests (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    mobile        VARCHAR(15) NOT NULL,
    otp_hash      VARCHAR(255) NOT NULL,
    expires_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    consumed_at   TIMESTAMP NULL,
    attempt_count SMALLINT NOT NULL DEFAULT 0 CHECK (attempt_count >= 0),
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_mobile ON otp_requests (mobile);

CREATE TABLE sessions (
    id          VARCHAR(64) PRIMARY KEY,
    user_id     BIGINT NOT NULL,
    payload     TEXT NOT NULL,
    last_active TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE rate_limits (
    id           BIGINT AUTO_INCREMENT PRIMARY KEY,
    bucket_key   VARCHAR(191) NOT NULL,   -- e.g. 'otp_request:ip:...' or 'foods_search:ip:...'
    window_start TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    count        INTEGER NOT NULL DEFAULT 0 CHECK (count >= 0),
    CONSTRAINT uq_bucket_window UNIQUE (bucket_key, window_start)
);
