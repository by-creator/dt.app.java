package com.dtapp.controller;

import com.dtapp.entity.Teks;
import com.dtapp.entity.User;
import com.dtapp.repository.TeksRepository;
import com.dtapp.repository.UserRepository;
import com.dtapp.util.PaginationUtils;
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

import java.io.IOException;
import java.nio.charset.StandardCharsets;

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
                             Authentication auth) {
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

    @GetMapping("/teks")
    public String publicIndex(@RequestParam(required = false) String search,
                              @RequestParam(defaultValue = "0") int page,
                              @RequestParam(defaultValue = "12") int size,
                              Model model) {
        Page<Teks> teksPage = teksRepository.search(safe(search), "", "", PaginationUtils.pageable(page, size));
        model.addAttribute("teksList", teksPage.getContent());
        model.addAttribute("search", value(search));
        PaginationUtils.addPageAttributes(model, teksPage);
        return "public/teks";
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
}
