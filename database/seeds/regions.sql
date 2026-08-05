-- One representative row per division, enough for the diet engine and
-- onboarding form to have something real to filter by. Real upazila-level
-- coverage is a much bigger data-entry job than a seed file should carry.
INSERT IGNORE INTO regions (district, upazila) VALUES
('ঢাকা', 'ঢাকা সদর'),
('চট্টগ্রাম', 'চট্টগ্রাম সদর'),
('রাজশাহী', 'রাজশাহী সদর'),
('খুলনা', 'খুলনা সদর'),
('সিলেট', 'সিলেট সদর'),
('বরিশাল', 'বরিশাল সদর'),
('রংপুর', 'রংপুর সদর'),
('ময়মনসিংহ', 'ময়মনসিংহ সদর');
