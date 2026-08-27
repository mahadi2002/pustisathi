-- A working starter set of common Bangladeshi foods — enough to make food
-- search and the diet plan engine actually functional. Values are typical
-- reference figures, not from a licensed source, hence data_source stays
-- at its default 'seed_unverified' (see the known-gap note in the build
-- docs) until a verified composition table replaces this via the admin
-- CSV import.
--
-- MySQL, not Postgres — `ON CONFLICT DO NOTHING` is Postgres/SQLite syntax
-- and threw a SQL syntax error against this app's mysql: PDO DSN
-- (App\Core\Db). INSERT IGNORE is the MySQL equivalent; food_items has no
-- unique key besides its auto-increment id, so this doesn't dedupe rows on
-- a repeat --seed run any more than the original ON CONFLICT (with no
-- explicit target) would have against a table with the same lack of a
-- unique constraint — it just stops the seed from crashing.
INSERT IGNORE INTO food_items (name_bn, name_en, category, cost_tier, per_100g_kcal, per_100g_protein_g, per_100g_carb_g, per_100g_fat_g, tags) VALUES
('সাদা ভাত', 'White rice, cooked', 'grain', 'low', 130.0, 2.7, 28.0, 0.3, '["high_gi"]'),
('লাল চালের ভাত', 'Brown rice, cooked', 'grain', 'mid', 123.0, 2.6, 26.0, 1.0, '["low_gi","high_fiber"]'),
('আটার রুটি', 'Wheat roti', 'grain', 'low', 240.0, 7.9, 50.0, 1.2, '["moderate_gi"]'),
('মুড়ি', 'Puffed rice', 'grain', 'low', 325.0, 7.0, 74.0, 0.5, '["high_gi"]'),

('মসুর ডাল', 'Red lentil dal, cooked', 'legume', 'low', 116.0, 9.0, 20.0, 0.4, '["high_fiber","low_gi"]'),
('ছোলা', 'Chickpeas, cooked', 'legume', 'low', 164.0, 8.9, 27.0, 2.6, '["high_fiber"]'),
('মুগ ডাল', 'Mung dal, cooked', 'legume', 'low', 105.0, 7.0, 19.0, 0.4, '["high_fiber","low_gi"]'),

('রুই মাছ', 'Rohu fish', 'fish', 'mid', 97.0, 16.6, 0.0, 3.3, '["high_protein","omega3"]'),
('ইলিশ মাছ', 'Hilsa fish', 'fish', 'high', 273.0, 21.0, 0.0, 20.0, '["high_protein","omega3"]'),
('পাঙ্গাস মাছ', 'Pangas fish', 'fish', 'low', 90.0, 15.0, 0.0, 3.0, '["high_protein"]'),
('চিংড়ি', 'Shrimp', 'fish', 'mid', 99.0, 24.0, 0.2, 0.3, '["high_protein","high_purine"]'),

('মুরগির মাংস', 'Chicken, skinless', 'meat', 'mid', 165.0, 31.0, 0.0, 3.6, '["high_protein"]'),
('গরুর মাংস', 'Beef', 'meat', 'high', 250.0, 26.0, 0.0, 15.0, '["high_protein","high_saturated_fat"]'),
('ডিম', 'Egg, whole', 'meat', 'low', 155.0, 13.0, 1.1, 11.0, '["high_protein","high_cholesterol"]'),

('দুধ', 'Milk, whole', 'dairy', 'mid', 61.0, 3.2, 4.8, 3.3, '[]'),
('দই', 'Yogurt, plain', 'dairy', 'mid', 61.0, 3.5, 4.7, 3.3, '[]'),
('পনির', 'Paneer', 'dairy', 'mid', 265.0, 18.0, 1.2, 20.0, '["high_saturated_fat"]'),

('আলু', 'Potato, boiled', 'vegetable', 'low', 87.0, 1.9, 20.0, 0.1, '["high_gi"]'),
('পালং শাক', 'Spinach', 'vegetable', 'low', 23.0, 2.9, 3.6, 0.4, '["high_fiber","high_iron","high_potassium"]'),
('লাউ', 'Bottle gourd', 'vegetable', 'low', 15.0, 0.6, 3.4, 0.1, '["low_gi"]'),
('বেগুন', 'Eggplant', 'vegetable', 'low', 25.0, 1.0, 6.0, 0.2, '["high_fiber"]'),
('টমেটো', 'Tomato', 'vegetable', 'low', 18.0, 0.9, 3.9, 0.2, '["low_gi"]'),
('গাজর', 'Carrot', 'vegetable', 'low', 41.0, 0.9, 10.0, 0.2, '["high_fiber","high_vitA"]'),
('ফুলকপি', 'Cauliflower', 'vegetable', 'low', 25.0, 1.9, 5.0, 0.3, '["high_fiber"]'),
('বাঁধাকপি', 'Cabbage', 'vegetable', 'low', 25.0, 1.3, 6.0, 0.1, '["high_fiber"]'),
('করলা', 'Bitter gourd', 'vegetable', 'low', 17.0, 1.0, 3.7, 0.2, '["low_gi","high_fiber"]'),
('মিষ্টি কুমড়া', 'Pumpkin', 'vegetable', 'low', 26.0, 1.0, 6.5, 0.1, '["high_vitA"]'),

('কলা', 'Banana', 'fruit', 'low', 89.0, 1.1, 23.0, 0.3, '["high_potassium"]'),
('আম', 'Mango', 'fruit', 'mid', 60.0, 0.8, 15.0, 0.4, '["high_sugar"]'),
('পেঁপে', 'Papaya', 'fruit', 'low', 43.0, 0.5, 11.0, 0.3, '["high_fiber"]'),
('কাঁঠাল', 'Jackfruit', 'fruit', 'mid', 95.0, 1.7, 23.0, 0.6, '["high_sugar"]'),
('আপেল', 'Apple', 'fruit', 'mid', 52.0, 0.3, 14.0, 0.2, '["high_fiber"]'),
('পেয়ারা', 'Guava', 'fruit', 'low', 68.0, 2.6, 14.0, 1.0, '["high_fiber","high_vitC"]'),

('সয়াবিন তেল', 'Soybean oil', 'oil', 'low', 884.0, 0.0, 0.0, 100.0, '["high_fat"]'),
('আদা', 'Ginger', 'spice', 'low', 80.0, 1.8, 18.0, 0.8, '[]'),
('রসুন', 'Garlic', 'spice', 'low', 149.0, 6.4, 33.0, 0.5, '[]'),
('চিনি', 'Sugar', 'other', 'low', 387.0, 0.0, 100.0, 0.0, '["high_sugar","high_gi"]');
