package com.dtapp.controller;

import com.dtapp.entity.RattachementBl;
import com.dtapp.entity.SatisfactionReponse;
import com.dtapp.entity.TiersUnify;
import com.dtapp.entity.User;
import com.dtapp.repository.GfaGuichetRepository;
import com.dtapp.repository.RattachementBlRepository;
import com.dtapp.repository.SatisfactionReponseRepository;
import com.dtapp.repository.TiersUnifyRepository;
import com.dtapp.repository.UserRepository;
import com.dtapp.service.BulkInsertService;
import com.dtapp.service.EmailService;
import jakarta.servlet.http.HttpServletResponse;
import org.apache.poi.ss.usermodel.*;
import org.apache.poi.xssf.usermodel.XSSFWorkbook;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Sort;
import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.security.core.Authentication;
import org.springframework.security.core.GrantedAuthority;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.util.StringUtils;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.io.IOException;
import java.math.BigDecimal;
import java.time.LocalDate;
import java.time.format.DateTimeFormatter;
import java.util.*;
import java.util.function.Function;
import java.util.stream.Collectors;

@Controller
public class MenuController {

    private final UserRepository userRepository;
    private final RattachementBlRepository rattachementBlRepository;
    private final SatisfactionReponseRepository satisfactionReponseRepository;
    private final GfaGuichetRepository gfaGuichetRepository;
    private final TiersUnifyRepository tiersUnifyRepository;
    private final EmailService emailService;
    private final BulkInsertService bulkInsertService;

    public MenuController(UserRepository userRepository,
                          RattachementBlRepository rattachementBlRepository,
                          SatisfactionReponseRepository satisfactionReponseRepository,
                          GfaGuichetRepository gfaGuichetRepository,
                          TiersUnifyRepository tiersUnifyRepository,
                          EmailService emailService,
                          BulkInsertService bulkInsertService) {
        this.userRepository = userRepository;
        this.rattachementBlRepository = rattachementBlRepository;
        this.satisfactionReponseRepository = satisfactionReponseRepository;
        this.gfaGuichetRepository = gfaGuichetRepository;
        this.tiersUnifyRepository = tiersUnifyRepository;
        this.emailService = emailService;
        this.bulkInsertService = bulkInsertService;
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
        boolean showRemises = roles.contains("ROLE_ADMIN")
                || "aliounebadara.sy@dakar-terminal.com".equals(loggedUser.getEmail())
                || "charles.sarr@dakar-terminal.com".equals(loggedUser.getEmail());
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("menuView", "facturation");
        model.addAttribute("isFacturation", isFacturation);
        model.addAttribute("showRemises", showRemises);
        return "menu";
    }

    @GetMapping("/menu/facturation/guichet-gfa")
    public String facturationGuichetGfa(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("guichets", gfaGuichetRepository.findAllByActifTrueOrderByNumeroAsc());
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

    @GetMapping("/menu/informatique")
    public String informatiqueMenu(Model model, Authentication auth) {
        return renderMenuView("informatique", model, auth);
    }

    @GetMapping("/menu/informatique/bon-de-sortie")
    public String informatiqueBonDeSortie(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        return "informatique/bon-de-sortie";
    }

    @GetMapping("/menu/direction-generale/gestion-remises")
    public String directionGeneraleRemises(@RequestParam(defaultValue = "0") int page,
                                           @RequestParam(required = false) String filterDate,
                                           @RequestParam(required = false) String filterNom,
                                           @RequestParam(required = false) String filterPrenom,
                                           @RequestParam(required = false) String filterEmail,
                                           @RequestParam(required = false) String filterBl,
                                           @RequestParam(required = false) String filterMaison,
                                           @RequestParam(required = false) String filterStatut,
                                           Model model, Authentication auth) {
        return renderRemisesPage(model, auth, "Direction Generale", "/menu/direction-generale", page,
                filterDate, filterNom, filterPrenom, filterEmail, filterBl, filterMaison, filterStatut);
    }

    @GetMapping("/menu/direction-financiere/gestion-remises")
    public String directionFinanciereRemises(@RequestParam(defaultValue = "0") int page,
                                             @RequestParam(required = false) String filterDate,
                                             @RequestParam(required = false) String filterNom,
                                             @RequestParam(required = false) String filterPrenom,
                                             @RequestParam(required = false) String filterEmail,
                                             @RequestParam(required = false) String filterBl,
                                             @RequestParam(required = false) String filterMaison,
                                             @RequestParam(required = false) String filterStatut,
                                             Model model, Authentication auth) {
        return renderRemisesPage(model, auth, "Direction Financiere", "/menu/direction-financiere", page,
                filterDate, filterNom, filterPrenom, filterEmail, filterBl, filterMaison, filterStatut);
    }

    @GetMapping("/menu/direction-exploitation/gestion-remises")
    public String directionExploitationRemises(@RequestParam(defaultValue = "0") int page,
                                               @RequestParam(required = false) String filterDate,
                                               @RequestParam(required = false) String filterNom,
                                               @RequestParam(required = false) String filterPrenom,
                                               @RequestParam(required = false) String filterEmail,
                                               @RequestParam(required = false) String filterBl,
                                               @RequestParam(required = false) String filterMaison,
                                               @RequestParam(required = false) String filterStatut,
                                               Model model, Authentication auth) {
        return renderRemisesPage(model, auth, "Direction Exploitation", "/menu/direction-exploitation", page,
                filterDate, filterNom, filterPrenom, filterEmail, filterBl, filterMaison, filterStatut);
    }

    @GetMapping("/menu/facturation/ies")
    public String facturationIesWizard(@RequestParam(defaultValue = "lien-acces") String tab,
                                       Model model,
                                       Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority).collect(Collectors.toSet());
        boolean isAdmin = roles.contains("ROLE_ADMIN");
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("activeTab", normalizeIesTab(tab));
        model.addAttribute("isAdmin", isAdmin);
        model.addAttribute("sidebarMenuUrl", isAdmin ? "/menu" : "/menu/facturation");
        return "facturation/ies";
    }

