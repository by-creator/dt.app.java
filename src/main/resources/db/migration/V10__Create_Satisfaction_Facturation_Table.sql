-- Create satisfaction_facturation table
CREATE TABLE satisfaction_facturation (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    satisfaction_info_id BIGINT NOT NULL,
    delais_facturation VARCHAR(50),
    notifications VARCHAR(50),
    reactivite_facture VARCHAR(50),
    usage_plateforme VARCHAR(50),
    usage_plateforme_detail TEXT,
    facilite_plateforme VARCHAR(50),
    fonctionnalites_plateforme VARCHAR(50),
    fonctionnalites_plateforme_detail TEXT,
    bugs_plateforme VARCHAR(50),
    bugs_plateforme_detail TEXT,
    assistance_plateforme VARCHAR(50),
    suggestions_facturation TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (satisfaction_info_id) REFERENCES satisfaction_info(id)
) ENGINE=InnoDB;