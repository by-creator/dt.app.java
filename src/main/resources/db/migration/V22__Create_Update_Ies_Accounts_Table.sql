CREATE TABLE update_ies_accounts (
    id BIGINT NOT NULL AUTO_INCREMENT,
    compte TEXT NOT NULL,
    statut TEXT NOT NULL,
    created_at DATETIME NULL,
    updated_at DATETIME NULL,
    PRIMARY KEY (id)
);
