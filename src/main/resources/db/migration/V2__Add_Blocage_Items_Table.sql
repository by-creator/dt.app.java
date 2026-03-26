-- Blocage items table for Douane module
CREATE TABLE IF NOT EXISTS blocage_items (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    item       VARCHAR(255) NOT NULL,
    statut     VARCHAR(50)  NOT NULL DEFAULT 'BLOQUE',
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_blocage_items_item   ON blocage_items(item);
CREATE INDEX idx_blocage_items_statut ON blocage_items(statut);
