-- Regions (for local food availability) and each patient's body profile.
CREATE TABLE regions (
    id        SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    district  VARCHAR(100) NOT NULL,
    upazila   VARCHAR(100) NOT NULL,
    UNIQUE KEY uq_region (district, upazila)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE body_profiles (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    age             TINYINT UNSIGNED NOT NULL,
    sex             ENUM('male','female','other') NOT NULL,
    height_cm       DECIMAL(5,1) NOT NULL,
    weight_kg       DECIMAL(5,1) NOT NULL,
    activity_level  ENUM('sedentary','light','moderate','active','very_active') NOT NULL,
    region_id       SMALLINT UNSIGNED NULL,
    budget_tier     ENUM('low','mid','high') NOT NULL DEFAULT 'mid',
    medical_flags_enc VARBINARY(1024) NULL,   -- AES-256-GCM ciphertext of the JSON flags array, never plaintext
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
