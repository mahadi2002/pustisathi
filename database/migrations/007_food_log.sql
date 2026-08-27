-- What a patient actually ate, logged day by day.
CREATE TABLE food_logs (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id       BIGINT NOT NULL,
    food_id       BIGINT NOT NULL,
    portion_grams DECIMAL(6,1) NOT NULL,
    logged_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE RESTRICT
);
CREATE INDEX idx_user_date ON food_logs (user_id, logged_at);
