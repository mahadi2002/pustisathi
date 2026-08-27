-- One representative row per division, enough for the diet engine and
-- onboarding form to have something real to filter by. Real upazila-level
-- coverage is a much bigger data-entry job than a seed file should carry.
--
-- MySQL, not Postgres — `ON CONFLICT DO NOTHING` is Postgres/SQLite syntax
-- and threw a SQL syntax error against this app's mysql: PDO DSN
-- (App\Core\Db). INSERT IGNORE is the MySQL equivalent, and actually dedupes
-- here (unlike food_items.sql) since regions has a real uq_region UNIQUE
-- (district, upazila) constraint — a repeat --seed run is a true no-op.
INSERT IGNORE INTO regions (district, upazila) VALUES
('ঢাকা', 'ঢাকা সদর'),
('চট্টগ্রাম', 'চট্টগ্রাম সদর'),
('রাজশাহী', 'রাজশাহী সদর'),
('খুলনা', 'খুলনা সদর'),
('সিলেট', 'সিলেট সদর'),
('বরিশাল', 'বরিশাল সদর'),
('রংপুর', 'রংপুর সদর'),
('ময়মনসিংহ', 'ময়মনসিংহ সদর');
