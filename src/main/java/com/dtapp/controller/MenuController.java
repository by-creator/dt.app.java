package com.dtapp.controller;

import com.dtapp.entity.User;
import com.dtapp.repository.UserRepository;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

@Controller
public class MenuController {

    private final UserRepository userRepository;

    public MenuController(UserRepository userRepository) {
        this.userRepository = userRepository;
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
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("menuView", "facturation");
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
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("sectionLabel", "Facturation");
        model.addAttribute("parentMenuPath", "/menu/facturation");
        return "validations/index";
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

    private String renderMenuView(String view, Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("menuView", view);
        return "menu";
    }

    private String renderRemisesPage(Model model,
                                     Authentication auth,
                                     String sectionLabel,
                                     String parentMenuPath) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("sectionLabel", sectionLabel);
        model.addAttribute("parentMenuPath", parentMenuPath);
        return "remises/index";
    }
}
