package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDateTime;

@Entity
@Table(name = "satisfaction_livraison")
@Getter
@Setter
@NoArgsConstructor
public class SatisfactionLivraison {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne
    @JoinColumn(name = "satisfaction_info_id", nullable = false)
    private SatisfactionInfo satisfactionInfo;

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

    private LocalDateTime createdAt;

    @PrePersist
    private void prePersist() {
        createdAt = LocalDateTime.now();
    }
}