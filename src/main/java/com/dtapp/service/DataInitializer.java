package com.dtapp.service;

import com.dtapp.entity.Authority;
import com.dtapp.entity.User;
import com.dtapp.repository.AuthorityRepository;
import com.dtapp.repository.UserRepository;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.boot.CommandLineRunner;
import org.springframework.security.crypto.password.PasswordEncoder;
import org.springframework.stereotype.Component;

@Component
public class DataInitializer implements CommandLineRunner {

    private static final Logger log = LoggerFactory.getLogger(DataInitializer.class);

    private static final String ADMIN_EMAIL    = "admin@dakar-terminal.com";
    private static final String ADMIN_USERNAME = "Admin";
    private static final String ADMIN_PASSWORD = "DakarTerminal2024!";

    private final UserRepository userRepository;
    private final AuthorityRepository authorityRepository;
    private final PasswordEncoder passwordEncoder;

    public DataInitializer(UserRepository userRepository,
                           AuthorityRepository authorityRepository,
                           PasswordEncoder passwordEncoder) {
        this.userRepository      = userRepository;
        this.authorityRepository = authorityRepository;
        this.passwordEncoder     = passwordEncoder;
    }

    @Override
    public void run(String... args) {
        if (userRepository.findByEmail(ADMIN_EMAIL).isPresent()) {
            log.info("Compte admin déjà présent — aucune action requise.");
            return;
        }

        User admin = new User();
        admin.setUsername(ADMIN_USERNAME);
        admin.setEmail(ADMIN_EMAIL);
        admin.setPassword(passwordEncoder.encode(ADMIN_PASSWORD));
        admin.setEnabled(true);
        admin = userRepository.save(admin);

        Authority role = new Authority();
        role.setUser(admin);
        role.setAuthority("ROLE_ADMIN");
        authorityRepository.save(role);

        log.info("Compte admin créé : {} / {}", ADMIN_EMAIL, ADMIN_PASSWORD);
    }
}
