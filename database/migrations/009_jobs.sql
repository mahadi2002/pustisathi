-- The job queue the cron script drains — rebill checks, plan refreshes, reminders.
CREATE TABLE jobs (
    id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    job_type   VARCHAR(100) NOT NULL,     -- 'daily_rebill_check','plan_refresh','reminder'
    payload    JSON NULL,
    run_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status     VARCHAR(20) NOT NULL DEFAULT 'pending' CHECK (status IN ('pending','running','done','failed')),
    attempts   SMALLINT NOT NULL DEFAULT 0 CHECK (attempts >= 0),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX idx_run ON jobs (status, run_at);
