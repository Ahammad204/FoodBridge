-- ============================================================
-- FoodBridge — database schema + sample data
-- Import in phpMyAdmin (XAMPP) or: mysql -u root < database.sql
-- ============================================================

CREATE DATABASE IF NOT EXISTS foodbridge
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE foodbridge;

-- ------------------------------------------------------------
-- users
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name              VARCHAR(100) NOT NULL,
    email             VARCHAR(150) NOT NULL UNIQUE,
    password          VARCHAR(255) NOT NULL,              -- password_hash() output
    phone             VARCHAR(20)  NULL,
    address           VARCHAR(255) NULL,
    role              ENUM('admin', 'donor', 'receiver') NOT NULL DEFAULT 'receiver',
    organization_name VARCHAR(150) NULL,
    is_verified       TINYINT(1)   NOT NULL DEFAULT 0,
    status            ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- food_donations
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS food_donations (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donor_id      INT UNSIGNED NOT NULL,
    title         VARCHAR(150) NOT NULL,
    description   TEXT         NULL,
    category      VARCHAR(100) NOT NULL,
    quantity      DECIMAL(10, 2) NOT NULL,
    unit          VARCHAR(20)  NOT NULL DEFAULT 'pcs',
    pickup_address VARCHAR(255) NOT NULL,
    expiry_time   DATETIME     NOT NULL,
    image         VARCHAR(255) NULL,                       -- file name in assets/uploads/
    status        ENUM('available', 'requested', 'completed', 'expired') NOT NULL DEFAULT 'available',
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_donations_donor
        FOREIGN KEY (donor_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- food_requests
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS food_requests (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donation_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    message     TEXT         NULL,
    status      ENUM('pending', 'accepted', 'rejected', 'completed') NOT NULL DEFAULT 'pending',
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_requests_donation
        FOREIGN KEY (donation_id) REFERENCES food_donations (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_requests_receiver
        FOREIGN KEY (receiver_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ------------------------------------------------------------
-- notifications
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id    INT UNSIGNED NOT NULL,
    type       VARCHAR(50)  NOT NULL,   -- new_request, request_accepted, request_rejected, request_completed
    message    VARCHAR(255) NOT NULL,
    link       VARCHAR(255) NULL,       -- where the bell click should go
    is_read    TINYINT(1)   NOT NULL DEFAULT 0,
    created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE = InnoDB;

-- ============================================================
-- Default admin account
-- Email: admin@foodbridge.local  |  Password: Admin@123
-- (bcrypt hash made with PHP password_hash(); change after login)
-- ============================================================
INSERT INTO users (id, name, email, password, phone, address, role, organization_name, is_verified, status)
VALUES (1, 'FoodBridge Admin', 'admin@foodbridge.local',
        '$2y$10$uuJ3TwII9PSYYwPU6I6I1eRy8/gw9yPHOPvFyXZn2xbbBsJh/bToS',
        '01000000000', 'Dhaka, Bangladesh', 'admin', 'FoodBridge', 1, 'active')
ON DUPLICATE KEY UPDATE email = email;

-- ============================================================
-- Sample data
-- Donor:    donor@example.com    / Donor@123
-- Receiver: receiver@example.com / Receiver@123
-- ============================================================
INSERT INTO users (id, name, email, password, phone, address, role, organization_name, is_verified, status) VALUES
(2, 'Rahim Uddin', 'donor@example.com',
 '$2y$10$u2HdnVhKvYsQoYG7VzSsRuplmht/IhW2oTy4Tk.ALO6/y3I5AFv8C',
 '01710000001', 'Mirpur, Dhaka', 'donor', 'Rahim Catering', 1, 'active'),
(3, 'Karim Bakery', 'bakery@example.com',
 '$2y$10$u2HdnVhKvYsQoYG7VzSsRuplmht/IhW2oTy4Tk.ALO6/y3I5AFv8C',
 '01710000002', 'Uttara, Dhaka', 'donor', 'Karim Bakery', 1, 'active'),
(4, 'Fatema Begum', 'receiver@example.com',
 '$2y$10$0xRBcTaHNXMT5k23k0aQy.SS1FbIdYgAM0SfKOnHJb5PD2YH.6amy',
 '01810000003', 'Mohammadpur, Dhaka', 'receiver', 'Mohammadpur Community Kitchen', 1, 'active'),
(5, 'Anisul Haque', 'shelter@example.com',
 '$2y$10$0xRBcTaHNXMT5k23k0aQy.SS1FbIdYgAM0SfKOnHJb5PD2YH.6amy',
 '01810000004', 'Jatrabari, Dhaka', 'receiver', 'Jatrabari Shelter Home', 0, 'active')
ON DUPLICATE KEY UPDATE email = email;

INSERT INTO food_donations (id, donor_id, title, description, category, quantity, unit, pickup_address, expiry_time, image, status) VALUES
(1, 2, 'Surplus Biryani — 30 packs', 'Cooked biryani left over from an event, packed and sealed.', 'Cooked Food', 30, 'packs', 'Mirpur 10, Dhaka', DATE_ADD(NOW(), INTERVAL 6 HOUR), NULL, 'available'),
(2, 3, 'Fresh Bread Loaves', 'Unsold bread from today, still fresh.', 'Bakery', 50, 'pcs', 'Uttara Sector 7, Dhaka', DATE_ADD(NOW(), INTERVAL 12 HOUR), NULL, 'available'),
(3, 2, 'Seasonal Vegetables', 'Mixed vegetables, good for community cooking.', 'Raw Food', 20, 'kg', 'Mirpur 10, Dhaka', DATE_ADD(NOW(), INTERVAL 2 DAY), NULL, 'requested')
ON DUPLICATE KEY UPDATE id = id;

INSERT INTO food_requests (id, donation_id, receiver_id, message, status) VALUES
(1, 3, 4, 'We cook for about 40 people every evening and can pick this up today.', 'pending')
ON DUPLICATE KEY UPDATE id = id;
