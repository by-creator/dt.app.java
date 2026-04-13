package com.dtapp.controller;

import com.dtapp.entity.Machine;
import com.dtapp.entity.PosteFixe;
import com.dtapp.entity.User;
import com.dtapp.repository.MachineRepository;
import com.dtapp.repository.PosteFixeRepository;
import com.dtapp.repository.UserRepository;
import com.dtapp.service.BulkInsertService;
import jakarta.servlet.http.HttpServletResponse;
import org.apache.poi.ss.usermodel.*;
import org.apache.poi.xssf.usermodel.XSSFWorkbook;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.io.BufferedReader;
import java.io.IOException;
import java.io.InputStream;
import java.io.InputStreamReader;
import java.nio.charset.StandardCharsets;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.List;

@Controller
@RequestMapping("/menu/informatique/parc")
public class InformatiqueController {

    private static final int MAX_TABLE_ROWS = 100000;

    private final UserRepository userRepository;
    private final MachineRepository machineRepository;
    private final PosteFixeRepository posteFixeRepository;
    private final BulkInsertService bulkInsertService;

    public InformatiqueController(UserRepository userRepository,
                                  MachineRepository machineRepository,
                                  PosteFixeRepository posteFixeRepository,
                                  BulkInsertService bulkInsertService) {
        this.userRepository      = userRepository;
        this.machineRepository   = machineRepository;
        this.posteFixeRepository = posteFixeRepository;
        this.bulkInsertService   = bulkInsertService;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PARC – sous-menu
    // ─────────────────────────────────────────────────────────────────────────

    @GetMapping
    public String parc(Model model, Authentication auth) {
        model.addAttribute("loggedUser", loggedUser(auth));
        return "informatique/parc";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MACHINES – liste / CRUD
    // ─────────────────────────────────────────────────────────────────────────

    @GetMapping("/machines")
    public String machines(@RequestParam(required = false) String search,
                           @RequestParam(required = false) String filterNom,
                           @RequestParam(required = false) String filterAjow,
                           @RequestParam(required = false) String filterType,
                           @RequestParam(required = false) String filterUser,
                           @RequestParam(required = false) String filterModel,
                           @RequestParam(required = false) String filterService,
                           @RequestParam(required = false) String filterSite,
                           @RequestParam(defaultValue = "0") int page,
                           Model model, Authentication auth) {
        boolean hasColumnFilter = hasText(filterNom) || hasText(filterAjow) || hasText(filterType)
                || hasText(filterUser) || hasText(filterModel) || hasText(filterService) || hasText(filterSite);

        Page<Machine> machinesPage;
        if (hasColumnFilter) {
            machinesPage = machineRepository.filterByColumns(
                    emptyNull(filterNom), emptyNull(filterAjow), emptyNull(filterType),
                    emptyNull(filterUser), emptyNull(filterModel), emptyNull(filterService),
                    emptyNull(filterSite), PageRequest.of(0, MAX_TABLE_ROWS));
        } else {
            machinesPage = machineRepository.searchPaged(search, PageRequest.of(0, MAX_TABLE_ROWS));
        }

        model.addAttribute("loggedUser",    loggedUser(auth));
        model.addAttribute("machines",      machinesPage.getContent());
        model.addAttribute("search",        search != null ? search : "");
        model.addAttribute("filterNom",     filterNom     != null ? filterNom     : "");
        model.addAttribute("filterAjow",    filterAjow    != null ? filterAjow    : "");
        model.addAttribute("filterType",    filterType    != null ? filterType    : "");
        model.addAttribute("filterUser",    filterUser    != null ? filterUser    : "");
        model.addAttribute("filterModel",   filterModel   != null ? filterModel   : "");
        model.addAttribute("filterService", filterService != null ? filterService : "");
        model.addAttribute("filterSite",    filterSite    != null ? filterSite    : "");
        model.addAttribute("currentPage",   0);
        model.addAttribute("totalPages",    0);
        model.addAttribute("totalItems",    machinesPage.getTotalElements());
        model.addAttribute("pageSize",      machinesPage.getContent().size());
        return "informatique/machines";
    }

    @PostMapping("/machines/create")
    public String createMachine(@RequestParam String name,
                                @RequestParam(required = false) String type,
                                @RequestParam(required = false) String ajowName,
                                @RequestParam(required = false) String username,
                                @RequestParam(required = false) String serviceTag,
                                @RequestParam(required = false) String versionOs,
                                @RequestParam(required = false) String model,
                                @RequestParam(required = false) String societe,
                                @RequestParam(required = false) String service,
                                @RequestParam(required = false) String emplacement,
                                @RequestParam(required = false) String sites,
                                @RequestParam(required = false) String dateAcquisition,
                                @RequestParam(required = false) String dateDeploiement,
                                @RequestParam(required = false) String commentaire,
                                RedirectAttributes ra) {
        if (name == null || name.isBlank()) {
            ra.addFlashAttribute("error", "Le nom de la machine est obligatoire.");
            return "redirect:/menu/informatique/parc/machines";
        }
        Machine m = new Machine();
        m.setName(name.trim());
        m.setType(blank(type));
        m.setAjowName(blank(ajowName));
        m.setUsername(blank(username));
        m.setServiceTag(blank(serviceTag));
        m.setVersionOs(blank(versionOs));
        m.setModel(blank(model));
        m.setSociete(blank(societe));
        m.setService(blank(service));
        m.setEmplacement(blank(emplacement));
        m.setSites(blank(sites));
        m.setDateAcquisition(blank(dateAcquisition));
        m.setDateDeploiement(blank(dateDeploiement));
        m.setCommentaire(blank(commentaire));
        machineRepository.save(m);
        ra.addFlashAttribute("success", "Machine « " + m.getName() + " » ajoutée.");
        return "redirect:/menu/informatique/parc/machines";
    }

    @PostMapping("/machines/{id}/edit")
    public String editMachine(@PathVariable long id,
                              @RequestParam String name,
                              @RequestParam(required = false) String type,
                              @RequestParam(required = false) String ajowName,
                              @RequestParam(required = false) String username,
                              @RequestParam(required = false) String serviceTag,
                              @RequestParam(required = false) String versionOs,
                              @RequestParam(required = false) String model,
                              @RequestParam(required = false) String societe,
                              @RequestParam(required = false) String service,
                              @RequestParam(required = false) String emplacement,
                              @RequestParam(required = false) String sites,
                              @RequestParam(required = false) String dateAcquisition,
                              @RequestParam(required = false) String dateDeploiement,
                              @RequestParam(required = false) String commentaire,
                              RedirectAttributes ra) {
        machineRepository.findById(id).ifPresent(m -> {
            m.setName(name.trim());
            m.setType(blank(type));
            m.setAjowName(blank(ajowName));
            m.setUsername(blank(username));
            m.setServiceTag(blank(serviceTag));
            m.setVersionOs(blank(versionOs));
            m.setModel(blank(model));
            m.setSociete(blank(societe));
            m.setService(blank(service));
            m.setEmplacement(blank(emplacement));
            m.setSites(blank(sites));
            m.setDateAcquisition(blank(dateAcquisition));
            m.setDateDeploiement(blank(dateDeploiement));
            m.setCommentaire(blank(commentaire));
            machineRepository.save(m);
            ra.addFlashAttribute("success", "Machine « " + m.getName() + " » mise à jour.");
        });
        return "redirect:/menu/informatique/parc/machines";
    }

    @PostMapping("/machines/{id}/delete")
    public String deleteMachine(@PathVariable long id,
                                @RequestParam(defaultValue = "0") int page,
                                @RequestParam(required = false) String search,
                                RedirectAttributes ra) {
        Machine m = machineRepository.findById(id).orElse(null);
        if (m != null) {
            machineRepository.delete(m);
            ra.addFlashAttribute("success", "Machine « " + m.getName() + " » supprimée.");
        }
        String redirect = "redirect:/menu/informatique/parc/machines";
        if (search != null && !search.isBlank()) redirect += "&search=" + search;
        return redirect;
    }

    // ─── Machines export ──────────────────────────────────────────────────────

    @GetMapping("/machines/export")
    public void exportMachines(HttpServletResponse response) throws IOException {
        List<Machine> all = machineRepository.findAll();
        response.setContentType("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        response.setHeader("Content-Disposition", "attachment; filename=\"machines.xlsx\"");

        try (XSSFWorkbook wb = new XSSFWorkbook()) {
            Sheet sheet = wb.createSheet("Machines");
            CellStyle headerStyle = buildHeaderStyle(wb);

            String[] headers = {
                "ID", "Nom", "Type", "Ajow Name", "Utilisateur", "Service Tag",
                "Version OS", "Modèle", "Société", "Service", "Emplacement",
                "Sites", "Date acquisition", "Date déploiement", "Commentaire", "Créé le"
            };
            Row headerRow = sheet.createRow(0);
            for (int i = 0; i < headers.length; i++) {
                Cell cell = headerRow.createCell(i);
                cell.setCellValue(headers[i]);
                cell.setCellStyle(headerStyle);
            }

            DateTimeFormatter dtf = DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm");
            int rowIdx = 1;
            for (Machine m : all) {
                Row row = sheet.createRow(rowIdx++);
                int c = 0;
                row.createCell(c++).setCellValue(m.getId() != null ? m.getId() : 0);
                row.createCell(c++).setCellValue(val(m.getName()));
                row.createCell(c++).setCellValue(val(m.getType()));
                row.createCell(c++).setCellValue(val(m.getAjowName()));
                row.createCell(c++).setCellValue(val(m.getUsername()));
                row.createCell(c++).setCellValue(val(m.getServiceTag()));
                row.createCell(c++).setCellValue(val(m.getVersionOs()));
                row.createCell(c++).setCellValue(val(m.getModel()));
                row.createCell(c++).setCellValue(val(m.getSociete()));
                row.createCell(c++).setCellValue(val(m.getService()));
                row.createCell(c++).setCellValue(val(m.getEmplacement()));
                row.createCell(c++).setCellValue(val(m.getSites()));
                row.createCell(c++).setCellValue(val(m.getDateAcquisition()));
                row.createCell(c++).setCellValue(val(m.getDateDeploiement()));
                row.createCell(c++).setCellValue(val(m.getCommentaire()));
                row.createCell(c++).setCellValue(m.getCreatedAt() != null ? m.getCreatedAt().format(dtf) : "");
            }
            for (int i = 0; i < headers.length; i++) sheet.autoSizeColumn(i);
            wb.write(response.getOutputStream());
        }
    }

    // ─── Machines import ──────────────────────────────────────────────────────

    @PostMapping("/machines/import")
    public String importMachines(@RequestParam MultipartFile file,
                                 RedirectAttributes ra) {
        if (file.isEmpty()) {
            ra.addFlashAttribute("error", "Veuillez sélectionner un fichier.");
            return "redirect:/menu/informatique/parc/machines";
        }
        String originalFilename = file.getOriginalFilename();
        String filename = originalFilename != null ? originalFilename.toLowerCase() : "";
        try {
            List<Machine> toSave = new ArrayList<>();
            if (filename.endsWith(".csv")) {
                try (BufferedReader reader = new BufferedReader(
                        new InputStreamReader(file.getInputStream(), StandardCharsets.UTF_8))) {
                    String firstLine = reader.readLine(); // detect delimiter from header
                    if (firstLine == null) throw new IllegalArgumentException("Fichier CSV vide.");
                    String sep = firstLine.contains(";") ? ";" : ",";
                    String line;
                    while ((line = reader.readLine()) != null) {
                        String[] cols = line.split(sep, -1);
                        String name = cols.length > 1 ? cols[1].trim() : "";
                        if (name.isBlank()) continue;
                        Machine m = new Machine();
                        m.setType(cols.length > 0 ? cols[0].trim() : null);
                        m.setName(name);
                        m.setAjowName(cols.length > 2  ? cols[2].trim()  : null);
                        m.setUsername(cols.length > 3  ? cols[3].trim()  : null);
                        m.setServiceTag(cols.length > 4  ? cols[4].trim()  : null);
                        m.setVersionOs(cols.length > 5  ? cols[5].trim()  : null);
                        m.setModel(cols.length > 6  ? cols[6].trim()  : null);
                        m.setSociete(cols.length > 7  ? cols[7].trim()  : null);
                        m.setService(cols.length > 8  ? cols[8].trim()  : null);
                        m.setEmplacement(cols.length > 9  ? cols[9].trim()  : null);
                        m.setSites(cols.length > 10 ? cols[10].trim() : null);
                        m.setDateAcquisition(cols.length > 11 ? cols[11].trim() : null);
                        m.setDateDeploiement(cols.length > 12 ? cols[12].trim() : null);
                        m.setCommentaire(cols.length > 13 ? cols[13].trim() : null);
                        toSave.add(m);
                    }
                }
            } else {
                try (InputStream is = file.getInputStream();
                     Workbook wb = new XSSFWorkbook(is)) {
                    Sheet sheet = wb.getSheetAt(0);
                    boolean firstRow = true;
                    for (Row row : sheet) {
                        if (firstRow) { firstRow = false; continue; }
                        String name = cellStr(row, 1);
                        if (name.isBlank()) continue;
                        Machine m = new Machine();
                        m.setName(name);
                        m.setType(cellStr(row, 0));
                        m.setAjowName(cellStr(row, 2));
                        m.setUsername(cellStr(row, 3));
                        m.setServiceTag(cellStr(row, 4));
                        m.setVersionOs(cellStr(row, 5));
                        m.setModel(cellStr(row, 6));
                        m.setSociete(cellStr(row, 7));
                        m.setService(cellStr(row, 8));
                        m.setEmplacement(cellStr(row, 9));
                        m.setSites(cellStr(row, 10));
                        m.setDateAcquisition(cellStr(row, 11));
                        m.setDateDeploiement(cellStr(row, 12));
                        m.setCommentaire(cellStr(row, 13));
                        toSave.add(m);
                    }
                }
            }
            bulkInsertService.bulkInsertMachines(toSave);
            ra.addFlashAttribute("success", toSave.size() + " machine(s) importée(s) avec succès.");
        } catch (Exception e) {
            ra.addFlashAttribute("error", "Erreur lors de l'import : " + e.getMessage());
        }
        return "redirect:/menu/informatique/parc/machines";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POSTES FIXES – liste / CRUD
    // ─────────────────────────────────────────────────────────────────────────

    @GetMapping("/postes-fixes")
    public String postesFixes(@RequestParam(required = false) String search,
                              @RequestParam(required = false) String filterAnnuaire,
                              @RequestParam(required = false) String filterNom,
                              @RequestParam(required = false) String filterPrenom,
                              @RequestParam(required = false) String filterType,
                              @RequestParam(defaultValue = "0") int page,
                              Model model, Authentication auth) {
        boolean hasColumnFilter = hasText(filterAnnuaire) || hasText(filterNom)
                || hasText(filterPrenom) || hasText(filterType);

        Page<PosteFixe> postesPage;
        if (hasColumnFilter) {
            postesPage = posteFixeRepository.filterByColumns(
                    emptyNull(filterAnnuaire), emptyNull(filterNom),
                    emptyNull(filterPrenom), emptyNull(filterType),
                    PageRequest.of(0, MAX_TABLE_ROWS));
        } else {
            postesPage = posteFixeRepository.searchPaged(search, PageRequest.of(0, MAX_TABLE_ROWS));
        }

        model.addAttribute("loggedUser",     loggedUser(auth));
        model.addAttribute("postes",         postesPage.getContent());
        model.addAttribute("search",         search != null ? search : "");
        model.addAttribute("filterAnnuaire", filterAnnuaire != null ? filterAnnuaire : "");
        model.addAttribute("filterNom",      filterNom      != null ? filterNom      : "");
        model.addAttribute("filterPrenom",   filterPrenom   != null ? filterPrenom   : "");
        model.addAttribute("filterType",     filterType     != null ? filterType     : "");
        model.addAttribute("currentPage",    0);
        model.addAttribute("totalPages",     0);
        model.addAttribute("totalItems",     postesPage.getTotalElements());
        model.addAttribute("pageSize",       postesPage.getContent().size());
        return "informatique/postes-fixes";
    }

    @PostMapping("/postes-fixes/create")
    public String createPosteFixe(@RequestParam(required = false) String annuaire,
                                  @RequestParam(required = false) String nom,
                                  @RequestParam(required = false) String prenom,
                                  @RequestParam(required = false) String type,
                                  @RequestParam(required = false) String commentaire,
                                  RedirectAttributes ra) {
        if ((nom == null || nom.isBlank()) && (annuaire == null || annuaire.isBlank())) {
            ra.addFlashAttribute("error", "Le nom ou l'annuaire est obligatoire.");
            return "redirect:/menu/informatique/parc/postes-fixes";
        }
        PosteFixe p = new PosteFixe();
        p.setAnnuaire(blank(annuaire));
        p.setNom(blank(nom));
        p.setPrenom(blank(prenom));
        p.setType(blank(type));
        p.setCommentaire(blank(commentaire));
        posteFixeRepository.save(p);
        ra.addFlashAttribute("success", "Poste fixe ajouté.");
        return "redirect:/menu/informatique/parc/postes-fixes";
    }

    @PostMapping("/postes-fixes/{id}/edit")
    public String editPosteFixe(@PathVariable long id,
                                @RequestParam(required = false) String annuaire,
                                @RequestParam(required = false) String nom,
                                @RequestParam(required = false) String prenom,
                                @RequestParam(required = false) String type,
                                @RequestParam(required = false) String commentaire,
                                RedirectAttributes ra) {
        posteFixeRepository.findById(id).ifPresent(p -> {
            p.setAnnuaire(blank(annuaire));
            p.setNom(blank(nom));
            p.setPrenom(blank(prenom));
            p.setType(blank(type));
            p.setCommentaire(blank(commentaire));
            posteFixeRepository.save(p);
            ra.addFlashAttribute("success", "Poste fixe mis à jour.");
        });
        return "redirect:/menu/informatique/parc/postes-fixes";
    }

    @PostMapping("/postes-fixes/{id}/delete")
    public String deletePosteFixe(@PathVariable long id,
                                  @RequestParam(defaultValue = "0") int page,
                                  @RequestParam(required = false) String search,
                                  RedirectAttributes ra) {
        PosteFixe p = posteFixeRepository.findById(id).orElse(null);
        if (p != null) {
            posteFixeRepository.delete(p);
            ra.addFlashAttribute("success", "Poste fixe supprimé.");
        }
        String redirect = "redirect:/menu/informatique/parc/postes-fixes";
        if (search != null && !search.isBlank()) redirect += "&search=" + search;
        return redirect;
    }

    // ─── Postes fixes export ──────────────────────────────────────────────────

    @GetMapping("/postes-fixes/export")
    public void exportPostesFixes(HttpServletResponse response) throws IOException {
        List<PosteFixe> all = posteFixeRepository.findAll();
        response.setContentType("application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        response.setHeader("Content-Disposition", "attachment; filename=\"postes_fixes.xlsx\"");

        try (XSSFWorkbook wb = new XSSFWorkbook()) {
            Sheet sheet = wb.createSheet("Postes Fixes");
            CellStyle headerStyle = buildHeaderStyle(wb);

            String[] headers = {"ID", "Annuaire", "Nom", "Prénom", "Type", "Commentaire", "Créé le"};
            Row headerRow = sheet.createRow(0);
            for (int i = 0; i < headers.length; i++) {
                Cell cell = headerRow.createCell(i);
                cell.setCellValue(headers[i]);
                cell.setCellStyle(headerStyle);
            }

            DateTimeFormatter dtf = DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm");
            int rowIdx = 1;
            for (PosteFixe p : all) {
                Row row = sheet.createRow(rowIdx++);
                int c = 0;
                row.createCell(c++).setCellValue(p.getId() != null ? p.getId() : 0);
                row.createCell(c++).setCellValue(val(p.getAnnuaire()));
                row.createCell(c++).setCellValue(val(p.getNom()));
                row.createCell(c++).setCellValue(val(p.getPrenom()));
                row.createCell(c++).setCellValue(val(p.getType()));
                row.createCell(c++).setCellValue(val(p.getCommentaire()));
                row.createCell(c++).setCellValue(p.getCreatedAt() != null ? p.getCreatedAt().format(dtf) : "");
            }
            for (int i = 0; i < headers.length; i++) sheet.autoSizeColumn(i);
            wb.write(response.getOutputStream());
        }
    }

    // ─── Postes fixes import ──────────────────────────────────────────────────

    @PostMapping("/postes-fixes/import")
    public String importPostesFixes(@RequestParam MultipartFile file,
                                    RedirectAttributes ra) {
        if (file.isEmpty()) {
            ra.addFlashAttribute("error", "Veuillez sélectionner un fichier.");
            return "redirect:/menu/informatique/parc/postes-fixes";
        }
        String originalFilename = file.getOriginalFilename();
        String filename = originalFilename != null ? originalFilename.toLowerCase() : "";
        try {
            List<PosteFixe> toSave = new ArrayList<>();
            if (filename.endsWith(".csv")) {
                try (BufferedReader reader = new BufferedReader(
                        new InputStreamReader(file.getInputStream(), StandardCharsets.UTF_8))) {
                    String firstLine = reader.readLine();
                    if (firstLine == null) throw new IllegalArgumentException("Fichier CSV vide.");
                    String sep = firstLine.contains(";") ? ";" : ",";
                    // Detect format: if first column header is "id" → export format (skip col 0)
                    // Otherwise source format (annuaire at col 0, no ID column)
                    String[] headers = firstLine.split(sep, -1);
                    int off = headers[0].trim().equalsIgnoreCase("id") ? 1 : 0;
                    String line;
                    while ((line = reader.readLine()) != null) {
                        String[] cols = line.split(sep, -1);
                        String annuaire = cols.length > off     ? cols[off].trim()     : "";
                        String nom      = cols.length > off + 1 ? cols[off + 1].trim() : "";
                        if (annuaire.isBlank() && nom.isBlank()) continue;
                        PosteFixe p = new PosteFixe();
                        p.setAnnuaire(annuaire.isBlank()             ? null : annuaire);
                        p.setNom(nom.isBlank()                       ? null : nom);
                        p.setPrenom(cols.length > off + 2            ? cols[off + 2].trim() : null);
                        p.setType(cols.length > off + 3              ? cols[off + 3].trim() : null);
                        p.setCommentaire(cols.length > off + 4       ? cols[off + 4].trim() : null);
                        toSave.add(p);
                    }
                }
            } else {
                try (InputStream is = file.getInputStream();
                     Workbook wb = new XSSFWorkbook(is)) {
                    Sheet sheet = wb.getSheetAt(0);
                    // Detect format from header row
                    int off = 0;
                    Row headerRow = sheet.getRow(0);
                    if (headerRow != null && cellStr(headerRow, 0).equalsIgnoreCase("id")) off = 1;
                    boolean firstRow = true;
                    for (Row row : sheet) {
                        if (firstRow) { firstRow = false; continue; }
                        String annuaire = cellStr(row, off);
                        String nom      = cellStr(row, off + 1);
                        if (annuaire.isBlank() && nom.isBlank()) continue;
                        PosteFixe p = new PosteFixe();
                        p.setAnnuaire(annuaire.isBlank() ? null : annuaire);
                        p.setNom(nom.isBlank()           ? null : nom);
                        p.setPrenom(cellStr(row, off + 2));
                        p.setType(cellStr(row, off + 3));
                        p.setCommentaire(cellStr(row, off + 4));
                        toSave.add(p);
                    }
                }
            }
            bulkInsertService.bulkInsertPostesFixes(toSave);
            ra.addFlashAttribute("success", toSave.size() + " poste(s) fixe(s) importé(s) avec succès.");
        } catch (Exception e) {
            ra.addFlashAttribute("error", "Erreur lors de l'import : " + e.getMessage());
        }
        return "redirect:/menu/informatique/parc/postes-fixes";
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private User loggedUser(Authentication auth) {
        return userRepository.findByEmail(auth.getName()).orElseThrow();
    }

    private String val(String s) {
        return s != null ? s : "";
    }

    private String blank(String s) {
        if (s == null || s.isBlank()) return null;
        return s.trim();
    }

    private boolean hasText(String s) {
        return s != null && !s.isBlank();
    }

    private String emptyNull(String s) {
        return (s == null || s.isBlank()) ? "" : s.trim();
    }

    private String cellStr(Row row, int col) {
        Cell cell = row.getCell(col);
        if (cell == null) return "";
        return switch (cell.getCellType()) {
            case STRING  -> cell.getStringCellValue().trim();
            case NUMERIC -> String.valueOf((long) cell.getNumericCellValue());
            case BOOLEAN -> String.valueOf(cell.getBooleanCellValue());
            default      -> "";
        };
    }

    private CellStyle buildHeaderStyle(XSSFWorkbook wb) {
        CellStyle style = wb.createCellStyle();
        Font font = wb.createFont();
        font.setBold(true);
        style.setFont(font);
        style.setFillForegroundColor(IndexedColors.GREY_25_PERCENT.getIndex());
        style.setFillPattern(FillPatternType.SOLID_FOREGROUND);
        return style;
    }
}
