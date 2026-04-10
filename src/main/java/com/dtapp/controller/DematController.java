package com.dtapp.controller;

import com.dtapp.entity.RattachementBl;
import com.dtapp.entity.SatisfactionInfo;
import com.dtapp.entity.SatisfactionGeneral;
import com.dtapp.entity.SatisfactionFacturation;
import com.dtapp.entity.SatisfactionBae;
import com.dtapp.entity.SatisfactionAccueil;
import com.dtapp.entity.SatisfactionLivraison;
import com.dtapp.entity.SatisfactionCommunication;
import com.dtapp.entity.TiersUnify;
import com.dtapp.repository.RattachementBlRepository;
import com.dtapp.repository.SatisfactionInfoRepository;
import com.dtapp.repository.SatisfactionGeneralRepository;
import com.dtapp.repository.SatisfactionFacturationRepository;
import com.dtapp.repository.SatisfactionBaeRepository;
import com.dtapp.repository.SatisfactionAccueilRepository;
import com.dtapp.repository.SatisfactionLivraisonRepository;
import com.dtapp.repository.SatisfactionCommunicationRepository;
import com.dtapp.repository.TiersUnifyRepository;
import com.dtapp.service.EmailService;
import org.springframework.data.domain.Sort;
import org.springframework.stereotype.Controller;
import org.springframework.ui.Model;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.multipart.MultipartFile;

import java.util.Arrays;
import java.util.List;

@Controller
public class DematController {

    private final RattachementBlRepository rattachementBlRepository;
    private final TiersUnifyRepository tiersUnifyRepository;
    private final SatisfactionInfoRepository satisfactionInfoRepository;
    private final SatisfactionGeneralRepository satisfactionGeneralRepository;
    private final SatisfactionFacturationRepository satisfactionFacturationRepository;
    private final SatisfactionBaeRepository satisfactionBaeRepository;
    private final SatisfactionAccueilRepository satisfactionAccueilRepository;
    private final SatisfactionLivraisonRepository satisfactionLivraisonRepository;
    private final SatisfactionCommunicationRepository satisfactionCommunicationRepository;
    private final EmailService emailService;

    public DematController(RattachementBlRepository rattachementBlRepository,
                           TiersUnifyRepository tiersUnifyRepository,
                           SatisfactionInfoRepository satisfactionInfoRepository,
                           SatisfactionGeneralRepository satisfactionGeneralRepository,
                           SatisfactionFacturationRepository satisfactionFacturationRepository,
                           SatisfactionBaeRepository satisfactionBaeRepository,
                           SatisfactionAccueilRepository satisfactionAccueilRepository,
                           SatisfactionLivraisonRepository satisfactionLivraisonRepository,
                           SatisfactionCommunicationRepository satisfactionCommunicationRepository,
                           EmailService emailService) {
        this.rattachementBlRepository = rattachementBlRepository;
        this.tiersUnifyRepository = tiersUnifyRepository;
        this.satisfactionInfoRepository = satisfactionInfoRepository;
        this.satisfactionGeneralRepository = satisfactionGeneralRepository;
        this.satisfactionFacturationRepository = satisfactionFacturationRepository;
        this.satisfactionBaeRepository = satisfactionBaeRepository;
        this.satisfactionAccueilRepository = satisfactionAccueilRepository;
        this.satisfactionLivraisonRepository = satisfactionLivraisonRepository;
        this.satisfactionCommunicationRepository = satisfactionCommunicationRepository;
        this.emailService = emailService;
    }

    @GetMapping("/demat")
    public String dematHome() {
        return "demat/index";
    }

    @GetMapping("/demat/validation")
    public String dematValidation(Model model) {
        List<TiersUnify> tiersList = tiersUnifyRepository.findAll(
                Sort.by(Sort.Direction.ASC, "raisonSociale"));
        model.addAttribute("tiersList", tiersList);
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

        // Rechercher le compte_ipaki associé à la raison sociale sélectionnée
        String compteIpaki = tiersUnifyRepository.findAll().stream()
                .filter(t -> maisonTransit != null && maisonTransit.equals(t.getRaisonSociale()))
                .map(TiersUnify::getCompteIpaki)
                .filter(c -> c != null && !c.isBlank())
                .findFirst()
                .orElse(null);

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
        emailService.sendValidationNotification(bl, compteIpaki, attachments);

        return "redirect:/demat/validation?success=true";
    }

