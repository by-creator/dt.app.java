package com.dtapp.config;

import lombok.Data;
import org.springframework.boot.context.properties.ConfigurationProperties;
import org.springframework.stereotype.Component;

/**
 * Configuration pour les automatisations
 */
@Data
@Component
@ConfigurationProperties(prefix = "app.automation")
public class AutomationProperties {
    
    /**
     * Chemin de base pour les automatisations (par défaut: "automatisation")
     */
    private String basepath = "automatisation";
    
    /**
     * Timeout d'exécution en secondes (par défaut: 600 = 10 minutes)
     */
    private int timeout = 600;

    /**
     * Token interne utilise par les scripts d'automatisation pour appeler l'application.
     */
    private String internalToken = "dt-app-internal-automation-token";
}
