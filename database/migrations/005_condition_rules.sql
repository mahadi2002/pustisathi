-- Medical-condition restrictions (diabetic, renal, cardiac, pregnancy) that
-- the diet engine applies when building a plan. The four rows below ship as
-- part of the migration rather than database/seeds/ because the engine
-- depends on them existing unconditionally, not only after a --seed run.
CREATE TABLE condition_rules (
    id                  SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    condition_code      VARCHAR(50) UNIQUE NOT NULL,   -- 'diabetic','renal','cardiac','pregnancy',...
    label_bn            VARCHAR(150) NOT NULL,
    restricted_tags     JSON NOT NULL,   -- food tags to exclude/limit
    required_tags       JSON NULL,       -- food tags to prioritize
    max_sodium_mg_day   INT UNSIGNED NULL,
    max_sugar_g_day     INT UNSIGNED NULL,
    notes_bn            TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO condition_rules (condition_code, label_bn, restricted_tags, required_tags, max_sodium_mg_day, max_sugar_g_day, notes_bn) VALUES
('diabetic', 'ডায়াবেটিস', '["high_gi","high_sugar"]', '["low_gi","high_fiber"]', NULL, 25, 'সাধারণ নির্দেশনা — চিকিৎসকের পরামর্শ আবশ্যক'),
('renal', 'কিডনি সমস্যা', '["high_potassium","high_sodium","high_phosphorus"]', NULL, 1500, NULL, 'সাধারণ নির্দেশনা — নেফ্রোলজিস্টের পরামর্শ আবশ্যক'),
('cardiac', 'হৃদরোগ', '["high_sodium","high_saturated_fat"]', '["high_fiber","omega3"]', 1500, NULL, 'সাধারণ নির্দেশনা — কার্ডিওলজিস্টের পরামর্শ আবশ্যক'),
('pregnancy', 'গর্ভাবস্থা', '["high_mercury","raw_unpasteurized"]', '["high_folate","high_iron"]', NULL, NULL, 'সাধারণ নির্দেশনা — গাইনোকোলজিস্টের পরামর্শ আবশ্যক');
