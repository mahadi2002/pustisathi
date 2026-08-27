-- Subscriptions and the billing events (charge success/fail, unsubscribe) that move them between states.
CREATE TABLE subscriptions (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT NOT NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'active',
    operator        TEXT NOT NULL CHECK (operator IN ('robi','airtel')),
    gateway         TEXT NOT NULL DEFAULT 'mock' CHECK (gateway IN ('mock','dcb')),
    gateway_ref     VARCHAR(191) NULL,        -- external subscription/txn id
    activated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_charge_at  TIMESTAMP NULL,
    next_charge_at  TIMESTAMP NULL,
    unsubscribed_at TIMESTAMP NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT chk_subscriptions_status CHECK (status IN ('active','unsubscribed','failed','expired'))
);
CREATE INDEX idx_status ON subscriptions (status);

CREATE TABLE billing_events (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    subscription_id BIGINT NOT NULL,
    event_type      TEXT NOT NULL CHECK (event_type IN ('charge_success','charge_fail','unsubscribe','webhook_unknown')),
    amount          DECIMAL(6,2) NULL,
    raw_payload     JSON NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
);
