package com.dtapp.controller;

import com.dtapp.entity.RattachementBl;
import com.dtapp.entity.User;
import com.dtapp.repository.RattachementBlRepository;
import com.dtapp.repository.UserRepository;
import com.dtapp.service.EmailService;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
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
    public String gestionValidations(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        List<RattachementBl> demandes = rattachementBlRepository.findByTypeOrderByCreatedAtDesc("FACTURATION");
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("demandes", demandes);
        return "facturation/gestion-validations";
    }

    @PostMapping("/facturation/gestion-validations/{id}/valider")
    public String validerValidation(@PathVariable Long id,
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
    public String rejeterValidation(@PathVariable Long id,
                                    @RequestParam String motif,
                                    Authentication auth,
                                    RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();
        bl.setStatut("REJETE");
        bl.setMotifRejet(motif);
        bl.setUserId(operator.getId());
        rattachementBlRepository.save(bl);
        emailService.notifyClientValidationRejete(bl);
        ra.addFlashAttribute("successMsg", "Demande rejetee.");
        return "redirect:/facturation/gestion-validations";
    }

    // ==================== GESTION REMISES ====================

    @GetMapping("/facturation/gestion-remises")
    public String gestionRemises(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        List<RattachementBl> demandes = rattachementBlRepository.findByTypeOrderByCreatedAtDesc("REMISE");
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority)
                .collect(Collectors.toSet());
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("demandes", demandes);
        model.addAttribute("isDirection", isDirection(roles));
        model.addAttribute("isFacturation", roles.contains("ROLE_FACTURATION") || roles.contains("ROLE_ADMIN"));
        return "facturation/gestion-remises";
    }

    @PostMapping("/facturation/gestion-remises/{id}/valider")
    public String validerRemise(@PathVariable Long id,
                                @RequestParam(required = false) BigDecimal pourcentage,
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
    public String rejeterRemise(@PathVariable Long id,
                                @RequestParam String motif,
                                Authentication auth,
                                RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();
        bl.setStatut("REJETE");
        bl.setMotifRejet(motif);
        bl.setUserId(operator.getId());
        rattachementBlRepository.save(bl);
        emailService.notifyClientRemiseRejete(bl);
        ra.addFlashAttribute("successMsg", "Demande rejetee.");
        return "redirect:/facturation/gestion-remises";
    }

    private String formatDate() {
        String raw = LocalDate.now()
                .format(DateTimeFormatter.ofPattern("EEEE d MMMM yyyy", new Locale("fr", "FR")));
        return raw.substring(0, 1).toUpperCase() + raw.substring(1);
    }

    private boolean isDirection(Set<String> roles) {
        return roles.contains("ROLE_ADMIN")
                || roles.contains("ROLE_DIRECTION_GENERALE")
                || roles.contains("ROLE_DIRECTION_FINANCIERE")
                || roles.contains("ROLE_DIRECTION_EXPLOITATION");
    }
}
