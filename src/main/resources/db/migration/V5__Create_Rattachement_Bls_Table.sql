CREATE TABLE IF NOT EXISTS rattachement_bls (
    id           BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id      BIGINT               NULL,
    nom          VARCHAR(100)         NULL,
    prenom       VARCHAR(100)         NULL,
    email        VARCHAR(150)         NULL,
    bl           VARCHAR(100)         NOT NULL,
    maison       VARCHAR(100)         NULL,
    motif_rejet  VARCHAR(100)         NULL,
    statut       VARCHAR(50)          NOT NULL DEFAULT 'EN_ATTENTE',
    type         VARCHAR(50)          NULL,
    pourcentage  DECIMAL(8, 2)        NULL,
    time_elapsed BIGINT               NULL,
    created_at   TIMESTAMP            DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP            DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_rattachement_bls_user
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL,

    INDEX idx_rattachement_bl     (bl),
    INDEX idx_rattachement_user   (user_id),
    INDEX idx_rattachement_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
