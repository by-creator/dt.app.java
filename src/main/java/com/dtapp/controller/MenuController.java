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
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
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
public class MenuController {

    private final UserRepository userRepository;
    private final RattachementBlRepository rattachementBlRepository;
    private final EmailService emailService;

    public MenuController(UserRepository userRepository,
                          RattachementBlRepository rattachementBlRepository,
                          EmailService emailService) {
        this.userRepository = userRepository;
        this.rattachementBlRepository = rattachementBlRepository;
        this.emailService = emailService;
    }

    @GetMapping("/menu")
    public String menu(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("menuView", "root");
        return "menu";
    }

    @GetMapping("/menu/dt")
    public String dtMenu(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("menuView", "dt");
        return "menu";
    }

    @GetMapping("/menu/facturation")
    public String facturationMenu(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority).collect(Collectors.toSet());
        boolean isFacturation = !roles.contains("ROLE_ADMIN") && roles.contains("ROLE_FACTURATION");
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("menuView", "facturation");
        model.addAttribute("isFacturation", isFacturation);
        return "menu";
    }

    @GetMapping("/menu/facturation/guichet-gfa")
    public String facturationGuichetGfa(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        return "facturation/guichet-gfa";
    }

    @GetMapping("/menu/planification")
    public String planificationMenu(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("menuView", "planification");
        return "menu";
    }

    @GetMapping("/direction-generale/dashboard")
    public String directionGeneraleDashboard(Model model, Authentication auth) {
        return renderModuleDashboard(model, auth, "Tableau de bord Direction Générale",
                "Module Direction Générale", "Bienvenue dans l'espace Direction Générale de Dakar Terminal.",
                "/menu/direction-generale", "/direction-generale/dashboard");
    }

    @GetMapping("/direction-financiere/dashboard")
    public String directionFinanciereDashboard(Model model, Authentication auth) {
        return renderModuleDashboard(model, auth, "Tableau de bord Direction Financière",
                "Module Direction Financière", "Bienvenue dans l'espace Direction Financière de Dakar Terminal.",
                "/menu/direction-financiere", "/direction-financiere/dashboard");
    }

    @GetMapping("/direction-exploitation/dashboard")
    public String directionExploitationDashboard(Model model, Authentication auth) {
        return renderModuleDashboard(model, auth, "Tableau de bord Direction Exploitation",
                "Module Direction Exploitation", "Bienvenue dans l'espace Direction Exploitation de Dakar Terminal.",
                "/menu/direction-exploitation", "/direction-exploitation/dashboard");
    }

    @GetMapping("/planification/dashboard")
    public String planificationDashboard(Model model, Authentication auth) {
        return renderModuleDashboard(model, auth, "Tableau de bord Planification",
                "Module Planification", "Bienvenue dans l'espace Planification de Dakar Terminal.",
                "/menu/planification", "/planification/dashboard");
    }

    @GetMapping("/menu/direction-generale")
    public String directionGeneraleMenu(Model model, Authentication auth) {
        return renderMenuView("direction-generale", model, auth);
    }

    @GetMapping("/menu/direction-financiere")
    public String directionFinanciereMenu(Model model, Authentication auth) {
        return renderMenuView("direction-financiere", model, auth);
    }

    @GetMapping("/menu/direction-exploitation")
    public String directionExploitationMenu(Model model, Authentication auth) {
        return renderMenuView("direction-exploitation", model, auth);
    }

    @GetMapping("/menu/direction-generale/gestion-remises")
    public String directionGeneraleRemises(Model model, Authentication auth) {
        return renderRemisesPage(model, auth, "Direction Generale", "/menu/direction-generale");
    }

    @GetMapping("/menu/direction-financiere/gestion-remises")
    public String directionFinanciereRemises(Model model, Authentication auth) {
        return renderRemisesPage(model, auth, "Direction Financiere", "/menu/direction-financiere");
    }

    @GetMapping("/menu/direction-exploitation/gestion-remises")
    public String directionExploitationRemises(Model model, Authentication auth) {
        return renderRemisesPage(model, auth, "Direction Exploitation", "/menu/direction-exploitation");
    }

    @GetMapping("/menu/facturation/ies")
    public String facturationIesWizard(@RequestParam(defaultValue = "lien-acces") String tab,
                                       Model model,
                                       Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("activeTab", normalizeIesTab(tab));
        return "facturation/ies";
    }

    @GetMapping("/menu/facturation/gestion-remises")
    public String facturationRemises(Model model, Authentication auth) {
        return renderRemisesPage(model, auth, "Facturation", "/menu/facturation");
    }

    @GetMapping("/menu/facturation/gestion-validations")
    public String facturationValidations(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        List<RattachementBl> demandes = rattachementBlRepository.findByTypeOrderByCreatedAtDesc("FACTURATION");
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority).collect(Collectors.toSet());
        boolean admin = roles.contains("ROLE_ADMIN");
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("demandes", demandes);
        model.addAttribute("sectionLabel", "Facturation");
        model.addAttribute("parentMenuPath", "/menu/facturation");
        model.addAttribute("currentPagePath", "/menu/facturation/gestion-validations");
        model.addAttribute("isAdmin", admin);
        model.addAttribute("sidebarMenuUrl", admin ? "/menu" : "/menu/facturation");
        return "validations/index";
    }

    @PostMapping("/menu/facturation/gestion-validations/{id}/valider")
    public String validerValidation(@PathVariable Long id, Authentication auth, RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();
        bl.setStatut("VALIDE");
        bl.setUserId(operator.getId());
        rattachementBlRepository.save(bl);
        emailService.notifyClientValidationValide(bl);
        ra.addFlashAttribute("successMsg", "Demande validee avec succes.");
        return "redirect:/menu/facturation/gestion-validations";
    }

    @PostMapping("/menu/facturation/gestion-validations/{id}/rejeter")
    public String rejeterValidation(@PathVariable Long id, @RequestParam String motif,
                                    Authentication auth, RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();
        bl.setStatut("REJETE");
        bl.setMotifRejet(motif);
        bl.setUserId(operator.getId());
        rattachementBlRepository.save(bl);
        emailService.notifyClientValidationRejete(bl);
        ra.addFlashAttribute("successMsg", "Demande rejetee.");
        return "redirect:/menu/facturation/gestion-validations";
    }

    @GetMapping("/menu/facturation/gestion-rapports")
    public String facturationReportsWizard(@RequestParam(defaultValue = "suivi-vides") String tab,
                                           Model model,
                                           Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("activeTab", normalizeReportsTab(tab));
        return "facturation/reports";
    }

    @GetMapping("/menu/facturation/unify")
    public String facturationUnifyWizard(@RequestParam(defaultValue = "formulaire-creation") String tab,
                                         @RequestParam(defaultValue = "1") int step,
                                         Model model,
                                         Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("activeTab", normalizeUnifyTab(tab));
        model.addAttribute("activeStep", normalizeUnifyStep(step));
        return "facturation/unify";
    }

    @PostMapping("/menu/facturation/gestion-rapports/admin")
    public String submitFacturationReportsAdmin(@RequestParam String reportType,
                                                @RequestParam("file") org.springframework.web.multipart.MultipartFile file,
                                                RedirectAttributes redirectAttributes) {
        String fileName = file != null ? file.getOriginalFilename() : null;
        redirectAttributes.addFlashAttribute("success",
                "Import prepare pour le type " + reportType + (fileName != null ? " avec le fichier " + fileName + "." : "."));
        return "redirect:/menu/facturation/gestion-rapports?tab=admin";
    }

    @PostMapping("/menu/facturation/ies/lien-acces")
    public String sendIesAccessLink(@RequestParam String email,
                                    RedirectAttributes redirectAttributes) {
        redirectAttributes.addFlashAttribute("success",
                "Lien d'acces prepare pour " + email.trim() + ".");
        return "redirect:/menu/facturation/ies?tab=lien-acces";
    }

    @PostMapping("/menu/facturation/ies/creation-compte")
    public String createIesAccount(@RequestParam String email,
                                   @RequestParam String password,
                                   RedirectAttributes redirectAttributes) {
        redirectAttributes.addFlashAttribute("success",
                "Creation de compte preparee pour " + email.trim() + ".");
        return "redirect:/menu/facturation/ies?tab=creation-compte";
    }

    @PostMapping("/menu/facturation/ies/reinitialisation-compte")
    public String resetIesAccount(@RequestParam String email,
                                  @RequestParam String password,
                                  RedirectAttributes redirectAttributes) {
        redirectAttributes.addFlashAttribute("success",
                "Reinitialisation de compte preparee pour " + email.trim() + ".");
        return "redirect:/menu/facturation/ies?tab=reinitialisation-compte";
    }

    private String normalizeIesTab(String tab) {
        if ("creation-compte".equalsIgnoreCase(tab)) {
            return "creation-compte";
        }
        if ("reinitialisation-compte".equalsIgnoreCase(tab)) {
            return "reinitialisation-compte";
        }
        return "lien-acces";
    }

    private String normalizeReportsTab(String tab) {
        if ("suivi-stationnement".equalsIgnoreCase(tab)) {
            return "suivi-stationnement";
        }
        if ("admin".equalsIgnoreCase(tab)) {
            return "admin";
        }
        return "suivi-vides";
    }

    private String normalizeUnifyTab(String tab) {
        if ("liste-tiers".equalsIgnoreCase(tab)) {
            return "liste-tiers";
        }
        if ("tutoriel".equalsIgnoreCase(tab)) {
            return "tutoriel";
        }
        if ("admin".equalsIgnoreCase(tab)) {
            return "admin";
        }
        return "formulaire-creation";
    }

    private int normalizeUnifyStep(int step) {
        if (step < 1) {
            return 1;
        }
        return Math.min(step, 4);
    }

    private String renderModuleDashboard(Model model, Authentication auth,
                                         String moduleTitle, String moduleName,
                                         String moduleSubtitle, String menuPath, String dashboardPath) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("currentDate", formatDate());
        model.addAttribute("moduleTitle", moduleTitle);
        model.addAttribute("moduleName", moduleName);
        model.addAttribute("moduleSubtitle", moduleSubtitle);
        model.addAttribute("menuPath", menuPath);
        model.addAttribute("dashboardPath", dashboardPath);
        return "module-dashboard";
    }

    private String formatDate() {
        String raw = LocalDate.now()
                .format(DateTimeFormatter.ofPattern("EEEE d MMMM yyyy", new Locale("fr", "FR")));
        return raw.substring(0, 1).toUpperCase() + raw.substring(1);
    }

    private String renderMenuView(String view, Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("menuView", view);
        return "menu";
    }

    @PostMapping("/menu/gestion-remises/{id}/valider")
    public String validerRemise(@PathVariable Long id,
                                @RequestParam(required = false) BigDecimal pourcentage,
                                @RequestParam String returnTo,
                                Authentication auth, RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority).collect(Collectors.toSet());
        if (isDirection(roles)) {
            bl.setStatut("VALIDE");
            bl.setPourcentage(pourcentage);
            emailService.notifyClientRemiseValide(bl);
        } else {
            bl.setStatut("EN_ATTENTE_DIRECTION");
            emailService.notifyClientRemiseEnAttenteDirection(bl);
        }
        bl.setUserId(operator.getId());
        rattachementBlRepository.save(bl);
        ra.addFlashAttribute("successMsg", "Demande mise a jour avec succes.");
        String target = returnTo != null && returnTo.startsWith("/menu/") ? returnTo : "/menu";
        return "redirect:" + target;
    }

    @PostMapping("/menu/gestion-remises/{id}/rejeter")
    public String rejeterRemise(@PathVariable Long id,
                                @RequestParam String motif,
                                @RequestParam String returnTo,
                                Authentication auth, RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();
        bl.setStatut("REJETE");
        bl.setMotifRejet(motif);
        bl.setUserId(operator.getId());
        rattachementBlRepository.save(bl);
        emailService.notifyClientRemiseRejete(bl);
        ra.addFlashAttribute("successMsg", "Demande rejetee.");
        String target = returnTo != null && returnTo.startsWith("/menu/") ? returnTo : "/menu";
        return "redirect:" + target;
    }

    private boolean isDirection(Set<String> roles) {
        return roles.contains("ROLE_ADMIN")
                || roles.contains("ROLE_DIRECTION_GENERALE")
                || roles.contains("ROLE_DIRECTION_FINANCIERE")
                || roles.contains("ROLE_DIRECTION_EXPLOITATION");
    }

    private String renderRemisesPage(Model model,
                                     Authentication auth,
                                     String sectionLabel,
                                     String parentMenuPath) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        List<RattachementBl> demandes = rattachementBlRepository.findByTypeOrderByCreatedAtDesc("REMISE");
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority).collect(Collectors.toSet());
        boolean admin = roles.contains("ROLE_ADMIN");
        String currentPagePath = parentMenuPath + "/gestion-remises";
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("demandes", demandes);
        model.addAttribute("sectionLabel", sectionLabel);
        model.addAttribute("parentMenuPath", parentMenuPath);
        model.addAttribute("currentPagePath", currentPagePath);
        model.addAttribute("isDirection", isDirection(roles));
        model.addAttribute("isAdmin", admin);
        model.addAttribute("sidebarMenuUrl", admin ? "/menu" : parentMenuPath);
        return "remises/index";
    }
}
