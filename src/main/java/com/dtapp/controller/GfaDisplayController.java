package com.dtapp.controller;

import com.dtapp.entity.GfaAgent;
import com.dtapp.entity.GfaGuichet;
import com.dtapp.entity.GfaService;
import com.dtapp.entity.GfaTicket;
import com.dtapp.entity.GfaWifiSettings;
import com.dtapp.entity.User;
import com.dtapp.repository.GfaAgentRepository;
import com.dtapp.repository.GfaGuichetRepository;
import com.dtapp.repository.GfaServiceRepository;
import com.dtapp.repository.GfaTicketRepository;
import com.dtapp.repository.GfaWifiSettingsRepository;
import com.dtapp.repository.UserRepository;
import com.dtapp.service.PusherService;
import org.springframework.dao.DataIntegrityViolationException;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpStatus;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.ResponseBody;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.net.URLEncoder;
import java.nio.charset.StandardCharsets;
import java.time.Duration;
import java.time.LocalDate;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.Locale;
import java.util.NoSuchElementException;
import java.util.Objects;
import java.util.Optional;
import java.util.UUID;
import java.util.function.Function;
import java.util.stream.Collectors;

@Controller
public class GfaDisplayController {

    private static final List<String> ACTIVE_TABS = List.of(
            "vue-globale", "services", "guichets", "agents", "tickets", "parametres", "ecran"
    );

    private static final String STATUS_WAITING = "EN_ATTENTE";
    private static final String STATUS_IN_PROGRESS = "EN_COURS";
    private static final String STATUS_COMPLETED = "TERMINE";
    private static final String STATUS_INCOMPLETE = "INCOMPLET";
    private static final String STATUS_ABSENT = "ABSENT";

    private final UserRepository userRepository;
    private final GfaServiceRepository gfaServiceRepository;
    private final GfaGuichetRepository gfaGuichetRepository;
    private final GfaAgentRepository gfaAgentRepository;
    private final GfaTicketRepository gfaTicketRepository;
    private final GfaWifiSettingsRepository gfaWifiSettingsRepository;
    private final PusherService pusherService;

    public GfaDisplayController(UserRepository userRepository,
                                GfaServiceRepository gfaServiceRepository,
                                GfaGuichetRepository gfaGuichetRepository,
                                GfaAgentRepository gfaAgentRepository,
                                GfaTicketRepository gfaTicketRepository,
                                GfaWifiSettingsRepository gfaWifiSettingsRepository,
                                PusherService pusherService) {
        this.userRepository = userRepository;
        this.gfaServiceRepository = gfaServiceRepository;
        this.gfaGuichetRepository = gfaGuichetRepository;
        this.gfaAgentRepository = gfaAgentRepository;
        this.gfaTicketRepository = gfaTicketRepository;
        this.gfaWifiSettingsRepository = gfaWifiSettingsRepository;
        this.pusherService = pusherService;
    }

    @GetMapping("/gfa/display")
    public String publicDisplay(Model model) {
        GfaWifiSettings wifiSettings = loadWifiSettings();
        DisplayStateResponse displayState = buildDisplayState(wifiSettings);
        String token = uniqueToken(null);
        String dynamicBaseUrl = org.springframework.web.servlet.support.ServletUriComponentsBuilder
                .fromCurrentContextPath().build().toUriString();
        model.addAttribute("wifiSettings", wifiSettings);
        model.addAttribute("wifiQrPayload", buildWifiQrPayload(wifiSettings));
        model.addAttribute("ticketEntryUrl", dynamicBaseUrl + "/gfa/ticket?token=" + token);
        model.addAttribute("displayState", displayState);
        model.addAttribute("pusherKey", pusherService.getKey());
        model.addAttribute("pusherCluster", pusherService.getCluster());
        return "facturation/gfa-display";
    }

    @GetMapping("/api/gfa/display/state")
    @ResponseBody
    public DisplayStateResponse publicDisplayState() {
        return buildDisplayState(loadWifiSettings());
    }

    @GetMapping("/gfa/ticket")
    public String publicTicket(@RequestParam(required = false) String token, Model model) {
        if (token == null || token.isBlank()) {
            return "redirect:/gfa/ticket?token=" + uniqueToken(null);
        }
        String generatedToken = normalizeTicketToken(token);
        model.addAttribute("ticketToken", generatedToken);
        model.addAttribute("services", gfaServiceRepository.findAllByActifTrueOrderByIdAsc());
        return "facturation/gfa-ticket";
    }

