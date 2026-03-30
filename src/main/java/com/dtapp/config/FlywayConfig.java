package com.dtapp.config;

import org.flywaydb.core.Flyway;
import org.flywaydb.core.api.FlywayException;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.boot.autoconfigure.flyway.FlywayMigrationStrategy;
import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;

@Configuration
public class FlywayConfig {

    private static final Logger log = LoggerFactory.getLogger(FlywayConfig.class);

    @Bean
    public FlywayMigrationStrategy repairAndMigrate() {
        return (Flyway flyway) -> {
            try {
                // Try to repair, but don't fail if database is not accessible
                flyway.repair();
                log.info("Flyway repair completed successfully");
            } catch (FlywayException e) {
                log.warn("Flyway repair failed (database might not be initialized): {}", e.getMessage());
                // Continue anyway - baseline will be created on migrate
            }

            try {
                flyway.migrate();
                log.info("Flyway migration completed successfully");
            } catch (FlywayException e) {
                log.error("Flyway migration failed: {}", e.getMessage());
                throw new RuntimeException("Database migration failed. Ensure database is configured and accessible.", e);
            }
        };
    }
}
