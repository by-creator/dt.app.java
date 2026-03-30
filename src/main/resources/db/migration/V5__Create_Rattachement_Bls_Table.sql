CREATE TABLE IF NOT EXISTS rattachement_bls (
    id           BIGSERIAL PRIMARY KEY,
    user_id      INT,
    nom          VARCHAR(100),
    prenom       VARCHAR(100),
    email        VARCHAR(150),
    bl           VARCHAR(100) NOT NULL,
    maison       VARCHAR(100),
    motif_rejet  VARCHAR(100),
    statut       VARCHAR(50) NOT NULL DEFAULT 'EN_ATTENTE',
    type         VARCHAR(50),
    pourcentage  DECIMAL(8, 2),
    time_elapsed BIGINT,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rattachement_bls_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_rattachement_bl     ON rattachement_bls(bl);
CREATE INDEX IF NOT EXISTS idx_rattachement_user   ON rattachement_bls(user_id);
CREATE INDEX IF NOT EXISTS idx_rattachement_statut ON rattachement_bls(statut);
