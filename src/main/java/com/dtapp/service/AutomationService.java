package com.dtapp.service;

import com.dtapp.config.AutomationProperties;
import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import java.io.*;
import java.nio.file.*;
import java.time.LocalDateTime;
import java.util.*;
import java.util.stream.Collectors;

/**
 * Service pour gérer les automatisations
 */
@Slf4j
@Service
public class AutomationService {

    private final AutomationProperties automationProperties;
    private final ObjectMapper objectMapper = new ObjectMapper();
    private final Map<String, AutomationInfo> automationCache = new HashMap<>();
    private final Map<String, AutomationResult> lastResults = new HashMap<>();

    public AutomationService(AutomationProperties automationProperties) {
        this.automationProperties = automationProperties;
    }

    /**
     * Récupère la liste de toutes les automatisations disponibles
     */
    public List<AutomationInfo> getAvailableAutomations() {
        return scanAutomations();
    }

    /**
     * Scanne le dossier automatisations et découvre les scripts Python disponibles
     */
    private List<AutomationInfo> scanAutomations() {
        List<AutomationInfo> automations = new ArrayList<>();
        
        try {
            Path basePath = Paths.get(automationProperties.getBasepath());
            if (!Files.exists(basePath)) {
                log.warn("Dossier automatisations non trouvé: {}", automationProperties.getBasepath());
                return automations;
            }

            // Parcourir les dossiers de catégories (ex: ies, bad, facture, etc.)
            try (DirectoryStream<Path> categories = Files.newDirectoryStream(basePath)) {
                for (Path category : categories) {
                    if (Files.isDirectory(category)) {
                        String categoryName = category.getFileName().toString();
                        scanCategory(category, categoryName, automations);
                    }
                }
            }

            return automations.stream()
                    .sorted(Comparator.comparing(AutomationInfo::getCategory)
                            .thenComparing(AutomationInfo::getName))
                    .collect(Collectors.toList());

        } catch (IOException e) {
            log.error("Erreur lors du scan des automatisations", e);
            return automations;
        }
    }

    /**
     * Scanne une catégorie d'automatisations
     */
    private void scanCategory(Path categoryPath, String categoryName, List<AutomationInfo> automations) {
        try (DirectoryStream<Path> subDirs = Files.newDirectoryStream(categoryPath)) {
            for (Path subDir : subDirs) {
                if (Files.isDirectory(subDir)) {
                    String subDirName = subDir.getFileName().toString();
                    
                    // Chercher les scripts Python dans ce sous-dossier
                    try (DirectoryStream<Path> files = Files.newDirectoryStream(subDir, "*.py")) {
                        for (Path file : files) {
                            if (Files.isRegularFile(file)) {
                                createAutomationInfo(file, categoryName, subDirName, automations);
                            }
                        }
                    }
                }
            }
        } catch (IOException e) {
            log.warn("Erreur lors du scan de la catégorie: {}", categoryName, e);
        }
    }

    /**
     * Crée une entrée AutomationInfo pour un script Python
     */
    private void createAutomationInfo(Path scriptPath, String category, String subCategory, 
                                     List<AutomationInfo> automations) {
        try {
            String scriptName = scriptPath.getFileName().toString();
            String id = category + ":" + subCategory + ":" + scriptName;
            
            // Chercher un fichier README ou config pour la description
            String description = getAutomationDescription(scriptPath);
            
            List<Map<String, String>> params = getAutomationParams(scriptPath);

            AutomationInfo info = new AutomationInfo(
                    id,
                    subCategory,
                    description,
                    category,
                    scriptPath.toString(),
                    AutomationStatus.IDLE,
                    lastResults.containsKey(id) ? lastResults.get(id).getExecutedAt() : null,
                    lastResults.containsKey(id) ? "SUCCESS" : null,
                    lastResults.containsKey(id) ? lastResults.get(id).getErrorMessage() : null,
                    lastResults.containsKey(id) ? lastResults.get(id).getScreenshotPath() : null,
                    params
            );
            
            automations.add(info);
            automationCache.put(id, info);
            
        } catch (Exception e) {
            log.warn("Erreur lors de la création d'info d'automatisation: {}", scriptPath, e);
        }
    }

    /**
     * Récupère la description d'une automatisation
     */
    private String getAutomationDescription(Path scriptPath) {
        try {
            // Chercher README.md dans le même dossier
            Path readmePath = scriptPath.getParent().resolve("README.md");
            if (Files.exists(readmePath)) {
                List<String> lines = Files.readAllLines(readmePath);
                return lines.stream()
                        .filter(line -> !line.isEmpty() && !line.startsWith("#"))
                        .limit(1)
                        .findFirst()
                        .orElse("Automatisation " + scriptPath.getFileName());
            }
            
            // Sinon, extraire la première ligne du docstring du Python
            List<String> lines = Files.readAllLines(scriptPath);
            for (String line : lines) {
                if (line.contains("\"\"\"") || line.contains("'''")) {
                    return line.replace("\"\"\"", "").replace("'''", "").trim();
                }
            }
            
            return "Automatisation " + scriptPath.getFileName();
        } catch (Exception e) {
            return "Automatisation";
        }
    }

    private List<Map<String, String>> getAutomationParams(Path scriptPath) {
        try {
            Path paramsPath = scriptPath.getParent().resolve("params.json");
            if (Files.exists(paramsPath)) {
                return objectMapper.readValue(paramsPath.toFile(),
                        new TypeReference<List<Map<String, String>>>() {});
            }
        } catch (Exception e) {
            log.warn("Erreur lors de la lecture de params.json: {}", scriptPath, e);
        }
        return Collections.emptyList();
    }

