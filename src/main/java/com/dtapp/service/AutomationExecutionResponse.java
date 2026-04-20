package com.dtapp.service;

import lombok.AllArgsConstructor;
import lombok.Data;
import java.util.List;

/**
 * Réponse d'exécution d'automatisation
 */
@Data
@AllArgsConstructor
public class AutomationExecutionResponse {
    private String id;
    private String name;
    private String status;
    private String message;
    private String error;
    private String screenshotPath;
    private String logPath;
    private long executionTime;
    private List<AutomationDocumentLink> documents;
}
