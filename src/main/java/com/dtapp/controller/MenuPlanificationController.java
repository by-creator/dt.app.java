package com.dtapp.controller;

import com.dtapp.entity.Codification;
import com.dtapp.repository.CodificationRepository;
import com.dtapp.repository.UserRepository;
import com.dtapp.service.BulkInsertService;
import com.dtapp.service.EdiExporter;
import com.dtapp.service.EdiParser;
import com.dtapp.service.EdiRecord;
import com.dtapp.service.XlsExporter;
import com.dtapp.util.PaginationUtils;
import lombok.extern.slf4j.Slf4j;
import org.apache.poi.ss.usermodel.Cell;
import org.apache.poi.ss.usermodel.DataFormatter;
import org.apache.poi.ss.usermodel.Workbook;
import org.apache.poi.ss.usermodel.WorkbookFactory;
import org.springframework.core.io.ByteArrayResource;
import org.springframework.core.io.Resource;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.Pageable;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.multipart.MultipartFile;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.io.ByteArrayInputStream;
import java.io.File;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.ArrayList;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Optional;

@Controller
@RequestMapping("/menu/planification")
@Slf4j
public class MenuPlanificationController {

    private final EdiParser parser;
    private final EdiExporter exporter;
    private final XlsExporter xlsExporter;
    private final CodificationRepository codificationRepository;
    private final UserRepository userRepository;
    private final BulkInsertService bulkInsertService;
    public MenuPlanificationController(EdiParser parser,
                                       EdiExporter exporter,
                                       XlsExporter xlsExporter,
                                       CodificationRepository codificationRepository,
                                       UserRepository userRepository,
                                       BulkInsertService bulkInsertService) {
        this.parser = parser;
        this.exporter = exporter;
        this.xlsExporter = xlsExporter;
        this.codificationRepository = codificationRepository;
        this.userRepository = userRepository;
        this.bulkInsertService = bulkInsertService;
    }

    @GetMapping("/upload-manifest")
    public String showUpload(@RequestParam(required = false) String search,
                             @RequestParam(defaultValue = "0") int page,
                             @RequestParam(defaultValue = "25") int size,
                             Model model,
                             Authentication auth) {
        var loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        var compagnie = loggedUser.getCompagnie();
        Pageable pageable = PaginationUtils.pageable(page, size);
        Page<Codification> codifications;

        if (compagnie == null) {
            codifications = Page.empty(pageable);
        } else if (search != null && !search.isEmpty()) {
            codifications = codificationRepository.findByCompagnieAndCallNumberContaining(compagnie, search, pageable);
        } else {
            codifications = codificationRepository.findByCompagnie(compagnie, pageable);
        }

        model.addAttribute("codifications", codifications.getContent());
        model.addAttribute("search", search);
        model.addAttribute("loggedUser", loggedUser);
        PaginationUtils.addPageAttributes(model, codifications);
        return "menu/planification/upload-manifest";
    }

    @PostMapping("/upload-manifest")
    public String storeManifest(@RequestParam("manifest") MultipartFile file,
                                RedirectAttributes redirectAttributes,
                                Authentication auth) {
        if (file.isEmpty()) {
            redirectAttributes.addFlashAttribute("error", "Veuillez selectionner un fichier TXT.");
            return "redirect:/menu/planification/upload-manifest";
        }
        if (!isValidManifestFile(file)) {
            redirectAttributes.addFlashAttribute("error", "Le fichier doit etre au format .txt et ne doit pas depasser 50 Mo.");
            return "redirect:/menu/planification/upload-manifest";
        }

        var loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        var compagnie = loggedUser.getCompagnie();
        if (compagnie == null) {
            redirectAttributes.addFlashAttribute("error", "Votre compte n'est rattache a aucune compagnie.");
            return "redirect:/menu/planification/upload-manifest";
        }

        try {
            Path tempFile = java.util.Objects.requireNonNull(Files.createTempFile("manifest_", ".txt"));
            try {
                file.transferTo(tempFile);

                List<EdiRecord> records = parser.parse(tempFile.toString());
                if (records.isEmpty()) {
                    redirectAttributes.addFlashAttribute("error", "Aucun enregistrement valide trouve dans le fichier.");
                    return "redirect:/menu/planification/upload-manifest";
                }

                String callNumber = records.get(0).data.getOrDefault("call_number", "").trim();
                String baseName   = sanitizeFileName(file.getOriginalFilename());
                String timestamp  = LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyyMMddHHmmss"));

                String manifestName = timestamp + "_" + file.getOriginalFilename();
                String xlsName      = timestamp + "_" + baseName + ".xlsx";
                String iftminName   = timestamp + "_" + baseName + ".edi";

                byte[] manifestBytes = file.getBytes();
                byte[] xlsBytes      = xlsExporter.exportToBytes(records, parser.getHeaders());
                byte[] iftminBytes   = exporter.exportToBytes(records);

                Codification codification = codificationRepository.save(java.util.Objects.requireNonNull(Codification.builder()
                        .callNumber(callNumber)
                        .manifest(manifestName)
                        .xls(xlsName)
                        .iftmin(iftminName)
                        .manifestData(manifestBytes)
                        .xlsData(xlsBytes)
                        .iftminData(iftminBytes)
                        .compagnie(compagnie)
                        .build()));

                // Insertion groupée des lignes via LOAD DATA LOCAL INFILE
                try {
                    bulkInsertService.bulkInsertLignes(records, codification.getId());
                } catch (Exception e) {
                    log.error("Erreur LOAD DATA INFILE pour codification_id={}", codification.getId(), e);
                }

                redirectAttributes.addFlashAttribute("success", "Manifeste traite avec succes. Call Number : " + callNumber);
                redirectAttributes.addFlashAttribute("codification_id", codification.getId());

            } finally {
                try { Files.deleteIfExists(tempFile); } catch (IOException ignored) {}
            }
        } catch (IOException e) {
            log.error("Error processing planification manifest file", e);
            redirectAttributes.addFlashAttribute("error", "Erreur lors du traitement du fichier.");
        }
        return "redirect:/menu/planification/upload-manifest";
    }

