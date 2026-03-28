package com.dtapp.controller;

import com.dtapp.entity.RattachementBl;
import com.dtapp.repository.RattachementBlRepository;
import com.dtapp.service.EmailService;
import org.springframework.stereotype.Controller;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.multipart.MultipartFile;

import java.util.Arrays;
import java.util.List;

@Controller
public class DematController {

    private final RattachementBlRepository rattachementBlRepository;
    private final EmailService emailService;

    public DematController(RattachementBlRepository rattachementBlRepository,
                           EmailService emailService) {
        this.rattachementBlRepository = rattachementBlRepository;
        this.emailService = emailService;
    }

    @GetMapping("/demat")
    public String dematHome() {
        return "demat/index";
    }

    @GetMapping("/demat/validation")
    public String dematValidation() {
        return "demat/validation";
    }

    @PostMapping("/demat/validation")
    public String submitValidation(
            @RequestParam String nom,
            @RequestParam String prenom,
            @RequestParam String email,
            @RequestParam("bl-number") String blNumber,
            @RequestParam("maison-transit") String maisonTransit,
            @RequestParam("bl-file") MultipartFile blFile,
            @RequestParam("bad-shipping") MultipartFile badShipping,
            @RequestParam("declaration") MultipartFile declaration) {

        RattachementBl bl = new RattachementBl();
        bl.setNom(nom);
        bl.setPrenom(prenom);
        bl.setEmail(email);
        bl.setBl(blNumber);
        bl.setMaison(maisonTransit);
        bl.setStatut("EN_ATTENTE");
        bl.setType("FACTURATION");
        rattachementBlRepository.save(bl);

        List<MultipartFile> attachments = Arrays.asList(blFile, badShipping, declaration);
        emailService.sendValidationNotification(bl, attachments);

        return "redirect:/demat/validation?success=true";
    }

    @GetMapping("/demat/remise")
    public String dematRemise() {
        return "demat/remise";
    }

    @PostMapping("/demat/remise")
    public String submitRemise(
            @RequestParam String nom,
            @RequestParam String prenom,
            @RequestParam String email,
            @RequestParam("bl-number") String blNumber,
            @RequestParam("maison-transit") String maisonTransit,
            @RequestParam("demande-manuscrite") MultipartFile demandeManuscrite,
            @RequestParam("bad-shipping") MultipartFile badShipping,
            @RequestParam("bl-file") MultipartFile blFile,
            @RequestParam("facture-file") MultipartFile factureFile,
            @RequestParam("declaration-file") MultipartFile declarationFile) {

        RattachementBl bl = new RattachementBl();
        bl.setNom(nom);
        bl.setPrenom(prenom);
        bl.setEmail(email);
        bl.setBl(blNumber);
        bl.setMaison(maisonTransit);
        bl.setStatut("EN_ATTENTE");
        bl.setType("REMISE");
        rattachementBlRepository.save(bl);

        List<MultipartFile> attachments = Arrays.asList(
                demandeManuscrite, badShipping, blFile, factureFile, declarationFile);
        emailService.sendRemiseNotification(bl, attachments);

        return "redirect:/demat/remise?success=true";
    }

    @GetMapping("/demat/paiement")
    public String dematPaiement() {
        return "demat/paiement";
    }

    @GetMapping("/demat/satisfaction")
    public String dematSatisfaction() {
        return "demat/satisfaction";
    }
}
