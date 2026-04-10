package com.dtapp.service;

import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.nio.ByteBuffer;
import java.nio.charset.CharacterCodingException;
import java.nio.charset.Charset;
import java.nio.charset.CharsetDecoder;
import java.nio.charset.CodingErrorAction;
import java.nio.charset.StandardCharsets;
import java.nio.file.Files;
import java.nio.file.Paths;
import java.util.*;
import java.util.regex.Pattern;
import java.util.stream.Collectors;

/**
 * Parse un fichier TXT à largeur fixe (manifeste) et retourne une liste d'EdiRecord.
 * Traduit exactement la logique PHP de App\Services\EdiParser.
 */
@Service
@Slf4j
public class EdiParser {

    private static final Pattern GRADE_CODE_1 = Pattern.compile("^[YN]?[A-Z]*GR(?:AD|ADE|ADES?)$");
    private static final Pattern GRADE_CODE_2 = Pattern.compile("^[A-Z]*GRADE?$");

    public List<EdiRecord> parse(String filePath) {
        byte[] content;
        try {
            content = Files.readAllBytes(Paths.get(filePath));
        } catch (IOException e) {
            throw new RuntimeException("Fichier introuvable : " + filePath, e);
        }

        // Détecter l'encodage UNE SEULE FOIS. Ne pas convertir tout le contenu :
        // les offsets fixes sont en octets (Windows-1252 = 1 octet/char).
        // Convertir avant le découpage décalerait tous les offsets après le 1er
        // caractère multi-octets UTF-8.
        Charset srcCharset = isValidUtf8(content) ? StandardCharsets.UTF_8
                                                   : Charset.forName("Windows-1252");

        List<EdiRecord> records = new ArrayList<>();

        // Découper par \n (0x0A) en gardant les bytes bruts
        int start = 0;
        for (int i = 0; i <= content.length; i++) {
            if (i == content.length || content[i] == (byte) '\n') {
                int len = i - start;
                // Supprimer le \r final éventuel
                if (len > 0 && content[start + len - 1] == (byte) '\r') len--;

                if (len >= 400) {
                    // Vérifier que les 5 premiers bytes sont alphanumériques ASCII
                    byte[] lineBytes = Arrays.copyOfRange(content, start, start + len);
                    String type = new String(lineBytes, 0, Math.min(5, lineBytes.length),
                                            StandardCharsets.US_ASCII).trim();
                    if (!type.isEmpty() && type.chars().allMatch(c -> (c >= 'A' && c <= 'Z') || (c >= '0' && c <= '9'))) {
                        records.add(EdiRecord.fromLine(lineBytes, srcCharset));
                    }
                }
                start = i + 1;
            }
        }

        // ── Agrégation des BLs multi-lignes ──────────────────────────────────
        // EdiRecord::fromLine stocke poids en tonnes (÷1 000 000) et volumes en m³ (÷1000).
        // Les agrégats restent dans ces unités internes.

        Map<String, Long> blCounts = records.stream()
                .collect(Collectors.groupingBy(r -> r.data.getOrDefault("bl_number", ""),
                         Collectors.counting()));

        Map<String, Double> blVolumes = records.stream()
                .collect(Collectors.groupingBy(r -> r.data.getOrDefault("bl_number", ""),
                         Collectors.summingDouble(r -> parseDouble(r.data.get("bl_volume")))));

        Map<String, Double> blWeights = records.stream()
                .collect(Collectors.groupingBy(r -> r.data.getOrDefault("bl_number", ""),
                         Collectors.summingDouble(r -> parseDouble(r.data.get("bl_weight")))));

        for (EdiRecord record : records) {
            String bl    = record.data.getOrDefault("bl_number", "");
            long   count = blCounts.getOrDefault(bl, 1L);

            if (count > 1) {
                record.data.put("number_of_yard_items", String.valueOf(count));
                record.data.put("number_of_packages",   String.valueOf(count));

                double totalVol = EdiRecord.roundTo(blVolumes.getOrDefault(bl, 0.0), 6);
                if (totalVol > 0) {
                    record.data.put("bl_volume", EdiRecord.formatDouble(totalVol));
                    // blitem_commodity_volume conserve sa valeur individuelle — ne pas écraser
                }

                double totalWgt = EdiRecord.roundTo(blWeights.getOrDefault(bl, 0.0), 6);
                if (totalWgt > 0) {
                    record.data.put("bl_weight", EdiRecord.formatDouble(totalWgt));
                    // blitem_commodity_weight conserve sa valeur individuelle — ne pas écraser
                }
            }

            // BUG-03 : véhicules R — seal_number_1 contient parfois la marque du véhicule
            if ("R".equals(record.data.getOrDefault("transport_mode", ""))) {
                String seal1 = record.data.getOrDefault("blitem_seal_number_1", "").trim();
                if (!seal1.isEmpty()) {
                    String sealClean = seal1.replaceAll("[| ]+$", "");
                    String model     = record.data.getOrDefault("blitem_vehicle_model", "").trim();
                    String[] words   = model.split("\\s+");
                    String firstWord = words.length > 0 ? words[0] : "";
                    if (!firstWord.isEmpty() && (
                            sealClean.equalsIgnoreCase(firstWord) ||
                            (sealClean.length() >= 4 &&
                             firstWord.toLowerCase().startsWith(sealClean.toLowerCase())))) {
                        record.data.put("blitem_seal_number_1", "");
                    }
                }

                // BUG-D : seal_number_2 peut contenir un code de grade parasite
                String seal2c = record.data.getOrDefault("blitem_seal_number_2", "");
                if (!seal2c.isEmpty()) {
                    java.util.regex.Matcher m = Pattern.compile(
                            "^(\\S+)\\s{2,}.*[A-Z]{3,}\\|$").matcher(seal2c.trim());
                    if (m.matches()) {
                        record.data.put("blitem_seal_number_2", m.group(1));
                    } else {
                        String seal2Clean = seal2c.trim().replaceAll("[| ]+$", "");
                        if (isGradeCode(seal2Clean)) {
                            record.data.put("blitem_seal_number_2", "");
                        }
                    }
                }
            }

            // BUG-E : conteneurs — extraire le vrai sceau depuis le champ 70-chars
            if (!"R".equals(record.data.getOrDefault("transport_mode", ""))) {
                String raw = record.data.getOrDefault("blitem_seal_number_1", "");
                String s1  = parseContainerSeal(raw);
                record.data.put("blitem_seal_number_1", s1);
                record.data.put("blitem_seal_number_2", s1);
            }

            // BUG-10 : final_destination_country ne doit pas commencer par 'TRANSIT:'
            String fdc = record.data.getOrDefault("final_destination_country", "").trim();
            if (fdc.startsWith("TRANSIT:")) {
                record.data.put("final_destination_country", "");
            }

            // BUG-F : réinjecter le '+' des numéros internationaux
            String[] phoneFields = {"adresse_2", "adresse_3", "adresse_4", "adresse_5",
                                    "notify2", "notify3", "notify4", "notify5"};
            for (String f : phoneFields) {
                String val = record.data.getOrDefault(f, "").trim();
                if (val.matches("[1-9][0-9]{7,14}")) {
                    record.data.put(f, "+" + val);
                } else if (val.matches("^[a-z]{1,2} [A-Z].{5,}$")) {
                    // Préfixe parasite "h Mr …" — supprimer les 1-2 lettres minuscules
                    record.data.put(f, val.replaceFirst("^[a-z]{1,2} ", ""));
                }
            }

            // BUG-H : number_of_yard_items peut lire une date à l'offset 1757 pour les BLs mono-ligne
            long blCount = blCounts.getOrDefault(bl, 1L);
            if (blCount == 1) {
                record.data.put("number_of_yard_items", "1");
                record.data.put("number_of_packages",   "1");
            } else {
                int nyi = 1;
                try { nyi = Integer.parseInt(record.data.getOrDefault("number_of_yard_items", "1")); }
                catch (NumberFormatException ignored) {}
                if (nyi > 999) {
                    record.data.put("number_of_yard_items", "1");
                    record.data.put("number_of_packages",   "1");
                }
            }
        }

        // BUG-01 : tri stable par bl_number
        records.sort(Comparator.comparing(r -> r.data.getOrDefault("bl_number", "")));

        log.info("Parsed {} records from file: {}", records.size(), filePath);
        return records;
    }

