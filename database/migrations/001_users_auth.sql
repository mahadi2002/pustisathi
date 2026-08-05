-- Accounts (one table, one role column — patient/nutritionist/admin all
-- live here), OTP requests, DB-backed sessions, and rate-limit buckets.
CREATE TABLE users (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role                ENUM('patient','nutritionist','admin') NOT NULL DEFAULT 'patient',
    mobile              VARCHAR(15) UNIQUE NULL,           -- 01XXXXXXXXX, patients
    email               VARCHAR(191) UNIQUE NULL,          -- nutritionist/admin
    password_hash       VARCHAR(255) NULL,                 -- staff only; patients auth via OTP
    operator            ENUM('robi','airtel','unknown') NULL,
    nutritionist_status ENUM('pending','approved','rejected') NULL,
    nutritionist_creds  VARCHAR(255) NULL,                 -- credential/license text, admin-verified
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE otp_requests (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    mobile       VARCHAR(15) NOT NULL,
    otp_hash     VARCHAR(255) NOT NULL,
    expires_at   DATETIME NOT NULL,
    consumed_at  DATETIME NULL,
    attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_mobile (mobile)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sessions (
    id           VARCHAR(64) PRIMARY KEY,
    user_id      BIGINT UNSIGNED NOT NULL,
    payload      TEXT NOT NULL,
    last_active  DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rate_limits (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bucket_key   VARCHAR(191) NOT NULL,   -- e.g. 'otp_request:ip:...' or 'foods_search:ip:...'
    window_start DATETIME NOT NULL,
    count        INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_bucket_window (bucket_key, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
