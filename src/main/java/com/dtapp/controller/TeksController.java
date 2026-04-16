package com.dtapp.controller;

import com.dtapp.entity.Teks;
import com.dtapp.entity.User;
import com.dtapp.repository.TeksRepository;
import com.dtapp.repository.UserRepository;
import com.dtapp.util.PaginationUtils;
import jakarta.servlet.http.HttpSession;
import org.apache.pdfbox.Loader;
import org.apache.pdfbox.io.RandomAccessReadBuffer;
import org.apache.pdfbox.multipdf.PDFMergerUtility;
import org.apache.pdfbox.multipdf.Splitter;
import org.apache.pdfbox.pdmodel.PDDocument;
import org.apache.pdfbox.text.PDFTextStripper;
import org.springframework.core.io.ByteArrayResource;
import org.springframework.data.domain.Page;
import org.springframework.http.ContentDisposition;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.security.core.Authentication;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.ResponseBody;
import org.springframework.web.multipart.MultipartFile;
import org.springframework.web.servlet.mvc.support.RedirectAttributes;

import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.io.Serializable;
import java.nio.charset.StandardCharsets;
import java.util.ArrayList;
import java.util.List;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

@Controller
public class TeksController {

    private final TeksRepository teksRepository;
    private final UserRepository userRepository;

    public TeksController(TeksRepository teksRepository,
                          UserRepository userRepository) {
        this.teksRepository = teksRepository;
        this.userRepository = userRepository;
    }

    @GetMapping("/menu/informatique/teks")
    public String adminIndex(@RequestParam(required = false) String search,
                             @RequestParam(required = false) String filterBl,
                             @RequestParam(required = false) String filterChassis,
                             @RequestParam(defaultValue = "0") int page,
                             @RequestParam(defaultValue = "25") int size,
                             Model model,
                             Authentication auth,
                             HttpSession session) {
        Page<Teks> teksPage = teksRepository.search(
                safe(search),
                safe(filterBl),
                safe(filterChassis),
                PaginationUtils.pageable(page, size));

        model.addAttribute("loggedUser", loggedUser(auth));
        model.addAttribute("teksList", teksPage.getContent());
        model.addAttribute("search", value(search));
        model.addAttribute("filterBl", value(filterBl));
        model.addAttribute("filterChassis", value(filterChassis));
        PaginationUtils.addPageAttributes(model, teksPage);

        @SuppressWarnings("unchecked")
        List<EscalePageData> escaleData = (List<EscalePageData>) session.getAttribute("ESCALE_PREVIEW");
        if (escaleData != null) {
            List<EscalePagePreview> previews = new ArrayList<>();
            for (int i = 0; i < escaleData.size(); i++) {
                EscalePageData d = escaleData.get(i);
                previews.add(new EscalePagePreview(i + 1, d.bl, d.chassis, d.escale, d.fileName));
            }
            model.addAttribute("escalePreview", previews);
        }

        return "informatique/teks";
    }

    @PostMapping("/menu/informatique/teks/create")
    public String create(@RequestParam String bl,
                         @RequestParam String chassis,
                         @RequestParam("file") MultipartFile file,
                         RedirectAttributes ra) {
        if (isBlank(bl) || isBlank(chassis) || file == null || file.isEmpty()) {
            ra.addFlashAttribute("error", "Les champs BL, chassis et fichier sont obligatoires.");
            return "redirect:/menu/informatique/teks";
        }

        try {
            Teks teks = new Teks();
            teks.setBl(bl.trim());
            teks.setChassis(chassis.trim());
            updateFile(teks, file);
            teksRepository.save(teks);
            ra.addFlashAttribute("success", "Teks ajoute avec succes.");
        } catch (IOException e) {
            ra.addFlashAttribute("error", "Impossible de lire le fichier televerse.");
        }
        return "redirect:/menu/informatique/teks";
    }

    @PostMapping("/menu/informatique/teks/{id}/edit")
    public String edit(@PathVariable long id,
                       @RequestParam String bl,
                       @RequestParam String chassis,
                       @RequestParam("file") MultipartFile file,
                       RedirectAttributes ra) {
        Teks teks = teksRepository.findById(id).orElse(null);
        if (teks == null) {
            ra.addFlashAttribute("error", "Teks introuvable.");
            return "redirect:/menu/informatique/teks";
        }

        if (isBlank(bl) || isBlank(chassis)) {
            ra.addFlashAttribute("error", "Les champs BL et chassis sont obligatoires.");
            return "redirect:/menu/informatique/teks";
        }

        try {
            teks.setBl(bl.trim());
            teks.setChassis(chassis.trim());
            if (file != null && !file.isEmpty()) {
                updateFile(teks, file);
            }
            teksRepository.save(teks);
            ra.addFlashAttribute("success", "Teks mis a jour avec succes.");
        } catch (IOException e) {
            ra.addFlashAttribute("error", "Impossible de lire le nouveau fichier.");
        }
        return "redirect:/menu/informatique/teks";
    }

