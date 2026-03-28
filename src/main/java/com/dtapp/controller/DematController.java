package com.dtapp.controller;

import com.dtapp.entity.RattachementBl;
import com.dtapp.entity.SatisfactionReponse;
import com.dtapp.repository.RattachementBlRepository;
import com.dtapp.repository.SatisfactionReponseRepository;
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
    private final SatisfactionReponseRepository satisfactionReponseRepository;
    private final EmailService emailService;

    public DematController(RattachementBlRepository rattachementBlRepository,
                           SatisfactionReponseRepository satisfactionReponseRepository,
                           EmailService emailService) {
        this.rattachementBlRepository       = rattachementBlRepository;
        this.satisfactionReponseRepository  = satisfactionReponseRepository;
        this.emailService                   = emailService;
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

    @PostMapping("/demat/satisfaction")
    public String submitSatisfaction(
            // Infos
            @RequestParam(required = false) String nom,
            @RequestParam(required = false) String telephone,
            @RequestParam(required = false) String email,
            // General
            @RequestParam(required = false) String volume,
            @RequestParam(required = false) String anciennete,
            // Facturation
            @RequestParam(name = "delais-facturation",               required = false) String delaisFacturation,
            @RequestParam(required = false)                                            String notifications,
            @RequestParam(name = "reactivite-facture",               required = false) String reactiviteFacture,
            @RequestParam(name = "usage-plateforme",                 required = false) String usagePlateforme,
            @RequestParam(name = "usage-plateforme-detail",          required = false) String usagePlateformeDetail,
            @RequestParam(name = "facilite-plateforme",              required = false) String facilitePlateforme,
            @RequestParam(name = "fonctionnalites-plateforme",       required = false) String fonctionnalitesPlateforme,
            @RequestParam(name = "fonctionnalites-plateforme-detail",required = false) String fonctionnalitesPlateformeDetail,
            @RequestParam(name = "bugs-plateforme",                  required = false) String bugsPlateforme,
            @RequestParam(name = "bugs-plateforme-detail",           required = false) String bugsPlateformeDetail,
            @RequestParam(name = "assistance-plateforme",            required = false) String assistancePlateforme,
            @RequestParam(name = "suggestions-facturation",          required = false) String suggestionsFacturation,
            // BAE
            @RequestParam(name = "delais-bae",           required = false) String delaisBae,
            @RequestParam(name = "suggestions-bae",      required = false) String suggestionsBae,
            @RequestParam(name = "suggestions-bae-detail",required = false) String suggestionsBaeDetail,
            // Accueil
            @RequestParam(name = "accueil-locaux",        required = false) String accueilLocaux,
            @RequestParam(name = "personnel-accueil",     required = false) String personnelAccueil,
            @RequestParam(required = false)                                  String infrastructures,
            @RequestParam(name = "infrastructures-detail",required = false) String infrastructuresDetail,
            // Livraison
            @RequestParam(name = "fluidite-livraison",         required = false) String fluiditeLivraison,
            @RequestParam(name = "horaires-livraison",         required = false) String horairesLivraison,
            @RequestParam(name = "horaires-livraison-detail",  required = false) String horairesLivraisonDetail,
            @RequestParam(name = "retards-livraison",          required = false) String retardsLivraison,
            @RequestParam(name = "retards-livraison-detail",   required = false) String retardsLivraisonDetail,
            @RequestParam(name = "coordination-livraison",     required = false) String coordinationLivraison,
            @RequestParam(name = "ameliorations-livraison",    required = false) String ameliorationsLivraison,
            // Communication
            @RequestParam(name = "communication-proactive",    required = false) String communicationProactive,
            @RequestParam(required = false)                                       String alertes,
            @RequestParam(name = "suggestions-communication",  required = false) String suggestionsCommunication,
            @RequestParam(name = "recommandations-generales",  required = false) String recommandationsGenerales) {

        SatisfactionReponse r = new SatisfactionReponse();
        r.setNom(nom);                 r.setTelephone(telephone);        r.setEmail(email);
        r.setVolume(volume);           r.setAnciennete(anciennete);
        r.setDelaisFacturation(delaisFacturation);
        r.setNotifications(notifications);
        r.setReactiviteFacture(reactiviteFacture);
        r.setUsagePlateforme(usagePlateforme);                 r.setUsagePlateformeDetail(usagePlateformeDetail);
        r.setFacilitePlateforme(facilitePlateforme);
        r.setFonctionnalitesPlateforme(fonctionnalitesPlateforme);
        r.setFonctionnalitesPlateformeDetail(fonctionnalitesPlateformeDetail);
        r.setBugsPlateforme(bugsPlateforme);                   r.setBugsPlateformeDetail(bugsPlateformeDetail);
        r.setAssistancePlateforme(assistancePlateforme);       r.setSuggestionsFacturation(suggestionsFacturation);
        r.setDelaisBae(delaisBae);
        r.setSuggestionsBae(suggestionsBae);                   r.setSuggestionsBaeDetail(suggestionsBaeDetail);
        r.setAccueilLocaux(accueilLocaux);                     r.setPersonnelAccueil(personnelAccueil);
        r.setInfrastructures(infrastructures);                 r.setInfrastructuresDetail(infrastructuresDetail);
        r.setFluiditeLivraison(fluiditeLivraison);
        r.setHorairesLivraison(horairesLivraison);             r.setHorairesLivraisonDetail(horairesLivraisonDetail);
        r.setRetardsLivraison(retardsLivraison);               r.setRetardsLivraisonDetail(retardsLivraisonDetail);
        r.setCoordinationLivraison(coordinationLivraison);     r.setAmeliorationsLivraison(ameliorationsLivraison);
        r.setCommunicationProactive(communicationProactive);
        r.setAlertes(alertes);
        r.setSuggestionsCommunication(suggestionsCommunication);
        r.setRecommandationsGenerales(recommandationsGenerales);
        satisfactionReponseRepository.save(r);

        return "redirect:/demat/satisfaction?success=true";
    }
}
