package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDateTime;

@Entity
@Table(name = "satisfaction_communication")
@Getter
@Setter
@NoArgsConstructor
public class SatisfactionCommunication {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne
    @JoinColumn(name = "satisfaction_info_id", nullable = false)
    private SatisfactionInfo satisfactionInfo;

    private String communicationProactive;
    private String alertes;
    @Column(columnDefinition = "TEXT")
    private String suggestionsCommunication;
    @Column(columnDefinition = "TEXT")
    private String recommandationsGenerales;

    private LocalDateTime createdAt;

    @PrePersist
    private void prePersist() {
        createdAt = LocalDateTime.now();
    }
}