    /**
     * Exécute une automatisation
     */
    public AutomationResult executeAutomation(String automationId) {
        return executeAutomation(automationId, Collections.emptyMap());
    }

    public AutomationResult executeAutomation(String automationId, Map<String, String> params) {
        long startTime = System.currentTimeMillis();
        
        try {
            AutomationInfo automation = automationCache.get(automationId);
            if (automation == null) {
                automation = scanAutomations().stream()
                        .filter(a -> a.getId().equals(automationId))
                        .findFirst()
                        .orElse(null);
                
                if (automation == null) {
                    return createErrorResult(automationId, "Automatisation non trouvée");
                }
            }

            // Vérifier que le script existe
            Path scriptPath = Paths.get(automation.getScriptPath());
            if (!Files.exists(scriptPath)) {
                return createErrorResult(automationId, "Script non trouvé: " + automation.getScriptPath());
            }

            // Créer le dossier des logs/résultats
            Path resultsDir = scriptPath.getParent().resolve("results");
            Files.createDirectories(resultsDir);

            // Préparer la commande
            String pythonCmd = getPythonCommand();
            ProcessBuilder pb = new ProcessBuilder(pythonCmd, scriptPath.toString());
            pb.directory(scriptPath.getParent().toFile());
            if (params != null && !params.isEmpty()) {
                pb.environment().putAll(params);
            }

            // Capturer stdout et stderr
            pb.redirectErrorStream(true);
            
            Process process = pb.start();
            
            // Lire la sortie
            StringBuilder output = new StringBuilder();
            try (BufferedReader reader = new BufferedReader(
                    new InputStreamReader(process.getInputStream()))) {
                String line;
                while ((line = reader.readLine()) != null) {
                    output.append(line).append("\n");
                    log.debug("[{}] {}", automationId, line);
                }
            }

            // Attendre la fin
            boolean completed = process.waitFor(automationProperties.getTimeout(), java.util.concurrent.TimeUnit.SECONDS);
            if (!completed) {
                process.destroyForcibly();
                return createErrorResult(automationId, "Timeout d'exécution après " + automationProperties.getTimeout() + " secondes");
            }

            int exitCode = process.exitValue();
            long executionTime = System.currentTimeMillis() - startTime;

            // Chercher les captures d'écran générées
            String screenshotPath = findScreenshot(resultsDir);

            // Enregistrer les logs
            String logPath = saveExecutionLog(resultsDir, automationId, output.toString());

            // Créer le résultat
            boolean success = exitCode == 0;
            AutomationResult result = new AutomationResult(
                    automationId,
                    success,
                    success ? "Exécution réussie" : "Exécution échouée (code: " + exitCode + ")",
                    success ? null : parseErrorMessage(output.toString()),
                    screenshotPath,
                    logPath,
                    executionTime,
                    LocalDateTime.now()
            );

            lastResults.put(automationId, result);
            return result;

        } catch (Exception e) {
            log.error("Erreur lors de l'exécution de l'automatisation: {}", automationId, e);
            return createErrorResult(automationId, "Erreur: " + e.getMessage());
        }
    }

    /**
     * Récupère la commande Python appropriée
     */
    private String getPythonCommand() {
        String osName = System.getProperty("os.name").toLowerCase();
        if (osName.contains("win")) {
            return "python";
        }
        return "python3";
    }

    /**
     * Trouve une capture d'écran générée
     */
    private String findScreenshot(Path resultsDir) {
        try {
            try (DirectoryStream<Path> files = Files.newDirectoryStream(resultsDir, "*{.png,.jpg,.jpeg}")) {
                for (Path file : files) {
                    if (Files.isRegularFile(file)) {
                        return file.toFile().getAbsolutePath();
                    }
                }
            }
        } catch (IOException e) {
            log.debug("Erreur lors de la recherche de captures", e);
        }
        return null;
    }

    /**
     * Enregistre les logs d'exécution
     */
    private String saveExecutionLog(Path resultsDir, String automationId, String output) {
        try {
            String timestamp = java.time.format.DateTimeFormatter.ofPattern("yyyyMMdd_HHmmss")
                    .format(LocalDateTime.now());
            Path logPath = resultsDir.resolve("execution_" + timestamp + ".log");
            Files.write(logPath, output.getBytes());
            return logPath.toFile().getAbsolutePath();
        } catch (IOException e) {
            log.warn("Erreur lors de la sauvegarde des logs", e);
            return null;
        }
    }

    /**
     * Crée un résultat d'erreur
     */
    private AutomationResult createErrorResult(String automationId, String error) {
        AutomationResult result = new AutomationResult(
                automationId,
                false,
                null,
                error,
                null,
                null,
                0,
                LocalDateTime.now()
        );
        lastResults.put(automationId, result);
        return result;
    }

    /**
     * Extrait le message d'erreur de la sortie
     */
    private String parseErrorMessage(String output) {
        String[] lines = output.split("\n");
        for (int i = lines.length - 1; i >= 0; i--) {
            String line = lines[i].trim();
            if (!line.isEmpty() && (line.startsWith("✗") || line.contains("Error") || line.contains("Erreur"))) {
                return line;
            }
        }
        return "Erreur non déterminée";
    }

    /**
     * Récupère le résultat de la dernière exécution
     */
    public AutomationResult getLastResult(String automationId) {
        return lastResults.get(automationId);
    }

    /**
     * Récupère l'automatisation par ID
     */
    public AutomationInfo getAutomation(String automationId) {
        return automationCache.get(automationId);
    }
}
