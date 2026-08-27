-- Which nutritionist is assigned to which patient, and their clinical notes.
CREATE TABLE nutritionist_patients (
    nutritionist_id BIGINT NOT NULL,
    patient_id      BIGINT NOT NULL,
    status          TEXT NOT NULL DEFAULT 'active' CHECK (status IN ('active','ended')),
    linked_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (nutritionist_id, patient_id),
    FOREIGN KEY (nutritionist_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE clinical_notes (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    nutritionist_id BIGINT NOT NULL,
    patient_id      BIGINT NOT NULL,
    note            TEXT NOT NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nutritionist_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES users(id) ON DELETE CASCADE
);
