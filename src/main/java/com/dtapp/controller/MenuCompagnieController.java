package com.dtapp.controller;

import com.dtapp.entity.Codification;
import com.dtapp.repository.CodificationRepository;
import com.dtapp.repository.UserRepository;
import com.dtapp.service.EdiExporter;
import com.dtapp.service.EdiParser;
import com.dtapp.service.EdiRecord;
import com.dtapp.service.XlsExporter;
import lombok.extern.slf4j.Slf4j;
import org.apache.poi.ss.usermodel.Cell;
import org.apache.poi.ss.usermodel.DataFormatter;
import org.apache.poi.ss.usermodel.Workbook;
import org.apache.poi.ss.usermodel.WorkbookFactory;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.core.io.FileSystemResource;
import org.springframework.core.io.Resource;
import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Pageable;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.*;
import org.springframework.web.multipart.MultipartFile;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.io.File;
import java.io.IOException;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;

@Controller
@RequestMapping("/menu/compagnie")
@Slf4j
public class MenuCompagnieController {

    private final EdiParser parser;
    private final EdiExporter exporter;
    private final XlsExporter xlsExporter;
    private final CodificationRepository codificationRepository;
    private final UserRepository userRepository;

    @Value("${app.upload.dir:uploads/codifications}")
    private String uploadDir;

    public MenuCompagnieController(
            EdiParser parser,
            EdiExporter exporter,
            XlsExporter xlsExporter,
            CodificationRepository codificationRepository,
            UserRepository userRepository
    ) {
        this.parser = parser;
        this.exporter = exporter;
        this.xlsExporter = xlsExporter;
        this.codificationRepository = codificationRepository;
        this.userRepository = userRepository;
    }

    /**
     * Affiche le formulaire de upload avec la liste des codifications
     */
    @GetMapping("/upload-manifest")
    public String showUpload(
            @RequestParam(required = false) String search,
            @RequestParam(defaultValue = "0") int page,
            Model model,
            Authentication auth
    ) {
        var loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        var compagnie = loggedUser.getCompagnie();

        Pageable pageable = PageRequest.of(page, 10);
        Page<Codification> codifications;

        if (compagnie == null) {
            codifications = Page.empty(pageable);
        } else if (search != null && !search.isEmpty()) {
            codifications = codificationRepository.findByCompagnieAndCallNumberContaining(compagnie, search, pageable);
        } else {
            codifications = codificationRepository.findByCompagnie(compagnie, pageable);
        }

        model.addAttribute("codifications", codifications);
        model.addAttribute("search", search);
        model.addAttribute("loggedUser", loggedUser);
        return "menu/compagnie/upload-manifest";
    }

