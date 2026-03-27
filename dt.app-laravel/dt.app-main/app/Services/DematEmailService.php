<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class DematEmailService
{
    private const DEFAULT_RECIPIENTS = [
        'sn004-proforma@dakar-terminal.com',
        'sn004-facturation@dakar-terminal.com',
    ];

    private const DEFAULT_DIRECTOR_EMAIL = 'sn004-remise.facturation@dakar-terminal.com';

    public function sendValidationEmail(
        string $nom,
        string $prenom,
        string $email,
        ?string $numeroBl,
        ?string $maisonTransit,
        ?UploadedFile $fileBl,
        ?UploadedFile $fileBadShipping,
        ?UploadedFile $fileDeclaration,
    ): void {
        $files = [
            'BL' => $this->fileInfo($fileBl),
            'BAD SHIPPING' => $this->fileInfo($fileBadShipping),
            'DECLARATION' => $this->fileInfo($fileDeclaration),
        ];

        $attachments = array_values(array_filter([$fileBl, $fileBadShipping, $fileDeclaration]));

        $subject = sprintf('[Dakar Terminal] Nouvelle demande de validation - %s %s', $nom, $prenom);
        $html = $this->buildRequestHtml(
            type: 'Demande de validation',
            accentColor: '#1565C0',
            nom: $nom,
            prenom: $prenom,
            email: $email,
            numeroBl: $numeroBl,
            maisonTransit: $maisonTransit,
            fichiers: $files,
        );

        $this->sendToAll($subject, $html, $attachments);
    }

    public function sendRemiseEmail(
        string $nom,
        string $prenom,
        string $email,
        ?string $numeroBl,
        ?string $maisonTransit,
        ?UploadedFile $fileDemandeManuscrite,
        ?UploadedFile $fileBadShipping,
        ?UploadedFile $fileBl,
        ?UploadedFile $fileFacture,
        ?UploadedFile $fileDeclaration,
    ): void {
        $files = [
            'DEMANDE MANUSCRITE' => $this->fileInfo($fileDemandeManuscrite),
            'BAD SHIPPING' => $this->fileInfo($fileBadShipping),
            'BL' => $this->fileInfo($fileBl),
            'FACTURE' => $this->fileInfo($fileFacture),
            'DECLARATION' => $this->fileInfo($fileDeclaration),
        ];

        $attachments = array_values(array_filter([
            $fileDemandeManuscrite,
            $fileBadShipping,
            $fileBl,
            $fileFacture,
            $fileDeclaration,
        ]));

        $subject = sprintf('[Dakar Terminal] Nouvelle demande de remise - %s %s', $nom, $prenom);
        $html = $this->buildRequestHtml(
            type: 'Demande de remise',
            accentColor: '#4B49AC',
            nom: $nom,
            prenom: $prenom,
            email: $email,
            numeroBl: $numeroBl,
            maisonTransit: $maisonTransit,
            fichiers: $files,
        );

        $this->sendToOne($this->directorEmail(), $subject, $html, $attachments);
    }

    public function sendValidationApprovedEmail(string $toEmail, ?string $nom, ?string $prenom, string $bl): void
    {
        $subject = '[Dakar Terminal] Votre dossier a ete valide - Ndeg BL '.$bl;
        $html = $this->buildResultHtml(
            approved: true,
            nom: $nom,
            prenom: $prenom,
            bl: $bl,
            motif: null,
            platformUrl: url('/demat'),
            title: 'Dossier valide',
            successMessage: 'Votre dossier a ete valide avec succes.',
        );

        $this->sendToOne($toEmail, $subject, $html);
    }

    public function sendValidationRejectedEmail(string $toEmail, ?string $nom, ?string $prenom, string $bl, ?string $motif): void
    {
        $subject = '[Dakar Terminal] Votre dossier a ete rejete - Ndeg BL '.$bl;
        $html = $this->buildResultHtml(
            approved: false,
            nom: $nom,
            prenom: $prenom,
            bl: $bl,
            motif: $motif,
            platformUrl: url('/demat'),
            title: 'Dossier rejete',
            successMessage: 'Votre dossier a ete rejete.',
        );

        $this->sendToOne($toEmail, $subject, $html);
    }

    public function sendRemiseDirectionNotifEmail(string $directorEmail, ?string $nom, ?string $prenom, string $bl, ?string $maison): void
    {
        $subject = '[Dakar Terminal] Demande de remise en attente de validation - Ndeg BL '.$bl;
        $html = $this->wrapHtml(
            'Demande de remise en attente de validation',
            '#4B49AC',
            implode('', [
                $this->infoRow('Nom', $nom),
                $this->infoRow('Prenom', $prenom),
                $this->infoRow('Ndeg BL', $bl),
                $this->infoRow('Maison de transit', $maison),
                $this->buttonRow(url('/facturation/remises'), 'Voir la demande'),
            ]),
            'Recue le <strong>'.$this->escape($this->nowFormatted()).'</strong>',
        );

        $this->sendToOne($directorEmail, $subject, $html);
    }

    public function sendRemiseValidatedByDirectionEmail(string $toEmail, ?string $nom, ?string $prenom, string $bl, mixed $pourcentage): void
    {
        $this->sendRemiseValidatedByDirectionEmailWithMotif($toEmail, $nom, $prenom, $bl, $pourcentage, null);
    }

    public function sendRemiseValidatedByDirectionEmailWithMotif(string $toEmail, ?string $nom, ?string $prenom, string $bl, mixed $pourcentage, ?string $motif): void
    {
        $pct = $pourcentage !== null ? rtrim(rtrim((string) $pourcentage, '0'), '.').' %' : '-';
        $motifRow = filled($motif)
            ? $this->infoRow('Motif / observation', '<span style="color:#4B49AC">'.$this->escape($motif).'</span>', true)
            : '';

        $subject = '[Dakar Terminal] Votre demande de remise a ete approuvee - Ndeg BL '.$bl;
        $html = $this->wrapHtml(
            'Remise approuvee',
            '#28a745',
            implode('', [
                '<tr><td colspan="2" style="padding:14px;text-align:center;font-size:15px;font-weight:700;color:#28a745;background:#28a74515">Votre demande de remise a ete approuvee.</td></tr>',
                $this->infoRow('Nom', $nom),
                $this->infoRow('Prenom', $prenom),
                $this->infoRow('Ndeg BL', $bl),
                $this->infoRow('Taux de remise accorde', '<strong style="color:#4B49AC">'.$this->escape($pct).'</strong>', true),
                $motifRow,
                $this->buttonRow(url('/demat'), 'Acceder a la plateforme'),
            ]),
            'Traitee le <strong>'.$this->escape($this->nowFormatted()).'</strong>',
        );

        $this->sendToDirectorAndUser($toEmail, $subject, $html);
    }

    public function sendRemiseRejectedEmail(string $toEmail, ?string $nom, ?string $prenom, string $bl, ?string $motif): void
    {
        $subject = '[Dakar Terminal] Votre demande de remise a ete rejetee - Ndeg BL '.$bl;
        $html = $this->buildResultHtml(
            approved: false,
            nom: $nom,
            prenom: $prenom,
            bl: $bl,
            motif: $motif,
            platformUrl: url('/demat'),
            title: 'Demande de remise rejetee',
            successMessage: 'Votre demande de remise a ete rejetee.',
        );

        $this->sendToDirectorAndUser($toEmail, $subject, $html);
    }

    public function directorEmail(): string
    {
        return (string) config('demat.director_email', self::DEFAULT_DIRECTOR_EMAIL);
    }

    public function sendIesAccessLinkEmail(string $toEmail, string $platformUrl): void
    {
        $subject = "[Dakar Terminal] Votre lien d'acces a la plateforme IES";
        $html = $this->buildIesHtml(
            title: 'Lien d\'acces IES',
            accentColor: '#4B49AC',
            intro: 'Bonjour,<br>Vous trouverez ci-dessous votre lien d\'acces a la plateforme IES de Dakar Terminal.',
            email: null,
            password: null,
            platformUrl: $platformUrl,
            buttonLabel: 'Acceder a la plateforme',
        );

        $this->sendToOne($toEmail, $subject, $html);
    }

    public function sendIesAccountCreatedEmail(string $toEmail, string $password, string $platformUrl): void
    {
        $subject = '[Dakar Terminal] Creation de votre compte IES';
        $html = $this->buildIesHtml(
            title: 'Creation de compte IES',
            accentColor: '#1565C0',
            intro: 'Bonjour,<br>Votre compte IES a ete cree. Voici vos informations de connexion :',
            email: $toEmail,
            password: $password,
            platformUrl: $platformUrl,
            buttonLabel: 'Acceder a la plateforme',
        );

        $this->sendToOne($toEmail, $subject, $html);
    }

    public function sendMenuDuJourEmail(string $toEmail, string $subject, string $html): void
    {
        $this->sendToOne($toEmail, $subject, $html);
    }

    public function buildMenuDuJourHtml(string $plat1, string $plat2): string
    {
        return $this->wrapHtml(
            'Menu du jour',
            '#4B49AC',
            implode('', [
                '<tr><td colspan="2" style="padding:0 0 18px;color:#555;font-size:13px;line-height:1.7">Bonjour,<br>Voici le menu du jour de Dakar Terminal.</td></tr>',
                $this->infoRow('Plat 1', $plat1),
                $this->infoRow('Plat 2', $plat2),
            ]),
            'Envoye le <strong>'.$this->escape($this->nowFormatted()).'</strong>',
        );
    }

    public function sendIesPasswordResetEmail(string $toEmail, string $newPassword, string $platformUrl): void
    {
        $subject = '[Dakar Terminal] Reinitialisation de votre mot de passe IES';
        $html = $this->buildIesHtml(
            title: 'Reinitialisation de mot de passe',
            accentColor: '#E65100',
            intro: 'Bonjour,<br>Votre mot de passe IES a ete reinitialise. Voici vos nouvelles informations :',
            email: $toEmail,
            password: $newPassword,
            platformUrl: $platformUrl,
            buttonLabel: 'Acceder a la plateforme',
        );

        $this->sendToOne($toEmail, $subject, $html);
    }

    private function sendToAll(string $subject, string $html, array $attachments = [], array $additionalRecipients = []): void
    {
        $recipients = array_unique(array_filter(array_merge($this->configuredRecipients(), $additionalRecipients)));

        Log::info('Demat mail broadcast prepared.', [
            'subject' => $subject,
            'recipients' => array_values($recipients),
            'attachments_count' => count($attachments),
        ]);

        foreach ($recipients as $recipient) {
            $this->sendToOne($recipient, $subject, $html, $attachments);
        }
    }

    private function sendToConfiguredAndUser(string $toEmail, string $subject, string $html, array $attachments = []): void
    {
        $recipients = array_unique(array_filter(array_merge([$toEmail], $this->configuredRecipients())));

        Log::info('Demat mail multi-recipient prepared.', [
            'subject' => $subject,
            'recipients' => array_values($recipients),
            'attachments_count' => count($attachments),
        ]);

        foreach ($recipients as $recipient) {
            $this->sendToOne($recipient, $subject, $html, $attachments);
        }
    }

    private function sendToDirectorAndUser(string $toEmail, string $subject, string $html, array $attachments = []): void
    {
        $recipients = array_unique(array_filter([$toEmail, $this->directorEmail()]));

        Log::info('Demat mail remise result prepared.', [
            'subject' => $subject,
            'recipients' => array_values($recipients),
            'attachments_count' => count($attachments),
        ]);

        foreach ($recipients as $recipient) {
            $this->sendToOne($recipient, $subject, $html, $attachments);
        }
    }

    private function sendToOne(string $to, string $subject, string $html, array $attachments = []): void
    {
        Log::info('Demat mail sending.', [
            'to' => $to,
            'subject' => $subject,
            'attachments_count' => count($attachments),
        ]);

        Mail::send([], [], function ($message) use ($to, $subject, $html, $attachments): void {
            $compiledHtml = $html;
            $logoPath = public_path('img/image.png');

            if (File::exists($logoPath)) {
                $compiledHtml = str_replace('__DT_LOGO__', $message->embed($logoPath), $compiledHtml);
            } else {
                $compiledHtml = str_replace('__DT_LOGO__', '', $compiledHtml);
            }

            $message->to($to)
                ->subject($subject)
                ->html($compiledHtml);

            foreach ($attachments as $attachment) {
                if (! $attachment instanceof UploadedFile || ! $attachment->isValid()) {
                    continue;
                }

                $message->attachData(
                    file_get_contents($attachment->getRealPath()),
                    $attachment->getClientOriginalName() ?: 'fichier',
                    ['mime' => $attachment->getMimeType() ?: 'application/octet-stream']
                );
            }
        });
    }

    private function buildRequestHtml(
        string $type,
        string $accentColor,
        ?string $nom,
        ?string $prenom,
        ?string $email,
        ?string $numeroBl,
        ?string $maisonTransit,
        array $fichiers,
    ): string {
        $fileRows = '';

        foreach ($fichiers as $label => $value) {
            $fileRows .= $this->infoRow(
                $label,
                $value ? '<span style="color:#28a745">Fourni ('.$value.')</span>' : '<span style="color:#dc3545">Non fourni</span>',
                true,
            );
        }

        return $this->wrapHtml(
            $type,
            $accentColor,
            implode('', [
                $this->infoRow('Nom', $nom),
                $this->infoRow('Prenom', $prenom),
                $this->infoRow('Email', $email),
                $this->infoRow('Numero de BL', $numeroBl),
                $this->infoRow('Maison de transit', $maisonTransit),
                $fileRows,
            ]),
            'Recue le <strong>'.$this->escape($this->nowFormatted()).'</strong>',
        );
    }

    private function buildResultHtml(
        bool $approved,
        ?string $nom,
        ?string $prenom,
        string $bl,
        ?string $motif,
        string $platformUrl,
        string $title,
        string $successMessage,
    ): string {
        $accentColor = $approved ? '#28a745' : '#dc3545';
        $motifRow = ! $approved && filled($motif)
            ? $this->infoRow('Motif du rejet', '<span style="color:#dc3545">'.$this->escape($motif).'</span>', true)
            : '';

        return $this->wrapHtml(
            $title,
            $accentColor,
            implode('', [
                '<tr><td colspan="2" style="padding:14px;text-align:center;font-size:15px;font-weight:700;color:'.$accentColor.';background:'.$accentColor.'15">'.$successMessage.'</td></tr>',
                $this->infoRow('Nom', $nom),
                $this->infoRow('Prenom', $prenom),
                $this->infoRow('Ndeg BL', $bl),
                $motifRow,
                $this->buttonRow($platformUrl, 'Acceder a la plateforme'),
            ]),
            'Traite le <strong>'.$this->escape($this->nowFormatted()).'</strong>',
        );
    }

    private function buildIesHtml(
        string $title,
        string $accentColor,
        string $intro,
        ?string $email,
        ?string $password,
        string $platformUrl,
        string $buttonLabel,
    ): string {
        $credentialsRows = '';

        if (filled($email)) {
            $credentialsRows .= $this->infoRow('Email', $email);
        }

        if (filled($password)) {
            $credentialsRows .= $this->infoRow(
                'Mot de passe',
                '<code style="background:#f4f4f4;padding:2px 6px;border-radius:4px;font-size:13px">'.$this->escape($password).'</code>',
                true,
            );
        }

        return $this->wrapHtml(
            $title,
            $accentColor,
            implode('', [
                '<tr><td colspan="2" style="padding:0 0 18px;color:#555;font-size:13px;line-height:1.7">'.$intro.'</td></tr>',
                $credentialsRows,
                $this->buttonRow($platformUrl, $buttonLabel),
            ]),
            'Notification envoyee le <strong>'.$this->escape($this->nowFormatted()).'</strong>',
        );
    }

    private function wrapHtml(string $title, string $accentColor, string $rows, string $intro): string
    {
        return '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head><body style="margin:0;padding:0;background:#f4f7ff;font-family:Helvetica,Arial,sans-serif;">'
            .'<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7ff;padding:40px 0"><tr><td align="center">'
            .'<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08)">'
            .'<tr><td style="padding:24px 32px;text-align:center;border-bottom:3px solid '.$accentColor.'">'
            .'<img src="__DT_LOGO__" alt="Dakar Terminal" style="max-width:260px;max-height:52px;width:auto;height:auto;display:block;margin:0 auto 14px;">'
            .'<p style="color:'.$accentColor.';margin:0;font-size:18px;font-weight:700;letter-spacing:.4px">'.$this->escape($title).'</p>'
            .'</td></tr>'
            .'<tr><td style="padding:32px">'
            .'<p style="color:#555;font-size:13px;margin:0 0 20px">'.$intro.'</p>'
            .'<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse">'.$rows.'</table>'
            .'</td></tr>'
            .'<tr><td style="background:#f8f9ff;padding:18px 32px;text-align:center;border-top:1px solid #e8e8f0">'
            .'<p style="color:#aaa;font-size:11px;margin:0">&copy; 2026 DakarTerminal - Ce message est genere automatiquement.</p>'
            .'</td></tr></table></td></tr></table></body></html>';
    }

    private function infoRow(string $label, mixed $value, bool $raw = false): string
    {
        if ($value === null || $value === '') {
            $display = '-';
        } elseif ($raw) {
            $display = (string) $value;
        } else {
            $display = $this->escape((string) $value);
        }

        return '<tr style="border-bottom:1px solid #f0f0f0">'
            .'<td style="padding:10px 0;font-size:13px;font-weight:700;color:#444;width:180px;vertical-align:top">'.$this->escape($label).'</td>'
            .'<td style="padding:10px 0;font-size:13px;color:#333;vertical-align:top">'.$display.'</td>'
            .'</tr>';
    }

    private function buttonRow(string $url, string $label): string
    {
        return '<tr><td colspan="2" style="padding:28px 0 8px;text-align:center">'
            .'<a href="'.$this->escape($url).'" style="display:inline-block;background:#4B49AC;color:#fff;text-decoration:none;border-radius:8px;padding:12px 28px;font-size:14px;font-weight:700;letter-spacing:.3px">'.$this->escape($label).'</a>'
            .'</td></tr>';
    }

    private function fileInfo(?UploadedFile $file): ?string
    {
        if (! $file instanceof UploadedFile || ! $file->isValid()) {
            return null;
        }

        return ($file->getClientOriginalName() ?: 'fichier').' ('.(int) ceil($file->getSize() / 1024).' Ko)';
    }

    private function nowFormatted(): string
    {
        return now()->format('d/m/Y H:i');
    }

    private function escape(string $value): string
    {
        return e($value);
    }

    private function configuredRecipients(): array
    {
        $configured = config('demat.recipients', []);

        if (! is_array($configured)) {
            $configured = [];
        }

        return array_values(array_unique(array_filter(array_merge(
            self::DEFAULT_RECIPIENTS,
            $configured,
        ))));
    }
}
