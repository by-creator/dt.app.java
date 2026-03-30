CREATE TABLE IF NOT EXISTS tiers_unify (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    compte_ipaki     VARCHAR(50)  NULL,
    compte_neptune   VARCHAR(50)  NULL,
    raison_sociale   VARCHAR(255) NULL,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
