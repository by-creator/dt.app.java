package com.dtapp.controller;

import com.dtapp.config.AutomationProperties;
import org.springframework.core.io.FileSystemResource;
import org.springframework.core.io.Resource;
import org.springframework.http.ContentDisposition;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;

import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;

@Controller
@RequestMapping("/api/automation-docs")
public class AutomationDocumentController {

    private final Path docsBase;

    public AutomationDocumentController(AutomationProperties automationProperties) {
        this.docsBase = Paths.get(automationProperties.getBasepath()).toAbsolutePath().resolve("docs");
    }

    @GetMapping("/{uuid}/{filename}")
    public ResponseEntity<Resource> serveDocument(
            @PathVariable String uuid,
            @PathVariable String filename,
            @RequestParam(required = false) String download) {

        // Rejeter tout uuid ou filename contenant des séparateurs de chemin
        if (uuid.contains("/") || uuid.contains("\\") || uuid.contains("..")
                || filename.contains("/") || filename.contains("\\") || filename.contains("..")) {
            return ResponseEntity.badRequest().build();
        }

        Path filePath = docsBase.resolve(uuid).resolve(filename).normalize();

        // Vérification anti path-traversal
        if (!filePath.startsWith(docsBase)) {
            return ResponseEntity.status(403).build();
        }

        if (!Files.exists(filePath) || !Files.isRegularFile(filePath)) {
            return ResponseEntity.notFound().build();
        }

        Resource resource = new FileSystemResource(filePath);

        if (download != null) {
            return ResponseEntity.ok()
                    .header(HttpHeaders.CONTENT_DISPOSITION,
                            ContentDisposition.attachment().filename(filename, StandardCharsets.UTF_8).build().toString())
                    .contentType(MediaType.APPLICATION_OCTET_STREAM)
                    .body(resource);
        }

        // Pour l'affichage inline, détecter le vrai type MIME
        MediaType mediaType = MediaType.APPLICATION_OCTET_STREAM;
        try {
            String mimeType = Files.probeContentType(filePath);
            if (mimeType != null) mediaType = MediaType.parseMediaType(mimeType);
        } catch (Exception ignored) {}

        return ResponseEntity.ok()
                .header(HttpHeaders.CONTENT_DISPOSITION,
                        ContentDisposition.inline().filename(filename, StandardCharsets.UTF_8).build().toString())
                .contentType(mediaType)
                .body(resource);
    }
}
