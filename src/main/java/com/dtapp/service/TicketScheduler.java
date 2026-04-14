package com.dtapp.service;

import com.dtapp.repository.GfaTicketRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

@Component
public class TicketScheduler {

    private static final Logger log = LoggerFactory.getLogger(TicketScheduler.class);

    private final GfaTicketRepository gfaTicketRepository;

    public TicketScheduler(GfaTicketRepository gfaTicketRepository) {
        this.gfaTicketRepository = gfaTicketRepository;
    }

    /**
     * Chaque matin du lundi au vendredi à 8h00 :
     * vide entièrement la table des tickets GFA (remise à zéro quotidienne).
     */
    @Scheduled(cron = "0 0 8 * * MON-FRI")
    @Transactional
    public void viderTickets() {
        long count = gfaTicketRepository.count();
        gfaTicketRepository.deleteAllInBatch();
        log.info("Tickets GFA : {} ticket(s) supprimé(s) à 8h00.", count);
    }
}
