package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDateTime;

@Entity
@Table(name = "satisfaction_facturation")
@Getter
@Setter
@NoArgsConstructor
public class SatisfactionFacturation {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne
    @JoinColumn(name = "satisfaction_info_id", nullable = false)
    private SatisfactionInfo satisfactionInfo;

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

    private LocalDateTime createdAt;

    @PrePersist
    private void prePersist() {
        createdAt = LocalDateTime.now();
    }
}