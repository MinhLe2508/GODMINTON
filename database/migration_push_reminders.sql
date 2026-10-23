-- Additive migration: idempotency and retry state for automatic booking reminders.
CREATE TABLE IF NOT EXISTS push_reminder_dispatches (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    booking_id INT(10) UNSIGNED NOT NULL,
    reminder_type VARCHAR(40) NOT NULL DEFAULT 'start_30_minutes',
    status ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    last_error VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_push_reminder_booking_type (booking_id, reminder_type),
    KEY idx_push_reminder_status (status, created_at),
    CONSTRAINT fk_push_reminder_booking FOREIGN KEY (booking_id)
        REFERENCES bookings (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
