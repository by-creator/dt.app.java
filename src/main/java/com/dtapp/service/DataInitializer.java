package com.dtapp.service;

import com.dtapp.entity.*;
import com.dtapp.repository.*;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.boot.CommandLineRunner;
import org.springframework.boot.autoconfigure.condition.ConditionalOnProperty;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Component
@ConditionalOnProperty(name = "app.data-initializer.enabled", havingValue = "true", matchIfMissing = true)
public class DataInitializer implements CommandLineRunner {

    private static final Logger log = LoggerFactory.getLogger(DataInitializer.class);

    private static final String ADMIN_EMAIL        = "admin@dakar-terminal.com";
    private static final String ADMIN_USERNAME     = "Admin";
    private static final String ADMIN_PASSWORD     = "DakarTerminal2024!";
    private static final String DEFAULT_COMPAGNIE  = "DAKAR-TERMINAL";

    private static final String PASSWORD_STANDARD  = "DakarDT2025@@";
    private static final String PASSWORD_DIRECTION = "SenegalDT2026@@";

    private static final List<String> DEFAULT_AUTHORITIES = List.of(
            "ROLE_DIRECTION_GENERALE",
            "ROLE_DIRECTION_FINANCIERE",
            "ROLE_DIRECTION_EXPLOITATION",
            "ROLE_INFORMATIQUE",
            "ROLE_FACTURATION",
            "ROLE_CAISSE",
            "ROLE_CONTROLE_DE_GESTION",
            "ROLE_COMPTABILITE",
            "ROLE_SERVICE_GENERAUX",
            "ROLE_PLANIFICATION",
            "ROLE_RESSOURCES_HUMAINES",
            "ROLE_JURIDIQUE",
            "ROLE_OPERATIONS",
            "ROLE_QHSE",
            "ROLE_DOUANE"
    );

    // [username, email, authority]
    private static final String[][] DEFAULT_USERS = {
            {"Mohamed Ngom",         "mohamed.ngom@dakar-terminal.com",          "ROLE_FACTURATION"},
            {"Mamadou Diouf",        "mamadou.diouf16@dakar-terminal.com",        "ROLE_FACTURATION"},
            {"Aissata Ba",           "aissata.ba@dakar-terminal.com",             "ROLE_FACTURATION"},
            {"Basile Manga",         "basile.manga@dakar-terminal.com",           "ROLE_FACTURATION"},
            {"Maimouna Fall",        "maimouna.fall@dakar-terminal.com",          "ROLE_FACTURATION"},
            {"Ababacar Fall",        "ababacar.fall@dakar-terminal.com",          "ROLE_FACTURATION"},
            {"Fatoumata Yaya Gueye", "fatoumata-yaya.gueye@dakar-terminal.com",  "ROLE_FACTURATION"},
            {"Charles Sarr",         "charles.sarr@dakar-terminal.com",           "ROLE_FACTURATION"},
            {"Alioune Badara Sy",    "aliounebadara.sy@dakar-terminal.com",       "ROLE_FACTURATION"},
            {"Serigne Ndiaye",       "serigne.ndiaye@dakar-terminal.com",         "ROLE_CAISSE"},
            {"Marie Diop",           "marie.diop@dakar-terminal.com",             "ROLE_CAISSE"},
            {"Adama Ndiaye",         "adama.n@dakar-terminal.com",                "ROLE_CAISSE"},
            {"Assane Diouf",         "assane.diouf@dakar-terminal.com",           "ROLE_DIRECTION_GENERALE"},
            {"Clarisse Ngueabo",     "clarisse.ngueabo@dakar-terminal.com",       "ROLE_DIRECTION_FINANCIERE"},
            {"Philippe Napolitano",  "philippe.Napolitano@dakar-terminal.com",    "ROLE_DIRECTION_EXPLOITATION"},
    };

    // [nom, code]
    private static final String[][] DEFAULT_SERVICES = {
            {"VALIDATION",  "V"},
            {"FACTURATION", "F"},
            {"CAISSE",      "C"},
            {"BAD",         "B"},
    };

