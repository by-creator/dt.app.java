package com.dtapp.service;

import com.dtapp.entity.Authority;
import com.dtapp.entity.Compagnie;
import com.dtapp.entity.User;
import com.dtapp.repository.AuthorityRepository;
import com.dtapp.repository.CompagnieRepository;
import com.dtapp.repository.UserRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.boot.CommandLineRunner;
import org.springframework.boot.autoconfigure.condition.ConditionalOnProperty;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Component;
import org.springframework.transaction.annotation.Transactional;

@Component
@ConditionalOnProperty(name = "app.data-initializer.enabled", havingValue = "true", matchIfMissing = true)
public class DataInitializer implements CommandLineRunner {

    private static final Logger log = LoggerFactory.getLogger(DataInitializer.class);

    private static final String ADMIN_EMAIL        = "admin@dakar-terminal.com";
    private static final String ADMIN_USERNAME     = "Admin";
    private static final String ADMIN_PASSWORD     = "DakarTerminal2024!";
    private static final String DEFAULT_COMPAGNIE  = "DAKAR-TERMINAL";

    private final UserRepository userRepository;
    private final AuthorityRepository authorityRepository;
    private final CompagnieRepository compagnieRepository;
    private final PasswordEncoder passwordEncoder;

    public DataInitializer(UserRepository userRepository,
                           AuthorityRepository authorityRepository,
                           CompagnieRepository compagnieRepository,
                           PasswordEncoder passwordEncoder) {
        this.userRepository      = userRepository;
        this.authorityRepository = authorityRepository;
        this.compagnieRepository = compagnieRepository;
        this.passwordEncoder     = passwordEncoder;
    }

    @Override
    @Transactional
    public void run(String... args) {
        // 1. Créer la compagnie DAKAR-TERMINAL si elle n'existe pas
        Compagnie dakarTerminal = compagnieRepository.findByName(DEFAULT_COMPAGNIE)
                .orElseGet(() -> {
                    Compagnie c = new Compagnie();
                    c.setName(DEFAULT_COMPAGNIE);
                    Compagnie saved = compagnieRepository.save(c);
                    log.info("Compagnie créée : {}", DEFAULT_COMPAGNIE);
                    return saved;
                });

        // 2. Créer le compte admin s'il n'existe pas, ou rattacher à la compagnie s'il en manque une
        userRepository.findByEmail(ADMIN_EMAIL).ifPresentOrElse(
                admin -> {
                    if (admin.getCompagnie() == null) {
                        admin.setCompagnie(dakarTerminal);
                        userRepository.save(admin);
                        log.info("Compagnie '{}' rattachée au compte admin existant.", DEFAULT_COMPAGNIE);
                    } else {
                        Integer compagnieId = admin.getCompagnie().getId();
                        String compagnieName = dakarTerminal.getId().equals(compagnieId)
                                ? dakarTerminal.getName()
                                : compagnieRepository.findById(compagnieId)
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
    }
}
