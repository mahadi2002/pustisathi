-- ADDENDUM.
--
-- Billing and security actions need a trail that survives the actors
-- involved changing their minds about what happened — a subscriber disputes
-- a charge, a staff account gets questioned, someone asks "did this really
-- get cancelled." ip_hash rather than a raw IP for the same reason mobile
-- numbers are encrypted: no reason to keep something reversible around that
-- doesn't need to be.
CREATE TABLE audit_log (
    id           BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT NULL,
    action       VARCHAR(100) NOT NULL,
    details_json JSON NULL,
    ip_hash      CHAR(64) NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);
CREATE INDEX idx_action_time ON audit_log (action, created_at);
