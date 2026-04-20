package com.dtapp.controller;

import com.dtapp.service.AutomationService;
import com.dtapp.service.AutomationInfo;
import com.dtapp.service.AutomationResult;
import com.dtapp.service.AutomationExecutionResponse;
import lombok.extern.slf4j.Slf4j;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;
import java.util.List;
import java.util.Map;

/**
 * REST API pour les automatisations
 */
@Slf4j
@RestController
@RequestMapping("/api/automations")
public class AutomationRestController {

    private final AutomationService automationService;

    public AutomationRestController(AutomationService automationService) {
        this.automationService = automationService;
    }

    /**
     * Récupère la liste de toutes les automatisations
     */
    @GetMapping
    public ResponseEntity<List<AutomationInfo>> listAutomations() {
        try {
            List<AutomationInfo> automations = automationService.getAvailableAutomations();
            return ResponseEntity.ok(automations);
        } catch (Exception e) {
            log.error("Erreur lors de la récupération des automatisations", e);
            return ResponseEntity.status(500).build();
        }
    }

    /**
     * Récupère les détails d'une automatisation
     */
    @GetMapping("/{id}")
    public ResponseEntity<AutomationInfo> getAutomation(@PathVariable String id) {
        try {
            AutomationInfo automation = automationService.getAutomation(id);
            if (automation != null) {
                return ResponseEntity.ok(automation);
            }
            return ResponseEntity.notFound().build();
        } catch (Exception e) {
            log.error("Erreur lors de la récupération de l'automatisation: {}", id, e);
            return ResponseEntity.status(500).build();
        }
    }

    /**
     * Lance une automatisation
     */
    @PostMapping("/{id}/execute")
    public ResponseEntity<AutomationExecutionResponse> executeAutomation(
            @PathVariable String id,
            @RequestBody(required = false) Map<String, String> params) {
        try {
            log.info("Exécution de l'automatisation: {}", id);

            AutomationResult result = automationService.executeAutomation(id,
                    params != null ? params : java.util.Collections.emptyMap());
            
            AutomationExecutionResponse response = new AutomationExecutionResponse(
                    result.getId(),
                    id,
                    result.isSuccess() ? "success" : "failed",
                    result.getMessage(),
                    result.getErrorMessage(),
                    result.getScreenshotPath(),
                    result.getLogPath(),
                    result.getExecutionTime()
            );
            
            log.info("Automatisation {} - Résultat: {}", id, result.isSuccess() ? "succès" : "échec");
            return ResponseEntity.ok(response);
            
        } catch (Exception e) {
            log.error("Erreur lors de l'exécution de l'automatisation: {}", id, e);
            AutomationExecutionResponse response = new AutomationExecutionResponse(
                    id,
                    id,
                    "error",
                    null,
                    "Erreur: " + e.getMessage(),
                    null,
                    null,
                    0
            );
            return ResponseEntity.status(500).body(response);
        }
    }

    /**
     * Récupère le résultat de la dernière exécution
     */
    @GetMapping("/{id}/result")
    public ResponseEntity<AutomationResult> getLastResult(@PathVariable String id) {
        try {
            AutomationResult result = automationService.getLastResult(id);
            if (result != null) {
                return ResponseEntity.ok(result);
            }
            return ResponseEntity.notFound().build();
        } catch (Exception e) {
            log.error("Erreur lors de la récupération du résultat: {}", id, e);
            return ResponseEntity.status(500).build();
        }
    }
}
