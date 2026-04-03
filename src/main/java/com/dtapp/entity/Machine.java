package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.Data;
import lombok.NoArgsConstructor;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

@Entity
@Table(name = "machines")
@Data
@NoArgsConstructor
public class Machine {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(length = 100)
    private String type;

    @Column(length = 255)
    private String name;

    @Column(name = "ajow_name", length = 255)
    private String ajowName;

    @Column(length = 255)
    private String username;

    @Column(name = "service_tag", length = 100)
    private String serviceTag;

    @Column(name = "version_os", length = 100)
    private String versionOs;

    @Column(length = 255)
    private String model;

    @Column(length = 255)
    private String societe;

    @Column(length = 255)
    private String service;

    @Column(length = 255)
    private String emplacement;

    @Column(length = 255)
    private String sites;

    @Column(name = "date_acquisition", length = 50)
    private String dateAcquisition;

    @Column(name = "date_deploiement", length = 50)
    private String dateDeploiement;

    @Column(columnDefinition = "TEXT")
    private String commentaire;

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

    public String getFormattedDate() {
        if (createdAt == null) return "";
        return createdAt.format(DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm"));
    }
}