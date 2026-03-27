package com.dtapp.service;

import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;

import java.io.FileWriter;
import java.io.IOException;
import java.util.List;
import java.util.Map;

@Service
@Slf4j
public class EdiExporter {

    /**
     * Exporte des enregistrements en format IFTMIN (EDIFACT)
     * @param records Liste des enregistrements à exporter
     * @param outputPath Chemin du fichier de sortie
     */
    public void export(List<Map<String, String>> records, String outputPath) {
        try (FileWriter writer = new FileWriter(outputPath)) {
            for (Map<String, String> record : records) {
                String iftminLine = convertToIftmin(record);
                writer.write(iftminLine);
                writer.write(System.lineSeparator());
            }
            log.info("IFTMIN file exported successfully: {}", outputPath);
        } catch (IOException e) {
            log.error("Error exporting IFTMIN file: {}", outputPath, e);
            throw new RuntimeException("Failed to export IFTMIN file", e);
        }
    }

    /**
     * Convertit un enregistrement en format IFTMIN
     * @param record Enregistrement à convertir
     * @return Ligne IFTMIN formatée
     */
    private String convertToIftmin(Map<String, String> record) {
        StringBuilder sb = new StringBuilder();

        // Format IFTMIN basique : champs séparés par le caractère EDIFACT
        // On utilise | comme séparateur pour la lisibilité
        boolean first = true;
        for (String value : record.values()) {
            if (!first) {
                sb.append("|");
            }
            sb.append(value.trim());
            first = false;
        }

        return sb.toString();
    }
}
