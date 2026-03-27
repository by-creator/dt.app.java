package com.dtapp.service;

import java.nio.charset.Charset;
import java.util.Locale;
import java.util.*;

/**
 * Représente un enregistrement EDI (une ligne du fichier TXT à largeur fixe).
 * Traduit exactement la logique PHP de App\Models\EdiRecord.
 */
public class EdiRecord {

    /** [offset, length] – null = champ dérivé (pas lu directement). */
    public static final Map<String, int[]> FIELDS;

    static {
        Map<String, int[]> f = new LinkedHashMap<>();
        f.put("bl_number",                       new int[]{62,   20});
        f.put("import_export",                   null);
        f.put("import_export_raw",               new int[]{694,  10});
        f.put("stevedore",                       null);
        f.put("shipping_agent",                  null);
        f.put("estimated_departure_date",        null);
        f.put("call_number",                     new int[]{21,   10});
        f.put("shipper",                         null);
        f.put("forwarder",                       null);
        f.put("related_customer",                null);
        f.put("forwarding_agent",                null);
        f.put("final_destination_country",       null);
        f.put("manifest",                        new int[]{926,  35});
        f.put("number_of_yard_items",            new int[]{1757,  5});
        f.put("number_of_packages",              new int[]{1757,  5});
        f.put("slot_file",                       null);
        f.put("transport_mode",                  new int[]{61,    1});
        f.put("consignee",                       new int[]{926,  35});
        f.put("custom_release_order",            null);
        f.put("custom_release_order_date",       null);
        f.put("delivery_order",                  null);
        f.put("delivery_order_date",             null);
        f.put("master_bl",                       null);
        f.put("bl_volume",                       new int[]{293,  12}); // conteneurs
        f.put("bl_volume_roro",                  new int[]{1308, 12}); // véhicules RORO
        f.put("bl_weight",                       new int[]{281,  12});
        f.put("bl_weight_alt",                   new int[]{1296, 12}); // poids alternatif
        f.put("incoterm",                        null);
        f.put("port_of_loading",                 new int[]{31,    5});
        f.put("reception_location",              new int[]{46,    5});
        f.put("transshipment_port_1",            new int[]{36,    5});
        f.put("transshipment_port_2",            new int[]{41,    5});
        f.put("commodity",                       null);
        f.put("yard_item_type",                  null);
        f.put("unit_of_measure",                 null);
        f.put("comment",                         null);
        f.put("direction_code",                  null);
        f.put("agent_name",                      new int[]{1111, 35});
        f.put("blitem_yard_item_type",           null);
        f.put("blitem_comment",                  new int[]{221,  35});
        f.put("blitem_yard_item_number",         new int[]{126,  20});
        f.put("blitem_allow_invalid",            null);
        f.put("blitem_yard_item_code",           new int[]{146,  35});
        f.put("blitem_out_of_gauge",             null);
        f.put("blitem_commodity",                null);
        f.put("blitem_unloading_date",           null);
        f.put("blitem_commodity_volume",         null);
        f.put("blitem_commodity_weight",         new int[]{281,  12});
        f.put("blitem_commodity_packages",       null);
        f.put("blitem_import_export",            null);
        f.put("blitem_custom_number",            null);
        f.put("blitem_seal_number_1",            new int[]{351,  70}); // 70 chars pour deux sceaux séparés par |
        f.put("blitem_seal_number_2",            null);                 // déduit du champ 1
        f.put("blitem_commodity_hazardous_class", null);
        f.put("blitem_barcode",                  new int[]{126,  20});
        f.put("blitem_vehicle_model",            new int[]{221,  35});
        f.put("blitem_chassis_number",           new int[]{126,  20});
        f.put("outgoing_call_number",            null);
        f.put("outgoing_slot_file",              null);
        f.put("is_lifter",                       null);
        f.put("stacked_chassis",                 null);
        f.put("stacked_model",                   null);
        f.put("stacked_weight",                  null);
        f.put("stacked_volume",                  null);
        f.put("new_transshipment_bl",            null);
        f.put("shipper_name",                    new int[]{741,  35});
        f.put("adresse_2",                       new int[]{961,  35});
        f.put("adresse_3",                       new int[]{996,  35});
        f.put("adresse_4",                       new int[]{1031, 35});
        f.put("adresse_5",                       new int[]{1066, 35});
        f.put("notify1",                         new int[]{1111, 35});
        f.put("notify2",                         new int[]{1146, 35});
        f.put("notify3",                         new int[]{1181, 35});
        f.put("notify4",                         new int[]{1216, 35});
        f.put("notify5",                         new int[]{1251, 35});
        FIELDS = Collections.unmodifiableMap(f);
    }

