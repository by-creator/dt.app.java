CREATE TABLE IF NOT EXISTS services (
    id     BIGINT AUTO_INCREMENT PRIMARY KEY,
    nom    VARCHAR(100) NOT NULL,
    code   VARCHAR(50)  NULL,
    actif  TINYINT(1)   NOT NULL DEFAULT 1,
    UNIQUE KEY uq_services_code (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS guichets (
    id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    numero     VARCHAR(20) NOT NULL,
    infos      VARCHAR(20) NOT NULL,
    service_id BIGINT      NULL,
    actif      TINYINT(1)  NOT NULL DEFAULT 1,
    CONSTRAINT fk_guichets_service
        FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS agents (
    id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    nom        VARCHAR(100) NOT NULL,
    prenom     VARCHAR(100) NULL,
    service_id BIGINT       NULL,
    guichet_id BIGINT       NULL,
    actif      TINYINT(1)   NOT NULL DEFAULT 1,
    CONSTRAINT fk_agents_service
        FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL,
    CONSTRAINT fk_agents_guichet
        FOREIGN KEY (guichet_id) REFERENCES guichets (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tickets (
    id              BIGINT AUTO_INCREMENT PRIMARY KEY,
    service_id      BIGINT      NULL,
    agent_id        BIGINT      NULL,
    guichet_id      BIGINT      NULL,
    statut          VARCHAR(50) NOT NULL DEFAULT 'EN_ATTENTE',
    numero          VARCHAR(20) NOT NULL,
    waiting_time    DATETIME    NULL,
    called_at       DATETIME    NULL,
    closed_at       DATETIME    NULL,
    processing_time BIGINT      NULL,
    created_at      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_tickets_service
        FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL,
    CONSTRAINT fk_tickets_agent
        FOREIGN KEY (agent_id) REFERENCES agents (id) ON DELETE SET NULL,
    CONSTRAINT fk_tickets_guichet
        FOREIGN KEY (guichet_id) REFERENCES guichets (id) ON DELETE SET NULL,
    INDEX idx_ticket_statut  (statut),
    INDEX idx_ticket_service (service_id),
    INDEX idx_ticket_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS gfa_wifi_settings (
    id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    ssid       VARCHAR(100) NOT NULL,
    password   VARCHAR(100) NOT NULL DEFAULT '',
    updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO gfa_wifi_settings (id, ssid, password, updated_at)
VALUES (1, 'DakarTerminal_WiFi', '', CURRENT_TIMESTAMP)
ON DUPLICATE KEY UPDATE
    ssid       = VALUES(ssid),
    password   = VALUES(password),
    updated_at = VALUES(updated_at);
