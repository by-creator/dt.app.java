-- V3__Create_Codifications_Table.sql

CREATE TABLE IF NOT EXISTS codifications (
    id            SERIAL PRIMARY KEY,
    call_number   VARCHAR(255) NOT NULL,
    manifest      VARCHAR(500),
    xls           VARCHAR(500),
    iftmin        VARCHAR(500),
    manifest_data BYTEA,
    xls_data      BYTEA,
    iftmin_data   BYTEA,
    compagnie_id  INT,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (compagnie_id) REFERENCES compagnies(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_call_number   ON codifications(call_number);
CREATE INDEX IF NOT EXISTS idx_created_at    ON codifications(created_at);
CREATE INDEX IF NOT EXISTS idx_compagnie_id  ON codifications(compagnie_id);