    /** Données extraites et dérivées de la ligne. */
    public final Map<String, String> data = new LinkedHashMap<>();

    /**
     * Construit un EdiRecord à partir des bytes bruts d'une ligne.
     * Les offsets s'appliquent sur les bytes bruts (encodage à octet unique).
     */
    public static EdiRecord fromLine(byte[] raw, Charset srcCharset) {
        EdiRecord rec = new EdiRecord();

        // ── Extraction brute par offset ───────────────────────────────────────
        for (Map.Entry<String, int[]> e : FIELDS.entrySet()) {
            String field = e.getKey();
            int[] def    = e.getValue();
            if (def == null) {
                rec.data.put(field, "");
                continue;
            }
            int offset = def[0];
            int length = def[1];
            String value = "";
            if (offset < raw.length) {
                int end = Math.min(offset + length, raw.length);
                value = new String(raw, offset, end - offset, srcCharset).trim();
            }
            rec.data.put(field, value);
        }

        // ── BL Number ────────────────────────────────────────────────────────
        rec.data.put("bl_number", rec.data.getOrDefault("bl_number", "").trim());

        // ── Poids : offset 281 (principal), fallback 1296 — valeur interne en tonnes ────────
        String rawW281 = stripLeadingZeros(rec.data.getOrDefault("bl_weight", ""));
        String rawW296 = stripLeadingZeros(rec.data.getOrDefault("bl_weight_alt", ""));
        double weight  = 0;
        if (isPositiveNumeric(rawW281)) {
            weight = roundTo(Double.parseDouble(rawW281) / 1_000_000.0, 6);
        } else if (isPositiveNumeric(rawW296)) {
            weight = roundTo(Double.parseDouble(rawW296) / 1_000_000.0, 6);
        }
        String weightStr = weight > 0 ? formatDouble(weight) : "";
        rec.data.put("bl_weight",               weightStr);
        rec.data.put("blitem_commodity_weight", weightStr);
        rec.data.remove("bl_weight_alt");

        // ── Volume : offset 293 (conteneurs) ou 1308 (RORO) ─────────────────
        String rawV293  = stripLeadingZeros(rec.data.getOrDefault("bl_volume", ""));
        String rawV1308 = stripLeadingZeros(rec.data.getOrDefault("bl_volume_roro", ""));
        double vol = 0;
        if (isPositiveNumeric(rawV293)) {
            vol = roundTo(Double.parseDouble(rawV293) / 1000.0, 3);
        } else if (isPositiveNumeric(rawV1308)) {
            vol = roundTo(Double.parseDouble(rawV1308) / 1000.0, 3);
        }
        String volStr = vol > 0 ? formatDouble(vol) : "";
        rec.data.put("bl_volume",               volStr);
        rec.data.put("blitem_commodity_volume", volStr);
        rec.data.remove("bl_volume_roro");

        // ── Nombre d'articles ─────────────────────────────────────────────────
        String nyi = rec.data.getOrDefault("number_of_yard_items", "").trim();
        String nyiVal = isNumeric(nyi) ? nyi : "1";
        rec.data.put("number_of_yard_items", nyiVal);
        rec.data.put("number_of_packages",   nyiVal);

        // ── ImportExport ──────────────────────────────────────────────────────
        String rawIE = rec.data.getOrDefault("import_export_raw", "").trim();
        String importExport = rawIE.startsWith("TS") ? "TRANSBO"
                            : rawIE.startsWith("TR") ? "IMPORT TRANSIT"
                            : "IMPORT";
        rec.data.put("import_export", importExport);
        rec.data.remove("import_export_raw");

        // ── Final Destination Country = adresse_5 ────────────────────────────
        rec.data.put("final_destination_country", rec.data.getOrDefault("adresse_5", "").trim());

        // ── YardItemType ──────────────────────────────────────────────────────
        String mode     = rec.data.getOrDefault("transport_mode", "").trim();
        String yardType = (mode.equals("R") || mode.equals("M")) ? "VEHICULE" : "CONTENEUR";
        rec.data.put("yard_item_type",       yardType);
        rec.data.put("blitem_yard_item_type", yardType);

        // ── AllowInvalid ──────────────────────────────────────────────────────
        rec.data.put("blitem_allow_invalid", "VRAI");

        // ── Commodity (tranches de poids véhicules, weight en tonnes) ────────
        if ("VEHICULE".equals(yardType) && weight > 0) {
            String commodity;
            if      (weight <= 1.5)   commodity = "VEH 0-1500Kgs";
            else if (weight <= 3.0)   commodity = "VEH 1501-3000Kgs";
            else if (weight <= 6.0)   commodity = "VEH 3001-6000Kgs";
            else if (weight <= 9.0)   commodity = "VEH 6001-9000Kgs";
            else if (weight <= 30.0)  commodity = "VEH 9001-30000Kgs";
            else                      commodity = "VEH +30000Kgs";
            rec.data.put("blitem_commodity", commodity);
        } else {
            rec.data.put("blitem_commodity", "");
        }

        // ── Sceaux ────────────────────────────────────────────────────────────
        String sealRaw = rec.data.getOrDefault("blitem_seal_number_1", "").trim();
        if (mode.equals("R") || mode.equals("M")) {
            // RORO : séparer sur |
            String[] parts = Arrays.stream(sealRaw.split("\\|"))
                    .map(String::trim).filter(s -> !s.isEmpty()).toArray(String[]::new);
            rec.data.put("blitem_seal_number_1", parts.length > 0 ? parts[0] + "|" : "");
            rec.data.put("blitem_seal_number_2", parts.length > 1 ? parts[1] + "|" : "");
        } else {
            rec.data.put("blitem_seal_number_1", sealRaw);
            rec.data.put("blitem_seal_number_2", sealRaw);
        }

        // ── Champs vides (pas de source dans le TXT) ─────────────────────────
        rec.data.put("blitem_hs_code",          "");
        rec.data.put("blitem_gross_weight",     "");
        rec.data.put("freight_prepaid_collect", "");
        rec.data.put("shipping_line_export_bl", "");
        rec.data.put("is_transfer",             "");
        rec.data.put("blitem_hazardous_class",  "");
        rec.data.put("attach_to_bl",            "");

        return rec;
    }

    public Map<String, String> toArray() { return data; }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private static String stripLeadingZeros(String s) {
        if (s == null || s.isEmpty()) return "0";
        String r = s.replaceAll("^0+", "");
        return r.isEmpty() ? "0" : r;
    }

    static boolean isPositiveNumeric(String s) {
        if (s == null || s.isEmpty() || s.equals("0")) return false;
        try { return Double.parseDouble(s) > 0; } catch (NumberFormatException e) { return false; }
    }

    static boolean isNumeric(String s) {
        if (s == null || s.isEmpty()) return false;
        try { Double.parseDouble(s); return true; } catch (NumberFormatException e) { return false; }
    }

    static double roundTo(double value, int decimals) {
        double factor = Math.pow(10, decimals);
        return Math.round(value * factor) / factor;
    }

    static String formatDouble(double d) {
        // Remove trailing zeros like PHP does
        String s = String.format(Locale.ROOT, "%.6f", d);
        s = s.replaceAll("0+$", "").replaceAll("\\.$", "");
        return s;
    }
}
