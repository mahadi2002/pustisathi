-- ADDENDUM.
--
-- A daily carrier charge can miss for reasons that have nothing to do with
-- someone wanting out — a bad network moment, a balance that's briefly too
-- low. Kicking a subscriber straight to expired on the first miss punishes
-- exactly the people who'd have paid the very next day. 'grace' gives one
-- retry cycle with access still on before landing on 'expired'; 'pending'
-- covers a gateway that confirms the charge asynchronously after OTP,
-- rather than in the same request.
ALTER TABLE subscriptions
    MODIFY status ENUM('pending','active','grace','unsubscribed','failed','expired') NOT NULL DEFAULT 'pending';
