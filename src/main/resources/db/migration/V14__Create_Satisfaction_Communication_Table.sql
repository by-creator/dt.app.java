-- Create satisfaction_communication table
CREATE TABLE satisfaction_communication (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    satisfaction_info_id BIGINT NOT NULL,
    communication_proactive VARCHAR(50),
    alertes VARCHAR(50),
    suggestions_communication TEXT,
    recommandations_generales TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (satisfaction_info_id) REFERENCES satisfaction_info(id)
) ENGINE=InnoDB;