    @GetMapping("/demat/remise")
    public String dematRemise(Model model) {
        List<TiersUnify> tiersList = tiersUnifyRepository.findAll(
                Sort.by(Sort.Direction.ASC, "raisonSociale"));
        model.addAttribute("tiersList", tiersList);
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

        String compteIpaki = tiersUnifyRepository.findAll().stream()
                .filter(t -> maisonTransit != null && maisonTransit.equals(t.getRaisonSociale()))
                .map(TiersUnify::getCompteIpaki)
                .filter(c -> c != null && !c.isBlank())
                .findFirst()
                .orElse(null);

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
        emailService.sendRemiseNotification(bl, compteIpaki, attachments);

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

        SatisfactionInfo info = new SatisfactionInfo();
        info.setNom(nom);
        info.setTelephone(telephone);
        info.setEmail(email);
        info = satisfactionInfoRepository.save(info);

        SatisfactionGeneral general = new SatisfactionGeneral();
        general.setSatisfactionInfo(info);
        general.setVolume(volume);
        general.setAnciennete(anciennete);
        satisfactionGeneralRepository.save(general);

        SatisfactionFacturation facturation = new SatisfactionFacturation();
        facturation.setSatisfactionInfo(info);
        facturation.setDelaisFacturation(delaisFacturation);
        facturation.setNotifications(notifications);
        facturation.setReactiviteFacture(reactiviteFacture);
        facturation.setUsagePlateforme(usagePlateforme);
        facturation.setUsagePlateformeDetail(usagePlateformeDetail);
        facturation.setFacilitePlateforme(facilitePlateforme);
        facturation.setFonctionnalitesPlateforme(fonctionnalitesPlateforme);
        facturation.setFonctionnalitesPlateformeDetail(fonctionnalitesPlateformeDetail);
        facturation.setBugsPlateforme(bugsPlateforme);
        facturation.setBugsPlateformeDetail(bugsPlateformeDetail);
        facturation.setAssistancePlateforme(assistancePlateforme);
        facturation.setSuggestionsFacturation(suggestionsFacturation);
        satisfactionFacturationRepository.save(facturation);

        SatisfactionBae bae = new SatisfactionBae();
        bae.setSatisfactionInfo(info);
        bae.setDelaisBae(delaisBae);
        bae.setSuggestionsBae(suggestionsBae);
        bae.setSuggestionsBaeDetail(suggestionsBaeDetail);
        satisfactionBaeRepository.save(bae);

        SatisfactionAccueil accueil = new SatisfactionAccueil();
        accueil.setSatisfactionInfo(info);
        accueil.setAccueilLocaux(accueilLocaux);
        accueil.setPersonnelAccueil(personnelAccueil);
        accueil.setInfrastructures(infrastructures);
        accueil.setInfrastructuresDetail(infrastructuresDetail);
        satisfactionAccueilRepository.save(accueil);

        SatisfactionLivraison livraison = new SatisfactionLivraison();
        livraison.setSatisfactionInfo(info);
        livraison.setFluiditeLivraison(fluiditeLivraison);
        livraison.setHorairesLivraison(horairesLivraison);
        livraison.setHorairesLivraisonDetail(horairesLivraisonDetail);
        livraison.setRetardsLivraison(retardsLivraison);
        livraison.setRetardsLivraisonDetail(retardsLivraisonDetail);
        livraison.setCoordinationLivraison(coordinationLivraison);
        livraison.setAmeliorationsLivraison(ameliorationsLivraison);
        satisfactionLivraisonRepository.save(livraison);

        SatisfactionCommunication communication = new SatisfactionCommunication();
        communication.setSatisfactionInfo(info);
        communication.setCommunicationProactive(communicationProactive);
        communication.setAlertes(alertes);
        communication.setSuggestionsCommunication(suggestionsCommunication);
        communication.setRecommandationsGenerales(recommandationsGenerales);
        satisfactionCommunicationRepository.save(communication);

        return "redirect:/demat/satisfaction?success=true";
    }
}
