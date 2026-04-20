package com.dtapp.controller;

import com.dtapp.entity.UpdateIesAccount;
import com.dtapp.repository.UpdateIesAccountRepository;
import com.dtapp.service.EmailService;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.util.StringUtils;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestHeader;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

import java.util.Map;

@RestController
@RequestMapping("/api/ies/accounts")
public class IesAutomationController {

    private final UpdateIesAccountRepository updateIesAccountRepository;
    private final EmailService emailService;

    @Value("${app.automation.internal-token:dt-app-internal-automation-token}")
    private String automationInternalToken;

    public IesAutomationController(UpdateIesAccountRepository updateIesAccountRepository,
                                   EmailService emailService) {
        this.updateIesAccountRepository = updateIesAccountRepository;
        this.emailService = emailService;
    }

    @PostMapping("/not-found")
    public ResponseEntity<Map<String, Object>> markAccountNotFound(
            @RequestHeader(value = "X-Automation-Token", required = false) String token,
            @RequestBody(required = false) Map<String, String> body) {
        if (!StringUtils.hasText(token) || !automationInternalToken.equals(token)) {
            return ResponseEntity.status(HttpStatus.UNAUTHORIZED)
                    .body(Map.of("success", false, "message", "Token invalide."));
        }

        String compte = body != null ? body.getOrDefault("compte", "").trim() : "";
        if (!StringUtils.hasText(compte)) {
            return ResponseEntity.badRequest()
                    .body(Map.of("success", false, "message", "Le compte est obligatoire."));
        }

        UpdateIesAccount account = updateIesAccountRepository
                .findTopByCompteOrderByCreatedAtDesc(compte)
                .orElseGet(UpdateIesAccount::new);
        account.setCompte(compte);
        account.setStatut("not found");
        updateIesAccountRepository.save(account);

        emailService.sendIesClientFacturationNotFound(compte);

        return ResponseEntity.ok(Map.of(
                "success", true,
                "message", "Compte IES mis a jour et email envoye.",
                "compte", compte,
                "statut", "not found"
        ));
    }
}