    @PostMapping("/menu/informatique/teks/{id}/delete")
    public String delete(@PathVariable long id,
                         RedirectAttributes ra) {
        Teks teks = teksRepository.findById(id).orElse(null);
        if (teks != null) {
            teksRepository.delete(teks);
            ra.addFlashAttribute("success", "Teks supprime avec succes.");
        }
        return "redirect:/menu/informatique/teks";
    }

    @GetMapping("/teks/print-all")
    public String printAllView(Model model) {
        boolean hasPdfs = teksRepository.findAll().stream()
                .anyMatch(t -> MediaType.APPLICATION_PDF_VALUE.equalsIgnoreCase(t.getFileContentType()));
        model.addAttribute("hasPdfs", hasPdfs);
        return "public/teks-print-all";
    }

    @GetMapping("/teks/print-all/file")
    @ResponseBody
    public ResponseEntity<ByteArrayResource> printAllFile() throws IOException {
        List<Teks> pdfs = teksRepository.findAll().stream()
                .filter(t -> MediaType.APPLICATION_PDF_VALUE.equalsIgnoreCase(t.getFileContentType()))
                .toList();

        if (pdfs.isEmpty()) {
            return ResponseEntity.noContent().build();
        }

        byte[] merged;
        if (pdfs.size() == 1) {
            merged = pdfs.get(0).getFileData();
        } else {
            PDFMergerUtility merger = new PDFMergerUtility();
            ByteArrayOutputStream out = new ByteArrayOutputStream();
            merger.setDestinationStream(out);
            for (Teks t : pdfs) {
                merger.addSource(new RandomAccessReadBuffer(t.getFileData()));
            }
            merger.mergeDocuments(null);
            merged = out.toByteArray();
        }

        ContentDisposition disposition = ContentDisposition.inline()
                .filename("teks-complet.pdf", StandardCharsets.UTF_8)
                .build();

        return ResponseEntity.ok()
                .contentType(MediaType.APPLICATION_PDF)
                .contentLength(merged.length)
                .header(HttpHeaders.CONTENT_DISPOSITION, disposition.toString())
                .body(new ByteArrayResource(merged));
    }

    @GetMapping("/teks")
    public String publicIndex(@RequestParam(required = false) String search,
                              @RequestParam(defaultValue = "0") int page,
                              @RequestParam(defaultValue = "12") int size,
                              Model model) {
        Page<Teks> teksPage = teksRepository.search(safe(search), "", "", PaginationUtils.pageable(page, size));
        model.addAttribute("teksList", teksPage.getContent());
        model.addAttribute("search", value(search));
        model.addAttribute("escales", teksRepository.findDistinctEscales());
        PaginationUtils.addPageAttributes(model, teksPage);
        return "public/teks";
    }

    @GetMapping("/teks/print-escale")
    public String printEscaleView(@RequestParam String escale, Model model) {
        model.addAttribute("escale", escale);
        return "public/teks-print-escale";
    }

    @GetMapping("/teks/print-escale/file")
    @ResponseBody
    public ResponseEntity<ByteArrayResource> printEscaleFile(@RequestParam String escale) throws IOException {
        List<Teks> pdfs = teksRepository.findByEscaleOrderByCreatedAtAsc(escale).stream()
                .filter(t -> MediaType.APPLICATION_PDF_VALUE.equalsIgnoreCase(t.getFileContentType()))
                .toList();

        if (pdfs.isEmpty()) {
            return ResponseEntity.noContent().build();
        }

        byte[] merged;
        if (pdfs.size() == 1) {
            merged = pdfs.get(0).getFileData();
        } else {
            PDFMergerUtility merger = new PDFMergerUtility();
            ByteArrayOutputStream out = new ByteArrayOutputStream();
            merger.setDestinationStream(out);
            for (Teks t : pdfs) {
                merger.addSource(new RandomAccessReadBuffer(t.getFileData()));
            }
            merger.mergeDocuments(null);
            merged = out.toByteArray();
        }

        ContentDisposition disposition = ContentDisposition.inline()
                .filename(escale + ".pdf", StandardCharsets.UTF_8)
                .build();

        return ResponseEntity.ok()
                .contentType(MediaType.APPLICATION_PDF)
                .contentLength(merged.length)
                .header(HttpHeaders.CONTENT_DISPOSITION, disposition.toString())
                .body(new ByteArrayResource(merged));
    }