    @PostMapping("/gfa/ticket")
    public String createPublicTicket(@RequestParam Long serviceId,
                                     @RequestParam String token,
                                     RedirectAttributes ra) {
        try {
            GfaService service = loadService(serviceId);
            GfaTicket ticket = new GfaTicket();
            ticket.setService(service);
            ticket.setNumero(generateTicketNumber(service));
            ticket.setToken(uniqueToken(token));
            ticket.setStatut(STATUS_WAITING);
            ticket.setCreatedAt(LocalDateTime.now());
            ticket.setUpdatedAt(LocalDateTime.now());
            GfaTicket savedTicket = gfaTicketRepository.save(ticket);
            return "redirect:/gfa/ticket/" + savedTicket.getId() + "?token=" + URLEncoder.encode(savedTicket.getToken(), StandardCharsets.UTF_8);
        } catch (NoSuchElementException ex) {
            ra.addFlashAttribute("errorMsg", "Service introuvable.");
            return "redirect:/gfa/ticket";
        }
    }

    @GetMapping("/gfa/ticket/{id}")
    public Object publicTicketSummary(@PathVariable long id,
                                      @RequestParam String token,
                                      Model model) {
        Optional<GfaTicket> ticketOpt = gfaTicketRepository.findByIdAndToken(id, token);
        if (ticketOpt.isEmpty()) {
            return ResponseEntity.status(404)
                    .header(HttpHeaders.CACHE_CONTROL, "no-store, no-cache, must-revalidate, max-age=0")
                    .body("Ticket introuvable.");
        }

        GfaTicket ticket = ticketOpt.get();
        model.addAttribute("ticket", ticket);
        model.addAttribute("queueRank", ticket.getService() != null && ticket.getCreatedAt() != null
                ? gfaTicketRepository.countWaitingRank(ticket.getService().getId(), ticket.getCreatedAt())
                : 0L);
        return "facturation/gfa-ticket-summary";
    }

    @GetMapping("/menu/facturation/gfa/admin")
    public String admin(@RequestParam(defaultValue = "vue-globale") String tab,
                        @RequestParam(required = false) Long editServiceId,
                        @RequestParam(required = false) Long editGuichetId,
                        @RequestParam(required = false) Long editAgentId,
                        Model model,
                        Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        List<GfaService> services = gfaServiceRepository.findAllByOrderByNomAsc();
        List<GfaGuichet> guichets = gfaGuichetRepository.findAllByOrderByNumeroAsc();
        List<GfaAgent> agents = gfaAgentRepository.findAllByOrderByNomAscPrenomAsc();
        List<GfaTicket> tickets = gfaTicketRepository.findAllByOrderByCreatedAtDesc();
        GfaWifiSettings wifiSettings = loadWifiSettings();

        boolean isAdmin = auth.getAuthorities().stream()
                .anyMatch(a -> a.getAuthority().equals("ROLE_ADMIN"));
        model.addAttribute("isAdmin", isAdmin);
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("activeTab", normalizeTab(tab));
        model.addAttribute("services", services);
        model.addAttribute("guichets", guichets);
        model.addAttribute("agents", agents);
        model.addAttribute("tickets", tickets);
        model.addAttribute("wifiSettings", wifiSettings);
        model.addAttribute("selectedService", findById(services, editServiceId));
        model.addAttribute("selectedGuichet", findById(guichets, editGuichetId));
        model.addAttribute("selectedAgent", findById(agents, editAgentId));
        model.addAttribute("serviceCount", gfaServiceRepository.countByActifTrue());
        model.addAttribute("guichetCount", gfaGuichetRepository.countByActifTrue());
        model.addAttribute("agentCount", gfaAgentRepository.countByActifTrue());
        model.addAttribute("waitingCount", gfaTicketRepository.countByStatutIgnoreCase(STATUS_WAITING));
        model.addAttribute("closedTodayCount", gfaTicketRepository.countClosedToday());
        model.addAttribute("activityRows", buildActivityRows(guichets, agents));
        model.addAttribute("serviceStats", buildServiceStats(services));
        return "facturation/gfa-admin";
    }

