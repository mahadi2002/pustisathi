-- The food catalog and which region each item is actually available in.
CREATE TABLE food_items (
    id                  BIGINT AUTO_INCREMENT PRIMARY KEY,
    name_bn             VARCHAR(150) NOT NULL,
    name_en             VARCHAR(150) NOT NULL,
    category            TEXT NOT NULL CHECK (category IN ('grain','vegetable','fruit','fish','meat','dairy','legume','spice','oil','other')),
    cost_tier           TEXT NOT NULL CHECK (cost_tier IN ('low','mid','high')),
    per_100g_kcal       DECIMAL(6,1) NOT NULL,
    per_100g_protein_g  DECIMAL(5,1) NOT NULL,
    per_100g_carb_g     DECIMAL(5,1) NOT NULL,
    per_100g_fat_g      DECIMAL(5,1) NOT NULL,
    micros_json         JSON NULL,             -- {"iron_mg":..,"vitA_ug":..,"sodium_mg":..,...}
    tags                JSON NULL,             -- ["high_potassium","high_purine","low_gi",...] used by condition_rules
    data_source         TEXT NOT NULL DEFAULT 'seed_unverified' CHECK (data_source IN ('seed_unverified','verified_import')),
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE food_availability (
    food_id       BIGINT NOT NULL,
    region_id     SMALLINT NOT NULL,
    seasonal      TEXT NOT NULL DEFAULT 'year_round' CHECK (seasonal IN ('year_round','seasonal')),
    season_months JSON NULL,               -- [6,7,8] if seasonal
    PRIMARY KEY (food_id, region_id),
    FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE
);
