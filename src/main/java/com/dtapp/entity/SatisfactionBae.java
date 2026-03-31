package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDateTime;

@Entity
@Table(name = "satisfaction_bae")
@Getter
@Setter
@NoArgsConstructor
public class SatisfactionBae {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @ManyToOne
    @JoinColumn(name = "satisfaction_info_id", nullable = false)
    private SatisfactionInfo satisfactionInfo;

    private String delaisBae;
    private String suggestionsBae;
    @Column(columnDefinition = "TEXT")
    private String suggestionsBaeDetail;

    private LocalDateTime createdAt;

    @PrePersist
    private void prePersist() {
        createdAt = LocalDateTime.now();
    }
}