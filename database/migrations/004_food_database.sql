-- The food catalog and which region each item is actually available in.
CREATE TABLE food_items (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name_bn         VARCHAR(150) NOT NULL,
    name_en         VARCHAR(150) NOT NULL,
    category        ENUM('grain','vegetable','fruit','fish','meat','dairy','legume','spice','oil','other') NOT NULL,
    cost_tier       ENUM('low','mid','high') NOT NULL,
    per_100g_kcal   DECIMAL(6,1) NOT NULL,
    per_100g_protein_g DECIMAL(5,1) NOT NULL,
    per_100g_carb_g DECIMAL(5,1) NOT NULL,
    per_100g_fat_g  DECIMAL(5,1) NOT NULL,
    micros_json     JSON NULL,             -- {"iron_mg":..,"vitA_ug":..,"sodium_mg":..,...}
    tags            JSON NULL,             -- ["high_potassium","high_purine","low_gi",...] used by condition_rules
    data_source     ENUM('seed_unverified','verified_import') NOT NULL DEFAULT 'seed_unverified',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FULLTEXT INDEX ft_names (name_bn, name_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE food_availability (
    food_id     BIGINT UNSIGNED NOT NULL,
    region_id   SMALLINT UNSIGNED NOT NULL,
    seasonal    ENUM('year_round','seasonal') NOT NULL DEFAULT 'year_round',
    season_months JSON NULL,               -- [6,7,8] if seasonal
    PRIMARY KEY (food_id, region_id),
    FOREIGN KEY (food_id) REFERENCES food_items(id) ON DELETE CASCADE,
    FOREIGN KEY (region_id) REFERENCES regions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
