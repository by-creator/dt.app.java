CREATE TABLE IF NOT EXISTS services (
    id     BIGSERIAL PRIMARY KEY,
    nom    VARCHAR(100) NOT NULL,
    code   VARCHAR(50),
    actif  BOOLEAN NOT NULL DEFAULT TRUE,
    UNIQUE (code)
);

CREATE TABLE IF NOT EXISTS guichets (
    id         BIGSERIAL PRIMARY KEY,
    numero     VARCHAR(20) NOT NULL,
    infos      VARCHAR(20) NOT NULL,
    service_id BIGINT,
    actif      BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_guichets_service
        FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS agents (
    id         BIGSERIAL PRIMARY KEY,
    nom        VARCHAR(100) NOT NULL,
    prenom     VARCHAR(100),
    service_id BIGINT,
    guichet_id BIGINT,
    actif      BOOLEAN NOT NULL DEFAULT TRUE,
    CONSTRAINT fk_agents_service
        FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL,
    CONSTRAINT fk_agents_guichet
        FOREIGN KEY (guichet_id) REFERENCES guichets (id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS tickets (
    id              BIGSERIAL PRIMARY KEY,
    service_id      BIGINT,
    agent_id        BIGINT,
    guichet_id      BIGINT,
    statut          VARCHAR(50) NOT NULL DEFAULT 'EN_ATTENTE',
    numero          VARCHAR(20) NOT NULL,
    token           VARCHAR(100) NOT NULL UNIQUE,
    waiting_time    TIMESTAMP,
    called_at       TIMESTAMP,
    closed_at       TIMESTAMP,
    processing_time BIGINT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tickets_service
        FOREIGN KEY (service_id) REFERENCES services (id) ON DELETE SET NULL,
    CONSTRAINT fk_tickets_agent
        FOREIGN KEY (agent_id) REFERENCES agents (id) ON DELETE SET NULL,
    CONSTRAINT fk_tickets_guichet
        FOREIGN KEY (guichet_id) REFERENCES guichets (id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_ticket_statut  ON tickets(statut);
CREATE INDEX IF NOT EXISTS idx_ticket_service ON tickets(service_id);
CREATE INDEX IF NOT EXISTS idx_ticket_created ON tickets(created_at);

CREATE TABLE IF NOT EXISTS gfa_wifi_settings (
    id         BIGSERIAL PRIMARY KEY,
    ssid       VARCHAR(100) NOT NULL,
    password   VARCHAR(100) NOT NULL DEFAULT '',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- PostgreSQL equivalent of INSERT ... ON DUPLICATE KEY UPDATE
INSERT INTO gfa_wifi_settings (id, ssid, password, updated_at)
VALUES (1, 'DakarTerminal_WiFi', '', CURRENT_TIMESTAMP)
ON CONFLICT (id) DO UPDATE SET
    ssid       = EXCLUDED.ssid,
    password   = EXCLUDED.password,
    updated_at = EXCLUDED.updated_at;
