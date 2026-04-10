package com.dtapp.service;

import com.dtapp.entity.RattachementBl;
import jakarta.mail.MessagingException;
import jakarta.mail.internet.MimeMessage;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.core.io.ByteArrayResource;
import org.springframework.lang.NonNull;
import org.springframework.core.io.ClassPathResource;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.mail.javamail.MimeMessageHelper;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;

import java.time.format.DateTimeFormatter;
import java.util.List;

@Service
public class EmailService {

    private final JavaMailSender mailSender;

    @Value("${app.mail.to}")
    @NonNull
    private String mailTo = "";

    @Value("${app.mail.to.remise}")
    @NonNull
    private String mailToRemise = "";

    @Value("${app.base-url}")
    @NonNull
    private String baseUrl = "";

    @Value("${spring.mail.username}")
    @NonNull
    private String mailFrom = "";

    public EmailService(JavaMailSender mailSender) {
        this.mailSender = mailSender;
    }

    public void sendValidationNotification(RattachementBl bl, String compteIpaki, List<MultipartFile> attachments) {
        try {
            MimeMessage message = mailSender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, true, "UTF-8");
            helper.setFrom(mailFrom, "DAKAR-TERMINAL");
            helper.setTo(mailTo);
            helper.setSubject("Nouvelle demande de validation - BL " + bl.getBl());
            helper.setText(buildValidationEmailHtml(bl, compteIpaki), true);
            helper.addInline("dtLogo", new ClassPathResource("static/img/image.png"));
            addAttachments(helper, attachments);
            mailSender.send(message);
        } catch (Exception e) {
            // Log error but do not fail the request
        }
    }

    public void sendRemiseNotification(RattachementBl bl, String compteIpaki, List<MultipartFile> attachments) {
        try {
            MimeMessage message = mailSender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, true, "UTF-8");
            helper.setFrom(mailFrom, "DAKAR-TERMINAL");
            helper.setTo(mailToRemise);
            helper.setSubject("Nouvelle demande de remise - BL " + bl.getBl());
            helper.setText(buildRemiseEmailHtml(bl, compteIpaki), true);
            helper.addInline("dtLogo", new ClassPathResource("static/img/image.png"));
            addAttachments(helper, attachments);
            mailSender.send(message);
        } catch (Exception e) {
            // Log error but do not fail the request
        }
    }

    private void addAttachments(MimeMessageHelper helper, List<MultipartFile> files) throws MessagingException {
        if (files == null) return;
        for (MultipartFile file : files) {
            if (file != null && !file.isEmpty()) {
                try {
                    String originalName = file.getOriginalFilename();
                    String filename = originalName != null ? originalName : "piece-jointe";
                    byte[] bytes = file.getBytes();
                    helper.addAttachment(filename, new ByteArrayResource(bytes));
                } catch (Exception ignored) {
                }
            }
        }
    }

    // ==================== RAPPELS AUTOMATIQUES ====================

    public void sendRappelValidation(List<RattachementBl> demandes) {
        if (demandes == null || demandes.isEmpty()) return;
        String html = buildRappelHtml(demandes, "Rappel — Demandes de validation en attente",
                "Les demandes de validation suivantes sont toujours en attente de traitement :", "#f97316");
        try {
            MimeMessage message = mailSender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, true, "UTF-8");
            helper.setFrom(mailFrom, "DAKAR-TERMINAL");
            helper.setTo(mailTo);
            helper.setSubject("Rappel : " + demandes.size() + " demande(s) de validation en attente");
            helper.setText(html, true);
            helper.addInline("dtLogo", new ClassPathResource("static/img/image.png"));
            mailSender.send(message);
        } catch (Exception e) {
            // Silent fail
        }
    }

    public void sendRappelRemise(List<RattachementBl> demandes) {
        if (demandes == null || demandes.isEmpty()) return;
        String html = buildRappelHtml(demandes, "Rappel — Demandes de remise en attente",
                "Les demandes de remise suivantes (EN_ATTENTE et EN_ATTENTE_DIRECTION) sont toujours en attente de traitement :", "#f97316");
        try {
            MimeMessage message = mailSender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, true, "UTF-8");
            helper.setFrom(mailFrom, "DAKAR-TERMINAL");
            helper.setTo(mailToRemise);
            helper.setSubject("Rappel : " + demandes.size() + " demande(s) de remise en attente");
            helper.setText(html, true);
            helper.addInline("dtLogo", new ClassPathResource("static/img/image.png"));
            mailSender.send(message);
        } catch (Exception e) {
            // Silent fail
        }
    }

    @NonNull
    private String buildRappelHtml(List<RattachementBl> demandes, String titre, String intro, String accentColor) {
        DateTimeFormatter fmt = DateTimeFormatter.ofPattern("dd/MM/yyyy HH:mm");
        String loginUrl = baseUrl + "/login";
        StringBuilder sb = new StringBuilder();
        sb.append("<!DOCTYPE html><html><body style=\"font-family:Arial,sans-serif;background:#f8fbff;margin:0;padding:20px\">")
          .append("<div style=\"max-width:680px;margin:0 auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08)\">")
          .append("  <div style=\"background:#ffffff;padding:28px;text-align:center;border-bottom:1px solid #e2e8f0\">")
          .append("    <img src=\"cid:dtLogo\" alt=\"Dakar Terminal\" style=\"max-height:50px;max-width:240px\">")
          .append("  </div>")
          .append("  <div style=\"padding:36px 40px\">")
          .append("    <div style=\"display:inline-block;background:").append(accentColor)
          .append(";color:white;padding:6px 16px;border-radius:999px;font-size:12px;font-weight:700;margin-bottom:20px\">RAPPEL</div>")
          .append("    <h2 style=\"color:#0f172a;margin:0 0 8px;font-size:20px\">").append(titre).append("</h2>")
          .append("    <p style=\"color:#64748b;margin:0 0 28px;font-size:14px\">").append(intro).append("</p>")
          .append("    <table style=\"width:100%;border-collapse:collapse;font-size:13px\">")
          .append("      <thead>")
          .append("        <tr style=\"background:#f1f5f9\">")
          .append("          <th style=\"padding:10px 8px;text-align:left;color:#475569;font-weight:600\">N° BL</th>")
          .append("          <th style=\"padding:10px 8px;text-align:left;color:#475569;font-weight:600\">Client</th>")
          .append("          <th style=\"padding:10px 8px;text-align:left;color:#475569;font-weight:600\">Maison de transit</th>")
          .append("          <th style=\"padding:10px 8px;text-align:left;color:#475569;font-weight:600\">Statut</th>")
          .append("          <th style=\"padding:10px 8px;text-align:left;color:#475569;font-weight:600\">Soumis le</th>")
          .append("        </tr>")
          .append("      </thead>")
          .append("      <tbody>");
        for (int i = 0; i < demandes.size(); i++) {
            RattachementBl d = demandes.get(i);
            String rowBg = (i % 2 == 0) ? "white" : "#f8fbff";
            String date = d.getCreatedAt() != null ? d.getCreatedAt().format(fmt) : "-";
            String statutColor = "EN_ATTENTE_DIRECTION".equals(d.getStatut()) ? "#dc2626" : "#f97316";
            String statutLabel = "EN_ATTENTE_DIRECTION".equals(d.getStatut()) ? "Attente Direction" : "En attente";
            sb.append("        <tr style=\"background:").append(rowBg).append("\">")
              .append("<td style=\"padding:10px 8px;color:#0f172a;font-weight:600\">").append(safe(d.getBl())).append("</td>")
              .append("<td style=\"padding:10px 8px;color:#334155\">").append(safe(d.getNom())).append(" ").append(safe(d.getPrenom())).append("</td>")
              .append("<td style=\"padding:10px 8px;color:#334155\">").append(safe(d.getMaison())).append("</td>")
              .append("<td style=\"padding:10px 8px\"><span style=\"background:").append(statutColor)
              .append(";color:white;padding:3px 10px;border-radius:999px;font-size:11px;font-weight:700\">").append(statutLabel).append("</span></td>")
              .append("<td style=\"padding:10px 8px;color:#64748b\">").append(date).append("</td>")
              .append("</tr>");
        }
        sb.append("      </tbody>")
          .append("    </table>")
          .append("    <div style=\"margin-top:36px;text-align:center\">")
          .append("      <a href=\"").append(loginUrl).append("\" style=\"display:inline-block;background:#3367bf;color:white;padding:14px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px\">Traiter les demandes</a>")
          .append("    </div>")
          .append("  </div>")
          .append("  <div style=\"padding:16px;text-align:center;background:#f8fbff;color:#94a3b8;font-size:12px\">&copy; 2026 Dakar Terminal. Tous droits reserves.</div>")
          .append("</div></body></html>");
        return java.util.Objects.requireNonNull(sb.toString());
    }

    // ==================== IES NOTIFICATIONS ====================

    public void sendIesAccessLink(String to) {
        String dematUrl = baseUrl + "/demat";
        String html = buildSimpleHtml(
            "Lien d'acces IES",
            "Bonjour,<br>Voici votre lien d'acces a la plateforme IES de Dakar Terminal.",
            null, null,
            "Acceder a la plateforme", dematUrl);
        sendSimpleMail(to, "Votre lien d'acces IES - Dakar Terminal", html);
    }

    public void sendIesCreationCompte(String to, String password) {
        String dematUrl = baseUrl + "/demat";
        String html = buildSimpleHtml(
            "Creation de compte IES",
            "Bonjour,<br>Votre compte IES a ete cree. Voici vos identifiants de connexion.",
            new String[]{"Email", "Mot de passe"},
            new String[]{to, password},
            "Acceder a la plateforme", dematUrl);
        sendSimpleMail(to, "Creation de votre compte IES - Dakar Terminal", html);
    }

    public void sendIesReinitialisationCompte(String to, String password) {
        String dematUrl = baseUrl + "/demat";
        String html = buildSimpleHtml(
            "Reinitialisation de compte IES",
            "Bonjour,<br>Votre mot de passe IES a ete reinitialise. Voici vos nouveaux identifiants.",
            new String[]{"Email", "Nouveau mot de passe"},
            new String[]{to, password},
            "Acceder a la plateforme", dematUrl);
        sendSimpleMail(to, "Reinitialisation de votre compte IES - Dakar Terminal", html);
    }

    public void sendRepasMenu(String plat1, String plat2) {
        String[] targets = {
            "assane.diouf@dakar-terminal.com",
            "clarisse.ngueabo@dakar-terminal.com",
            "philippe.napolitano@dakar-terminal.com",
            "jeannette.ndong@dakar-terminal.com",
            "aminata.ndiathe@dakar-terminal.com",
            "marc.bongoyeba@dakar-terminal.com",
            "moussa.thiaw@dakar-terminal.com",
            "cheikh.aw@dakar-terminal.com",
            "sophie-yande.diouf@dakar-terminal.com",
            "alioune-badara.dia@dakar-terminal.com",
            "fatou-kine.niang@dakar-terminal.com",
            "dieynaba.sy@dakar-terminal.com",
            "ramatoulaye.diallo@dakar-terminal.com",
            "christian.sarr@dakar-terminal.com",
            "mor-kebe.fall@dakar-terminal.com",
            "ousmane.tall@dakar-terminal.com",
            "fatou.konte@dakar-terminal.com",
            "aissatou.sow@dakar-terminal.com",
            "mame-aminata.ndaw@dakar-terminal.com",
            "mamadou-bafou.fall@dakar-terminal.com",
            "elhadji-babacar.sane@dakar-terminal.com",
            "mouhameth.mbengue@dakar-terminal.com",
            "ndeye-marieme.gueye@dakar-terminal.com",
            "aby.traore@dakar-terminal.com",
            "fatou.gueye@dakar-terminal.com",
            "rokhaya.cisse@dakar-terminal.com",
            "abdourahmane.diouf@dakar-terminal.com",
            "mohamed.ngom@dakar-terminal.com",
            "mamadou.diouf16@dakar-terminal.com",
            "aissata.ba@dakar-terminal.com",
            "basile.manga@dakar-terminal.com",
            "maimouna.fall@dakar-terminal.com",
            "ababacar.fall@dakar-terminal.com",
            "fatoumata-yaya.gueye@dakar-terminal.com",
            "charles.sarr@dakar-terminal.com",
            "aliounebadara.sy@dakar-terminal.com",
            "serigne.ndiaye@dakar-terminal.com",
            "marie.diop@dakar-terminal.com",
            "adama.n@dakar-terminal.com",
            "gaye.maliki@dakar-terminal.com"
        };
        String html = buildSimpleHtml(
            "Menu du jour",
            "Bonjour,<br>Voici le menu du jour de Dakar Terminal.",
            new String[]{"Plat 1", "Plat 2"},
            new String[]{plat1, plat2},
            null, null);
        try {
            MimeMessage message = mailSender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, true, "UTF-8");
            helper.setFrom(mailFrom, "DAKAR-TERMINAL");
            helper.setTo(mailFrom);
            helper.setBcc(targets);
            helper.setSubject("Menu du jour - Dakar Terminal");
            helper.setText(html, true);
            helper.addInline("dtLogo", new ClassPathResource("static/img/image.png"));
            mailSender.send(message);
        } catch (Exception e) {
            // Silent fail
        }
    }

    private void sendSimpleMail(String to, @NonNull String subject, @NonNull String html) {
        if (to == null || to.isBlank()) return;
        try {
            MimeMessage message = mailSender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, true, "UTF-8");
            helper.setFrom(mailFrom, "DAKAR-TERMINAL");
            helper.setTo(to);
            helper.setSubject(subject);
            helper.setText(html, true);
            helper.addInline("dtLogo", new ClassPathResource("static/img/image.png"));
            mailSender.send(message);
        } catch (Exception e) {
            // Silent fail
        }
    }

    @NonNull
    private String buildSimpleHtml(String titre, String intro,
                                   String[] labels, String[] values,
                                   String btnLabel, String btnUrl) {
        StringBuilder sb = new StringBuilder();
        sb.append("<!DOCTYPE html><html><body style=\"font-family:Arial,sans-serif;background:#f8fbff;margin:0;padding:20px\">")
          .append("<div style=\"max-width:600px;margin:0 auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08)\">")
          .append("  <div style=\"background:#ffffff;padding:28px;text-align:center;border-bottom:1px solid #e2e8f0\">")
          .append("    <img src=\"cid:dtLogo\" alt=\"Dakar Terminal\" style=\"max-height:50px;max-width:240px\">")
          .append("  </div>")
          .append("  <div style=\"padding:36px 40px\">")
          .append("    <h2 style=\"color:#0f172a;margin:0 0 16px;font-size:20px\">").append(titre).append("</h2>")
          .append("    <p style=\"color:#334155;font-size:15px;line-height:1.7;margin:0 0 24px\">").append(intro).append("</p>");
        if (labels != null && values != null) {
            sb.append("    <table style=\"width:100%;border-collapse:collapse;font-size:14px\">");
            for (int i = 0; i < labels.length; i++) {
                String border = (i < labels.length - 1) ? "border-bottom:1px solid #f1f5f9;" : "";
                sb.append("      <tr style=\"").append(border).append("\">")
                  .append("<td style=\"padding:10px 0;color:#94a3b8;width:180px\">").append(labels[i]).append("</td>")
                  .append("<td style=\"padding:10px 0;font-weight:600;color:#0f172a\">").append(safe(values[i])).append("</td></tr>");
            }
            sb.append("    </table>");
        }
        if (btnLabel != null && btnUrl != null) {
            sb.append("    <div style=\"margin-top:36px;text-align:center\">")
              .append("      <a href=\"").append(btnUrl).append("\" style=\"display:inline-block;background:#3367bf;color:white;padding:14px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px\">").append(btnLabel).append("</a>")
              .append("    </div>");
        }
        sb.append("  </div>")
          .append("  <div style=\"padding:16px;text-align:center;background:#f8fbff;color:#94a3b8;font-size:12px\">&copy; 2026 Dakar Terminal. Tous droits reserves.</div>")
          .append("</div></body></html>");
        return java.util.Objects.requireNonNull(sb.toString());
    }

    // ==================== CLIENT NOTIFICATIONS ====================

    public void notifyClientValidationValide(RattachementBl bl) {
        sendClientMail(bl, bl.getEmail(),
                "Votre demande de validation a ete approuvee - BL " + bl.getBl(),
                buildClientStatusHtml(bl, "#16a34a", "Demande approuvee",
                        "Nous avons le plaisir de vous informer que votre demande de validation pour le BL <strong>" + safe(bl.getBl()) + "</strong> a ete approuvee.",
                        null, null));
    }

    public void notifyClientValidationRejete(RattachementBl bl) {
        sendClientMail(bl, bl.getEmail(),
                "Votre demande de validation a ete rejetee - BL " + bl.getBl(),
                buildClientStatusHtml(bl, "#dc2626", "Demande rejetee",
                        "Nous vous informons que votre demande de validation pour le BL <strong>" + safe(bl.getBl()) + "</strong> a ete rejetee.",
                        "Motif de rejet", safe(bl.getMotifRejet())));
    }

    public void notifyClientRemiseEnAttenteDirection(RattachementBl bl) {
        sendClientMail(bl, bl.getEmail(),
                "Votre demande de remise est en cours de traitement - BL " + bl.getBl(),
                buildClientStatusHtml(bl, "#f97316", "En attente de la direction",
                        "Votre demande de remise pour le BL <strong>" + safe(bl.getBl()) + "</strong> a ete transmise a la direction pour validation finale.",
                        null, null));
    }

    public void notifyClientRemiseValide(RattachementBl bl) {
        String pct = bl.getPourcentage() != null ? bl.getPourcentage().stripTrailingZeros().toPlainString() + "%" : "-";
        sendClientMail(bl, bl.getEmail(),
                "Votre demande de remise a ete approuvee - BL " + bl.getBl(),
                buildClientStatusHtml(bl, "#16a34a", "Demande approuvee",
                        "Nous avons le plaisir de vous informer que votre demande de remise pour le BL <strong>" + safe(bl.getBl()) + "</strong> a ete approuvee.",
                        "Pourcentage accorde", pct));
    }

    public void notifyClientRemiseRejete(RattachementBl bl) {
        sendClientMail(bl, bl.getEmail(),
                "Votre demande de remise a ete rejetee - BL " + bl.getBl(),
                buildClientStatusHtml(bl, "#dc2626", "Demande rejetee",
                        "Nous vous informons que votre demande de remise pour le BL <strong>" + safe(bl.getBl()) + "</strong> a ete rejetee.",
                        "Motif de rejet", safe(bl.getMotifRejet())));
    }

    private void sendClientMail(RattachementBl bl, String to, @NonNull String subject, @NonNull String htmlBody) {
        if (to == null || to.isBlank()) return;
        try {
            MimeMessage message = mailSender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, true, "UTF-8");
            helper.setFrom(mailFrom, "DAKAR-TERMINAL");
            helper.setTo(to);
            helper.setSubject(subject);
            helper.setText(htmlBody, true);
            helper.addInline("dtLogo", new ClassPathResource("static/img/image.png"));
            mailSender.send(message);
        } catch (Exception e) {
            // Silent fail — do not block the action
        }
    }

    @NonNull
    private String buildClientStatusHtml(RattachementBl bl, String accentColor,
                                         String statusLabel, String message,
                                         String extraLabel, String extraValue) {
        String dematUrl = baseUrl + "/demat";
        StringBuilder sb = new StringBuilder();
        sb.append("<!DOCTYPE html><html><body style=\"font-family:Arial,sans-serif;background:#f8fbff;margin:0;padding:20px\">")
          .append("<div style=\"max-width:600px;margin:0 auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08)\">")
          .append("  <div style=\"background:#ffffff;padding:28px;text-align:center;border-bottom:1px solid #e2e8f0\">")
          .append("    <img src=\"cid:dtLogo\" alt=\"Dakar Terminal\" style=\"max-height:50px;max-width:240px\">")
          .append("  </div>")
          .append("  <div style=\"padding:36px 40px\">")
          .append("    <div style=\"display:inline-block;background:").append(accentColor)
          .append(";color:white;padding:6px 16px;border-radius:999px;font-size:12px;font-weight:700;margin-bottom:20px\">")
          .append(statusLabel).append("</div>")
          .append("    <p style=\"color:#0f172a;font-size:15px;line-height:1.7;margin:0 0 24px\">")
          .append("Bonjour <strong>").append(safe(bl.getNom())).append(" ").append(safe(bl.getPrenom())).append("</strong>,<br>")
          .append(message).append("</p>")
          .append("    <table style=\"width:100%;border-collapse:collapse;font-size:14px\">")
          .append("      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8;width:180px\">Numero BL</td>")
          .append("<td style=\"padding:10px 0;font-weight:600;color:#0f172a\">").append(safe(bl.getBl())).append("</td></tr>")
          .append("      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Maison de transit</td>")
          .append("<td style=\"padding:10px 0;font-weight:600;color:#0f172a\">").append(safe(bl.getMaison())).append("</td></tr>");
        if (extraLabel != null && extraValue != null) {
            sb.append("      <tr><td style=\"padding:10px 0;color:#94a3b8\">").append(extraLabel).append("</td>")
              .append("<td style=\"padding:10px 0;font-weight:600;color:").append(accentColor).append("\">").append(extraValue).append("</td></tr>");
        }
        sb.append("    </table>")
          .append("    <div style=\"margin-top:36px;text-align:center\">")
          .append("      <a href=\"").append(dematUrl).append("\" style=\"display:inline-block;background:#3367bf;color:white;padding:14px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px\">Acceder a l'application</a>")
          .append("    </div>")
          .append("  </div>")
          .append("  <div style=\"padding:16px;text-align:center;background:#f8fbff;color:#94a3b8;font-size:12px\">&copy; 2026 Dakar Terminal. Tous droits reserves.</div>")
          .append("</div></body></html>");
        return java.util.Objects.requireNonNull(sb.toString());
    }

    @NonNull
    private String buildValidationEmailHtml(RattachementBl bl, String compteIpaki) {
        String loginUrl = baseUrl + "/login";
        String ipakiRow = (compteIpaki != null && !compteIpaki.isBlank())
                ? "      <tr><td style=\"padding:10px 0;color:#94a3b8\">Compte Ipaki</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(compteIpaki) + "</td></tr>"
                : "";
        return "<!DOCTYPE html><html><body style=\"font-family:Arial,sans-serif;background:#f8fbff;margin:0;padding:20px\">" +
               "<div style=\"max-width:600px;margin:0 auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08)\">" +
               "  <div style=\"background:#ffffff;padding:28px;text-align:center;border-bottom:1px solid #e2e8f0\">" +
               "    <img src=\"cid:dtLogo\" alt=\"Dakar Terminal\" style=\"max-height:50px;max-width:240px\">" +
               "  </div>" +
               "  <div style=\"padding:36px 40px\">" +
               "    <h2 style=\"color:#0f172a;margin:0 0 8px;font-size:20px\">Nouvelle demande de validation</h2>" +
               "    <p style=\"color:#64748b;margin:0 0 28px;font-size:14px\">Une nouvelle demande a ete recue. Voici les informations :</p>" +
               "    <table style=\"width:100%;border-collapse:collapse;font-size:14px\">" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8;width:160px\">Nom</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getNom()) + "</td></tr>" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Prenom</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getPrenom()) + "</td></tr>" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Email</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getEmail()) + "</td></tr>" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Numero BL</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getBl()) + "</td></tr>" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Maison de transit</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getMaison()) + "</td></tr>" +
               ipakiRow +
               "    </table>" +
               "    <div style=\"margin-top:36px;text-align:center\">" +
               "      <a href=\"" + loginUrl + "\" style=\"display:inline-block;background:#3367bf;color:white;padding:14px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px\">Acceder a l'application</a>" +
               "    </div>" +
               "  </div>" +
               "  <div style=\"padding:16px;text-align:center;background:#f8fbff;color:#94a3b8;font-size:12px\">&copy; 2026 Dakar Terminal. Tous droits reserves.</div>" +
               "</div></body></html>";
    }

    @NonNull
    private String buildRemiseEmailHtml(RattachementBl bl, String compteIpaki) {
        String loginUrl = baseUrl + "/login";
        String ipakiRow = (compteIpaki != null && !compteIpaki.isBlank())
                ? "      <tr><td style=\"padding:10px 0;color:#94a3b8\">Compte Ipaki</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(compteIpaki) + "</td></tr>"
                : "";
        return "<!DOCTYPE html><html><body style=\"font-family:Arial,sans-serif;background:#f8fbff;margin:0;padding:20px\">" +
               "<div style=\"max-width:600px;margin:0 auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08)\">" +
               "  <div style=\"background:#ffffff;padding:28px;text-align:center;border-bottom:1px solid #e2e8f0\">" +
               "    <img src=\"cid:dtLogo\" alt=\"Dakar Terminal\" style=\"max-height:50px;max-width:240px\">" +
               "  </div>" +
               "  <div style=\"padding:36px 40px\">" +
               "    <h2 style=\"color:#0f172a;margin:0 0 8px;font-size:20px\">Nouvelle demande de remise</h2>" +
               "    <p style=\"color:#64748b;margin:0 0 28px;font-size:14px\">Une nouvelle demande a ete recue. Voici les informations :</p>" +
               "    <table style=\"width:100%;border-collapse:collapse;font-size:14px\">" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8;width:160px\">Nom</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getNom()) + "</td></tr>" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Prenom</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getPrenom()) + "</td></tr>" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Email</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getEmail()) + "</td></tr>" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Numero BL</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getBl()) + "</td></tr>" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Maison de transit</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getMaison()) + "</td></tr>" +
               ipakiRow +
               "    </table>" +
               "    <div style=\"margin-top:36px;text-align:center\">" +
               "      <a href=\"" + loginUrl + "\" style=\"display:inline-block;background:#3367bf;color:white;padding:14px 36px;border-radius:10px;text-decoration:none;font-weight:700;font-size:15px\">Acceder a l'application</a>" +
               "    </div>" +
               "  </div>" +
               "  <div style=\"padding:16px;text-align:center;background:#f8fbff;color:#94a3b8;font-size:12px\">&copy; 2026 Dakar Terminal. Tous droits reserves.</div>" +
               "</div></body></html>";
    }

    private String safe(String value) {
        return value != null ? value : "-";
    }
}
