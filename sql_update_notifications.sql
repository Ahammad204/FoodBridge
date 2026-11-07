-- FoodBridge — update for EXISTING installs (run once in phpMyAdmin)
-- Adds the notifications table. Fresh installs do not need this:
-- database.sql already contains the table.

USE foodbridge;

CREATE TABLE IF NOT EXISTS notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    type       VARCHAR(50)  NOT NULL,
    message    VARCHAR(255) NOT NULL,
    link       VARCHAR(255) NULL,
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;
