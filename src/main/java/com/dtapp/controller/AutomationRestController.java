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
     * Lance une automatisation de façon asynchrone, retourne un jobId immédiatement
     */
    @PostMapping("/{id}/execute")
    public ResponseEntity<Map<String, String>> executeAutomation(
            @PathVariable String id,
            @RequestBody(required = false) Map<String, String> params) {
        try {
            log.info("Soumission de l'automatisation: {}", id);
            String jobId = automationService.submitAutomation(id, params);
            return ResponseEntity.ok(Map.of("jobId", jobId, "status", "pending"));
        } catch (Exception e) {
            log.error("Erreur lors de la soumission de l'automatisation: {}", id, e);
            return ResponseEntity.status(500).body(Map.of("status", "error", "error", e.getMessage()));
        }
    }

    /**
     * Poll le statut d'un job asynchrone
     */
    @GetMapping("/jobs/{jobId}")
    public ResponseEntity<?> getJobStatus(@PathVariable String jobId) {
        AutomationService.JobStatus job = automationService.getJobStatus(jobId);
        if (job == null) {
            return ResponseEntity.notFound().build();
        }
        if (job.getResult() == null) {
            return ResponseEntity.ok(Map.of("jobId", jobId, "status", job.getStatus()));
        }
        AutomationResult result = job.getResult();
        AutomationExecutionResponse response = new AutomationExecutionResponse(
                result.getId(),
                jobId,
                job.getStatus(),
                result.getMessage(),
                result.getErrorMessage(),
                result.getScreenshotPath(),
                result.getLogPath(),
                result.getExecutionTime(),
                result.getDocuments()
        );
        return ResponseEntity.ok(response);
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
