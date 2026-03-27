package com.dtapp.service;

import lombok.extern.slf4j.Slf4j;
import org.apache.poi.ss.usermodel.*;
import org.apache.poi.xssf.usermodel.XSSFWorkbook;
import org.springframework.stereotype.Service;

import java.io.FileOutputStream;
import java.io.IOException;
import java.util.List;
import java.util.Map;

@Service
@Slf4j
public class XlsExporter {

    /**
     * Exporte des enregistrements en fichier Excel
     * @param records Liste des enregistrements à exporter
     * @param headers En-têtes de colonnes
     * @param outputPath Chemin du fichier de sortie
     */
    public void export(List<Map<String, String>> records, List<String> headers, String outputPath) {
        try (Workbook workbook = new XSSFWorkbook()) {
            Sheet sheet = workbook.createSheet("Data");

            // Créer les en-têtes
            createHeaderRow(sheet, headers);

            // Créer les lignes de données
            createDataRows(sheet, records, headers);

            // Auto-ajuster les colonnes
            for (int i = 0; i < headers.size(); i++) {
                sheet.autoSizeColumn(i);
            }

            // Écrire le fichier
            try (FileOutputStream fileOut = new FileOutputStream(outputPath)) {
                workbook.write(fileOut);
                log.info("Excel file exported successfully: {}", outputPath);
            }
        } catch (IOException e) {
            log.error("Error exporting Excel file: {}", outputPath, e);
            throw new RuntimeException("Failed to export Excel file", e);
        }
    }

    /**
     * Crée la ligne d'en-tête
     * @param sheet Feuille de calcul
     * @param headers En-têtes
     */
    private void createHeaderRow(Sheet sheet, List<String> headers) {
        Row headerRow = sheet.createRow(0);

        CellStyle headerStyle = sheet.getWorkbook().createCellStyle();
        Font headerFont = sheet.getWorkbook().createFont();
        headerFont.setBold(true);
        headerFont.setColor(IndexedColors.WHITE.getIndex());
        headerStyle.setFont(headerFont);
        headerStyle.setFill(FillPatternType.SOLID_FOREGROUND);
        headerStyle.setFillForegroundColor(IndexedColors.BLUE.getIndex());
        headerStyle.setAlignment(HorizontalAlignment.CENTER);
        headerStyle.setVerticalAlignment(VerticalAlignment.CENTER);

        for (int i = 0; i < headers.size(); i++) {
            Cell cell = headerRow.createCell(i);
            cell.setCellValue(headers.get(i));
            cell.setCellStyle(headerStyle);
        }
    }

    /**
     * Crée les lignes de données
     * @param sheet Feuille de calcul
     * @param records Enregistrements
     * @param headers En-têtes
     */
    private void createDataRows(Sheet sheet, List<Map<String, String>> records, List<String> headers) {
        CellStyle dataStyle = sheet.getWorkbook().createCellStyle();
        dataStyle.setAlignment(HorizontalAlignment.LEFT);
        dataStyle.setVerticalAlignment(VerticalAlignment.TOP);
        dataStyle.setWrapText(true);

        for (int rowIndex = 0; rowIndex < records.size(); rowIndex++) {
            Row row = sheet.createRow(rowIndex + 1);
            Map<String, String> record = records.get(rowIndex);

            for (int colIndex = 0; colIndex < headers.size(); colIndex++) {
                String headerName = headers.get(colIndex);
                String value = record.getOrDefault(headerName, "");

                Cell cell = row.createCell(colIndex);
                cell.setCellValue(value);
                cell.setCellStyle(dataStyle);
            }
        }
    }
}