    /**
     * Parse un champ sceau de conteneur de la forme "SEAL|SECOND_SEAL   GRADE_CODE   ".
     * Retourne "SEAL|SECOND_SEAL" si un vrai second sceau existe, "SEAL|" sinon, ou "" si grade.
     */
    private String parseContainerSeal(String raw) {
        raw = raw.trim();
        if (raw.isEmpty()) return "";

        int pipePos = raw.indexOf('|');
        if (pipePos < 0) {
            return isGradeCode(raw) ? "" : raw;
        }

        String mainSeal = raw.substring(0, pipePos).trim();
        if (isGradeCode(mainSeal)) return "";

        String afterPipe = raw.substring(pipePos + 1).stripLeading();
        String firstWord = afterPipe.split("[ \t]")[0];
        if (firstWord.isEmpty() || isGradeCode(firstWord)) {
            return mainSeal + "|";
        }
        return mainSeal + "|" + firstWord;
    }

    private boolean isGradeCode(String val) {
        String v = val.trim().replaceAll("[| ]+$", "");
        return GRADE_CODE_1.matcher(v).matches() || GRADE_CODE_2.matcher(v).matches();
    }

    /** Retourne les en-têtes ordonnées (nom interne → libellé d'affichage). */
    public Map<String, String> getHeaders() {
        Map<String, String> h = new LinkedHashMap<>();
        h.put("bl_number",                        "BL Number");
        h.put("import_export",                    "ImportExport");
        h.put("stevedore",                        "Stevedore");
        h.put("shipping_agent",                   "Shipping Agent");
        h.put("estimated_departure_date",         "Estimated Departure Date");
        h.put("call_number",                      "Call Number");
        h.put("shipper",                          "Shipper");
        h.put("forwarder",                        "Forwarder");
        h.put("related_customer",                 "Related Customer");
        h.put("forwarding_agent",                 "Forwarding Agent");
        h.put("final_destination_country",        "Final Destination Country");
        h.put("manifest",                         "Manifest");
        h.put("number_of_yard_items",             "Number of Yard Items");
        h.put("number_of_packages",               "Number of Packages");
        h.put("slot_file",                        "SlotFile");
        h.put("transport_mode",                   "TransportMode");
        h.put("consignee",                        "Consignee");
        h.put("custom_release_order",             "CustomReleaseOrder");
        h.put("custom_release_order_date",        "CustomReleaseOrderDate");
        h.put("delivery_order",                   "DeliveryOrder");
        h.put("delivery_order_date",              "DeliveryOrderDate");
        h.put("master_bl",                        "MasterBL");
        h.put("bl_volume",                        "BLVolume");
        h.put("bl_weight",                        "BLWeight");
        h.put("incoterm",                         "Incoterm");
        h.put("port_of_loading",                  "Port_of_Loading UNLOCODE");
        h.put("reception_location",               "Reception_Location UNLOCODE");
        h.put("transshipment_port_1",             "Transshipment port 1 UNLOCODE");
        h.put("transshipment_port_2",             "Transshipment port 2 UNLOCODE");
        h.put("commodity",                        "Commodity");
        h.put("yard_item_type",                   "YardItemType");
        h.put("unit_of_measure",                  "UnitOfMeasure");
        h.put("comment",                          "Comment");
        h.put("direction_code",                   "DirectionCode");
        h.put("agent_name",                       "Agent Name");
        h.put("blitem_yard_item_type",            "BLItem YardItemType");
        h.put("blitem_comment",                   "BLItem Comment");
        h.put("blitem_yard_item_number",          "BLItem YardItemNumber");
        h.put("blitem_allow_invalid",             "BLItem AllowInvalidYardItemNumber");
        h.put("blitem_yard_item_code",            "BLItem YardItemCode");
        h.put("blitem_out_of_gauge",              "BLItem OutOfGauge");
        h.put("blitem_commodity",                 "BLItem Commodity");
        h.put("blitem_hs_code",                   "BLItem HS Code");
        h.put("blitem_unloading_date",            "BLItem YardItemUnloadingDate");
        h.put("blitem_commodity_volume",          "BLItem Commodity Volume");
        h.put("blitem_commodity_weight",          "BLItem Commodity Weight");
        h.put("blitem_commodity_packages",        "BLItem Commodity Packages");
        h.put("blitem_import_export",             "BLItem ImportExport");
        h.put("blitem_custom_number",             "BLItem CustomNumber");
        h.put("blitem_seal_number_1",             "BLItem SealNumber1");
        h.put("blitem_seal_number_2",             "BLItem SealNumber2");
        h.put("blitem_commodity_hazardous_class", "BLItem Commodity HazardousClass");
        h.put("blitem_barcode",                   "BLItem BarCode");
        h.put("blitem_vehicle_model",             "BLItem VehicleModel");
        h.put("blitem_chassis_number",            "BLItem ChassisNumber");
        h.put("blitem_gross_weight",              "BLItem GrossWeight");
        h.put("outgoing_call_number",             "OutGoingCallNumber");
        h.put("outgoing_slot_file",               "OutGoingSlotFile");
        h.put("is_lifter",                        "Is Lifter");
        h.put("stacked_chassis",                  "Stacked Vehicle Chassis Number");
        h.put("stacked_model",                    "Stacked Vehicle Model");
        h.put("stacked_weight",                   "Stacked Vehicle Weight");
        h.put("stacked_volume",                   "Stacked Vehicle Volume");
        h.put("new_transshipment_bl",             "New Transshipment BL");
        h.put("shipper_name",                     "Shipper Name");
        h.put("freight_prepaid_collect",          "Freight Prepaid / Collect");
        h.put("shipping_line_export_bl",          "Shipping Line Export BL Number");
        h.put("is_transfer",                      "Is Transfer");
        h.put("blitem_hazardous_class",           "BLItem HazardousClass");
        h.put("attach_to_bl",                     "Attach to BL");
        h.put("adresse_2",                        "Adresse 2");
        h.put("adresse_3",                        "Adresse 3");
        h.put("adresse_4",                        "Adresse 4");
        h.put("adresse_5",                        "Adresse 5");
        h.put("notify1",                          "Notify1");
        h.put("notify2",                          "Notify2");
        h.put("notify3",                          "Notify3");
        h.put("notify4",                          "Notify4");
        h.put("notify5",                          "Notify5");
        return h;
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static boolean isValidUtf8(byte[] data) {
        CharsetDecoder decoder = StandardCharsets.UTF_8.newDecoder()
                .onMalformedInput(CodingErrorAction.REPORT)
                .onUnmappableCharacter(CodingErrorAction.REPORT);
        try {
            decoder.decode(ByteBuffer.wrap(data));
            return true;
        } catch (CharacterCodingException e) {
            return false;
        }
    }

    private static double parseDouble(String s) {
        if (s == null || s.isEmpty()) return 0;
        try { return Double.parseDouble(s.replace(',', '.')); } catch (NumberFormatException e) { return 0; }
    }
}
