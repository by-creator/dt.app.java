package com.dtapp.service;

import com.dtapp.entity.RattachementBl;
import com.dtapp.repository.RattachementBlRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

import java.util.ArrayList;
import java.util.List;

@Component
public class RappelScheduler {

    private static final Logger log = LoggerFactory.getLogger(RappelScheduler.class);

    private final RattachementBlRepository rattachementBlRepository;
    private final EmailService emailService;

    public RappelScheduler(RattachementBlRepository rattachementBlRepository, EmailService emailService) {
        this.rattachementBlRepository = rattachementBlRepository;
        this.emailService = emailService;
    }

    /**
     * Toutes les 30 minutes, envoie des rappels pour les demandes toujours en attente :
     * - Validation (FACTURATION / EN_ATTENTE) → sn004-proforma@dakar-terminal.com
     * - Remise (REMISE / EN_ATTENTE + EN_ATTENTE_DIRECTION) → sn004-remise.facturation@dakar-terminal.com
     */
    @Scheduled(fixedDelayString = "${app.rappel.interval-ms:1800000}")
    public void envoyerRappels() {
        List<RattachementBl> validationsEnAttente =
                rattachementBlRepository.findByTypeAndStatutOrderByCreatedAtAsc("FACTURATION", "EN_ATTENTE");

        List<RattachementBl> remisesEnAttente =
                rattachementBlRepository.findByTypeAndStatutOrderByCreatedAtAsc("REMISE", "EN_ATTENTE");

        List<RattachementBl> remisesEnAttenteDirection =
                rattachementBlRepository.findByTypeAndStatutOrderByCreatedAtAsc("REMISE", "EN_ATTENTE_DIRECTION");

        List<RattachementBl> toutesRemises = new ArrayList<>(remisesEnAttente);
        toutesRemises.addAll(remisesEnAttenteDirection);

        log.info("Rappels : {} validation(s) EN_ATTENTE, {} remise(s) en attente (dont {} EN_ATTENTE_DIRECTION)",
                validationsEnAttente.size(), toutesRemises.size(), remisesEnAttenteDirection.size());

        emailService.sendRappelValidation(validationsEnAttente);
        emailService.sendRappelRemise(toutesRemises);
    }
}