    /**
     * Traite l'upload du fichier manifest
     */
    @PostMapping("/upload-manifest")
    public String storeManifest(
            @RequestParam("manifest") MultipartFile file,
            RedirectAttributes redirectAttributes,
            Authentication auth
    ) {
        // Validation du fichier
        if (file.isEmpty()) {
            redirectAttributes.addFlashAttribute("error", "Veuillez sélectionner un fichier TXT.");
            return "redirect:/menu/compagnie/upload-manifest";
        }

        if (!isValidManifestFile(file)) {
            redirectAttributes.addFlashAttribute("error", "Le fichier doit être au format .txt et ne doit pas dépasser 50 Mo.");
            return "redirect:/menu/compagnie/upload-manifest";
        }

        var loggedUser = userRepository.findByEmail(auth.getName()).orElseThrow();
        var compagnie = loggedUser.getCompagnie();

        if (compagnie == null) {
            redirectAttributes.addFlashAttribute("error", "Votre compte n'est rattaché à aucune compagnie.");
            return "redirect:/menu/compagnie/upload-manifest";
        }

        try {
            // Créer le répertoire s'il n'existe pas
            Path uploadPath = Paths.get(uploadDir).toAbsolutePath();
            Files.createDirectories(uploadPath);

            // Parser le fichier
            Path tempFile = uploadPath.resolve("temp_" + file.getOriginalFilename());
            file.transferTo(tempFile);
            String tempFilePath = tempFile.toString();

            List<EdiRecord> records = parser.parse(tempFilePath);

            if (records.isEmpty()) {
                redirectAttributes.addFlashAttribute("error", "Aucun enregistrement valide trouvé dans le fichier.");
                new File(tempFilePath).delete();
                return "redirect:/menu/compagnie/upload-manifest";
            }

            // Récupérer le call_number du premier enregistrement
            String callNumber = records.get(0).data.getOrDefault("call_number", "").trim();
            String baseName = sanitizeFileName(file.getOriginalFilename());
            String timestamp = LocalDateTime.now().format(DateTimeFormatter.ofPattern("yyyyMMddHHmmss"));

            // Stocker le fichier manifest TXT
            String manifestName = timestamp + "_" + file.getOriginalFilename();
            String manifestPath = "codifications/" + manifestName;
            Path manifestFile = uploadPath.resolve(manifestName);
            Files.copy(Paths.get(tempFilePath), manifestFile);

            // Générer le fichier XLSX
            String xlsName = timestamp + "_" + baseName + ".xlsx";
            String xlsPath = "codifications/" + xlsName;
            Path xlsAbsPath = uploadPath.resolve(xlsName);
            Map<String, String> headers = parser.getHeaders();
            xlsExporter.export(records, headers, xlsAbsPath.toString());

            // Générer le fichier IFTMIN
            String iftminName = timestamp + "_" + baseName + ".edi";
            String iftminPath = "codifications/" + iftminName;
            Path iftminAbsPath = uploadPath.resolve(iftminName);
            exporter.export(records, iftminAbsPath.toString());

            // Enregistrer dans la base de données
            Codification codification = Codification.builder()
                    .callNumber(callNumber)
                    .manifest(manifestPath)
                    .xls(xlsPath)
                    .iftmin(iftminPath)
                    .compagnie(compagnie)
                    .build();

            codificationRepository.save(codification);

            // Nettoyer le fichier temporaire
            new File(tempFilePath).delete();

            redirectAttributes.addFlashAttribute("success", "Manifeste traité avec succès. Call Number : " + callNumber);
            redirectAttributes.addFlashAttribute("codification_id", codification.getId());

        } catch (IOException e) {
            log.error("Error processing manifest file", e);
            redirectAttributes.addFlashAttribute("error", "Erreur lors du traitement du fichier.");
        }

        return "redirect:/menu/compagnie/upload-manifest";
    }

    /**
     * Affiche un aperçu du codification
     */
    @GetMapping("/{id}/preview")
    public String preview(@PathVariable Integer id, Model model, Authentication auth) {
        model.addAttribute("loggedUser", userRepository.findByEmail(auth.getName()).orElseThrow());
        Optional<Codification> codification = codificationRepository.findById(id);

        if (codification.isEmpty()) {
            return "redirect:/menu/compagnie/upload-manifest";
        }

        Codification cod = codification.get();
        Path uploadPath = Paths.get(uploadDir).toAbsolutePath();

        // Lire le fichier XLS
        List<String> xlsHeaders = new ArrayList<>();
        List<Map<String, String>> xlsRows = new ArrayList<>();

        try {
            Path xlsFile = uploadPath.resolve(cod.getXls().replace("codifications/", ""));
            if (Files.exists(xlsFile)) {
                Workbook workbook = WorkbookFactory.create(xlsFile.toFile());
                var sheet = workbook.getSheetAt(0);

                // Récupérer les en-têtes
                var headerRow = sheet.getRow(0);
                if (headerRow != null) {
                    for (int i = 0; i < headerRow.getLastCellNum(); i++) {
                        xlsHeaders.add(headerRow.getCell(i).getStringCellValue());
                    }
                }

                // Récupérer les données
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
                workbook.close();
            }
        } catch (IOException e) {
            log.error("Error reading XLS file", e);
        }

        // Lire le fichier IFTMIN
        String iftminContent = "";
        try {
            Path iftminFile = uploadPath.resolve(cod.getIftmin().replace("codifications/", ""));
            if (Files.exists(iftminFile)) {
                iftminContent = Files.readString(iftminFile);
            }
        } catch (IOException e) {
            log.error("Error reading IFTMIN file", e);
        }

        model.addAttribute("codification", cod);
        model.addAttribute("xlsHeaders", xlsHeaders);
        model.addAttribute("xlsRows", xlsRows);
        model.addAttribute("iftminContent", iftminContent);

        return "menu/compagnie/preview";
    }

