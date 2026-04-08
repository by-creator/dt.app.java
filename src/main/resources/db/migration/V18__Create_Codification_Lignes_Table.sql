-- Table pour stocker chaque ligne parsée d'un manifeste EDI
-- Liée à la table codifications par codification_id
-- Alimentée via LOAD DATA LOCAL INFILE pour minimiser les requêtes vers la base
CREATE TABLE codification_lignes (
    id                          INT AUTO_INCREMENT PRIMARY KEY,
    codification_id             INT          NOT NULL,

    -- Identification
    bl_number                   VARCHAR(20),
    call_number                 VARCHAR(10),
    transport_mode              VARCHAR(1),
    import_export               VARCHAR(20),
    manifest                    VARCHAR(35),

    -- Parties
    consignee                   VARCHAR(35),
    shipper_name                VARCHAR(35),
    agent_name                  VARCHAR(35),

    -- Mesures
    bl_weight                   VARCHAR(20),
    bl_volume                   VARCHAR(20),

    -- Ports
    port_of_loading             VARCHAR(5),
    reception_location          VARCHAR(5),
    transshipment_port_1        VARCHAR(5),
    transshipment_port_2        VARCHAR(5),

    -- Quantités
    number_of_yard_items        VARCHAR(5),
    number_of_packages          VARCHAR(5),

    -- Classification
    yard_item_type              VARCHAR(20),
    final_destination_country   VARCHAR(35),

    -- Détail BL Item
    blitem_yard_item_number     VARCHAR(20),
    blitem_yard_item_code       VARCHAR(35),
    blitem_yard_item_type       VARCHAR(20),
    blitem_commodity            VARCHAR(30),
    blitem_commodity_weight     VARCHAR(20),
    blitem_commodity_volume     VARCHAR(20),
    blitem_seal_number_1        VARCHAR(70),
    blitem_seal_number_2        VARCHAR(70),
    blitem_barcode              VARCHAR(20),
    blitem_chassis_number       VARCHAR(20),
    blitem_vehicle_model        VARCHAR(35),
    blitem_comment              VARCHAR(35),
    blitem_allow_invalid        VARCHAR(5),

    -- Adresses
    adresse_2                   VARCHAR(35),
    adresse_3                   VARCHAR(35),
    adresse_4                   VARCHAR(35),
    adresse_5                   VARCHAR(35),

    -- Notifications
    notify1                     VARCHAR(35),
    notify2                     VARCHAR(35),
    notify3                     VARCHAR(35),
    notify4                     VARCHAR(35),
    notify5                     VARCHAR(35),

    CONSTRAINT fk_cod_ligne_codification
        FOREIGN KEY (codification_id) REFERENCES codifications(id) ON DELETE CASCADE,
    INDEX idx_cod_ligne_cid (codification_id),
    INDEX idx_cod_ligne_bl  (bl_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
