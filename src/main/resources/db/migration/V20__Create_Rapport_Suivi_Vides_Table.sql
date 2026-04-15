CREATE TABLE IF NOT EXISTS rapport_suivi_vides (
    id                   BIGINT AUTO_INCREMENT PRIMARY KEY,
    terminal             VARCHAR(255) NULL,
    shipowner            VARCHAR(255) NULL,
    item_type            VARCHAR(100) NULL,
    equipment_number     VARCHAR(100) NULL,
    equipment_type_size  VARCHAR(100) NULL,
    event_code           VARCHAR(50)  NULL,
    event_family         VARCHAR(100) NULL,
    event_date           VARCHAR(50)  NULL,
    booking_sec_no       VARCHAR(100) NULL,
    created_at           TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
