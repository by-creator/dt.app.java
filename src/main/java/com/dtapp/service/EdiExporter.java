package com.dtapp.service;

import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;

import java.io.IOException;
import java.io.PrintWriter;
import java.nio.file.Files;
import java.nio.file.Paths;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;

/**
 * Convertit une liste d'EdiRecord en fichier EDI IFTMIN D04 96B UN.
 * Traduit exactement la logique PHP de App\Services\EdiExporter.
 */
@Service
@Slf4j
public class EdiExporter {

    private static final Map<String, String> PORT_NAMES;
    private static final Map<String, String> TRANSPORT_MODES;

    static {
        Map<String, String> p = new LinkedHashMap<>();
        p.put("USNYC", "NEW YORK");         p.put("USBWI", "BALTIMORE");
        p.put("USBA1", "BALTIMORE");        p.put("USPVD", "PROVIDENCE");
        p.put("USFPO", "FREEPORT");         p.put("USVAB", "Virginia Beach");
        p.put("USPNJ", "NEWARK OCEAN PORT");p.put("USHOU", "HOUSTON");
        p.put("USLAX", "LOS ANGELES");      p.put("USSAV", "SAVANNAH");
        p.put("SNDKR", "DAKAR");            p.put("CIABJ", "ABIDJAN");
        p.put("TGLFW", "LOME");             p.put("CMDLA", "DOUALA");
        p.put("NGAPP", "APAPA LAGOS");      p.put("GHTEM", "TEMA");
        p.put("GNCKY", "CONAKRY");          p.put("SLFNA", "FREETOWN");
        p.put("MRMTL", "NOUAKCHOTT");       p.put("ANLAD", "LUANDA");
        p.put("AOLAD", "LUANDA");           p.put("GCGRAD", "GRANDE ABIDJAN");
        p.put("LRMLW", "MONROVIA");         p.put("BJOOO", "COTONOU");
        PORT_NAMES = Collections.unmodifiableMap(p);

        Map<String, String> m = new LinkedHashMap<>();
        m.put("R", "Roro");
        m.put("C", "Container");
        m.put("B", "Bulk");
        m.put("M", "Mafi");
        TRANSPORT_MODES = Collections.unmodifiableMap(m);
    }

    private String sender      = "GRIMALDI";
    private String recipient   = "DAKARDTT";
    private String documentRef = "";

    public void export(List<EdiRecord> records, String outputPath) {
        this.documentRef = generateDocRef();
        LocalDateTime now = LocalDateTime.now();

        String dateStr        = now.format(DateTimeFormatter.ofPattern("yyMMdd"));  // 251006
        String timeStr        = now.format(DateTimeFormatter.ofPattern("HHmm"));    // 1038
        int interchangeRef    = new Random().nextInt(900) + 100;

        List<String> lines = new ArrayList<>();

        // UNA + UNB
        lines.add("UNA:+.? '");
        lines.add("UNB+UNOA:2+" + sender + "+" + recipient + "+" + dateStr + ":" + timeStr
                  + "+" + interchangeRef + "'");

        int msgNum    = 11394;
        int blockCount = 0;

        for (EdiRecord record : records) {
            Map<String, String> d = record.toArray();
            List<String> blockSegs = buildBlock(d, msgNum, now);
            int segCount = blockSegs.size() + 1; // +1 for UNT itself

            lines.addAll(blockSegs);
            lines.add("UNT+" + segCount + "+" + msgNum + "'");
            msgNum++;
            blockCount++;
        }

        lines.add("UNZ+" + blockCount + "+" + interchangeRef + "'");

        try (PrintWriter pw = new PrintWriter(Files.newBufferedWriter(Paths.get(outputPath)))) {
            for (String line : lines) {
                pw.print(line + "\r\n");
            }
            log.info("IFTMIN file exported: {}", outputPath);
        } catch (IOException e) {
            log.error("Error exporting IFTMIN file: {}", outputPath, e);
            throw new RuntimeException("Failed to export IFTMIN file", e);
        }
    }