    /**
     * Télécharge le fichier XLS
     */
    @GetMapping("/{id}/download-xls")
    public ResponseEntity<Resource> downloadXls(@PathVariable Integer id) {
        return downloadFile(id, "xls");
    }

    /**
     * Télécharge le fichier IFTMIN
     */
    @GetMapping("/{id}/download-iftmin")
    public ResponseEntity<Resource> downloadIftmin(@PathVariable Integer id) {
        return downloadFile(id, "iftmin");
    }

    /**
     * Télécharge le fichier manifest
     */
    @GetMapping("/{id}/download-manifest")
    public ResponseEntity<Resource> downloadManifest(@PathVariable Integer id) {
        return downloadFile(id, "manifest");
    }

    /**
     * Méthode utilitaire pour télécharger un fichier
     */
    private ResponseEntity<Resource> downloadFile(Integer id, String fileType) {
        Optional<Codification> codification = codificationRepository.findById(id);

        if (codification.isEmpty()) {
            return ResponseEntity.notFound().build();
        }

        Codification cod = codification.get();
        String filePath;
        String contentType;
        String filename;

        switch (fileType) {
            case "xls":
                filePath = cod.getXls();
                contentType = "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
                filename = new File(filePath).getName();
                break;
            case "iftmin":
                filePath = cod.getIftmin();
                contentType = "application/edifact";
                filename = new File(filePath).getName().replaceAll("\\.(iftmin|edi)$", ".edi");
                break;
            case "manifest":
                filePath = cod.getManifest();
                contentType = "text/plain";
                filename = new File(filePath).getName();
                break;
            default:
                return ResponseEntity.badRequest().build();
        }

        Path file = Paths.get(uploadDir).toAbsolutePath().resolve(filePath.replace("codifications/", ""));

        if (!Files.exists(file)) {
            return ResponseEntity.notFound().build();
        }

        try {
            Resource resource = new FileSystemResource(file);
            return ResponseEntity.ok()
                    .header(HttpHeaders.CONTENT_DISPOSITION, "attachment; filename=\"" + filename + "\"")
                    .contentType(MediaType.parseMediaType(contentType))
                    .body(resource);
        } catch (Exception e) {
            log.error("Error downloading file: {}", filePath, e);
            return ResponseEntity.internalServerError().build();
        }
    }

    /**
     * Valide qu'un fichier est un manifest valide
     */
    private boolean isValidManifestFile(MultipartFile file) {
        if (file.getSize() > 52428800) { // 50 MB
            return false;
        }

        String originalName = file.getOriginalFilename();
        if (originalName == null) {
            return false;
        }

        // Accepter .txt et .text
        return originalName.toLowerCase().endsWith(".txt") ||
                originalName.toLowerCase().endsWith(".text");
    }

    /**
     * Nettoie le nom de fichier
     */
    private String sanitizeFileName(String fileName) {
        if (fileName == null) {
            return "file";
        }
        // Supprimer l'extension
        String baseName = fileName.replaceAll("\\.[^.]*$", "");
        // Supprimer les caractères spéciaux et remplacer par des tirets
        return baseName.replaceAll("[^a-zA-Z0-9._-]", "_").toLowerCase();
    }
}
