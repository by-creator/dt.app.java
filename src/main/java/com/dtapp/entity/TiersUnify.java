package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDateTime;

@Entity
@Table(name = "tiers_unify")
@Getter
@Setter
@NoArgsConstructor
public class TiersUnify {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "compte_ipaki", length = 50)
    private String compteIpaki;

    @Column(name = "compte_neptune", length = 50)
    private String compteNeptune;

    @Column(name = "raison_sociale")
    private String raisonSociale;

    @Column(name = "created_at")
    private LocalDateTime createdAt;

    @PrePersist
    public void prePersist() {
        if (createdAt == null) createdAt = LocalDateTime.now();
    }
}