    private List<String> buildBlock(Map<String, String> d, int msgNum, LocalDateTime now) {
        List<String> segs = new ArrayList<>();

        String bl        = d.getOrDefault("bl_number", "");
        String callNum   = d.getOrDefault("call_number", "").trim();
        String loadPort  = d.getOrDefault("port_of_loading", "").trim();
        String discharge = d.getOrDefault("reception_location", "").trim();
        String transship1 = d.getOrDefault("transshipment_port_1", "").trim();
        String transship2 = d.getOrDefault("transshipment_port_2", "").trim();
        String transpMode = d.getOrDefault("transport_mode", "").trim();
        String container  = d.getOrDefault("blitem_yard_item_number", "").trim();
        String itemCode   = d.getOrDefault("blitem_yard_item_code", "").trim();
        String itemDesc   = d.getOrDefault("blitem_comment", "").trim();

        // Poids en tonnes → KGM (entier)
        double rawWeight = parseDouble(d.get("bl_weight"));
        String weight    = rawWeight > 0 ? String.valueOf((long) Math.round(rawWeight * 1000)) : "";

        // Volume en m³
        double rawVolume = parseDouble(d.get("bl_volume"));
        String volume    = rawVolume > 0 ? rtrimZeros(String.format("%.3f", rawVolume)) : "";

        String seal1      = cleanSeal(d.getOrDefault("blitem_seal_number_1", ""));
        String seal2      = cleanSeal(d.getOrDefault("blitem_seal_number_2", ""));
        String shipperName = d.getOrDefault("shipper_name", "").trim();
        String consignee  = d.getOrDefault("manifest", "").trim();
        String addr2      = d.getOrDefault("adresse_2", "").trim();
        String addr3      = d.getOrDefault("adresse_3", "").trim();
        String addr4      = d.getOrDefault("adresse_4", "").trim();
        String addr5      = d.getOrDefault("adresse_5", "").trim();
        String notify1    = d.getOrDefault("notify1", "").trim();
        String notify2    = d.getOrDefault("notify2", "").trim();
        String notify3    = d.getOrDefault("notify3", "").trim();
        String nyi        = d.getOrDefault("number_of_yard_items", "1");
        String goods      = d.getOrDefault("goods_nature", itemDesc).trim();

        String createdAt  = now.format(DateTimeFormatter.ofPattern("yyyyMMddHHmm")); // 202510061614

        // ── UNH ──────────────────────────────────────────────────────────────
        segs.add("UNH+" + msgNum + "+IFTMIN:D04:96B:UN'");

        // ── BGM ──────────────────────────────────────────────────────────────
        segs.add("BGM+705+" + documentRef + "+9'");

        // ── DTM ──────────────────────────────────────────────────────────────
        segs.add("DTM+137:" + createdAt + ":203'");

        // ── LOC ──────────────────────────────────────────────────────────────
        if (!loadPort.isEmpty()) {
            String name = PORT_NAMES.getOrDefault(loadPort, loadPort);
            segs.add("LOC+9+" + loadPort + "::6:" + name + "'");
        }
        if (!discharge.isEmpty()) {
            String name = PORT_NAMES.getOrDefault(discharge, discharge);
            segs.add("LOC+12+" + discharge + "::6:" + name + "'");
        }
        if (!transship1.isEmpty() && !transship1.equals(loadPort)) {
            String name = PORT_NAMES.getOrDefault(transship1, transship1);
            segs.add("LOC+88+" + transship1 + "::6:" + name + "'");
        }
        if (!transship2.isEmpty() && !transship2.equals(loadPort) && !transship2.equals(transship1)) {
            String name = PORT_NAMES.getOrDefault(transship2, transship2);
            segs.add("LOC+91+" + transship2 + "::6:" + name + "'");
        }
        if (!discharge.isEmpty()) {
            String name = PORT_NAMES.getOrDefault(discharge, discharge);
            segs.add("LOC+13+" + discharge + "::6:" + name + "'");
        }

        // ── RFF ──────────────────────────────────────────────────────────────
        segs.add("RFF+BM:" + bl + "'");
        segs.add("RFF+BN:" + bl + "'");

        // ── TDT ──────────────────────────────────────────────────────────────
        if (!callNum.isEmpty()) {
            segs.add("TDT+20+" + callNum + "+1+13++++9680712:103::GRANDE ABIDJAN'");
        }

        // ── NAD (parties) ─────────────────────────────────────────────────────
        if (!shipperName.isEmpty()) {
            segs.add(buildNad("FW", "NEW", shipperName, "", ""));
            segs.add("DOC+705:::" + documentRef + "+++1+3'");
            segs.add(buildNad("CZ", "NEW", shipperName, "", ""));
            segs.add("DOC+705:::" + documentRef + "+++1+3'");
        }
        if (!consignee.isEmpty()) {
            segs.add(buildNad("CN", "BROEKMAN", consignee, addr2, addr5.isEmpty() ? addr4 : addr5));
            segs.add("DOC+705:::" + documentRef + "+++1+3'");
        }
        if (!notify1.isEmpty()) {
            segs.add(buildNad("N1", "BROEKMAN", notify1, notify2, notify3));
            segs.add("DOC+705:::" + documentRef + "+++1+3'");
        }

        // ── GID ──────────────────────────────────────────────────────────────
        String unit = "C".equals(transpMode) ? "PCS:::PIECE(S)" : "UNT:::UNIT(S)";
        segs.add("GID+1+" + nyi + ":" + unit + "'");
        segs.add("TMD+3'");

        // ── FTX ──────────────────────────────────────────────────────────────
        if (!goods.isEmpty()) {
            segs.add("FTX+AAA+++" + escapeFtx(goods) + "'");
        }

        // ── MEA ──────────────────────────────────────────────────────────────
        if (!weight.isEmpty()) segs.add("MEA+WT+AAE+KGM:" + weight + "'");
        if (!volume.isEmpty()) segs.add("MEA+VOL+ABJ+MTQ:" + volume + "'");

        // ── SGP + EQD (conteneur/équipement) ─────────────────────────────────
        if (!container.isEmpty()) {
            segs.add("SGP+" + container + "+" + nyi + "'");
            if (!weight.isEmpty()) segs.add("MEA+WT+AAE+KGM:" + weight + "'");
            if (!volume.isEmpty()) segs.add("MEA+VOL+ABJ+MTQ:.000'");

            String eqSize = equipmentSize(itemCode);
            String loadSt = "C".equals(transpMode) ? "C3" : "C1";
            segs.add("EQD+HV+" + container + "+" + eqSize + "+2+" + loadSt + "+5'");
            segs.add("TMD+3'");
            if (!weight.isEmpty()) segs.add("MEA+WT+AAL+KGM:" + weight + "'");
            segs.add("MEA+WT+T+KGM:.000'");
            segs.add("MEA+VOL+ABJ+MTW:.000'");

            if (!seal1.isEmpty()) segs.add("SEL+" + seal1 + "'");
            if (!seal2.isEmpty() && !seal2.equals(seal1)) segs.add("SEL+" + seal2 + "'");
        }

        return segs;
    }

