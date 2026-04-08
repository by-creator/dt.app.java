package com.dtapp.service;

import org.apache.poi.ss.usermodel.Cell;
import org.apache.poi.ss.usermodel.DataFormatter;
import org.apache.poi.ss.usermodel.Row;
import org.apache.poi.ss.usermodel.Sheet;
import org.apache.poi.ss.usermodel.Workbook;
import org.apache.poi.ss.usermodel.WorkbookFactory;
import org.junit.jupiter.api.Assumptions;
import org.junit.jupiter.api.Test;

import java.io.InputStream;
import java.nio.file.Files;
import java.nio.file.Path;
import java.nio.file.Paths;
import java.util.Comparator;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;
import java.util.Objects;
import java.util.stream.Collectors;
import java.util.stream.Stream;

import static org.junit.jupiter.api.Assertions.assertEquals;
import static org.junit.jupiter.api.Assertions.assertTrue;

class ManifestReferenceComparisonTest {

    private static final Path BASE_DIR = Paths.get("src", "main", "tests");

    @Test
    void parserMatchesReferenceFileForWeightAndVolumeColumns() throws Exception {
        Path txtPath = resolveFixtureOrSkip("ALL_DAKAR_BL_Extract2", ".TXT");
        Path xlsPath = resolveFixtureOrSkip("EXTRACTION", ".xls");

        EdiParser parser = new EdiParser();
        List<EdiRecord> records = parser.parse(txtPath.toString());

        Map<String, Map<String, Double>> actual = records.stream()
                .collect(Collectors.toMap(
                        this::buildKey,
                        this::extractActualValues,
                        (left, right) -> left,
                        LinkedHashMap::new
                ));

        Map<String, Map<String, Double>> expected = readReferenceRows(xlsPath);

        assertEquals(expected.size(), actual.size(),
                "Le nombre de lignes parsees ne correspond pas au fichier de reference.");

        for (Map.Entry<String, Map<String, Double>> entry : expected.entrySet()) {
            String key = entry.getKey();
            assertTrue(actual.containsKey(key), "Cle absente dans la sortie parser: " + key);

            Map<String, Double> expectedRow = entry.getValue();
            Map<String, Double> actualRow = actual.get(key);

            assertClose(expectedRow.get("BLVolume"), actualRow.get("BLVolume"), key + " / BLVolume");
            assertClose(expectedRow.get("BLWeight"), actualRow.get("BLWeight"), key + " / BLWeight");
            assertClose(expectedRow.get("BLItem Commodity Volume"), actualRow.get("BLItem Commodity Volume"),
                    key + " / BLItem Commodity Volume");
            assertClose(expectedRow.get("BLItem Commodity Weight"), actualRow.get("BLItem Commodity Weight"),
                    key + " / BLItem Commodity Weight");
        }
    }

    private Map<String, Map<String, Double>> readReferenceRows(Path xlsPath) throws Exception {
        try (InputStream inputStream = Files.newInputStream(xlsPath);
             Workbook workbook = WorkbookFactory.create(inputStream)) {
            Sheet sheet = workbook.getSheetAt(0);
            Row headerRow = sheet.getRow(0);
            DataFormatter formatter = new DataFormatter();

            Map<String, Integer> indexes = new LinkedHashMap<>();
            for (Cell cell : headerRow) {
                indexes.put(formatter.formatCellValue(cell).trim(), cell.getColumnIndex());
            }

            Map<String, Map<String, Double>> rows = new LinkedHashMap<>();
            for (int rowIndex = 1; rowIndex <= sheet.getLastRowNum(); rowIndex++) {
                Row row = sheet.getRow(rowIndex);
                if (row == null) {
                    continue;
                }
                String key = buildKey(
                        readCell(row, indexes.get("BL Number"), formatter),
                        readCell(row, indexes.get("BLItem YardItemNumber"), formatter)
                );
                if (key.equals("::")) {
                    continue;
                }
                rows.putIfAbsent(key, Map.of(
                        "BLVolume", parseDouble(readCell(row, indexes.get("BLVolume"), formatter)),
                        "BLWeight", parseDouble(readCell(row, indexes.get("BLWeight"), formatter)),
                        "BLItem Commodity Volume",
                        parseDouble(readCell(row, indexes.get("BLItem Commodity Volume"), formatter)),
                        "BLItem Commodity Weight",
                        parseDouble(readCell(row, indexes.get("BLItem Commodity Weight"), formatter))
                ));
            }
            return rows;
        }
    }

