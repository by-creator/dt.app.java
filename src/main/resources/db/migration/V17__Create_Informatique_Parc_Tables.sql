CREATE TABLE IF NOT EXISTS machines (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    type             VARCHAR(100) NULL,
    name             VARCHAR(255) NULL,
    ajow_name        VARCHAR(255) NULL,
    username         VARCHAR(255) NULL,
    service_tag      VARCHAR(100) NULL,
    version_os       VARCHAR(100) NULL,
    model            VARCHAR(255) NULL,
    societe          VARCHAR(255) NULL,
    service          VARCHAR(255) NULL,
    emplacement      VARCHAR(255) NULL,
    sites            VARCHAR(255) NULL,
    date_acquisition VARCHAR(50)  NULL,
    date_deploiement VARCHAR(50)  NULL,
    commentaire      TEXT         NULL,
    created_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at       TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS postes_fixes (
    id          BIGINT AUTO_INCREMENT PRIMARY KEY,
    annuaire    VARCHAR(100) NULL,
    nom         VARCHAR(255) NULL,
    prenom      VARCHAR(255) NULL,
    type        VARCHAR(255) NULL,
    commentaire TEXT         NULL,
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;