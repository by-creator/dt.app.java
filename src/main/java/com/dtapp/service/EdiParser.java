package com.dtapp.service;

import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;

import java.io.BufferedReader;
import java.io.FileReader;
import java.io.IOException;
import java.util.*;

@Service
@Slf4j
public class EdiParser {

    private List<String> headers;

    /**
     * Parse un fichier TXT/EDIFACT et retourne les enregistrements
     * @param filePath Chemin du fichier à parser
     * @return Liste des enregistrements parsés
     */
    public List<Map<String, String>> parse(String filePath) {
        List<Map<String, String>> records = new ArrayList<>();
        headers = new ArrayList<>();

        try (BufferedReader reader = new BufferedReader(new FileReader(filePath))) {
            String line;
            int lineNumber = 0;

            while ((line = reader.readLine()) != null) {
                lineNumber++;

                // Ignorer les lignes vides
                if (line.trim().isEmpty()) {
                    continue;
                }

                // La première ligne est l'en-tête
                if (lineNumber == 1) {
                    parseHeaders(line);
                    continue;
                }

                // Parser chaque ligne de données
                Map<String, String> record = parseLine(line);
                if (!record.isEmpty()) {
                    records.add(record);
                }
            }

            log.info("Parsed {} records from file: {}", records.size(), filePath);
        } catch (IOException e) {
            log.error("Error parsing file: {}", filePath, e);
        }

        return records;
    }

    /**
     * Parse l'en-tête du fichier
     * @param headerLine Ligne d'en-tête
     */
    private void parseHeaders(String headerLine) {
        String[] parts = headerLine.split("\\|");
        headers.clear();
        for (String part : parts) {
            headers.add(part.trim());
        }
    }

    /**
     * Parse une ligne de données
     * @param line Ligne à parser
     * @return Map avec les données
     */
    private Map<String, String> parseLine(String line) {
        Map<String, String> record = new LinkedHashMap<>();
        String[] values = line.split("\\|");

        for (int i = 0; i < values.length && i < headers.size(); i++) {
            record.put(headers.get(i), values[i].trim());
        }

        return record;
    }

    /**
     * Retourne les en-têtes du fichier parsé
     * @return Liste des en-têtes
     */
    public List<String> getHeaders() {
        return new ArrayList<>(headers);
    }
}
