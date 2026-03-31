-- Create satisfaction_bae table
CREATE TABLE satisfaction_bae (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    satisfaction_info_id BIGINT NOT NULL,
    delais_bae VARCHAR(50),
    suggestions_bae VARCHAR(50),
    suggestions_bae_detail TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (satisfaction_info_id) REFERENCES satisfaction_info(id)
) ENGINE=InnoDB;