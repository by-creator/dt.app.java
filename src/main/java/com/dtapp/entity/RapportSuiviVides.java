package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.NoArgsConstructor;
import lombok.Setter;

import java.time.LocalDateTime;

@Entity
@Table(name = "rapport_suivi_vides")
@Getter
@Setter
@NoArgsConstructor
public class RapportSuiviVides {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Long id;

    @Column(name = "terminal")
    private String terminal;

    @Column(name = "shipowner")
    private String shipowner;

    @Column(name = "item_type", length = 100)
    private String itemType;

    @Column(name = "equipment_number", length = 100)
    private String equipmentNumber;

    @Column(name = "equipment_type_size", length = 100)
    private String equipmentTypeSize;

    @Column(name = "event_code", length = 50)
    private String eventCode;

    @Column(name = "event_family", length = 100)
    private String eventFamily;

    @Column(name = "event_date", length = 50)
    private String eventDate;

    @Column(name = "booking_sec_no", length = 100)
    private String bookingSecNo;

    @Column(name = "created_at")
    private LocalDateTime createdAt;

    @PrePersist
    public void prePersist() {
        if (createdAt == null) createdAt = LocalDateTime.now();
    }
}