    @GetMapping("/teks/{id}/print")
    public String printView(@PathVariable long id, Model model) {
        Teks teks = teksRepository.findById(id).orElseThrow();
        model.addAttribute("teks", teks);
        model.addAttribute("fileUrl", "/teks/files/" + id);
        model.addAttribute("printable", isPrintable(teks));
        return "public/teks-print";
    }

    @GetMapping("/teks/files/{id}")
    @ResponseBody
    public ResponseEntity<ByteArrayResource> file(@PathVariable long id,
                                                  @RequestParam(defaultValue = "false") boolean download) {
        Teks teks = teksRepository.findById(id).orElseThrow();
        MediaType mediaType = resolveMediaType(teks.getFileContentType());
        ContentDisposition disposition = (download ? ContentDisposition.attachment() : ContentDisposition.inline())
                .filename(teks.getFileName(), StandardCharsets.UTF_8)
                .build();

        return ResponseEntity.ok()
                .contentType(mediaType)
                .contentLength(teks.getFileData().length)
                .header(HttpHeaders.CONTENT_DISPOSITION, disposition.toString())
                .body(new ByteArrayResource(teks.getFileData()));
    }

    private User loggedUser(Authentication auth) {
        return userRepository.findByEmail(auth.getName()).orElseThrow();
    }

    private void updateFile(Teks teks, MultipartFile file) throws IOException {
        teks.setFileName(file.getOriginalFilename() != null ? file.getOriginalFilename().trim() : "teks-file");
        teks.setFileContentType(file.getContentType());
        teks.setFileData(file.getBytes());
    }

    private String safe(String value) {
        return value == null ? "" : value.trim();
    }

    private String value(String value) {
        return value == null ? "" : value;
    }

    private boolean isBlank(String value) {
        return value == null || value.isBlank();
    }

    private boolean isPrintable(Teks teks) {
        String contentType = teks.getFileContentType();
        if (contentType == null) {
            return false;
        }
        return contentType.equalsIgnoreCase(MediaType.APPLICATION_PDF_VALUE)
                || contentType.startsWith("image/");
    }

    private MediaType resolveMediaType(String contentType) {
        if (contentType == null || contentType.isBlank()) {
            return MediaType.APPLICATION_OCTET_STREAM;
        }
        try {
            return MediaType.parseMediaType(contentType);
        } catch (Exception ignored) {
            return MediaType.APPLICATION_OCTET_STREAM;
        }
    }

    // -------------------------------------------------------------------------
    // Upload Escale
    // -------------------------------------------------------------------------

    @PostMapping("/menu/informatique/teks/upload-escale/preview")
    public String escalePreview(@RequestParam("escaleFile") MultipartFile file,
                                HttpSession session,
                                RedirectAttributes ra) {
        if (file == null || file.isEmpty()) {
            ra.addFlashAttribute("error", "Veuillez selectionner un fichier PDF.");
            return "redirect:/menu/informatique/teks";
        }
        if (!"application/pdf".equalsIgnoreCase(file.getContentType())) {
            ra.addFlashAttribute("error", "Seuls les fichiers PDF sont acceptes.");
            return "redirect:/menu/informatique/teks";
        }
        try {
            List<EscalePageData> pages = parseEscalePdf(file.getBytes());
            if (pages.isEmpty()) {
                ra.addFlashAttribute("error", "Aucune page extractable dans ce PDF.");
                return "redirect:/menu/informatique/teks";
            }
            session.setAttribute("ESCALE_PREVIEW", pages);
        } catch (IOException e) {
            ra.addFlashAttribute("error", "Impossible de lire le fichier PDF : " + e.getMessage());
        }
        return "redirect:/menu/informatique/teks";
    }

    @PostMapping("/menu/informatique/teks/upload-escale/confirm")
    public String escaleConfirm(HttpSession session, RedirectAttributes ra) {
        @SuppressWarnings("unchecked")
        List<EscalePageData> pages = (List<EscalePageData>) session.getAttribute("ESCALE_PREVIEW");
        if (pages == null || pages.isEmpty()) {
            ra.addFlashAttribute("error", "Aucune donnee a importer. Veuillez re-uploader le fichier.");
            return "redirect:/menu/informatique/teks";
        }
        int count = 0;
        for (EscalePageData d : pages) {
            Teks teks = new Teks();
            teks.setBl(d.bl);
            teks.setChassis(d.chassis);
            teks.setEscale(d.escale);
            teks.setFileName(d.fileName);
            teks.setFileContentType(MediaType.APPLICATION_PDF_VALUE);
            teks.setFileData(d.pdfBytes);
            teksRepository.save(teks);
            count++;
        }
        session.removeAttribute("ESCALE_PREVIEW");
        ra.addFlashAttribute("success", count + " enregistrement(s) importe(s) avec succes depuis Escale.");
        return "redirect:/menu/informatique/teks";
    }

