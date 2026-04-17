package com.dtapp.controller;

import com.dtapp.entity.RattachementBl;
import com.dtapp.entity.User;
import com.dtapp.repository.RattachementBlRepository;
import com.dtapp.repository.UserRepository;
import com.dtapp.service.EmailService;
import com.dtapp.util.PaginationUtils;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.data.domain.Page;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.util.List;
import java.util.Locale;
import java.util.Set;
import java.util.stream.Collectors;

@Controller
public class FacturationController {

    private final RattachementBlRepository rattachementBlRepository;
    private final UserRepository userRepository;
    private final EmailService emailService;

    public FacturationController(RattachementBlRepository rattachementBlRepository,
                                 UserRepository userRepository,
                                 EmailService emailService) {
        this.rattachementBlRepository = rattachementBlRepository;
        this.emailService = emailService;
        this.userRepository = userRepository;
    }

    // ==================== DASHBOARD ====================

    @GetMapping("/facturation/dashboard")
    public String dashboard(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("currentDate", formatDate());
        model.addAttribute("moduleTitle", "Tableau de bord Facturation");
        model.addAttribute("moduleName", "Module Facturation");
        model.addAttribute("moduleSubtitle", "Bienvenue dans l'espace facturation de Dakar Terminal.");
        model.addAttribute("menuPath", "/menu/facturation");
        model.addAttribute("dashboardPath", "/facturation/dashboard");
        return "module-dashboard";
    }

    // ==================== GESTION VALIDATIONS ====================

    @GetMapping("/facturation/gestion-validations")
    public String gestionValidations(@RequestParam(defaultValue = "0") int page,
                                     @RequestParam(defaultValue = "25") int size,
                                     @RequestParam(required = false) String filterDate,
                                     @RequestParam(required = false) String filterNom,
                                     @RequestParam(required = false) String filterPrenom,
                                     @RequestParam(required = false) String filterEmail,
                                     @RequestParam(required = false) String filterBl,
                                     @RequestParam(required = false) String filterMaison,
                                     @RequestParam(required = false) String filterStatut,
                                     Model model,
                                     Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority)
                .collect(Collectors.toSet());
        boolean admin = roles.contains("ROLE_ADMIN");
        Page<RattachementBl> demandesPage = fetchBls("FACTURATION", filterDate,
                filterNom, filterPrenom, filterEmail, filterBl, filterMaison, filterStatut, page, size);

