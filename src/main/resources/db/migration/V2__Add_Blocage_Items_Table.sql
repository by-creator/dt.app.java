-- Blocage items table for Douane module
CREATE TABLE IF NOT EXISTS blocage_items (
    id         SERIAL PRIMARY KEY,
    item       VARCHAR(255) NOT NULL,
    statut     VARCHAR(50)  NOT NULL DEFAULT 'BLOQUE',
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_blocage_items_item   ON blocage_items(item);
CREATE INDEX IF NOT EXISTS idx_blocage_items_statut ON blocage_items(statut);
