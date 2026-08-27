-- Generated diet plans and the individual meal lines that make them up.
CREATE TABLE diet_plans (
    id                 BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id            BIGINT NOT NULL,          -- the patient
    created_by         BIGINT NOT NULL,          -- self (=user_id) or nutritionist user id
    source             TEXT NOT NULL CHECK (source IN ('self_generated','nutritionist_authored')),
    target_kcal        INTEGER NOT NULL CHECK (target_kcal >= 0),
    macro_targets_json JSON NOT NULL,   -- {"protein_g":..,"carb_g":..,"fat_g":..}
    budget_tier        TEXT NOT NULL CHECK (budget_tier IN ('low','mid','high')),
    condition_codes    JSON NULL,         -- applied condition_rules at generation time
    status             TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','archived')),
    created_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE plan_meals (
    id            BIGINT AUTO_INCREMENT PRIMARY KEY,
    plan_id       BIGINT NOT NULL,
    meal_slot     TEXT NOT NULL CHECK (meal_slot IN ('breakfast','lunch','dinner','snack')),
    food_id       BIGINT NOT NULL,
    portion_grams DECIMAL(6,1) NOT NULL,
    sort_order    SMALLINT NOT NULL DEFAULT 0 CHECK (sort_order >= 0),
    FOREIGN KEY (plan_id) REFERENCES diet_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE RESTRICT
);
