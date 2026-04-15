package com.dtapp.service;

import com.dtapp.entity.Machine;
import com.dtapp.entity.PosteFixe;
import com.dtapp.entity.RapportSuiviVides;
import com.dtapp.entity.TiersUnify;
import lombok.extern.slf4j.Slf4j;
import org.springframework.jdbc.core.BatchPreparedStatementSetter;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.lang.NonNull;
import org.springframework.scheduling.annotation.Async;
import org.springframework.stereotype.Service;

import javax.sql.DataSource;
import java.sql.PreparedStatement;
import java.sql.SQLException;
import java.util.Objects;
import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.List;
import java.util.Map;

/**
 * Insère en masse des enregistrements via JdbcTemplate.batchUpdate() —
 * avec rewriteBatchedStatements=true dans l'URL JDBC, MySQL réécrit le tout
 * en un seul INSERT multi-lignes, ce qui minimise les requêtes vers la base
 * et évite les dépassements de quota sur Heroku.
 *
 * Compatible avec tous les serveurs MySQL managés (JawsDB, ClearDB…)
 * sans nécessiter local_infile côté serveur.
 */
@Service
@Slf4j
public class BulkInsertService {

    private static final DateTimeFormatter SQL_DT =
            DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");

    /** Champs EdiRecord exportés dans codification_lignes (ordre = colonnes SQL). */
    private static final List<String> EDI_FIELDS = List.of(
        "bl_number", "call_number", "transport_mode", "import_export",
        "manifest", "consignee", "shipper_name", "agent_name",
        "bl_weight", "bl_volume",
        "port_of_loading", "reception_location",
        "transshipment_port_1", "transshipment_port_2",
        "number_of_yard_items", "number_of_packages",
        "yard_item_type", "final_destination_country",
        "blitem_yard_item_number", "blitem_yard_item_code", "blitem_yard_item_type",
        "blitem_commodity", "blitem_commodity_weight", "blitem_commodity_volume",
        "blitem_seal_number_1", "blitem_seal_number_2",
        "blitem_barcode", "blitem_chassis_number", "blitem_vehicle_model",
        "blitem_comment", "blitem_allow_invalid",
        "adresse_2", "adresse_3", "adresse_4", "adresse_5",
        "notify1", "notify2", "notify3", "notify4", "notify5"
    );

    private final JdbcTemplate jdbc;

    public BulkInsertService(DataSource dataSource) {
        this.jdbc = new JdbcTemplate(Objects.requireNonNull(dataSource));
    }

    // ── codification_lignes ────────────────────────────────────────────────────

    /**
     * Insère en masse les lignes EDI dans codification_lignes.
     * Avec rewriteBatchedStatements=true, MySQL envoie un seul INSERT multi-lignes.
     */
    public void bulkInsertLignes(List<EdiRecord> records, int codificationId) {
        if (records.isEmpty()) return;

        // Colonnes : codification_id + 40 champs EDI
        String columns = "codification_id, " + String.join(", ", EDI_FIELDS);
        String placeholders = "?" + ", ?".repeat(EDI_FIELDS.size());
        String sql = "INSERT INTO codification_lignes (" + columns + ") VALUES (" + placeholders + ")";

        jdbc.batchUpdate(sql, new BatchPreparedStatementSetter() {
            @Override
            public void setValues(@NonNull PreparedStatement ps, int i) throws SQLException {
                Map<String, String> d = records.get(i).data;
                ps.setInt(1, codificationId);
                int col = 2;
                for (String field : EDI_FIELDS) {
                    ps.setString(col++, nullToEmpty(d.get(field)));
                }
            }
            @Override
            public int getBatchSize() { return records.size(); }
        });

        log.info("batchUpdate codification_lignes : {} lignes insérées pour codification_id={}",
                records.size(), codificationId);
    }

    // ── tiers_unify ────────────────────────────────────────────────────────────

    /**
     * Insère en masse des TiersUnify — exécuté en tâche de fond (@Async)
     * pour éviter le timeout H12 de Heroku (30 s).
     */
    @Async
    public void bulkInsertTiersUnify(List<TiersUnify> items) {
        if (items.isEmpty()) return;

        String sql = "INSERT INTO tiers_unify (raison_sociale, compte_ipaki, compte_neptune, created_at)"
                   + " VALUES (?, ?, ?, ?)";
        String now = LocalDateTime.now().format(SQL_DT);

        jdbc.batchUpdate(sql, new BatchPreparedStatementSetter() {
            @Override
            public void setValues(@NonNull PreparedStatement ps, int i) throws SQLException {
                TiersUnify t = items.get(i);
                ps.setString(1, nullToEmpty(t.getRaisonSociale()));
                ps.setString(2, nullToEmpty(t.getCompteIpaki()));
                ps.setString(3, nullToEmpty(t.getCompteNeptune()));
                ps.setString(4, now);
            }
            @Override
            public int getBatchSize() { return items.size(); }
        });

        log.info("batchUpdate tiers_unify : {} enregistrements insérés", items.size());
    }

    // ── machines ───────────────────────────────────────────────────────────────