    // [numero, infos, serviceCode]
    private static final String[][] DEFAULT_GUICHETS = {
            {"1",  "Mohamed Ngom",        "F"},
            {"2",  "Mamadou Diouf",       "B"},
            {"3",  "Aissata Ba",          "V"},
            {"4",  "Basile Manga",        "F"},
            {"6",  "Maimouna Fall",       "F"},
            {"7",  "Ababacar Fall",       "V"},
            {"9",  "Fatoumata Yaya Gueye","V"},
            {"10", "Charles Sarr",        "B"},
            {"11", "Serigne Ndiaye",      "C"},
            {"12", "Marie Diop",          "C"},
            {"13", "Adama Ndiaye",        "C"},
    };

    // [nom, prenom, serviceCode, guichetNumero]
    private static final String[][] DEFAULT_AGENTS = {
            {"Ngom",   "Mohamed",       "F", "1"},
            {"Diouf",  "Mamadou",       "B", "2"},
            {"Ba",     "Aissata",       "V", "3"},
            {"Manga",  "Basile",        "F", "4"},
            {"Fall",   "Maimouna",      "F", "6"},
            {"Fall",   "Ababacar",      "V", "7"},
            {"Gueye",  "Fatoumata Yaya","V", "9"},
            {"Sarr",   "Charles",       "B", "10"},
            {"Ndiaye", "Serigne",       "C", "11"},
            {"Diop",   "Marie",         "C", "12"},
            {"Ndiaye", "Adama",         "C", "13"},
    };

    private final UserRepository userRepository;
    private final AuthorityRepository authorityRepository;
    private final AuthorityDefinitionRepository authorityDefinitionRepository;
    private final CompagnieRepository compagnieRepository;
    private final GfaServiceRepository gfaServiceRepository;
    private final GfaGuichetRepository gfaGuichetRepository;
    private final GfaAgentRepository gfaAgentRepository;
    private final PasswordEncoder passwordEncoder;

    public DataInitializer(UserRepository userRepository,
                           AuthorityRepository authorityRepository,
                           AuthorityDefinitionRepository authorityDefinitionRepository,
                           CompagnieRepository compagnieRepository,
                           GfaServiceRepository gfaServiceRepository,
                           GfaGuichetRepository gfaGuichetRepository,
                           GfaAgentRepository gfaAgentRepository,
                           PasswordEncoder passwordEncoder) {
        this.userRepository                = userRepository;
        this.authorityRepository           = authorityRepository;
        this.authorityDefinitionRepository = authorityDefinitionRepository;
        this.compagnieRepository           = compagnieRepository;
        this.gfaServiceRepository          = gfaServiceRepository;
        this.gfaGuichetRepository          = gfaGuichetRepository;
        this.gfaAgentRepository            = gfaAgentRepository;
        this.passwordEncoder               = passwordEncoder;
    }

