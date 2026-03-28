package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.EqualsAndHashCode;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;
import lombok.ToString;

import java.time.LocalDateTime;

@Entity
@Table(name = "tickets")
@Getter
@Setter
@NoArgsConstructor
@EqualsAndHashCode(onlyExplicitlyIncluded = true)
@ToString(exclude = {"service", "agent", "guichet"})
public class GfaTicket {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    @EqualsAndHashCode.Include
    private Long id;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "service_id")
    private GfaService service;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "agent_id")
    private GfaAgent agent;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "guichet_id")
    private GfaGuichet guichet;

    @Column(nullable = false, length = 50)
    private String statut;

    @Column(nullable = false, length = 20)
    private String numero;

    @Column(length = 100)
    private String token;

    @Column(name = "waiting_time")
    private LocalDateTime waitingTime;

    @Column(name = "called_at")
    private LocalDateTime calledAt;

    @Column(name = "closed_at")
    private LocalDateTime closedAt;

    @Column(name = "processing_time")
    private Long processingTime;

    @Column(name = "created_at")
    private LocalDateTime createdAt;

    @Column(name = "updated_at")
    private LocalDateTime updatedAt;
}