    @PostMapping("/menu/informatique/teks/upload-escale/cancel")
    public String escaleCancel(HttpSession session) {
        session.removeAttribute("ESCALE_PREVIEW");
        return "redirect:/menu/informatique/teks";
    }

    private List<EscalePageData> parseEscalePdf(byte[] pdfBytes) throws IOException {
        List<EscalePageData> result = new ArrayList<>();
        // BL NUMBER: valeur sur la même ligne (on ne consomme pas les sauts de ligne)
        Pattern blPattern = Pattern.compile("BL[ \\t]+NUMBER[ \\t]*[:\\-][ \\t]*([^\\n\\r]+)", Pattern.CASE_INSENSITIVE);

        try (PDDocument doc = Loader.loadPDF(pdfBytes)) {
            Splitter splitter = new Splitter();
            List<PDDocument> pageDocs = splitter.split(doc);
            PDFTextStripper stripper = new PDFTextStripper();

            for (int i = 0; i < pageDocs.size(); i++) {
                PDDocument pageDoc = pageDocs.get(i);
                try {
                    String text = stripper.getText(pageDoc);
                    Matcher blMatcher = blPattern.matcher(text);
                    String bl = blMatcher.find() ? blMatcher.group(1).trim() : "";
                    String chassis = extractChassis(text);
                    String escale = extractEscale(text);

                    ByteArrayOutputStream out = new ByteArrayOutputStream();
                    pageDoc.save(out);
                    result.add(new EscalePageData(bl, chassis, escale, out.toByteArray(), "escale-p" + (i + 1) + ".pdf"));
                } finally {
                    pageDoc.close();
                }
            }
        }
        return result;
    }

    /**
     * Extrait la valeur chassis (ITEM ID) depuis le texte d'une page PDF.
     * Stratégie 1 : valeur sur la même ligne que "ITEM ID:" (sans franchir le saut de ligne).
     * Stratégie 2 : si vide ou label parasite (se termine par ":"), cherche en remontant
     *               depuis la fin du texte le premier token alphanumérique pur (≥ 8 chars),
     *               qui correspond au grand texte VIN affiché en bas de page.
     */
    private String extractChassis(String text) {
        // Stratégie 1 : même ligne — on n'autorise que espaces/tabulations entre ":" et la valeur
        Pattern sameLine = Pattern.compile("ITEM[ \\t]+ID[ \\t]*[:\\-][ \\t]*(\\S+)", Pattern.CASE_INSENSITIVE);
        Matcher m = sameLine.matcher(text);
        if (m.find()) {
            String val = m.group(1).trim();
            if (!val.isEmpty() && !val.endsWith(":") && !val.endsWith("-")) {
                return val;
            }
        }
        // Stratégie 2 : grand texte VIN en bas de page (alphanumériques purs, ≥ 8 caractères)
        String[] lines = text.split("[\\n\\r]+");
        for (int j = lines.length - 1; j >= 0; j--) {
            String line = lines[j].trim();
            if (line.matches("[A-Z0-9]{8,}")) {
                return line;
            }
        }
        return "";
    }

    /**
     * Extrait le titre de l'escale depuis le texte d'une page PDF.
     * C'est la première ligne contenant uniquement des majuscules, chiffres et underscores
     * (ex : GAMBURGO_GRI_0424SB).
     */
    private String extractEscale(String text) {
        for (String line : text.split("[\\n\\r]+")) {
            String trimmed = line.trim();
            if (trimmed.matches("[A-Z][A-Z0-9_]{4,}")) {
                return trimmed;
            }
        }
        return "";
    }

    // -------------------------------------------------------------------------
    // Inner types
    // -------------------------------------------------------------------------

    private static class EscalePageData implements Serializable {
        final String bl;
        final String chassis;
        final String escale;
        final byte[] pdfBytes;
        final String fileName;

        EscalePageData(String bl, String chassis, String escale, byte[] pdfBytes, String fileName) {
            this.bl = bl;
            this.chassis = chassis;
            this.escale = escale;
            this.pdfBytes = pdfBytes;
            this.fileName = fileName;
        }
    }

    public static class EscalePagePreview {
        private final int pageNumber;
        private final String bl;
        private final String chassis;
        private final String escale;
        private final String fileName;

        public EscalePagePreview(int pageNumber, String bl, String chassis, String escale, String fileName) {
            this.pageNumber = pageNumber;
            this.bl = bl;
            this.chassis = chassis;
            this.escale = escale;
            this.fileName = fileName;
        }

        public int getPageNumber() { return pageNumber; }
        public String getBl()      { return bl; }
        public String getChassis() { return chassis; }
        public String getEscale()  { return escale; }
        public String getFileName(){ return fileName; }
    }
}