    @Override
    @Transactional
    public void run(String... args) {
        // 1. Créer les authority definitions manquantes
        for (String name : DEFAULT_AUTHORITIES) {
            if (!authorityDefinitionRepository.existsByName(name)) {
                AuthorityDefinition def = new AuthorityDefinition();
                def.setName(name);
                authorityDefinitionRepository.save(def);
                log.info("AuthorityDefinition créée : {}", name);
            }
        }

        // 2. Créer la compagnie DAKAR-TERMINAL si elle n'existe pas
        Compagnie dakarTerminal = compagnieRepository.findByName(DEFAULT_COMPAGNIE)
                .orElseGet(() -> {
                    Compagnie c = new Compagnie();
                    c.setName(DEFAULT_COMPAGNIE);
                    Compagnie saved = compagnieRepository.save(c);
                    log.info("Compagnie créée : {}", DEFAULT_COMPAGNIE);
                    return saved;
                });

        // 3. Créer le compte admin s'il n'existe pas, ou rattacher à la compagnie s'il en manque une
        userRepository.findByEmail(ADMIN_EMAIL).ifPresentOrElse(
                admin -> {
                    if (admin.getCompagnie() == null) {
                        admin.setCompagnie(dakarTerminal);
                        userRepository.save(admin);
                        log.info("Compagnie '{}' rattachée au compte admin existant.", DEFAULT_COMPAGNIE);
                    } else {
                        Integer adminCompagnieId = admin.getCompagnie().getId();
                        String compagnieName = java.util.Objects.equals(dakarTerminal.getId(), adminCompagnieId)
                                ? dakarTerminal.getName()
                                : compagnieRepository.findById(java.util.Objects.requireNonNull(adminCompagnieId))
                                        .map(Compagnie::getName)
                                        .orElse("compagnie inconnue");
                        log.info("Compte admin déjà présent et rattaché à '{}'.", compagnieName);
                    }
                },
                () -> {
                    User admin = new User();
                    admin.setUsername(ADMIN_USERNAME);
                    admin.setEmail(ADMIN_EMAIL);
                    admin.setPassword(passwordEncoder.encode(ADMIN_PASSWORD));
                    admin.setEnabled(true);
                    admin.setCompagnie(dakarTerminal);
                    admin = userRepository.save(admin);

                    Authority role = new Authority();
                    role.setUser(admin);
                    role.setAuthority("ROLE_ADMIN");
                    authorityRepository.save(role);

                    log.info("Compte admin créé : {} / {} — compagnie : {}", ADMIN_EMAIL, ADMIN_PASSWORD, DEFAULT_COMPAGNIE);
                }
        );

        // 4. Créer les utilisateurs par défaut
        for (String[] u : DEFAULT_USERS) {
            String username  = u[0];
            String email     = u[1];
            String authority = u[2];

            if (userRepository.findByEmail(email).isEmpty()) {
                String rawPassword = authority.equals("ROLE_FACTURATION") || authority.equals("ROLE_CAISSE")
                        ? PASSWORD_STANDARD
                        : PASSWORD_DIRECTION;

                User user = new User();
                user.setUsername(username);
                user.setEmail(email);
                user.setPassword(passwordEncoder.encode(rawPassword));
                user.setEnabled(true);
                user.setCompagnie(dakarTerminal);
                user = userRepository.save(user);

                Authority auth = new Authority();
                auth.setUser(user);
                auth.setAuthority(authority);
                authorityRepository.save(auth);

                log.info("Utilisateur créé : {} ({})", email, authority);
            }
        }

        // 5. Créer les services GFA
        for (String[] s : DEFAULT_SERVICES) {
            String nom  = s[0];
            String code = s[1];

            if (!gfaServiceRepository.existsByCodeIgnoreCase(code)) {
                GfaService service = new GfaService();
                service.setNom(nom);
                service.setCode(code);
                gfaServiceRepository.save(service);
                log.info("Service GFA créé : {} ({})", nom, code);
            }
        }

        // 6. Créer les guichets GFA
        for (String[] g : DEFAULT_GUICHETS) {
            String numero      = g[0];
            String infos       = g[1];
            String serviceCode = g[2];

            if (!gfaGuichetRepository.existsByNumero(numero)) {
                GfaService service = gfaServiceRepository.findByCodeIgnoreCase(serviceCode).orElse(null);
                GfaGuichet guichet = new GfaGuichet();
                guichet.setNumero(numero);
                guichet.setInfos(infos);
                guichet.setService(service);
                gfaGuichetRepository.save(guichet);
                log.info("Guichet GFA créé : {} — {} ({})", numero, infos, serviceCode);
            }
        }

        // 7. Créer les agents GFA
        for (String[] a : DEFAULT_AGENTS) {
            String nom           = a[0];
            String prenom        = a[1];
            String serviceCode   = a[2];
            String guichetNumero = a[3];

            if (!gfaAgentRepository.existsByNomAndPrenom(nom, prenom)) {
                GfaService service = gfaServiceRepository.findByCodeIgnoreCase(serviceCode).orElse(null);
                GfaGuichet guichet = gfaGuichetRepository.findByNumero(guichetNumero).orElse(null);

                GfaAgent agent = new GfaAgent();
                agent.setNom(nom);
                agent.setPrenom(prenom);
                agent.setService(service);
                agent.setGuichet(guichet);
                gfaAgentRepository.save(agent);
                log.info("Agent GFA créé : {} {} — guichet {}", prenom, nom, guichetNumero);
            }
        }
    }
}
