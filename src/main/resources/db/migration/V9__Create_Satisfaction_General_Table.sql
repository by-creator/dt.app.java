-- Create satisfaction_info table (if not already created by V8)
CREATE TABLE IF NOT EXISTS satisfaction_info (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(255) NOT NULL,
    telephone VARCHAR(50),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Create satisfaction_general table
CREATE TABLE satisfaction_general (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    satisfaction_info_id BIGINT NOT NULL,
    volume VARCHAR(100),
    anciennete VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (satisfaction_info_id) REFERENCES satisfaction_info(id)
) ENGINE=InnoDB;