package com.dtapp.service;

import lombok.AllArgsConstructor;
import lombok.Data;
import java.time.LocalDateTime;
import java.util.List;
import java.util.Map;

/**
 * Classe représentant une automatisation
 */
@Data
@AllArgsConstructor
public class AutomationInfo {
    private String id;
    private String name;
    private String description;
    private String category;
    private String scriptPath;
    private AutomationStatus status;
    private LocalDateTime lastRun;
    private String lastRunStatus;
    private String lastRunError;
    private String lastRunScreenshot;
    private List<Map<String, String>> params;
}

/**
 * Statut d'une automatisation
 */
enum AutomationStatus {
    IDLE("Inactif"),
    RUNNING("En cours"),
    SUCCESS("Succès"),
    FAILED("Échoué"),
    ERROR("Erreur");

    private final String display;

    AutomationStatus(String display) {
        this.display = display;
    }

    public String getDisplay() {
        return display;
    }
}