    @GetMapping("/api/gfa/guichets/{id}/state")
    @ResponseBody
    public ResponseEntity<?> guichetState(@PathVariable long id) {
        try {
            return ResponseEntity.ok(buildGuichetState(loadGuichet(id)));
        } catch (NoSuchElementException ex) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND).body(new ErrorResponse(ex.getMessage()));
        }
    }

    @PostMapping("/api/gfa/guichets/{id}/actions/{action}")
    @ResponseBody
    public ResponseEntity<ActionResponse> guichetAction(@PathVariable long id,
                                                        @PathVariable String action) {
        try {
            GfaGuichet guichet = loadGuichet(id);
            return switch (action.toLowerCase(Locale.ROOT)) {
                case "next" -> ResponseEntity.ok(callNextTicket(guichet));
                case "recall" -> ResponseEntity.ok(recallCurrentTicket(guichet));
                case "complete" -> ResponseEntity.ok(closeCurrentTicket(guichet, STATUS_COMPLETED, "Ticket marque comme termine."));
                case "incomplete" -> ResponseEntity.ok(closeCurrentTicket(guichet, STATUS_INCOMPLETE, "Ticket marque comme incomplet."));
                case "absent" -> ResponseEntity.ok(closeCurrentTicket(guichet, STATUS_ABSENT, "Ticket marque comme absent."));
                default -> ResponseEntity.badRequest().body(buildErrorActionResponse(guichet, "Action de guichet inconnue."));
            };
        } catch (NoSuchElementException ex) {
            return ResponseEntity.status(HttpStatus.NOT_FOUND)
                    .body(new ActionResponse(false, ex.getMessage(), null, buildDisplayState(loadWifiSettings())));
        } catch (IllegalStateException ex) {
            GfaGuichet guichet = safeLoadGuichet(id);
            return ResponseEntity.badRequest().body(buildErrorActionResponse(guichet, ex.getMessage()));
        }
    }

    @PostMapping("/menu/facturation/gfa/admin/services")
    public String createService(@RequestParam String nom,
                                @RequestParam(required = false) String code,
                                @RequestParam(defaultValue = "false") boolean actif,
                                RedirectAttributes ra) {
        try {
            GfaService service = new GfaService();
            service.setNom(normalizeText(nom));
            service.setCode(normalizeCode(code));
            service.setActif(actif);
            validateService(service, null);
            gfaServiceRepository.save(service);
            ra.addFlashAttribute("successMsg", "Service ajoute avec succes.");
        } catch (IllegalArgumentException | DataIntegrityViolationException ex) {
            ra.addFlashAttribute("errorMsg", ex.getMessage());
        }
        return redirectToTab("services");
    }

    @PostMapping("/menu/facturation/gfa/admin/services/{id}")
    public String updateService(@PathVariable long id,
                                @RequestParam String nom,
                                @RequestParam(required = false) String code,
                                @RequestParam(defaultValue = "false") boolean actif,
                                RedirectAttributes ra) {
        try {
            GfaService service = gfaServiceRepository.findById(id).orElseThrow();
            service.setNom(normalizeText(nom));
            service.setCode(normalizeCode(code));
            service.setActif(actif);
            validateService(service, id);
            gfaServiceRepository.save(service);
            ra.addFlashAttribute("successMsg", "Service mis a jour avec succes.");
        } catch (NoSuchElementException ex) {
            ra.addFlashAttribute("errorMsg", "Service introuvable.");
        } catch (IllegalArgumentException | DataIntegrityViolationException ex) {
            ra.addFlashAttribute("errorMsg", ex.getMessage());
        }
        return redirectToTab("services");
    }

    @PostMapping("/menu/facturation/gfa/admin/services/{id}/delete")
    public String deleteService(@PathVariable long id, RedirectAttributes ra) {
        try {
            gfaServiceRepository.deleteById(id);
            ra.addFlashAttribute("successMsg", "Service supprime avec succes.");
        } catch (Exception ex) {
            ra.addFlashAttribute("errorMsg", "Impossible de supprimer ce service.");
        }
        return redirectToTab("services");
    }

    @PostMapping("/menu/facturation/gfa/admin/guichets")
    public String createGuichet(@RequestParam String numero,
                                @RequestParam String infos,
                                @RequestParam(required = false) Long serviceId,
                                @RequestParam(defaultValue = "false") boolean actif,
                                RedirectAttributes ra) {
        try {
            GfaGuichet guichet = new GfaGuichet();
            guichet.setNumero(normalizeText(numero));
            guichet.setInfos(normalizeText(infos));
            guichet.setService(loadService(serviceId));
            guichet.setActif(actif);
            validateGuichet(guichet);
            gfaGuichetRepository.save(guichet);
            ra.addFlashAttribute("successMsg", "Guichet ajoute avec succes.");
        } catch (IllegalArgumentException | NoSuchElementException ex) {
            ra.addFlashAttribute("errorMsg", ex.getMessage());
        }
        return redirectToTab("guichets");
    }

    @PostMapping("/menu/facturation/gfa/admin/guichets/{id}")
    public String updateGuichet(@PathVariable long id,
                                @RequestParam String numero,
                                @RequestParam String infos,
                                @RequestParam(required = false) Long serviceId,
                                @RequestParam(defaultValue = "false") boolean actif,
                                RedirectAttributes ra) {
        try {
            GfaGuichet guichet = gfaGuichetRepository.findById(id).orElseThrow();
            guichet.setNumero(normalizeText(numero));
            guichet.setInfos(normalizeText(infos));
            guichet.setService(loadService(serviceId));
            guichet.setActif(actif);
            validateGuichet(guichet);
            gfaGuichetRepository.save(guichet);
            ra.addFlashAttribute("successMsg", "Guichet mis a jour avec succes.");
        } catch (NoSuchElementException ex) {
            ra.addFlashAttribute("errorMsg", "Guichet introuvable.");
        } catch (IllegalArgumentException ex) {
            ra.addFlashAttribute("errorMsg", ex.getMessage());
        }
        return redirectToTab("guichets");
    }

    @PostMapping("/menu/facturation/gfa/admin/guichets/{id}/delete")
    public String deleteGuichet(@PathVariable long id, RedirectAttributes ra) {
        try {
            gfaGuichetRepository.deleteById(id);
            ra.addFlashAttribute("successMsg", "Guichet supprime avec succes.");
        } catch (Exception ex) {
            ra.addFlashAttribute("errorMsg", "Impossible de supprimer ce guichet.");
        }
        return redirectToTab("guichets");
    }

    @PostMapping("/menu/facturation/gfa/admin/agents")
    public String createAgent(@RequestParam String nom,
                              @RequestParam(required = false) String prenom,
                              @RequestParam(required = false) Long serviceId,
                              @RequestParam(required = false) Long guichetId,
                              @RequestParam(defaultValue = "false") boolean actif,
                              RedirectAttributes ra) {
        try {
            GfaAgent agent = new GfaAgent();
            agent.setNom(normalizeText(nom));
            agent.setPrenom(normalizeOptionalText(prenom));
            agent.setService(loadService(serviceId));
            agent.setGuichet(loadGuichet(guichetId));
            agent.setActif(actif);
            validateAgent(agent);
            gfaAgentRepository.save(agent);
            ra.addFlashAttribute("successMsg", "Agent ajoute avec succes.");
        } catch (IllegalArgumentException | NoSuchElementException ex) {
            ra.addFlashAttribute("errorMsg", ex.getMessage());
        }
        return redirectToTab("agents");
    }

    @PostMapping("/menu/facturation/gfa/admin/agents/{id}")
    public String updateAgent(@PathVariable long id,
                              @RequestParam String nom,
                              @RequestParam(required = false) String prenom,
                              @RequestParam(required = false) Long serviceId,
                              @RequestParam(required = false) Long guichetId,
                              @RequestParam(defaultValue = "false") boolean actif,
                              RedirectAttributes ra) {
        try {
            GfaAgent agent = gfaAgentRepository.findById(id).orElseThrow();
            agent.setNom(normalizeText(nom));
            agent.setPrenom(normalizeOptionalText(prenom));
            agent.setService(loadService(serviceId));
            agent.setGuichet(loadGuichet(guichetId));
            agent.setActif(actif);
            validateAgent(agent);
            gfaAgentRepository.save(agent);
            ra.addFlashAttribute("successMsg", "Agent mis a jour avec succes.");
        } catch (NoSuchElementException ex) {
            ra.addFlashAttribute("errorMsg", "Agent introuvable.");
        } catch (IllegalArgumentException ex) {
            ra.addFlashAttribute("errorMsg", ex.getMessage());
        }
        return redirectToTab("agents");
    }

    @PostMapping("/menu/facturation/gfa/admin/agents/{id}/delete")
    public String deleteAgent(@PathVariable long id, RedirectAttributes ra) {
        try {
            gfaAgentRepository.deleteById(id);
            ra.addFlashAttribute("successMsg", "Agent supprime avec succes.");
        } catch (Exception ex) {
            ra.addFlashAttribute("errorMsg", "Impossible de supprimer cet agent.");
        }
        return redirectToTab("agents");
    }

    @PostMapping("/menu/facturation/gfa/admin/parametres")
    public String updateWifiSettings(@RequestParam String ssid,
                                     @RequestParam(required = false) String password,
                                     RedirectAttributes ra) {
        try {
            GfaWifiSettings wifiSettings = loadWifiSettings();
            wifiSettings.setSsid(normalizeText(ssid));
            wifiSettings.setPassword(normalizeOptionalText(password, ""));
            gfaWifiSettingsRepository.save(wifiSettings);
            ra.addFlashAttribute("successMsg", "Parametres Wi-Fi enregistres avec succes.");
        } catch (IllegalArgumentException ex) {
            ra.addFlashAttribute("errorMsg", ex.getMessage());
        }
        return redirectToTab("parametres");
    }

    private ActionResponse callNextTicket(GfaGuichet guichet) {
        GfaService service = guichet.getService();
        if (service == null || service.getId() == null) {
            throw new IllegalStateException("Ce guichet n'est rattache a aucun service.");
        }
        if (!Boolean.TRUE.equals(guichet.getActif())) {
            throw new IllegalStateException("Ce guichet est inactif.");
        }
        if (findCurrentTicket(guichet.getId()).isPresent()) {
            throw new IllegalStateException("Veuillez terminer le client en cours avant d'appeler le suivant.");
        }

        GfaTicket nextTicket = gfaTicketRepository
                .findFirstByServiceIdAndStatutIgnoreCaseOrderByCreatedAtAsc(service.getId(), STATUS_WAITING)
                .orElseThrow(() -> new IllegalStateException("Aucun ticket en attente pour ce service."));

        LocalDateTime now = LocalDateTime.now();
        nextTicket.setGuichet(guichet);
        nextTicket.setAgent(resolveAgentForGuichet(guichet.getId()).orElse(null));
        nextTicket.setStatut(STATUS_IN_PROGRESS);
        nextTicket.setCalledAt(now);
        nextTicket.setUpdatedAt(now);
        gfaTicketRepository.save(nextTicket);
        DisplayStateResponse displayState = buildDisplayState(loadWifiSettings());
        pusherService.triggerDisplayUpdate(displayState);
        return new ActionResponse(true, "Ticket appele avec succes.", buildGuichetState(guichet), displayState);
    }

    private ActionResponse recallCurrentTicket(GfaGuichet guichet) {
        GfaTicket currentTicket = findCurrentTicket(guichet.getId())
                .orElseThrow(() -> new IllegalStateException("Aucun ticket en cours a rappeler."));

        LocalDateTime now = LocalDateTime.now();
        currentTicket.setCalledAt(now);
        currentTicket.setUpdatedAt(now);
        currentTicket.setAgent(resolveAgentForGuichet(guichet.getId()).orElse(currentTicket.getAgent()));
        gfaTicketRepository.save(currentTicket);
        DisplayStateResponse displayState = buildDisplayState(loadWifiSettings());
        pusherService.triggerDisplayUpdate(displayState);
        return new ActionResponse(true, "Le client en cours a ete rappele.", buildGuichetState(guichet), displayState);
    }

    private ActionResponse closeCurrentTicket(GfaGuichet guichet, String finalStatus, String successMessage) {
        GfaTicket currentTicket = findCurrentTicket(guichet.getId())
                .orElseThrow(() -> new IllegalStateException("Aucun ticket en cours sur ce guichet."));

        LocalDateTime now = LocalDateTime.now();
        currentTicket.setStatut(finalStatus);
        currentTicket.setClosedAt(now);
        currentTicket.setUpdatedAt(now);
        if (currentTicket.getCalledAt() != null) {
            currentTicket.setProcessingTime(Duration.between(currentTicket.getCalledAt(), now).getSeconds());
        }
        gfaTicketRepository.save(currentTicket);
        DisplayStateResponse displayState = buildDisplayState(loadWifiSettings());
        pusherService.triggerDisplayUpdate(displayState);
        return new ActionResponse(true, successMessage, buildGuichetState(guichet), displayState);
    }

    private GuichetStateResponse buildGuichetState(GfaGuichet guichet) {
        Optional<GfaAgent> assignedAgent = resolveAgentForGuichet(guichet.getId());
        Optional<GfaTicket> currentTicket = findCurrentTicket(guichet.getId());
        List<GfaTicket> waitingTickets = guichet.getService() != null && guichet.getService().getId() != null
                ? gfaTicketRepository.findByServiceIdAndStatutIgnoreCaseOrderByCreatedAtAsc(guichet.getService().getId(), STATUS_WAITING)
                : List.of();

        LocalDate today = LocalDate.now();
        List<GfaTicket> guichetTickets = gfaTicketRepository.findAllByOrderByCreatedAtDesc().stream()
                .filter(ticket -> ticket.getGuichet() != null && Objects.equals(ticket.getGuichet().getId(), guichet.getId()))
                .toList();

        long servedToday = guichetTickets.stream()
                .filter(ticket -> ticket.getClosedAt() != null && ticket.getClosedAt().toLocalDate().equals(today))
                .count();
        long absentToday = guichetTickets.stream()
                .filter(ticket -> STATUS_ABSENT.equalsIgnoreCase(ticket.getStatut()))
                .filter(ticket -> ticket.getClosedAt() != null && ticket.getClosedAt().toLocalDate().equals(today))
                .count();
        long incompleteToday = guichetTickets.stream()
                .filter(ticket -> STATUS_INCOMPLETE.equalsIgnoreCase(ticket.getStatut()))
                .filter(ticket -> ticket.getClosedAt() != null && ticket.getClosedAt().toLocalDate().equals(today))
                .count();

        return new GuichetStateResponse(
                guichet.getId(),
                guichet.getNumero(),
                guichet.getInfos(),
                guichet.getService() != null ? guichet.getService().getId() : null,
                guichet.getService() != null ? guichet.getService().getNom() : "Aucun service rattache",
                guichet.getService() != null ? normalizeOptionalText(guichet.getService().getCode(), "") : "",
                Boolean.TRUE.equals(guichet.getActif()),
                assignedAgent.map(this::formatAgent).orElse("Aucun agent affecte"),
                currentTicket.map(this::toTicketView).orElse(null),
                waitingTickets.stream().map(this::toTicketView).toList(),
                waitingTickets.size(),
                new ReportSummary(servedToday, absentToday, incompleteToday)
        );
    }

    private DisplayStateResponse buildDisplayState(GfaWifiSettings wifiSettings) {
        GfaTicket currentCall = gfaTicketRepository.findCurrentCallsOrdered().stream().findFirst().orElse(null);
        TicketView currentCallView = currentCall == null ? null : toTicketView(currentCall);
        String announcementKey = "";
        if (currentCall != null) {
            String timestamp = currentCall.getCalledAt() != null ? currentCall.getCalledAt().toString() : String.valueOf(currentCall.getId());
            announcementKey = currentCall.getId() + ":" + timestamp;
        }

        return new DisplayStateResponse(
                currentCallView,
                announcementKey,
                wifiSettings.getSsid(),
                buildWifiQrPayload(wifiSettings),
                "/gfa/ticket"
        );
    }

    private Optional<GfaTicket> findCurrentTicket(Long guichetId) {
        return gfaTicketRepository.findTopByGuichetIdAndStatutIgnoreCaseOrderByCalledAtDescIdDesc(guichetId, STATUS_IN_PROGRESS);
    }

    private Optional<GfaAgent> resolveAgentForGuichet(Long guichetId) {
        if (guichetId == null) {
            return Optional.empty();
        }
        return gfaAgentRepository.findFirstByGuichetIdAndActifTrueOrderByNomAscPrenomAsc(guichetId);
    }

    private TicketView toTicketView(GfaTicket ticket) {
        String guichetNumero = ticket.getGuichet() != null ? ticket.getGuichet().getNumero() : "-";
        String serviceNom = ticket.getService() != null ? ticket.getService().getNom() : "-";
        String agentNom = ticket.getAgent() != null ? formatAgent(ticket.getAgent()) : resolveAgentForGuichet(ticket.getGuichet() != null ? ticket.getGuichet().getId() : null)
                .map(this::formatAgent)
                .orElse("-");

        return new TicketView(
                ticket.getId(),
                ticket.getNumero(),
                serviceNom,
                guichetNumero,
                agentNom,
                ticket.getStatut(),
                ticket.getCreatedAt() != null ? ticket.getCreatedAt().toString() : null,
                ticket.getCalledAt() != null ? ticket.getCalledAt().toString() : null
        );
    }

    private ActionResponse buildErrorActionResponse(GfaGuichet guichet, String message) {
        return new ActionResponse(
                false,
                message,
                guichet != null ? buildGuichetState(guichet) : null,
                buildDisplayState(loadWifiSettings())
        );
    }

    private List<ActivityRow> buildActivityRows(List<GfaGuichet> guichets, List<GfaAgent> agents) {
        java.util.Map<Long, GfaAgent> agentByGuichet = agents.stream()
                .filter(agent -> agent.getGuichet() != null && agent.getGuichet().getId() != null)
                .collect(Collectors.toMap(agent -> agent.getGuichet().getId(), Function.identity(), (first, second) -> first));

        List<ActivityRow> rows = new ArrayList<>();
        for (GfaGuichet guichet : guichets) {
            GfaAgent agent = agentByGuichet.get(guichet.getId());
            Optional<GfaTicket> currentTicket = findCurrentTicket(guichet.getId());
            long waiting = guichet.getService() != null && guichet.getService().getId() != null
                    ? gfaTicketRepository.countByServiceIdAndStatutIgnoreCase(guichet.getService().getId(), STATUS_WAITING)
                    : 0L;

            rows.add(new ActivityRow(
                    guichet.getNumero(),
                    guichet.getService() != null ? guichet.getService().getNom() : "-",
                    agent != null ? formatAgent(agent) : "-",
                    currentTicket.map(GfaTicket::getNumero).orElse("-"),
                    currentTicket.map(GfaTicket::getStatut).orElse(Boolean.TRUE.equals(guichet.getActif()) ? "Disponible" : "Ferme"),
                    waiting
            ));
        }
        return rows;
    }

    private String formatAgent(GfaAgent agent) {
        String prenom = agent.getPrenom() == null ? "" : agent.getPrenom().trim();
        String nom = agent.getNom() == null ? "" : agent.getNom().trim();
        return (prenom + " " + nom).trim();
    }

    private <T> T findById(List<T> values, Long id) {
        if (id == null) {
            return null;
        }
        for (T value : values) {
            if (value instanceof GfaService service && Objects.equals(service.getId(), id)) {
                return value;
            }
            if (value instanceof GfaGuichet guichet && Objects.equals(guichet.getId(), id)) {
                return value;
            }
            if (value instanceof GfaAgent agent && Objects.equals(agent.getId(), id)) {
                return value;
            }
        }
        return null;
    }

    private GfaService loadService(Long id) {
        if (id == null) {
            return null;
        }
        return gfaServiceRepository.findById(id).orElseThrow(() -> new NoSuchElementException("Service introuvable."));
    }

    private GfaGuichet loadGuichet(Long id) {
        if (id == null) {
            return null;
        }
        return gfaGuichetRepository.findById(id).orElseThrow(() -> new NoSuchElementException("Guichet introuvable."));
    }

    private GfaGuichet safeLoadGuichet(Long id) {
        if (id == null) {
            return null;
        }
        return gfaGuichetRepository.findById(id).orElse(null);
    }

    private void validateService(GfaService service, Long currentId) {
        if (service.getNom() == null || service.getNom().isBlank()) {
            throw new IllegalArgumentException("Le nom du service est obligatoire.");
        }
        if (service.getCode() != null && !service.getCode().isBlank()) {
            boolean duplicate = currentId == null
                    ? gfaServiceRepository.existsByCodeIgnoreCase(service.getCode())
                    : gfaServiceRepository.existsByCodeIgnoreCaseAndIdNot(service.getCode(), currentId);
            if (duplicate) {
                throw new IllegalArgumentException("Le code du service existe deja.");
            }
        }
    }

    private void validateGuichet(GfaGuichet guichet) {
        if (guichet.getNumero() == null || guichet.getNumero().isBlank()) {
            throw new IllegalArgumentException("Le numero du guichet est obligatoire.");
        }
        if (guichet.getInfos() == null || guichet.getInfos().isBlank()) {
            throw new IllegalArgumentException("Les informations du guichet sont obligatoires.");
        }
    }

    private void validateAgent(GfaAgent agent) {
        if (agent.getNom() == null || agent.getNom().isBlank()) {
            throw new IllegalArgumentException("Le nom de l'agent est obligatoire.");
        }
    }

    private GfaWifiSettings loadWifiSettings() {
        return gfaWifiSettingsRepository.findTopByOrderByIdAsc().orElseGet(() -> {
            GfaWifiSettings settings = new GfaWifiSettings();
            settings.setSsid("DakarTerminal_WiFi");
            settings.setPassword("");
            return settings;
        });
    }

    private String buildWifiQrPayload(GfaWifiSettings settings) {
        String ssid = settings.getSsid() == null ? "" : settings.getSsid();
        String password = settings.getPassword() == null ? "" : settings.getPassword();
        String payload = "WIFI:T:WPA;S:" + escapeWifi(ssid) + ";P:" + escapeWifi(password) + ";;";
        return URLEncoder.encode(payload, StandardCharsets.UTF_8);
    }

    private String generateTicketNumber(GfaService service) {
        String rawCode = service.getCode() != null && !service.getCode().isBlank()
                ? service.getCode()
                : service.getNom();
        String prefix = rawCode.substring(0, 1).toUpperCase(Locale.ROOT);
        long nextSequence = gfaTicketRepository.countByServiceId(service.getId()) + 1;
        return prefix + "-" + String.format("%03d", nextSequence);
    }

    private String normalizeTicketToken(String token) {
        return uniqueToken(token);
    }

    private String uniqueToken(String token) {
        String candidate = (token == null || token.isBlank()) ? UUID.randomUUID().toString().replace("-", "") : token.trim();
        while (gfaTicketRepository.existsByToken(candidate)) {
            candidate = UUID.randomUUID().toString().replace("-", "");
        }
        return candidate;
    }

    private String escapeWifi(String value) {
        return value.replace("\\", "\\\\")
                .replace(";", "\\;")
                .replace(",", "\\,")
                .replace(":", "\\:");
    }

    private String normalizeTab(String tab) {
        return ACTIVE_TABS.contains(tab) ? tab : "vue-globale";
    }

    private String redirectToTab(String tab) {
        return "redirect:/menu/facturation/gfa/admin?tab=" + normalizeTab(tab);
    }

    private String normalizeText(String value) {
        String normalized = normalizeOptionalText(value);
        if (normalized == null || normalized.isBlank()) {
            throw new IllegalArgumentException("Les champs obligatoires doivent etre renseignes.");
        }
        return normalized;
    }

    private String normalizeCode(String value) {
        String normalized = normalizeOptionalText(value);
        return normalized == null ? null : normalized.toUpperCase(Locale.ROOT);
    }

    private String normalizeOptionalText(String value) {
        return normalizeOptionalText(value, null);
    }

    private String normalizeOptionalText(String value, String fallback) {
        if (value == null) {
            return fallback;
        }
        String normalized = value.trim();
        return normalized.isEmpty() ? fallback : normalized;
    }

    public record ActivityRow(String guichet,
                              String service,
                              String agent,
                              String ticketActuel,
                              String statut,
                              long enAttente) {
    }

    public record ServiceStats(String nom,
                               long attente,
                               long enCours,
                               long termines,
                               long incomplets,
                               long absents) {
    }

    private List<ServiceStats> buildServiceStats(List<GfaService> services) {
        List<ServiceStats> stats = new ArrayList<>();
        for (GfaService service : services) {
            Long id = service.getId();
            stats.add(new ServiceStats(
                    service.getNom(),
                    gfaTicketRepository.countByServiceIdAndStatutIgnoreCase(id, STATUS_WAITING),
                    gfaTicketRepository.countByServiceIdAndStatutIgnoreCase(id, STATUS_IN_PROGRESS),
                    gfaTicketRepository.countByServiceIdAndStatutIgnoreCase(id, STATUS_COMPLETED),
                    gfaTicketRepository.countByServiceIdAndStatutIgnoreCase(id, STATUS_INCOMPLETE),
                    gfaTicketRepository.countByServiceIdAndStatutIgnoreCase(id, STATUS_ABSENT)
            ));
        }

        // Ordre demandé : VALIDATION, FACTURATION, CAISSE, BAD, puis les autres
        List<String> desiredOrder = List.of("VALIDATION", "FACTURATION", "CAISSE", "BAD");
        stats.sort((s1, s2) -> {
            String nom1 = s1.nom() != null ? s1.nom().trim().toUpperCase(Locale.ROOT) : "";
            String nom2 = s2.nom() != null ? s2.nom().trim().toUpperCase(Locale.ROOT) : "";
            int index1 = desiredOrder.indexOf(nom1);
            int index2 = desiredOrder.indexOf(nom2);
            if (index1 < 0) index1 = desiredOrder.size();
            if (index2 < 0) index2 = desiredOrder.size();
            int compareIndex = Integer.compare(index1, index2);
            if (compareIndex != 0) return compareIndex;
            return nom1.compareTo(nom2);
        });

        return stats;
    }

    public record TicketView(Long id,
                             String numero,
                             String service,
                             String guichet,
                             String agent,
                             String statut,
                             String createdAt,
                             String calledAt) {
    }

    public record ReportSummary(long traitesAujourdhui,
                                long absentsAujourdhui,
                                long incompletsAujourdhui) {
    }

    public record GuichetStateResponse(Long guichetId,
                                       String guichetNumero,
                                       String guichetInfos,
                                       Long serviceId,
                                       String serviceNom,
                                       String serviceCode,
                                       boolean actif,
                                       String agentNom,
                                       TicketView currentCall,
                                       List<TicketView> waitingTickets,
                                       int waitingCount,
                                       ReportSummary reports) {
    }

    public record DisplayStateResponse(TicketView currentCall,
                                       String announcementKey,
                                       String wifiSsid,
                                       String wifiQrPayload,
                                       String ticketEntryUrl) {
    }

    public record ActionResponse(boolean success,
                                 String message,
                                 GuichetStateResponse state,
                                 DisplayStateResponse display) {
    }

    public record ErrorResponse(String message) {
    }
}
