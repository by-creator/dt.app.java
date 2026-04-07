-- Drop table if exists (may have been created as a table instead of a view)
DROP TABLE IF EXISTS satisfaction_reponses;

-- Create view for satisfaction responses combining all tables
CREATE OR REPLACE VIEW satisfaction_reponses AS
SELECT
    i.id,
    i.nom,
    i.telephone,
    i.email,
    g.volume,
    g.anciennete,
    f.delais_facturation,
    f.notifications,
    f.reactivite_facture,
    f.usage_plateforme,
    f.usage_plateforme_detail,
    f.facilite_plateforme,
    f.fonctionnalites_plateforme,
    f.fonctionnalites_plateforme_detail,
    f.bugs_plateforme,
    f.bugs_plateforme_detail,
    f.assistance_plateforme,
    f.suggestions_facturation,
    b.delais_bae,
    b.suggestions_bae,
    b.suggestions_bae_detail,
    a.accueil_locaux,
    a.personnel_accueil,
    a.infrastructures,
    a.infrastructures_detail,
    l.fluidite_livraison,
    l.horaires_livraison,
    l.horaires_livraison_detail,
    l.retards_livraison,
    l.retards_livraison_detail,
    l.coordination_livraison,
    l.ameliorations_livraison,
    c.communication_proactive,
    c.alertes,
    c.suggestions_communication,
    c.recommandations_generales,
    i.created_at
FROM satisfaction_info i
LEFT JOIN satisfaction_general g ON i.id = g.satisfaction_info_id
LEFT JOIN satisfaction_facturation f ON i.id = f.satisfaction_info_id
LEFT JOIN satisfaction_bae b ON i.id = b.satisfaction_info_id
LEFT JOIN satisfaction_accueil a ON i.id = a.satisfaction_info_id
LEFT JOIN satisfaction_livraison l ON i.id = l.satisfaction_info_id
LEFT JOIN satisfaction_communication c ON i.id = c.satisfaction_info_id;
