package com.dtapp.service;

import com.dtapp.entity.RattachementBl;
import jakarta.mail.MessagingException;
import jakarta.mail.internet.MimeMessage;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.core.io.ByteArrayResource;
import org.springframework.core.io.ClassPathResource;
import org.springframework.mail.javamail.JavaMailSender;
import org.springframework.mail.javamail.MimeMessageHelper;
import org.springframework.stereotype.Service;
import org.springframework.web.multipart.MultipartFile;

import java.util.List;

@Service
public class EmailService {

    private final JavaMailSender mailSender;

    @Value("${app.mail.to}")
    private String mailTo;

    @Value("${app.base-url}")
    private String baseUrl;

    @Value("${spring.mail.username}")
    private String mailFrom;

    public EmailService(JavaMailSender mailSender) {
        this.mailSender = mailSender;
    }

    public void sendValidationNotification(RattachementBl bl, List<MultipartFile> attachments) {
        try {
            MimeMessage message = mailSender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, true, "UTF-8");
            helper.setFrom(mailFrom, "DAKAR-TERMINAL");
            helper.setTo(mailTo);
            helper.setSubject("Nouvelle demande de validation - BL " + bl.getBl());
            helper.setText(buildEmailHtml(bl, "validation"), true);
            helper.addInline("dtLogo", new ClassPathResource("static/img/image.png"));
            addAttachments(helper, attachments);
            mailSender.send(message);
        } catch (Exception e) {
            // Log error but do not fail the request
        }
    }

    public void sendRemiseNotification(RattachementBl bl, List<MultipartFile> attachments) {
        try {
            MimeMessage message = mailSender.createMimeMessage();
            MimeMessageHelper helper = new MimeMessageHelper(message, true, "UTF-8");
            helper.setFrom(mailFrom, "DAKAR-TERMINAL");
            helper.setTo(mailTo);
            helper.setSubject("Nouvelle demande de remise - BL " + bl.getBl());
            helper.setText(buildEmailHtml(bl, "remise"), true);
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
                    String filename = file.getOriginalFilename() != null ? file.getOriginalFilename() : "piece-jointe";
                    byte[] bytes = file.getBytes();
                    helper.addAttachment(filename, new ByteArrayResource(bytes));
                } catch (Exception ignored) {
                }
            }
        }
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

    private void sendClientMail(RattachementBl bl, String to, String subject, String htmlBody) {
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
        return sb.toString();
    }

    private String buildEmailHtml(RattachementBl bl, String type) {
        String titre = "validation".equals(type)
                ? "Nouvelle demande de validation"
                : "Nouvelle demande de remise";
        String loginUrl = baseUrl + "/login";

        return "<!DOCTYPE html><html><body style=\"font-family:Arial,sans-serif;background:#f8fbff;margin:0;padding:20px\">" +
               "<div style=\"max-width:600px;margin:0 auto;background:white;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.08)\">" +
               "  <div style=\"background:#ffffff;padding:28px;text-align:center;border-bottom:1px solid #e2e8f0\">" +
               "    <img src=\"cid:dtLogo\" alt=\"Dakar Terminal\" style=\"max-height:50px;max-width:240px\">" +
               "  </div>" +
               "  <div style=\"padding:36px 40px\">" +
               "    <h2 style=\"color:#0f172a;margin:0 0 8px;font-size:20px\">" + titre + "</h2>" +
               "    <p style=\"color:#64748b;margin:0 0 28px;font-size:14px\">Une nouvelle demande a ete recue. Voici les informations :</p>" +
               "    <table style=\"width:100%;border-collapse:collapse;font-size:14px\">" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8;width:160px\">Nom</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getNom()) + "</td></tr>" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Prenom</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getPrenom()) + "</td></tr>" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Email</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getEmail()) + "</td></tr>" +
               "      <tr style=\"border-bottom:1px solid #f1f5f9\"><td style=\"padding:10px 0;color:#94a3b8\">Numero BL</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getBl()) + "</td></tr>" +
               "      <tr><td style=\"padding:10px 0;color:#94a3b8\">Maison de transit</td><td style=\"padding:10px 0;font-weight:600;color:#0f172a\">" + safe(bl.getMaison()) + "</td></tr>" +
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