        List<RattachementBl> demandes = demandesPage.getContent();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("demandes", demandes);
        PaginationUtils.addPageAttributes(model, demandesPage);
        model.addAttribute("filterDate", filterDate != null ? filterDate : "");
        model.addAttribute("filterNom", filterNom != null ? filterNom : "");
        model.addAttribute("filterPrenom", filterPrenom != null ? filterPrenom : "");
        model.addAttribute("filterEmail", filterEmail != null ? filterEmail : "");
        model.addAttribute("filterBl", filterBl != null ? filterBl : "");
        model.addAttribute("filterMaison", filterMaison != null ? filterMaison : "");
        model.addAttribute("filterStatut", filterStatut != null ? filterStatut : "");
        model.addAttribute("sectionLabel", "Facturation");
        model.addAttribute("parentMenuPath", "/menu/facturation");
        model.addAttribute("currentPagePath", "/facturation/gestion-validations");
        model.addAttribute("isAdmin", admin);
        model.addAttribute("sidebarMenuUrl", admin ? "/menu" : "/menu/facturation");
        return "validations/index";
    }

    @PostMapping("/facturation/gestion-validations/{id}/valider")
    public String validerValidation(@PathVariable long id,
                                    Authentication auth,
                                    RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();
        bl.setStatut("VALIDE");
        bl.setUserId(operator.getId());
        rattachementBlRepository.save(bl);
        emailService.notifyClientValidationValide(bl);
        ra.addFlashAttribute("successMsg", "Demande validee avec succes.");
        return "redirect:/facturation/gestion-validations";
    }

    @PostMapping("/facturation/gestion-validations/{id}/rejeter")
    public String rejeterValidation(@PathVariable long id,
                                    @RequestParam(value = "motif", required = false) String motif,
                                    @RequestParam(value = "motifAutre", required = false) String motifAutre,
                                    Authentication auth,
                                    RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();
        String motifFinal = (motif != null && !motif.isBlank()) ? motif.trim() : "";
        if ("Autre".equalsIgnoreCase(motifFinal) || motifFinal.isBlank()) {
            if (motifAutre != null && !motifAutre.isBlank()) {
                motifFinal = motifAutre.trim();
            }
        }
        if (motifFinal.isBlank()) {
            motifFinal = "Motif non precise";
        }
        bl.setStatut("REJETE");
        bl.setMotifRejet(motifFinal);
        bl.setUserId(operator.getId());
        rattachementBlRepository.save(bl);
        emailService.notifyClientValidationRejete(bl);
        ra.addFlashAttribute("successMsg", "Demande rejetee.");
        return "redirect:/facturation/gestion-validations";
    }

    // ==================== GESTION REMISES ====================

    @GetMapping("/facturation/gestion-remises")
    public String gestionRemises(@RequestParam(defaultValue = "0") int page,
                                 @RequestParam(defaultValue = "25") int size,
                                 @RequestParam(required = false) String filterDate,
                                 @RequestParam(required = false) String filterNom,
                                 @RequestParam(required = false) String filterPrenom,
                                 @RequestParam(required = false) String filterEmail,
                                 @RequestParam(required = false) String filterBl,
                                 @RequestParam(required = false) String filterMaison,
                                 @RequestParam(required = false) String filterStatut,
                                 Model model,
                                 Authentication auth) {
        return renderRemisesPage(model, auth, "Facturation", "/menu/facturation", "/facturation/gestion-remises",
                "/facturation/gestion-remises", page, size, filterDate, filterNom, filterPrenom, filterEmail, filterBl, filterMaison, filterStatut);
    }

    @PostMapping("/facturation/gestion-remises/{id}/valider")
    public String validerRemise(@PathVariable long id,
                                @RequestParam(required = false) BigDecimal pourcentage,
                                @RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate dateValiditeRemise,
                                Authentication auth,
                                RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority)
                .collect(Collectors.toSet());

        if (isDirection(roles)) {
            bl.setStatut("VALIDE");
            bl.setPourcentage(pourcentage);
            bl.setDateValiditeRemise(dateValiditeRemise);
        } else {
            bl.setStatut("EN_ATTENTE_DIRECTION");
        }
        bl.setUserId(operator.getId());
        rattachementBlRepository.save(bl);
        if (isDirection(roles)) {
            emailService.notifyClientRemiseValide(bl);
        } else {
            emailService.notifyClientRemiseEnAttenteDirection(bl);
        }
        ra.addFlashAttribute("successMsg", "Demande mise a jour avec succes.");
        return "redirect:/facturation/gestion-remises";
    }

    @PostMapping("/facturation/gestion-remises/{id}/rejeter")
    public String rejeterRemise(@PathVariable long id,
                                @RequestParam(value = "motif", required = false) String motif,
                                @RequestParam(value = "motifAutre", required = false) String motifAutre,
                                Authentication auth,
                                RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();
        String motifFinal = (motif != null && !motif.isBlank()) ? motif.trim() : "";
        if ("Autre".equalsIgnoreCase(motifFinal) || motifFinal.isBlank()) {
            if (motifAutre != null && !motifAutre.isBlank()) {
                motifFinal = motifAutre.trim();
            }
        }
        if (motifFinal.isBlank()) {
            motifFinal = "Motif non precise";
        }
        bl.setStatut("REJETE");
        bl.setMotifRejet(motifFinal);
        bl.setUserId(operator.getId());
        rattachementBlRepository.save(bl);
        emailService.notifyClientRemiseRejete(bl);
        ra.addFlashAttribute("successMsg", "Demande rejetee.");
        return "redirect:/facturation/gestion-remises";
    }

    private String formatDate() {
        String raw = LocalDate.now()
                .format(DateTimeFormatter.ofPattern("EEEE d MMMM yyyy", Locale.of("fr", "FR")));
        return raw.substring(0, 1).toUpperCase() + raw.substring(1);
    }

    private boolean isDirection(Set<String> roles) {
        return roles.contains("ROLE_ADMIN")
                || roles.contains("ROLE_DIRECTION_GENERALE")
                || roles.contains("ROLE_DIRECTION_FINANCIERE")
                || roles.contains("ROLE_DIRECTION_EXPLOITATION");
    }

    private Page<RattachementBl> fetchBls(String type,
                                          String filterDate,
                                          String nom,
                                          String prenom,
                                          String email,
                                          String bl,
                                          String maison,
                                          String statut,
                                          int page,
                                          int size) {
        java.time.LocalDateTime dateStart = null;
        java.time.LocalDateTime dateEnd = null;
        if (filterDate != null && !filterDate.isBlank()) {
            try {
                LocalDate date = LocalDate.parse(filterDate);
                dateStart = date.atStartOfDay();
                dateEnd = date.plusDays(1).atStartOfDay();
            } catch (Exception ignored) {
            }
        }

        String sNom = nom != null ? nom.trim() : "";
        String sPrenom = prenom != null ? prenom.trim() : "";
        String sEmail = email != null ? email.trim() : "";
        String sBl = bl != null ? bl.trim() : "";
        String sMaison = maison != null ? maison.trim() : "";
        String sStatut = statut != null ? statut.trim() : "";

        return rattachementBlRepository.findByTypeWithFilters(
                type, dateStart, dateEnd, sNom, sPrenom, sEmail, sBl, sMaison, sStatut,
                PaginationUtils.pageable(page, size));
    }

    private String renderRemisesPage(Model model,
                                     Authentication auth,
                                     String sectionLabel,
                                     String parentMenuPath,
                                     String currentPagePath,
                                     String actionBasePath,
                                     int page,
                                     int size,
                                     String filterDate,
                                     String filterNom,
                                     String filterPrenom,
                                     String filterEmail,
                                     String filterBl,
                                     String filterMaison,
                                     String filterStatut) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority)
                .collect(Collectors.toSet());
        boolean admin = roles.contains("ROLE_ADMIN");
        Page<RattachementBl> demandesPage = fetchBls("REMISE", filterDate,
                filterNom, filterPrenom, filterEmail, filterBl, filterMaison, filterStatut, page, size);

        List<RattachementBl> demandes = demandesPage.getContent();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("demandes", demandes);
        PaginationUtils.addPageAttributes(model, demandesPage);
        model.addAttribute("filterDate", filterDate != null ? filterDate : "");
        model.addAttribute("filterNom", filterNom != null ? filterNom : "");
        model.addAttribute("filterPrenom", filterPrenom != null ? filterPrenom : "");
        model.addAttribute("filterEmail", filterEmail != null ? filterEmail : "");
        model.addAttribute("filterBl", filterBl != null ? filterBl : "");
        model.addAttribute("filterMaison", filterMaison != null ? filterMaison : "");
        model.addAttribute("filterStatut", filterStatut != null ? filterStatut : "");
        model.addAttribute("sectionLabel", sectionLabel);
        model.addAttribute("parentMenuPath", parentMenuPath);
        model.addAttribute("currentPagePath", currentPagePath);
        model.addAttribute("actionBasePath", actionBasePath);
        model.addAttribute("isDirection", isDirection(roles));
        model.addAttribute("isAdmin", admin);
        model.addAttribute("sidebarMenuUrl", admin ? "/menu" : parentMenuPath);
        return "remises/index";
    }
}
