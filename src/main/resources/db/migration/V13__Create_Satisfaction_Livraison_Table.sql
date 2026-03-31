-- Create satisfaction_livraison table
CREATE TABLE satisfaction_livraison (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    satisfaction_info_id BIGINT NOT NULL,
    fluidite_livraison VARCHAR(50),
    horaires_livraison VARCHAR(100),
    horaires_livraison_detail TEXT,
    retards_livraison VARCHAR(50),
    retards_livraison_detail TEXT,
    coordination_livraison VARCHAR(50),
    ameliorations_livraison TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (satisfaction_info_id) REFERENCES satisfaction_info(id)
) ENGINE=InnoDB;