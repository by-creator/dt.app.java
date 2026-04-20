CREATE TABLE IF NOT EXISTS escale_code_barres (
    id BIGINT NOT NULL AUTO_INCREMENT,
    bl VARCHAR(100) NOT NULL,
    chassis VARCHAR(100) NOT NULL,
    escale TEXT,
    file_name VARCHAR(255) NOT NULL,
    file_content_type VARCHAR(150),
    file_data LONGBLOB NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    INDEX idx_escale_code_barres_bl (bl),
    INDEX idx_escale_code_barres_chassis (chassis),
    INDEX idx_escale_code_barres_escale (escale(100)),
    INDEX idx_escale_code_barres_created_at (created_at)
);