    private String buildNad(String role, String qualifier, String name,
                            String addr1, String city) {
        name  = escapeEdi(name);
        addr1 = escapeEdi(addr1);
        city  = escapeEdi(city);
        List<String> parts = new ArrayList<>();
        if (!name.isEmpty())  parts.add(name);
        if (!addr1.isEmpty()) parts.add(addr1);
        if (!city.isEmpty())  parts.add(city);
        return "NAD+" + role + "+" + qualifier + "+" + String.join(":", parts) + "'";
    }

    private String cleanSeal(String seal) {
        return seal.replace("|", "").replace(" ", "").trim();
    }

    private String escapeFtx(String text) {
        return text.replaceAll("[+:'?]", " ").replaceAll("\\s+", " ").trim();
    }

    private String escapeEdi(String text) {
        return text.replaceAll("[+:']", " ").trim();
    }

    private String equipmentSize(String itemCode) {
        String code = itemCode.toUpperCase().trim();
        if (code.contains("40HC") || code.contains("40HQ")) return "4500";
        if (code.contains("40")) return "4200";
        if (code.contains("20")) return "2200";
        return "4500";
    }

    private String generateDocRef() {
        long min = 100_000_000_000L;
        long max = 999_999_999_999L;
        return String.valueOf(min + (long)(Math.random() * (max - min)));
    }

    private static double parseDouble(String s) {
        if (s == null || s.isEmpty()) return 0;
        try { return Double.parseDouble(s); } catch (NumberFormatException e) { return 0; }
    }

    /** Supprime les zéros et le point décimal de fin : "1.000" → "1", "1.500" → "1.5" */
    private static String rtrimZeros(String s) {
        if (!s.contains(".")) return s;
        s = s.replaceAll("0+$", "");
        if (s.endsWith(".")) s = s.substring(0, s.length() - 1);
        return s;
    }
}