    /**
     * Insère en masse des Machines.
     */
    public void bulkInsertMachines(List<Machine> items) {
        if (items.isEmpty()) return;

        String sql = "INSERT INTO machines"
                   + " (type, name, ajow_name, username, service_tag, version_os, model,"
                   + "  societe, service, emplacement, sites,"
                   + "  date_acquisition, date_deploiement, commentaire, created_at, updated_at)"
                   + " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        String now = LocalDateTime.now().format(SQL_DT);

        jdbc.batchUpdate(sql, new BatchPreparedStatementSetter() {
            @Override
            public void setValues(@NonNull PreparedStatement ps, int i) throws SQLException {
                Machine m = items.get(i);
                ps.setString(1,  nullToEmpty(m.getType()));
                ps.setString(2,  nullToEmpty(m.getName()));
                ps.setString(3,  nullToEmpty(m.getAjowName()));
                ps.setString(4,  nullToEmpty(m.getUsername()));
                ps.setString(5,  nullToEmpty(m.getServiceTag()));
                ps.setString(6,  nullToEmpty(m.getVersionOs()));
                ps.setString(7,  nullToEmpty(m.getModel()));
                ps.setString(8,  nullToEmpty(m.getSociete()));
                ps.setString(9,  nullToEmpty(m.getService()));
                ps.setString(10, nullToEmpty(m.getEmplacement()));
                ps.setString(11, nullToEmpty(m.getSites()));
                ps.setString(12, nullToEmpty(m.getDateAcquisition()));
                ps.setString(13, nullToEmpty(m.getDateDeploiement()));
                ps.setString(14, nullToEmpty(m.getCommentaire()));
                ps.setString(15, now);
                ps.setString(16, now);
            }
            @Override
            public int getBatchSize() { return items.size(); }
        });

        log.info("batchUpdate machines : {} enregistrements insérés", items.size());
    }

    // ── postes_fixes ───────────────────────────────────────────────────────────

    /**
     * Insère en masse des PostesFixes.
     */
    public void bulkInsertPostesFixes(List<PosteFixe> items) {
        if (items.isEmpty()) return;

        String sql = "INSERT INTO postes_fixes"
                   + " (annuaire, nom, prenom, type, commentaire, created_at, updated_at)"
                   + " VALUES (?, ?, ?, ?, ?, ?, ?)";
        String now = LocalDateTime.now().format(SQL_DT);

        jdbc.batchUpdate(sql, new BatchPreparedStatementSetter() {
            @Override
            public void setValues(@NonNull PreparedStatement ps, int i) throws SQLException {
                PosteFixe p = items.get(i);
                ps.setString(1, nullToEmpty(p.getAnnuaire()));
                ps.setString(2, nullToEmpty(p.getNom()));
                ps.setString(3, nullToEmpty(p.getPrenom()));
                ps.setString(4, nullToEmpty(p.getType()));
                ps.setString(5, nullToEmpty(p.getCommentaire()));
                ps.setString(6, now);
                ps.setString(7, now);
            }
            @Override
            public int getBatchSize() { return items.size(); }
        });

        log.info("batchUpdate postes_fixes : {} enregistrements insérés", items.size());
    }

    // ── rapport_suivi_vides ────────────────────────────────────────────────────

    /**
     * Insère en masse des RapportSuiviVides — exécuté en tâche de fond (@Async).
     */
    @Async
    public void bulkInsertRapportSuiviVides(List<RapportSuiviVides> items) {
        if (items.isEmpty()) return;

        String sql = "INSERT INTO rapport_suivi_vides"
                   + " (terminal, shipowner, item_type, equipment_number, equipment_type_size,"
                   + "  event_code, event_family, event_date, booking_sec_no, created_at)"
                   + " VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        String now = LocalDateTime.now().format(SQL_DT);

        jdbc.batchUpdate(sql, new BatchPreparedStatementSetter() {
            @Override
            public void setValues(@NonNull PreparedStatement ps, int i) throws SQLException {
                RapportSuiviVides r = items.get(i);
                ps.setString(1,  nullToEmpty(r.getTerminal()));
                ps.setString(2,  nullToEmpty(r.getShipowner()));
                ps.setString(3,  nullToEmpty(r.getItemType()));
                ps.setString(4,  nullToEmpty(r.getEquipmentNumber()));
                ps.setString(5,  nullToEmpty(r.getEquipmentTypeSize()));
                ps.setString(6,  nullToEmpty(r.getEventCode()));
                ps.setString(7,  nullToEmpty(r.getEventFamily()));
                ps.setString(8,  nullToEmpty(r.getEventDate()));
                ps.setString(9,  nullToEmpty(r.getBookingSecNo()));
                ps.setString(10, now);
            }
            @Override
            public int getBatchSize() { return items.size(); }
        });

        log.info("batchUpdate rapport_suivi_vides : {} enregistrements insérés", items.size());
    }

    // ── Helper ─────────────────────────────────────────────────────────────────

    private String nullToEmpty(String s) {
        return s != null ? s : "";
    }
}
