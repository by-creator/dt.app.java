CREATE TABLE IF NOT EXISTS tiers_unify (
    id               BIGSERIAL PRIMARY KEY,
    compte_ipaki     VARCHAR(50),
    compte_neptune   VARCHAR(50),
    raison_sociale   VARCHAR(255),
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
