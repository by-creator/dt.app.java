package com.dtapp.service;

import lombok.AllArgsConstructor;
import lombok.Data;
import java.time.LocalDateTime;

/**
 * Résultat d'exécution d'une automatisation
 */
@Data
@AllArgsConstructor
public class AutomationResult {
    private String id;
    private boolean success;
    private String message;
    private String errorMessage;
    private String screenshotPath;
    private String logPath;
    private long executionTime;        // en ms
    private LocalDateTime executedAt;
}