    @GetMapping("/menu/facturation/gestion-remises")
    public String facturationRemises(@RequestParam(defaultValue = "0") int page,
                                     @RequestParam(required = false) String filterDate,
                                     @RequestParam(required = false) String filterNom,
                                     @RequestParam(required = false) String filterPrenom,
                                     @RequestParam(required = false) String filterEmail,
                                     @RequestParam(required = false) String filterBl,
                                     @RequestParam(required = false) String filterMaison,
                                     @RequestParam(required = false) String filterStatut,
                                     Model model, Authentication auth) {
        return renderRemisesPage(model, auth, "Facturation", "/menu/facturation", page,
                filterDate, filterNom, filterPrenom, filterEmail, filterBl, filterMaison, filterStatut);
    }

    @GetMapping("/menu/facturation/gestion-validations")
    public String facturationValidations(@RequestParam(defaultValue = "0") int page,
                                         @RequestParam(required = false) String filterDate,
                                         @RequestParam(required = false) String filterNom,
                                         @RequestParam(required = false) String filterPrenom,
                                         @RequestParam(required = false) String filterEmail,
                                         @RequestParam(required = false) String filterBl,
                                         @RequestParam(required = false) String filterMaison,
                                         @RequestParam(required = false) String filterStatut,
                                         Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority).collect(Collectors.toSet());
        boolean admin = roles.contains("ROLE_ADMIN");
        Page<RattachementBl> demandesPage = fetchBls("FACTURATION", filterDate,
                filterNom, filterPrenom, filterEmail, filterBl, filterMaison, filterStatut, 0);
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("demandes", demandesPage.getContent());
        model.addAttribute("currentPage", 0);
        model.addAttribute("totalPages", 0);
        model.addAttribute("totalItems", demandesPage.getTotalElements());
        model.addAttribute("filterDate",   filterDate   != null ? filterDate   : "");
        model.addAttribute("filterNom",    filterNom    != null ? filterNom    : "");
        model.addAttribute("filterPrenom", filterPrenom != null ? filterPrenom : "");
        model.addAttribute("filterEmail",  filterEmail  != null ? filterEmail  : "");
        model.addAttribute("filterBl",     filterBl     != null ? filterBl     : "");
        model.addAttribute("filterMaison", filterMaison != null ? filterMaison : "");
        model.addAttribute("filterStatut", filterStatut != null ? filterStatut : "");
        model.addAttribute("sectionLabel", "Facturation");
        model.addAttribute("parentMenuPath", "/menu/facturation");
        model.addAttribute("currentPagePath", "/menu/facturation/gestion-validations");
        model.addAttribute("isAdmin", admin);
        model.addAttribute("sidebarMenuUrl", admin ? "/menu" : "/menu/facturation");
        return "validations/index";
    }

    @PostMapping("/menu/facturation/gestion-validations/{id}/valider")
    public String validerValidation(@PathVariable long id, Authentication auth, RedirectAttributes ra) {
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
    public String rejeterValidation(@PathVariable long id,
                                    @RequestParam(value = "motif", required = false) String motif,
                                    @RequestParam(value = "motifAutre", required = false) String motifAutre,
                                    Authentication auth, RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();

        String motifFinal = (motif != null && !motif.isBlank()) ? motif : "";
        if (motifFinal.isBlank() && motifAutre != null && !motifAutre.isBlank()) {
            motifFinal = motifAutre.trim();
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
        return "redirect:/menu/facturation/gestion-validations";
    }

    @GetMapping("/menu/facturation/gestion-rapports")
    public String facturationReportsWizard(@RequestParam(defaultValue = "suivi-vides") String tab,
                                           Model model,
                                           Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        boolean isAdmin = auth.getAuthorities().stream()
                .anyMatch(a -> a.getAuthority().equals("ROLE_ADMIN"));
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("activeTab", normalizeReportsTab(tab));
        model.addAttribute("isAdmin", isAdmin);
        model.addAttribute("sidebarMenuUrl", isAdmin ? "/menu" : "/menu/facturation");
        return "facturation/reports";
    }

    private static final int MAX_TABLE_ROWS  = 100000;

    @GetMapping("/menu/facturation/unify")
    public String facturationUnifyWizard(@RequestParam(defaultValue = "formulaire-creation") String tab,
                                         @RequestParam(defaultValue = "1") int step,
                                         @RequestParam(required = false) String search,
                                         @RequestParam(defaultValue = "0") int page,
                                         Model model,
                                         Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        boolean isAdmin = auth.getAuthorities().stream()
                .anyMatch(a -> a.getAuthority().equals("ROLE_ADMIN"));

        Page<TiersUnify> tiersPage = tiersUnifyRepository.search(
                search,
                PageRequest.of(0, MAX_TABLE_ROWS, Sort.by(Sort.Direction.DESC, "createdAt"))
        );

        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("activeTab", normalizeUnifyTab(tab));
        model.addAttribute("activeStep", normalizeUnifyStep(step));
        model.addAttribute("isAdmin", isAdmin);
        model.addAttribute("tiers", tiersPage.getContent());
        model.addAttribute("search", search != null ? search : "");
        model.addAttribute("currentPage", 0);
        model.addAttribute("totalPages", 0);
        model.addAttribute("totalItems", tiersPage.getTotalElements());
        model.addAttribute("sidebarMenuUrl", isAdmin ? "/menu" : "/menu/facturation");
        return "facturation/unify";
    }

    @PostMapping("/menu/facturation/unify/sauvegarder")
    public String sauvegarderTiersUnify(@RequestParam(required = false) String compteIpaki,
                                        @RequestParam(required = false) String compteNeptune,
                                        @RequestParam(required = false) String raisonSociale,
                                        RedirectAttributes ra) {
        TiersUnify t = new TiersUnify();
        t.setCompteIpaki(compteIpaki);
        t.setCompteNeptune(compteNeptune);
        t.setRaisonSociale(raisonSociale);
        tiersUnifyRepository.save(t);
        ra.addFlashAttribute("successMsg", "Tiers enregistre avec succes.");
        return "redirect:/menu/facturation/unify?tab=liste-tiers";
    }

    @GetMapping("/menu/facturation/unify/imprimer-fiche")
    public String imprimerFiche(@RequestParam(required = false) String date,
                                @RequestParam(required = false) String typePersonne,
                                @RequestParam(required = false) String compteIpaki,
                                @RequestParam(required = false) String compteNeptune,
                                @RequestParam(required = false) String raisonSociale,
                                @RequestParam(required = false) String telephone,
                                @RequestParam(required = false) String email,
                                @RequestParam(required = false) String adresse,
                                @RequestParam(required = false) String dg,
                                @RequestParam(required = false) String telephoneDg,
                                @RequestParam(required = false) String df,
                                @RequestParam(required = false) String telephoneDf,
                                @RequestParam(required = false) String ninea,
                                @RequestParam(required = false) String registre,
                                Model model) {
        model.addAttribute("date", date);
        model.addAttribute("typePersonne", StringUtils.hasText(typePersonne) ? typePersonne : "MORALE");
        model.addAttribute("compteIpaki", compteIpaki);
        model.addAttribute("compteNeptune", compteNeptune);
        model.addAttribute("raisonSociale", raisonSociale);
        model.addAttribute("telephone", telephone);
        model.addAttribute("email", email);
        model.addAttribute("adresse", adresse);
        model.addAttribute("dg", dg);
        model.addAttribute("telephoneDg", telephoneDg);
        model.addAttribute("df", df);
        model.addAttribute("telephoneDf", telephoneDf);
        model.addAttribute("ninea", ninea);
        model.addAttribute("registre", registre);
        return "facturation/unify-fiche";
    }

    @GetMapping("/menu/facturation/unify/imprimer-attestation")
    public String imprimerAttestation(@RequestParam(required = false) String compteIpaki,
                                      @RequestParam(required = false) String raisonSociale,
                                      @RequestParam(required = false) String ninea,
                                      @RequestParam(required = false) String registre,
                                      Model model) {
        model.addAttribute("compteIpaki", compteIpaki);
        model.addAttribute("raisonSociale", raisonSociale);
        model.addAttribute("ninea", ninea);
        model.addAttribute("registre", registre);
        return "facturation/unify-attestation";
    }

    @PostMapping("/menu/facturation/unify/admin/ajouter")
    public String adminAjouterTiers(@RequestParam(required = false) String raisonSociale,
                                    @RequestParam(required = false) String compteIpaki,
                                    @RequestParam(required = false) String compteNeptune,
                                    RedirectAttributes ra) {
        TiersUnify t = new TiersUnify();
        t.setRaisonSociale(raisonSociale);
        t.setCompteIpaki(compteIpaki);
        t.setCompteNeptune(compteNeptune);
        tiersUnifyRepository.save(t);
        ra.addFlashAttribute("successMsg", "Tiers ajoute avec succes.");
        return "redirect:/menu/facturation/unify?tab=admin";
    }

    @PostMapping("/menu/facturation/unify/admin/modifier/{id}")
    public String adminModifierTiers(@PathVariable long id,
                                     @RequestParam(required = false) String raisonSociale,
                                     @RequestParam(required = false) String compteIpaki,
                                     @RequestParam(required = false) String compteNeptune,
                                     RedirectAttributes ra) {
        tiersUnifyRepository.findById(id).ifPresent(t -> {
            t.setRaisonSociale(raisonSociale);
            t.setCompteIpaki(compteIpaki);
            t.setCompteNeptune(compteNeptune);
            tiersUnifyRepository.save(t);
        });
        ra.addFlashAttribute("successMsg", "Tiers modifie avec succes.");
        return "redirect:/menu/facturation/unify?tab=admin";
    }

    @PostMapping("/menu/facturation/unify/admin/supprimer/{id}")
    public String adminSupprimerTiers(@PathVariable long id, RedirectAttributes ra) {
        tiersUnifyRepository.deleteById(id);
        ra.addFlashAttribute("successMsg", "Tiers supprime.");
        return "redirect:/menu/facturation/unify?tab=admin";
    }

    @PostMapping("/menu/facturation/unify/admin/purger-invalides")
    public String purgerTiersInvalides(RedirectAttributes ra) {
        int count = tiersUnifyRepository.deleteInvalidCompteIpaki();
        ra.addFlashAttribute("successMsg", count + " tiers avec compte Ipaki invalide supprime" + (count > 1 ? "s" : "") + ".");
        return "redirect:/menu/facturation/unify?tab=admin";
    }

    @GetMapping("/menu/facturation/unify/admin/export")
    public void exportTiersUnify(HttpServletResponse response) throws IOException {
        List<TiersUnify> tiers = tiersUnifyRepository.findAll(Sort.by(Sort.Direction.DESC, "createdAt"));
        response.setContentType("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        response.setHeader("Content-Disposition", "attachment; filename=\"tiers-unify.xlsx\"");
        try (XSSFWorkbook wb = new XSSFWorkbook()) {
            Sheet sheet = wb.createSheet("Tiers Unify");
            CellStyle headerStyle = wb.createCellStyle();
            Font headerFont = wb.createFont();
            headerFont.setBold(true);
            headerStyle.setFont(headerFont);
            headerStyle.setFillForegroundColor(IndexedColors.GREY_25_PERCENT.getIndex());
            headerStyle.setFillPattern(FillPatternType.SOLID_FOREGROUND);
            String[] headers = {"ID", "Raison sociale", "Compte Ipaki", "Compte Neptune", "Date creation"};
            Row headerRow = sheet.createRow(0);
            for (int i = 0; i < headers.length; i++) {
                Cell cell = headerRow.createCell(i);
                cell.setCellValue(headers[i]);
                cell.setCellStyle(headerStyle);
            }
            DateTimeFormatter dtf = DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm");
            int rowIdx = 1;
            for (TiersUnify t : tiers) {
                Row row = sheet.createRow(rowIdx++);
                row.createCell(0).setCellValue(t.getId() != null ? t.getId() : 0);
                row.createCell(1).setCellValue(val(t.getRaisonSociale()));
                row.createCell(2).setCellValue(val(t.getCompteIpaki()));
                row.createCell(3).setCellValue(val(t.getCompteNeptune()));
                row.createCell(4).setCellValue(t.getCreatedAt() != null ? t.getCreatedAt().format(dtf) : "");
            }
            for (int i = 0; i < headers.length; i++) sheet.autoSizeColumn(i);
            wb.write(response.getOutputStream());
        }
    }

    @PostMapping("/menu/facturation/unify/admin/import")
    public String importTiersUnify(@RequestParam("file") org.springframework.web.multipart.MultipartFile file,
                                   RedirectAttributes ra) throws IOException {
        if (file == null || file.isEmpty()) {
            ra.addFlashAttribute("successMsg", "Aucun fichier selectionne.");
            return "redirect:/menu/facturation/unify?tab=admin";
        }
        String originalName = java.util.Objects.toString(file.getOriginalFilename(), "").toLowerCase();
        java.util.List<TiersUnify> batch = new java.util.ArrayList<>();
        try {
            if (originalName.endsWith(".csv")) {
                // CSV parsing — supports comma or semicolon delimiter
                try (java.io.BufferedReader reader = new java.io.BufferedReader(
                        new java.io.InputStreamReader(file.getInputStream(), java.nio.charset.StandardCharsets.UTF_8))) {
                    String line;
                    boolean firstLine = true;
                    while ((line = reader.readLine()) != null) {
                        if (firstLine) { firstLine = false; continue; } // skip header
                        if (line.isBlank()) continue;
                        String delimiter = line.contains(";") ? ";" : ",";
                        String[] cols = line.split(delimiter, -1);
                        TiersUnify t = new TiersUnify();
                        t.setRaisonSociale(cols.length > 0 ? cols[0].trim() : null);
                        t.setCompteIpaki(cols.length > 1 ? cols[1].trim() : null);
                        t.setCompteNeptune(cols.length > 2 ? cols[2].trim() : null);
                        batch.add(t);
                    }
                }
            } else {
                // XLSX parsing via SAX (event-based, memory efficient)
                try (java.io.InputStream is = file.getInputStream();
                     org.apache.poi.openxml4j.opc.OPCPackage pkg = org.apache.poi.openxml4j.opc.OPCPackage.open(is)) {

                    org.apache.poi.xssf.eventusermodel.XSSFReader xssfReader =
                            new org.apache.poi.xssf.eventusermodel.XSSFReader(pkg);
                    org.apache.poi.xssf.model.SharedStrings sharedStrings = xssfReader.getSharedStringsTable();

                    javax.xml.parsers.SAXParserFactory factory = javax.xml.parsers.SAXParserFactory.newInstance();
                    factory.setNamespaceAware(true);
                    org.xml.sax.XMLReader xmlReader = factory.newSAXParser().getXMLReader();

                    org.apache.poi.xssf.eventusermodel.XSSFSheetXMLHandler.SheetContentsHandler rowHandler =
                            new org.apache.poi.xssf.eventusermodel.XSSFSheetXMLHandler.SheetContentsHandler() {
                                private final String[] rowData = new String[3];

                                @Override
                                public void startRow(int rowNum) {
                                    java.util.Arrays.fill(rowData, null);
                                }

                                @Override
                                public void endRow(int rowNum) {
                                    if (rowNum == 0) return; // skip header
                                    TiersUnify t = new TiersUnify();
                                    t.setRaisonSociale(rowData[0]);
                                    t.setCompteIpaki(rowData[1]);
                                    t.setCompteNeptune(rowData[2]);
                                    batch.add(t);
                                }

                                @Override
                                public void cell(String cellRef, String value, org.apache.poi.xssf.usermodel.XSSFComment c) {
                                    if (cellRef == null || value == null || value.isBlank()) return;
                                    int col = org.apache.poi.ss.util.CellReference.convertColStringToIndex(
                                            cellRef.replaceAll("[0-9]", ""));
                                    if (col >= 0 && col < 3) rowData[col] = value.trim();
                                }
                            };

                    org.apache.poi.xssf.eventusermodel.XSSFSheetXMLHandler sheetHandler =
                            new org.apache.poi.xssf.eventusermodel.XSSFSheetXMLHandler(
                                    xssfReader.getStylesTable(), null, sharedStrings, rowHandler,
                                    new org.apache.poi.ss.usermodel.DataFormatter(), false);
                    xmlReader.setContentHandler(sheetHandler);

                    org.apache.poi.xssf.eventusermodel.XSSFReader.SheetIterator sheets =
                            (org.apache.poi.xssf.eventusermodel.XSSFReader.SheetIterator) xssfReader.getSheetsData();
                    if (sheets.hasNext()) {
                        try (java.io.InputStream sheetStream = sheets.next()) {
                            xmlReader.parse(new org.xml.sax.InputSource(sheetStream));
                        }
                    }
                }
            }
        } catch (Exception e) {
            ra.addFlashAttribute("successMsg", "Erreur lors de l'import : " + e.getMessage());
            return "redirect:/menu/facturation/unify?tab=admin";
        }
        if (!batch.isEmpty()) {
            bulkInsertService.bulkInsertTiersUnify(batch);
        }
        ra.addFlashAttribute("successMsg",
                batch.size() + " tiers en cours d'import en arrière-plan. Actualisez dans quelques instants.");
        return "redirect:/menu/facturation/unify?tab=admin";
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
        emailService.sendIesAccessLink(email.trim());
        redirectAttributes.addFlashAttribute("success",
                "Lien d'acces envoye a " + email.trim() + ".");
        return "redirect:/menu/facturation/ies?tab=lien-acces";
    }

    @PostMapping("/menu/facturation/ies/creation-compte")
    public String createIesAccount(@RequestParam String email,
                                   @RequestParam String password,
                                   RedirectAttributes redirectAttributes) {
        emailService.sendIesCreationCompte(email.trim(), password);
        redirectAttributes.addFlashAttribute("success",
                "Email de creation de compte envoye a " + email.trim() + ".");
        return "redirect:/menu/facturation/ies?tab=creation-compte";
    }

    @PostMapping("/menu/facturation/ies/reinitialisation-compte")
    public String resetIesAccount(@RequestParam String email,
                                  @RequestParam String password,
                                  RedirectAttributes redirectAttributes) {
        emailService.sendIesReinitialisationCompte(email.trim(), password);
        redirectAttributes.addFlashAttribute("success",
                "Email de reinitialisation envoye a " + email.trim() + ".");
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

    @GetMapping("/menu/dt/repas")
    public String repasPage(Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        model.addAttribute("loggedUser", loggedUser);
        return "repas";
    }

    @PostMapping("/menu/dt/repas")
    public String sendRepas(@RequestParam String plat1,
                            @RequestParam String plat2,
                            RedirectAttributes redirectAttributes) {
        emailService.sendRepasMenu(plat1.trim(), plat2.trim());
        redirectAttributes.addFlashAttribute("success", "Menu du jour envoye avec succes.");
        return "redirect:/menu/dt/repas";
    }

    @GetMapping("/menu/dt/satisfaction")
    public String satisfactionDashboard(@RequestParam(defaultValue = "0") int page,
                                        Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        List<SatisfactionReponse> reponses = satisfactionReponseRepository
                .findAll(Sort.by(Sort.Direction.DESC, "createdAt"));
        int total = reponses.size();

        org.springframework.data.domain.Page<SatisfactionReponse> reponsesPage =
                satisfactionReponseRepository.findAll(
                        PageRequest.of(0, MAX_TABLE_ROWS, Sort.by(Sort.Direction.DESC, "createdAt")));

        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("currentDate", formatDate());
        model.addAttribute("reponses", reponsesPage.getContent());
        model.addAttribute("currentPage", 0);
        model.addAttribute("totalPages", 0);
        model.addAttribute("totalItems", reponsesPage.getTotalElements());
        model.addAttribute("total", total);

        // ── Général ────────────────────────────────────────────────────────
        model.addAttribute("distVolume",     dist(reponses, SatisfactionReponse::getVolume));
        model.addAttribute("distAnciennete", dist(reponses, SatisfactionReponse::getAnciennete));

        // ── Facturation ────────────────────────────────────────────────────
        model.addAttribute("distDelaisFacturation",         dist(reponses, SatisfactionReponse::getDelaisFacturation));
        model.addAttribute("distNotifications",             dist(reponses, SatisfactionReponse::getNotifications));
        model.addAttribute("distReactiviteFacture",         dist(reponses, SatisfactionReponse::getReactiviteFacture));
        model.addAttribute("distUsagePlateforme",           dist(reponses, SatisfactionReponse::getUsagePlateforme));
        model.addAttribute("distFacilitePlateforme",        dist(reponses, SatisfactionReponse::getFacilitePlateforme));
        model.addAttribute("distFonctionnalitesPlateforme", dist(reponses, SatisfactionReponse::getFonctionnalitesPlateforme));
        model.addAttribute("distBugsPlateforme",            dist(reponses, SatisfactionReponse::getBugsPlateforme));
        model.addAttribute("distAssistancePlateforme",      dist(reponses, SatisfactionReponse::getAssistancePlateforme));

        // ── BAE ────────────────────────────────────────────────────────────
        model.addAttribute("distDelaisBae",      dist(reponses, SatisfactionReponse::getDelaisBae));
        model.addAttribute("distSuggestionsBae", dist(reponses, SatisfactionReponse::getSuggestionsBae));

        // ── Accueil ────────────────────────────────────────────────────────
        model.addAttribute("distAccueilLocaux",     dist(reponses, SatisfactionReponse::getAccueilLocaux));
        model.addAttribute("distPersonnelAccueil",  dist(reponses, SatisfactionReponse::getPersonnelAccueil));
        model.addAttribute("distInfrastructures",   dist(reponses, SatisfactionReponse::getInfrastructures));

        // ── Livraison ──────────────────────────────────────────────────────
        model.addAttribute("distFluiditeLivraison",     dist(reponses, SatisfactionReponse::getFluiditeLivraison));
        model.addAttribute("distHorairesLivraison",     dist(reponses, SatisfactionReponse::getHorairesLivraison));
        model.addAttribute("distRetardsLivraison",      dist(reponses, SatisfactionReponse::getRetardsLivraison));
        model.addAttribute("distCoordinationLivraison", dist(reponses, SatisfactionReponse::getCoordinationLivraison));

        // ── Communication ──────────────────────────────────────────────────
        model.addAttribute("distCommunicationProactive", dist(reponses, SatisfactionReponse::getCommunicationProactive));
        model.addAttribute("distAlertes",                dist(reponses, SatisfactionReponse::getAlertes));

        return "satisfaction-dashboard";
    }

    @GetMapping("/menu/dt/satisfaction/export")
    public void satisfactionExport(HttpServletResponse response) throws IOException {
        List<SatisfactionReponse> reponses = satisfactionReponseRepository
                .findAll(Sort.by(Sort.Direction.DESC, "createdAt"));

        response.setContentType("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        response.setHeader("Content-Disposition", "attachment; filename=\"satisfaction.xlsx\"");

        try (XSSFWorkbook wb = new XSSFWorkbook()) {
            Sheet sheet = wb.createSheet("Réponses");

            // Header style
            CellStyle headerStyle = wb.createCellStyle();
            Font headerFont = wb.createFont();
            headerFont.setBold(true);
            headerStyle.setFont(headerFont);
            headerStyle.setFillForegroundColor(IndexedColors.GREY_25_PERCENT.getIndex());
            headerStyle.setFillPattern(FillPatternType.SOLID_FOREGROUND);

            String[] headers = {
                "ID","Date","Nom","Téléphone","Email","Volume","Ancienneté",
                "Délais facturation","Notifications","Réactivité facture",
                "Usage plateforme","Usage plateforme (détail)","Facilité plateforme",
                "Fonctionnalités plateforme","Fonctionnalités plateforme (détail)",
                "Bugs plateforme","Bugs plateforme (détail)","Assistance plateforme",
                "Suggestions facturation",
                "Délais BAE","Suggestions BAE","Suggestions BAE (détail)",
                "Accueil locaux","Personnel accueil","Infrastructures","Infrastructures (détail)",
                "Fluidité livraison","Horaires livraison","Horaires livraison (détail)",
                "Retards livraison","Retards livraison (détail)","Coordination livraison",
                "Améliorations livraison",
                "Communication proactive","Alertes","Suggestions communication",
                "Recommandations générales"
            };

            Row headerRow = sheet.createRow(0);
            for (int i = 0; i < headers.length; i++) {
                Cell cell = headerRow.createCell(i);
                cell.setCellValue(headers[i]);
                cell.setCellStyle(headerStyle);
            }

            int rowIdx = 1;
            DateTimeFormatter dtf = DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm");
            for (SatisfactionReponse r : reponses) {
                Row row = sheet.createRow(rowIdx++);
                int c = 0;
                row.createCell(c++).setCellValue(r.getId() != null ? r.getId() : 0);
                row.createCell(c++).setCellValue(r.getCreatedAt() != null ? r.getCreatedAt().format(dtf) : "");
                row.createCell(c++).setCellValue(val(r.getNom()));
                row.createCell(c++).setCellValue(val(r.getTelephone()));
                row.createCell(c++).setCellValue(val(r.getEmail()));
                row.createCell(c++).setCellValue(val(r.getVolume()));
                row.createCell(c++).setCellValue(val(r.getAnciennete()));
                row.createCell(c++).setCellValue(val(r.getDelaisFacturation()));
                row.createCell(c++).setCellValue(val(r.getNotifications()));
                row.createCell(c++).setCellValue(val(r.getReactiviteFacture()));
                row.createCell(c++).setCellValue(val(r.getUsagePlateforme()));
                row.createCell(c++).setCellValue(val(r.getUsagePlateformeDetail()));
                row.createCell(c++).setCellValue(val(r.getFacilitePlateforme()));
                row.createCell(c++).setCellValue(val(r.getFonctionnalitesPlateforme()));
                row.createCell(c++).setCellValue(val(r.getFonctionnalitesPlateformeDetail()));
                row.createCell(c++).setCellValue(val(r.getBugsPlateforme()));
                row.createCell(c++).setCellValue(val(r.getBugsPlateformeDetail()));
                row.createCell(c++).setCellValue(val(r.getAssistancePlateforme()));
                row.createCell(c++).setCellValue(val(r.getSuggestionsFacturation()));
                row.createCell(c++).setCellValue(val(r.getDelaisBae()));
                row.createCell(c++).setCellValue(val(r.getSuggestionsBae()));
                row.createCell(c++).setCellValue(val(r.getSuggestionsBaeDetail()));
                row.createCell(c++).setCellValue(val(r.getAccueilLocaux()));
                row.createCell(c++).setCellValue(val(r.getPersonnelAccueil()));
                row.createCell(c++).setCellValue(val(r.getInfrastructures()));
                row.createCell(c++).setCellValue(val(r.getInfrastructuresDetail()));
                row.createCell(c++).setCellValue(val(r.getFluiditeLivraison()));
                row.createCell(c++).setCellValue(val(r.getHorairesLivraison()));
                row.createCell(c++).setCellValue(val(r.getHorairesLivraisonDetail()));
                row.createCell(c++).setCellValue(val(r.getRetardsLivraison()));
                row.createCell(c++).setCellValue(val(r.getRetardsLivraisonDetail()));
                row.createCell(c++).setCellValue(val(r.getCoordinationLivraison()));
                row.createCell(c++).setCellValue(val(r.getAmeliorationsLivraison()));
                row.createCell(c++).setCellValue(val(r.getCommunicationProactive()));
                row.createCell(c++).setCellValue(val(r.getAlertes()));
                row.createCell(c++).setCellValue(val(r.getSuggestionsCommunication()));
                row.createCell(c).setCellValue(val(r.getRecommandationsGenerales()));
            }

            for (int i = 0; i < headers.length; i++) {
                sheet.autoSizeColumn(i);
            }

            wb.write(response.getOutputStream());
        }
    }

    private Map<String, Long> dist(List<SatisfactionReponse> list,
                                   Function<SatisfactionReponse, String> getter) {
        return list.stream()
                .map(getter)
                .filter(Objects::nonNull)
                .filter(v -> !v.isBlank())
                .collect(Collectors.groupingBy(v -> v, LinkedHashMap::new, Collectors.counting()));
    }

    private String val(String s) {
        return s != null ? s : "";
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
                .format(DateTimeFormatter.ofPattern("EEEE d MMMM yyyy", Locale.of("fr", "FR")));
        return raw.substring(0, 1).toUpperCase() + raw.substring(1);
    }

    private String renderMenuView(String view, Model model, Authentication auth) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority)
                .collect(Collectors.toSet());
        boolean hideMainMenuReturn = roles.contains("ROLE_DIRECTION_GENERALE")
                || roles.contains("ROLE_DIRECTION_FINANCIERE")
                || roles.contains("ROLE_DIRECTION_EXPLOITATION");
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("menuView", view);
        model.addAttribute("hideMainMenuReturn", hideMainMenuReturn);
        return "menu";
    }

    @PostMapping("/menu/gestion-remises/{id}/valider")
    public String validerRemise(@PathVariable long id,
                                @RequestParam(required = false) BigDecimal pourcentage,
                                @RequestParam(required = false) @DateTimeFormat(iso = DateTimeFormat.ISO.DATE) LocalDate dateValiditeRemise,
                                @RequestParam String returnTo,
                                Authentication auth, RedirectAttributes ra) {
        RattachementBl bl = rattachementBlRepository.findById(id).orElseThrow();
        User operator = userRepository.findByEmail(auth.getName()).orElseThrow();
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority).collect(Collectors.toSet());
        if (isDirection(roles)) {
            bl.setStatut("VALIDE");
            bl.setPourcentage(pourcentage);
            bl.setDateValiditeRemise(dateValiditeRemise);
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
    public String rejeterRemise(@PathVariable long id,
                                @RequestParam(value = "motif", required = false) String motif,
                                @RequestParam(value = "motifAutre", required = false) String motifAutre,
                                @RequestParam String returnTo,
                                Authentication auth, RedirectAttributes ra) {
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
        String target = returnTo != null && returnTo.startsWith("/menu/") ? returnTo : "/menu";
        return "redirect:" + target;
    }

    private Page<RattachementBl> fetchBls(String type, String filterDate,
                                          String nom, String prenom, String email,
                                          String bl, String maison, String statut,
                                          int safePage) {
        java.time.LocalDateTime dateStart = null;
        java.time.LocalDateTime dateEnd   = null;
        if (filterDate != null && !filterDate.isBlank()) {
            try {
                LocalDate date = LocalDate.parse(filterDate);
                dateStart = date.atStartOfDay();
                dateEnd   = date.plusDays(1).atStartOfDay();
            } catch (Exception ignored) {
            }
        }
        String sNom    = nom    != null ? nom.trim()    : "";
        String sPrenom = prenom != null ? prenom.trim() : "";
        String sEmail  = email  != null ? email.trim()  : "";
        String sBl     = bl     != null ? bl.trim()     : "";
        String sMaison = maison != null ? maison.trim() : "";
        String sStatut = statut != null ? statut.trim() : "";

        return rattachementBlRepository.findByTypeWithFilters(
                type, dateStart, dateEnd, sNom, sPrenom, sEmail, sBl, sMaison, sStatut,
                PageRequest.of(0, MAX_TABLE_ROWS));
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
                                     String parentMenuPath,
                                     int page,
                                     String filterDate,
                                     String filterNom,
                                     String filterPrenom,
                                     String filterEmail,
                                     String filterBl,
                                     String filterMaison,
                                     String filterStatut) {
        User loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        Set<String> roles = auth.getAuthorities().stream()
                .map(GrantedAuthority::getAuthority).collect(Collectors.toSet());
        boolean admin = roles.contains("ROLE_ADMIN");
        String currentPagePath = parentMenuPath + "/gestion-remises";
        Page<RattachementBl> demandesPage = fetchBls("REMISE", filterDate,
                filterNom, filterPrenom, filterEmail, filterBl, filterMaison, filterStatut, 0);
        model.addAttribute("loggedUser", loggedUser);
        model.addAttribute("demandes", demandesPage.getContent());
        model.addAttribute("currentPage", 0);
        model.addAttribute("totalPages", 0);
        model.addAttribute("totalItems", demandesPage.getTotalElements());
        model.addAttribute("filterDate",   filterDate   != null ? filterDate   : "");
        model.addAttribute("filterNom",    filterNom    != null ? filterNom    : "");
        model.addAttribute("filterPrenom", filterPrenom != null ? filterPrenom : "");
        model.addAttribute("filterEmail",  filterEmail  != null ? filterEmail  : "");
        model.addAttribute("filterBl",     filterBl     != null ? filterBl     : "");
        model.addAttribute("filterMaison", filterMaison != null ? filterMaison : "");
        model.addAttribute("filterStatut", filterStatut != null ? filterStatut : "");
        model.addAttribute("sectionLabel", sectionLabel);
        model.addAttribute("parentMenuPath", parentMenuPath);
        model.addAttribute("currentPagePath", currentPagePath);
        model.addAttribute("actionBasePath", "/menu/gestion-remises");
        model.addAttribute("isDirection", isDirection(roles));
        model.addAttribute("isAdmin", admin);
        model.addAttribute("sidebarMenuUrl", admin ? "/menu" : parentMenuPath);
        return "remises/index";
    }
}
