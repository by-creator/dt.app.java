package com.dtapp.controller;

import org.springframework.core.io.FileSystemResource;
import org.springframework.core.io.Resource;
import org.springframework.http.HttpHeaders;
import org.springframework.http.MediaType;
import org.springframework.http.ResponseEntity;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.GetMapping;

import java.io.File;
import java.nio.file.Path;
import java.nio.file.Paths;

@Controller
public class HomeController {

    @GetMapping("/")
    public String home() {
        return "redirect:/dashboard";
    }

    @GetMapping("/login")
    public String login() {
        return "login";
    }

    @GetMapping("/politique_securite.pdf")
    public ResponseEntity<Resource> politiqueSecuritePdf() {
        Path pdfPath = Paths.get("politique_securite.pdf").toAbsolutePath().normalize();
        File pdfFile = pdfPath.toFile();
        if (!pdfFile.exists() || !pdfFile.isFile()) {
            return ResponseEntity.notFound().build();
        }
        Resource resource = new FileSystemResource(pdfFile);
        return ResponseEntity.ok()
                .contentType(MediaType.APPLICATION_PDF)
                .header(HttpHeaders.CONTENT_DISPOSITION, "inline; filename=\"politique_securite.pdf\"")
                .body(resource);
    }
}
