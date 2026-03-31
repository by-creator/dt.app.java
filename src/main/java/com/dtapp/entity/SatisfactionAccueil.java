package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDateTime;

@Entity
@Table(name = "satisfaction_accueil")
@Getter
@Setter
@NoArgsConstructor
public class SatisfactionAccueil {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne
    @JoinColumn(name = "satisfaction_info_id", nullable = false)
    private SatisfactionInfo satisfactionInfo;

    private String accueilLocaux;
    private String personnelAccueil;
    private String infrastructures;
    @Column(columnDefinition = "TEXT")
    private String infrastructuresDetail;

    private LocalDateTime createdAt;

    @PrePersist
    private void prePersist() {
        createdAt = LocalDateTime.now();
    }
}