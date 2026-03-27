-- V2__Create_Codifications_Table.sql

CREATE TABLE IF NOT EXISTS codifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    call_number VARCHAR(255) NOT NULL,
    manifest VARCHAR(500) NOT NULL,
    xls VARCHAR(500) NOT NULL,
    iftmin VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_call_number (call_number),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
