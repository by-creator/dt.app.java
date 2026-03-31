-- Create satisfaction_accueil table
CREATE TABLE satisfaction_accueil (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    satisfaction_info_id BIGINT NOT NULL,
    accueil_locaux VARCHAR(50),
    personnel_accueil VARCHAR(50),
    infrastructures VARCHAR(50),
    infrastructures_detail TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (satisfaction_info_id) REFERENCES satisfaction_info(id)
) ENGINE=InnoDB;