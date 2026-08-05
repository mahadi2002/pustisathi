-- The job queue the cron script drains — rebill checks, plan refreshes, reminders.
CREATE TABLE jobs (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_type     VARCHAR(100) NOT NULL,     -- 'daily_rebill_check','plan_refresh','reminder'
    payload      JSON NULL,
    run_at       DATETIME NOT NULL,
    status       ENUM('pending','running','done','failed') NOT NULL DEFAULT 'pending',
    attempts     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_run (status, run_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
