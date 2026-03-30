CREATE TABLE IF NOT EXISTS satisfaction_reponses (
    id                                  BIGSERIAL PRIMARY KEY,

    -- Infos
    nom                                 VARCHAR(255),
    telephone                           VARCHAR(50),
    email                               VARCHAR(255),

    -- General
    volume                              VARCHAR(255),
    anciennete                          VARCHAR(255),

    -- Facturation
    delais_facturation                  VARCHAR(255),
    notifications                       VARCHAR(255),
    reactivite_facture                  VARCHAR(255),
    usage_plateforme                    VARCHAR(255),
    usage_plateforme_detail             TEXT,
    facilite_plateforme                 VARCHAR(255),
    fonctionnalites_plateforme          VARCHAR(255),
    fonctionnalites_plateforme_detail   TEXT,
    bugs_plateforme                     VARCHAR(255),
    bugs_plateforme_detail              TEXT,
    assistance_plateforme               VARCHAR(255),
    suggestions_facturation             TEXT,

    -- BAE
    delais_bae                          VARCHAR(255),
    suggestions_bae                     VARCHAR(255),
    suggestions_bae_detail              TEXT,

    -- Accueil
    accueil_locaux                      VARCHAR(255),
    personnel_accueil                   VARCHAR(255),
    infrastructures                     VARCHAR(255),
    infrastructures_detail              TEXT,

    -- Livraison
    fluidite_livraison                  VARCHAR(255),
    horaires_livraison                  VARCHAR(255),
    horaires_livraison_detail           TEXT,
    retards_livraison                   VARCHAR(255),
    retards_livraison_detail            TEXT,
    coordination_livraison              VARCHAR(255),
    ameliorations_livraison             TEXT,

    -- Communication
    communication_proactive             VARCHAR(255),
    alertes                             VARCHAR(255),
    suggestions_communication           TEXT,
    recommandations_generales           TEXT,

    created_at                          TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
