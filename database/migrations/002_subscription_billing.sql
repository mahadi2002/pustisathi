-- Subscriptions and the billing events (charge success/fail, unsubscribe) that move them between states.
CREATE TABLE subscriptions (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       BIGINT UNSIGNED NOT NULL,
    status        ENUM('active','unsubscribed','failed','expired') NOT NULL DEFAULT 'active',
    operator      ENUM('robi','airtel') NOT NULL,
    gateway       ENUM('mock','dcb') NOT NULL DEFAULT 'mock',
    gateway_ref   VARCHAR(191) NULL,        -- external subscription/txn id
    activated_at  DATETIME NOT NULL,
    last_charge_at DATETIME NULL,
    next_charge_at DATETIME NULL,
    unsubscribed_at DATETIME NULL,
    created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE billing_events (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subscription_id BIGINT UNSIGNED NOT NULL,
    event_type      ENUM('charge_success','charge_fail','unsubscribe','webhook_unknown') NOT NULL,
    amount          DECIMAL(6,2) NULL,
    raw_payload     JSON NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
