-- FlowForge MySQL / MariaDB schema (used when DB_DRIVER=mysql)
-- Same tables as schema.sql (SQLite), with MySQL data types.

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS approval_history;
DROP TABLE IF EXISTS activity_requests;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('submitter', 'reviewer') NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE activity_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    event_date DATE NOT NULL,
    venue VARCHAR(200) NOT NULL,
    status ENUM('draft', 'submitted', 'approved', 'rejected') NOT NULL DEFAULT 'draft',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    INDEX idx_activity_requests_user (user_id),
    INDEX idx_activity_requests_status (status),
    CONSTRAINT fk_requests_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE approval_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_id INT UNSIGNED NOT NULL,
    actor_id INT UNSIGNED NOT NULL,
    action VARCHAR(20) NOT NULL,
    remarks TEXT NULL,
    from_status VARCHAR(20) NULL,
    to_status VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX idx_approval_history_request (request_id),
    INDEX idx_approval_history_actor (actor_id),
    CONSTRAINT fk_history_request FOREIGN KEY (request_id) REFERENCES activity_requests(id) ON DELETE CASCADE,
    CONSTRAINT fk_history_actor FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
