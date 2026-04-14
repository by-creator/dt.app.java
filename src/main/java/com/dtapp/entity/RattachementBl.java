package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.LocalDateTime;

@Entity
@Table(name = "rattachement_bls")
@Getter
@Setter
@NoArgsConstructor
public class RattachementBl {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "user_id")
    private Integer userId;

    @Column(name = "nom", length = 100)
    private String nom;

    @Column(name = "prenom", length = 100)
    private String prenom;

    @Column(name = "email", length = 150)
    private String email;

    @Column(name = "bl", length = 100, nullable = false)
    private String bl;

    @Column(name = "maison", length = 100)
    private String maison;

    @Column(name = "motif_rejet", length = 100)
    private String motifRejet;

    @Column(name = "statut", length = 50, nullable = false)
    private String statut = "EN_ATTENTE";

    @Column(name = "type", length = 50)
    private String type;

    @Column(name = "pourcentage", precision = 8, scale = 2)
    private BigDecimal pourcentage;

    @Column(name = "date_validite_remise")
    private LocalDate dateValiditeRemise;

    @Column(name = "time_elapsed")
    private Long timeElapsed;

    @Column(name = "created_at")
    private LocalDateTime createdAt;

    @Column(name = "updated_at")
    private LocalDateTime updatedAt;

    @PrePersist
    public void prePersist() {
        createdAt = LocalDateTime.now();
        updatedAt = LocalDateTime.now();
    }

    @PreUpdate
    public void preUpdate() {
        updatedAt = LocalDateTime.now();
    }
}