    @GetMapping("/{id}/preview")
    public String preview(@PathVariable int id, Model model, Authentication auth) {
        model.addAttribute("loggedUser", userRepository.findByEmail(auth.getName()).orElseThrow());
        Optional<Codification> codification = codificationRepository.findById(id);
        if (codification.isEmpty()) {
            return "redirect:/menu/planification/upload-manifest";
        }

        Codification cod = codification.get();
        List<String> xlsHeaders = new ArrayList<>();
        List<Map<String, String>> xlsRows = new ArrayList<>();

        if (cod.getXlsData() != null) {
            try (Workbook workbook = WorkbookFactory.create(new ByteArrayInputStream(cod.getXlsData()))) {
                var sheet = workbook.getSheetAt(0);
                var headerRow = sheet.getRow(0);
                if (headerRow != null) {
                    for (int i = 0; i < headerRow.getLastCellNum(); i++) {
                        xlsHeaders.add(headerRow.getCell(i).getStringCellValue());
                    }
                }
                DataFormatter formatter = new DataFormatter();
                for (int i = 1; i <= sheet.getLastRowNum(); i++) {
                    var row = sheet.getRow(i);
                    if (row != null) {
                        Map<String, String> rowData = new LinkedHashMap<>();
                        for (int j = 0; j < xlsHeaders.size(); j++) {
                            Cell cell = row.getCell(j);
                            rowData.put(xlsHeaders.get(j), cell != null ? formatter.formatCellValue(cell) : "");
                        }
                        xlsRows.add(rowData);
                    }
                }
            } catch (IOException e) {
                log.error("Error reading XLS data for codification {}", id, e);
            }
        }

        String iftminContent = "";
        if (cod.getIftminData() != null) {
            iftminContent = new String(cod.getIftminData(), java.nio.charset.StandardCharsets.UTF_8);
        }

        model.addAttribute("codification", cod);
        model.addAttribute("xlsHeaders", xlsHeaders);
        model.addAttribute("xlsRows", xlsRows);
        model.addAttribute("iftminContent", iftminContent);
        return "menu/planification/preview";
    }

    @GetMapping("/{id}/download-xls")
    public ResponseEntity<Resource> downloadXls(@PathVariable int id) { return downloadFile(id, "xls"); }

    @GetMapping("/{id}/download-iftmin")
    public ResponseEntity<Resource> downloadIftmin(@PathVariable int id) { return downloadFile(id, "iftmin"); }

    @GetMapping("/{id}/download-manifest")
    public ResponseEntity<Resource> downloadManifest(@PathVariable int id) { return downloadFile(id, "manifest"); }

    private ResponseEntity<Resource> downloadFile(int id, String fileType) {
        Optional<Codification> codification = codificationRepository.findById(id);
        if (codification.isEmpty()) return ResponseEntity.notFound().build();

        Codification cod = codification.get();
        byte[] data;
        String contentType;
        String filename;

        switch (fileType) {
            case "xls":
                data = cod.getXlsData();
                contentType = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
                filename = cod.getXls() != null ? new File(cod.getXls()).getName() : "export.xlsx";
                break;
            case "iftmin":
                data = cod.getIftminData();
                contentType = "application/edifact";
                filename = cod.getIftmin() != null
                        ? new File(cod.getIftmin()).getName().replaceAll("\\.(iftmin|edi)$", ".edi")
                        : "export.edi";
                break;
            case "manifest":
                data = cod.getManifestData();
                contentType = "text/plain";
                filename = cod.getManifest() != null ? new File(cod.getManifest()).getName() : "manifest.txt";
                break;
            default:
                return ResponseEntity.badRequest().build();
        }

        if (data == null || data.length == 0) return ResponseEntity.notFound().build();

        return ResponseEntity.ok()
                .header(HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=\"" + filename + "\"")
                .contentType(MediaType.parseMediaType(contentType))
                .body(new ByteArrayResource(data));
    }

    private boolean isValidManifestFile(MultipartFile file) {
        if (file.getSize() > 52428800) return false;
        String originalName = file.getOriginalFilename();
        return originalName != null && (originalName.toLowerCase().endsWith(".txt") || originalName.toLowerCase().endsWith(".text"));
    }

    private String sanitizeFileName(String fileName) {
        if (fileName == null) return "file";
        String baseName = fileName.replaceAll("\\.[^.]*$", "");
        return baseName.replaceAll("[^a-zA-Z0-9._-]", "_").toLowerCase();
    }
}
