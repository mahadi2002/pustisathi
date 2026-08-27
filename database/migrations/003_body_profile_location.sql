-- Regions (for local food availability) and each patient's body profile.
CREATE TABLE regions (
    id       SMALLINT AUTO_INCREMENT PRIMARY KEY,
    district VARCHAR(100) NOT NULL,
    upazila  VARCHAR(100) NOT NULL,
    CONSTRAINT uq_region UNIQUE (district, upazila)
);

CREATE TABLE body_profiles (
    id                BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id           BIGINT NOT NULL,
    age               SMALLINT NOT NULL CHECK (age >= 0),
    sex               TEXT NOT NULL CHECK (sex IN ('male','female','other')),
    height_cm         DECIMAL(5,1) NOT NULL,
    weight_kg         DECIMAL(5,1) NOT NULL,
    activity_level    TEXT NOT NULL CHECK (activity_level IN ('sedentary','light','moderate','active','very_active')),
    region_id         SMALLINT NULL,
    budget_tier       TEXT NOT NULL DEFAULT 'mid' CHECK (budget_tier IN ('low','mid','high')),
    medical_flags_enc TEXT NULL,   -- base64(iv|tag|ciphertext) from App\Core\Crypto — always printable ASCII, never raw binary
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE SET NULL
);
