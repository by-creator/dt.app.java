CREATE TABLE teks (
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
    INDEX idx_teks_bl (bl),
    INDEX idx_teks_chassis (chassis),
    INDEX idx_teks_escale (escale(100)),
    INDEX idx_teks_created_at (created_at)
);