    private Map<String, Double> extractActualValues(EdiRecord record) {
        Map<String, String> data = new XlsRowProjection().project(record);
        return Map.of(
                "BLVolume", parseDouble(data.get("bl_volume")),
                "BLWeight", parseDouble(data.get("bl_weight")),
                "BLItem Commodity Volume", parseDouble(data.get("blitem_commodity_volume")),
                "BLItem Commodity Weight", parseDouble(data.get("blitem_commodity_weight"))
        );
    }

    private String buildKey(EdiRecord record) {
        return buildKey(
                record.data.getOrDefault("bl_number", ""),
                record.data.getOrDefault("blitem_yard_item_number", "")
        );
    }

    private String buildKey(String blNumber, String itemNumber) {
        return Objects.toString(blNumber, "").trim() + "::" + Objects.toString(itemNumber, "").trim();
    }

    private String readCell(Row row, Integer index, DataFormatter formatter) {
        if (index == null) {
            return "";
        }
        Cell cell = row.getCell(index);
        return cell == null ? "" : formatter.formatCellValue(cell).trim();
    }

    private double parseDouble(String value) {
        if (value == null || value.isBlank()) {
            return 0d;
        }
        try {
            return Double.parseDouble(value.trim().replace(',', '.'));
        } catch (NumberFormatException e) {
            return 0d;
        }
    }

    private void assertClose(double expected, double actual, String label) {
        assertEquals(expected, actual, 0.01d, label);
    }

    private Path resolveFixtureOrNull(String requiredNameFragment, String requiredExtension) {
        try (Stream<Path> paths = Files.list(BASE_DIR)) {
            return paths
                    .filter(Files::isRegularFile)
                    .filter(path -> path.getFileName().toString().toUpperCase().endsWith(requiredExtension.toUpperCase()))
                    .filter(path -> path.getFileName().toString().toUpperCase().contains(requiredNameFragment.toUpperCase()))
                    .sorted(Comparator.comparing(path -> path.getFileName().toString()))
                    .findFirst()
                    .orElse(null);
        } catch (Exception e) {
            return null;
        }
    }

    private Path resolveFixtureOrSkip(String requiredNameFragment, String requiredExtension) {
        Path path = resolveFixtureOrNull(requiredNameFragment, requiredExtension);
        Assumptions.assumeTrue(path != null,
                "Fichier de comparaison absent: " + requiredNameFragment + "*" + requiredExtension);
        return Objects.requireNonNull(path);
    }

    static class XlsRowProjection {
        Map<String, String> project(EdiRecord record) {
            Map<String, String> data = new LinkedHashMap<>(record.toArray());

            double rawWeight = parse(data.get("bl_weight"));
            data.put("bl_weight", rawWeight > 0
                    ? format(EdiRecord.roundTo(rawWeight * 1000, 2)) : null);

            double rawItemWeight = parse(data.get("blitem_commodity_weight"));
            boolean isVehicle = "VEHICULE".equals(data.get("blitem_yard_item_type"));
            if (rawItemWeight > 0) {
                data.put("blitem_commodity_weight", format(EdiRecord.roundTo(rawItemWeight * 1000, 2)));
            } else {
                data.put("blitem_commodity_weight", isVehicle ? "0" : null);
            }

            double rawVolume = parse(data.get("bl_volume"));
            data.put("bl_volume", rawVolume > 0
                    ? format(EdiRecord.roundTo(rawVolume, 3)) : "0");

            double rawItemVolume = parse(data.get("blitem_commodity_volume"));
            String itemType = data.getOrDefault("blitem_yard_item_type", "");
            if (rawItemVolume > 0) {
                data.put("blitem_commodity_volume", format(EdiRecord.roundTo(rawItemVolume, 3)));
            } else {
                data.put("blitem_commodity_volume",
                        ("CONTENEUR".equals(itemType) || "VEHICULE".equals(itemType)) ? "0" : null);
            }

            return data;
        }

        private double parse(String value) {
            if (value == null || value.isBlank()) {
                return 0d;
            }
            try {
                return Double.parseDouble(value.trim().replace(',', '.'));
            } catch (NumberFormatException e) {
                return 0d;
            }
        }

        private String format(double value) {
            if (value == Math.floor(value) && !Double.isInfinite(value)) {
                return String.valueOf((long) value);
            }
            return String.valueOf(value);
        }
    }
}
