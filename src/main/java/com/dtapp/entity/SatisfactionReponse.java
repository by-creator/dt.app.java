package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDateTime;

@Entity
@Table(name = "satisfaction_reponses")
@Getter
@Setter
@NoArgsConstructor
public class SatisfactionReponse {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    // ── Infos ────────────────────────────────────────────────────────────
    private String nom;
    private String telephone;
    private String email;

    // ── General ──────────────────────────────────────────────────────────
    private String volume;
    private String anciennete;

    // ── Facturation ───────────────────────────────────────────────────────
    private String delaisFacturation;
    private String notifications;
    private String reactiviteFacture;
    private String usagePlateforme;
    @Column(columnDefinition = "TEXT")
    private String usagePlateformeDetail;
    private String facilitePlateforme;
    private String fonctionnalitesPlateforme;
    @Column(columnDefinition = "TEXT")
    private String fonctionnalitesPlateformeDetail;
    private String bugsPlateforme;
    @Column(columnDefinition = "TEXT")
    private String bugsPlateformeDetail;
    private String assistancePlateforme;
    @Column(columnDefinition = "TEXT")
    private String suggestionsFacturation;

    // ── BAE ───────────────────────────────────────────────────────────────
    private String delaisBae;
    private String suggestionsBae;
    @Column(columnDefinition = "TEXT")
    private String suggestionsBaeDetail;

    // ── Accueil ───────────────────────────────────────────────────────────
    private String accueilLocaux;
    private String personnelAccueil;
    private String infrastructures;
    @Column(columnDefinition = "TEXT")
    private String infrastructuresDetail;

    // ── Livraison ─────────────────────────────────────────────────────────
    private String fluiditeLivraison;
    private String horairesLivraison;
    @Column(columnDefinition = "TEXT")
    private String horairesLivraisonDetail;
    private String retardsLivraison;
    @Column(columnDefinition = "TEXT")
    private String retardsLivraisonDetail;
    private String coordinationLivraison;
    @Column(columnDefinition = "TEXT")
    private String ameliorationsLivraison;

    // ── Communication ─────────────────────────────────────────────────────
    private String communicationProactive;
    private String alertes;
    @Column(columnDefinition = "TEXT")
    private String suggestionsCommunication;
    @Column(columnDefinition = "TEXT")
    private String recommandationsGenerales;

    // ── Metadata ──────────────────────────────────────────────────────────
    private LocalDateTime createdAt;

    @PrePersist
    private void prePersist() {
        createdAt = LocalDateTime.now();
    }
}
