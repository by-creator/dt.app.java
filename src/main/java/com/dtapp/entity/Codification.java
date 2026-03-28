package com.dtapp.entity;

import jakarta.persistence.*;
import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;

@Entity
@Table(name = "codifications", indexes = {
    @Index(name = "idx_call_number", columnList = "call_number"),
    @Index(name = "idx_created_at", columnList = "created_at")
})
@Data
@NoArgsConstructor
@AllArgsConstructor
@Builder
public class Codification {

    @Id
    @GeneratedValue(strategy = GenerationType.IDENTITY)
    private Integer id;

    @Column(nullable = false)
    private String callNumber;

    /** Original filename of the manifest TXT (used for download Content-Disposition). */
    @Column(length = 500)
    private String manifest;

    /** Original filename of the generated XLSX (used for download Content-Disposition). */
    @Column(length = 500)
    private String xls;

    /** Original filename of the generated EDI/IFTMIN (used for download Content-Disposition). */
    @Column(length = 500)
    private String iftmin;

    /** Binary content of the manifest TXT file. */
    @Lob
    @Column(name = "manifest_data", columnDefinition = "LONGBLOB")
    private byte[] manifestData;

    /** Binary content of the generated XLSX file. */
    @Lob
    @Column(name = "xls_data", columnDefinition = "LONGBLOB")
    private byte[] xlsData;

    /** Binary content of the generated EDI/IFTMIN file. */
    @Lob
    @Column(name = "iftmin_data", columnDefinition = "LONGBLOB")
    private byte[] iftminData;

    @ManyToOne(fetch = FetchType.LAZY)
    @JoinColumn(name = "compagnie_id")
    private Compagnie compagnie;

    @Column(nullable = false, updatable = false)
    private LocalDateTime createdAt;

    @Column(nullable = false)
    private LocalDateTime updatedAt;

    public String getFormattedDate() {
        if (createdAt == null) return "";
        return createdAt.format(DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm"));
    }

    @PrePersist
    protected void onCreate() {
        createdAt = LocalDateTime.now();
        updatedAt = LocalDateTime.now();
    }

    @PreUpdate
    protected void onUpdate() {
        updatedAt = LocalDateTime.now();
    }
}
