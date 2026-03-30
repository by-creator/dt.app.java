-- V3__Create_Codifications_Table.sql

CREATE TABLE IF NOT EXISTS codifications (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    call_number   VARCHAR(255) NOT NULL,
    manifest      VARCHAR(500) NULL,
    xls           VARCHAR(500) NULL,
    iftmin        VARCHAR(500) NULL,
    manifest_data LONGBLOB     NULL,
    xls_data      LONGBLOB     NULL,
    iftmin_data   LONGBLOB     NULL,
    compagnie_id  INT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (compagnie_id) REFERENCES compagnies(id) ON DELETE SET NULL,
    INDEX idx_call_number   (call_number),
    INDEX idx_created_at    (created_at),
    INDEX idx_compagnie_id  (compagnie_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
