package com.dtapp.service;

import lombok.extern.slf4j.Slf4j;
import org.apache.poi.ss.usermodel.*;
import org.apache.poi.xssf.usermodel.XSSFWorkbook;
import org.springframework.stereotype.Service;

import java.io.FileOutputStream;
import java.io.IOException;
import java.util.List;
import java.util.Map;

/**
 * Génère un fichier Excel (.xlsx) à partir d'une liste d'EdiRecord.
 * Traduit exactement la logique PHP de App\Services\XlsExporter.
 */
@Service
@Slf4j
public class XlsExporter {

    public void export(List<EdiRecord> records, Map<String, String> headers, String outputPath) {
        try (Workbook workbook = new XSSFWorkbook()) {
            Sheet sheet = workbook.createSheet("Bl importer");

            // ── Ligne 1 : en-têtes ────────────────────────────────────────────
            CellStyle headerStyle = workbook.createCellStyle();
            Font headerFont = workbook.createFont();
            headerFont.setBold(true);
            headerFont.setColor(IndexedColors.WHITE.getIndex());
            headerStyle.setFont(headerFont);
            headerStyle.setFillPattern(FillPatternType.SOLID_FOREGROUND);
            headerStyle.setFillForegroundColor(IndexedColors.BLUE.getIndex());
            headerStyle.setAlignment(HorizontalAlignment.CENTER);

            Row headerRow = sheet.createRow(0);
            int col = 0;
            for (String displayName : headers.values()) {
                Cell cell = headerRow.createCell(col++);
                cell.setCellValue(displayName);
                cell.setCellStyle(headerStyle);
            }

            // ── Lignes de données (à partir de la ligne 2) ───────────────────
            int rowIdx = 1;
            for (EdiRecord record : records) {
                Map<String, String> data = new java.util.LinkedHashMap<>(record.toArray());

                // ── Conversion poids : tonnes → kg ──────────────────────────
                double rawWeight = parseDouble(data.get("bl_weight"));
                data.put("bl_weight", rawWeight > 0
                        ? formatNum(EdiRecord.roundTo(rawWeight * 1000, 2)) : null);

                double rawItemWeight = parseDouble(data.get("blitem_commodity_weight"));
                boolean isVehicle = "VEHICULE".equals(data.get("blitem_yard_item_type"));
                double itemWeightKg = 0;
                if (rawItemWeight > 0) {
                    itemWeightKg = EdiRecord.roundTo(rawItemWeight * 1000, 2);
                    data.put("blitem_commodity_weight", formatNum(itemWeightKg));
                } else {
                    data.put("blitem_commodity_weight", isVehicle ? "0" : null);
                }

                // ── Conversion volume : m³ → float ──────────────────────────
                double rawVolume = parseDouble(data.get("bl_volume"));
                data.put("bl_volume", rawVolume > 0
                        ? formatNum(EdiRecord.roundTo(rawVolume, 3)) : "0");

                double rawItemVol = parseDouble(data.get("blitem_commodity_volume"));
                String itemType = data.getOrDefault("blitem_yard_item_type", "");
                if (rawItemVol > 0) {
                    data.put("blitem_commodity_volume", formatNum(EdiRecord.roundTo(rawItemVol, 3)));
                } else {
                    data.put("blitem_commodity_volume",
                            ("CONTENEUR".equals(itemType) || "VEHICULE".equals(itemType)) ? "0" : null);
                }

                // ── Recalcul commodity véhicule depuis le poids individuel ──
                if (isVehicle) {
                    String commodity;
                    if      (itemWeightKg <= 0)     commodity = "VEH 0-1500Kgs";
                    else if (itemWeightKg <= 1500)  commodity = "VEH 0-1500Kgs";
                    else if (itemWeightKg <= 3000)  commodity = "VEH 1501-3000Kgs";
                    else if (itemWeightKg <= 6000)  commodity = "VEH 3001-6000Kgs";
                    else if (itemWeightKg <= 9000)  commodity = "VEH 6001-9000Kgs";
                    else if (itemWeightKg <= 30000) commodity = "VEH 9001-30000Kgs";
                    else                            commodity = "VEH +30000Kgs";
                    data.put("blitem_commodity", commodity);
                }

                // ── Champs vidés dans le XLS ─────────────────────────────────
                data.put("consignee",                 "");
                data.put("shipper_name",              "");
                data.put("final_destination_country", "");
                data.put("transshipment_port_1",      "");
                data.put("transshipment_port_2",      "");

                // ── Écriture des cellules dans l'ordre des headers ───────────
                Row row = sheet.createRow(rowIdx++);
                col = 0;
                for (String key : headers.keySet()) {
                    Cell cell = row.createCell(col++);
                    String value = data.get(key);
                    if (value == null || value.isEmpty()) {
                        // Cellule vide
                    } else {
                        // Tenter d'écrire comme nombre si possible
                        try {
                            cell.setCellValue(Double.parseDouble(value));
                        } catch (NumberFormatException e) {
                            cell.setCellValue(value);
                        }
                    }
                }
            }

            // ── Auto-size colonnes ────────────────────────────────────────────
            for (int i = 0; i < headers.size(); i++) {
                sheet.autoSizeColumn(i);
            }

            try (FileOutputStream fos = new FileOutputStream(outputPath)) {
                workbook.write(fos);
                log.info("Excel file exported: {}", outputPath);
            }
        } catch (IOException e) {
            log.error("Error exporting Excel file: {}", outputPath, e);
            throw new RuntimeException("Failed to export Excel file", e);
        }
    }

    private static double parseDouble(String s) {
        if (s == null || s.isEmpty()) return 0;
        try { return Double.parseDouble(s); } catch (NumberFormatException e) { return 0; }
    }

    private static String formatNum(double d) {
        if (d == Math.floor(d) && !Double.isInfinite(d)) {
            return String.valueOf((long) d);
        }
        return String.valueOf(d);
    }
}
