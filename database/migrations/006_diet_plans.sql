-- Generated diet plans and the individual meal lines that make them up.
CREATE TABLE diet_plans (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id          BIGINT UNSIGNED NOT NULL,          -- the patient
    created_by       BIGINT UNSIGNED NOT NULL,          -- self (=user_id) or nutritionist user id
    source           ENUM('self_generated','nutritionist_authored') NOT NULL,
    target_kcal      INT UNSIGNED NOT NULL,
    macro_targets_json JSON NOT NULL,   -- {"protein_g":..,"carb_g":..,"fat_g":..}
    budget_tier      ENUM('low','mid','high') NOT NULL,
    condition_codes  JSON NULL,         -- applied condition_rules at generation time
    status           ENUM('active','archived') NOT NULL DEFAULT 'active',
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE plan_meals (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id       BIGINT UNSIGNED NOT NULL,
    meal_slot     ENUM('breakfast','lunch','dinner','snack') NOT NULL,
    food_id       BIGINT UNSIGNED NOT NULL,
    portion_grams DECIMAL(6,1) NOT NULL,
    sort_order    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (plan_id) REFERENCES diet_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
