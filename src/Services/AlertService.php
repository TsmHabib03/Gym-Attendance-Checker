<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;
use App\Core\Logger;
use DateTimeImmutable;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

final class AlertService
{
    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    public function sendExpiredScanAlert(array $member): void
    {
        $appName = (string) Config::get('APP_NAME', 'REP CORE FITNESS');
        $appUrl  = rtrim((string) Config::get('APP_URL', ''), '/');
        $logoUrl = 'cid:logo';
        $now     = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $subject = '[' . $appName . '] Expired Membership Scan Alert';

        $htmlBody = $this->renderTemplate('expired_scan_alert', [
            'memberName' => $member['full_name'] ?? 'Unknown',
            'memberCode' => $member['member_code'] ?? '—',
            'expiryDate' => $member['membership_end_date'] ?? '—',
            'scannedAt'  => $now,
            'appName'    => $appName,
            'appUrl'     => $appUrl,
            'logoUrl'    => $logoUrl,
        ]);

        $plainBody = sprintf(
            "[%s] Expired Membership Scan Alert\n\n"
            . "Member %s (%s) attempted to check in with an expired membership.\n"
            . "Membership end date: %s\n"
            . "Scan attempted at: %s\n\n"
            . "Please contact the member to arrange renewal.",
            $appName,
            $member['full_name'] ?? 'Unknown',
            $member['member_code'] ?? '—',
            $member['membership_end_date'] ?? '—',
            $now
        );

        $this->send(
            (string) Config::get('ADMIN_ALERT_EMAIL'),
            $subject,
            $htmlBody,
            $plainBody,
            ['event_type' => 'expired_scan_alert', 'member_id' => $member['id'] ?? null]
        );
    }

    /**
     * Send check-in alerts:
     *   1. Admin alert  — always, using a fresh SMTP connection
     *   2. Member alert — only if the member has an email, using its own fresh connection
     *
     * Each send gets its own PHPMailer instance so there is zero shared MIME
     * state between the two messages. A short pause between sends keeps Gmail
     * from treating the second connection as a duplicate submission.
     */
    public function sendCheckInAlert(array $member): void
    {
        $appName     = (string) Config::get('APP_NAME', 'REP CORE FITNESS');
        $appUrl      = rtrim((string) Config::get('APP_URL', ''), '/');
        $adminEmail  = (string) Config::get('ADMIN_ALERT_EMAIL', '');
        $memberEmail = trim((string) ($member['email'] ?? ''));
        $logoUrl     = 'cid:logo';
        $now         = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        // Resolve member photo once — reused in both emails if present
        $memberPhotoUrl = '';
        $extraImages    = [];
        if (!empty($member['photo_path'])) {
            $photoFullPath = dirname(__DIR__, 2) . '/public' . $member['photo_path'];
            if (is_file($photoFullPath)) {
                $memberPhotoUrl = 'cid:memberphoto';
                $extraImages[]  = [
                    'path' => $photoFullPath,
                    'cid'  => 'memberphoto',
                    'name' => 'member_photo.png',
                ];
            }
        }

        // ── 1. Admin alert — ALWAYS, FIRST ────────────────────────────────
        if ($adminEmail !== '') {
            $adminSubject = '[' . $appName . '] Member Check-In — ' . ($member['full_name'] ?? 'Unknown');

            $adminHtml = $this->renderTemplate('checkin_admin_alert', [
                'memberName'     => $member['full_name'] ?? 'Unknown',
                'memberCode'     => $member['member_code'] ?? '—',
                'memberEmail'    => $memberEmail !== '' ? $memberEmail : '(no email)',
                'checkInDate'    => $now,
                'expiryDate'     => $member['membership_end_date'] ?? '—',
                'appName'        => $appName,
                'appUrl'         => $appUrl,
                'logoUrl'        => $logoUrl,
                'memberPhotoUrl' => $memberPhotoUrl,
            ]);

            // Fall back gracefully if the admin template is missing
            if ($adminHtml === '') {
                $adminHtml = $this->renderTemplate('checkin_alert', [
                    'memberName'     => $member['full_name'] ?? 'Unknown',
                    'memberCode'     => $member['member_code'] ?? '—',
                    'checkInDate'    => $now,
                    'expiryDate'     => $member['membership_end_date'] ?? '—',
                    'appName'        => $appName,
                    'appUrl'         => $appUrl,
                    'logoUrl'        => $logoUrl,
                    'memberPhotoUrl' => $memberPhotoUrl,
                ]);
            }

            $adminPlain = sprintf(
                "[%s] Member Check-In\n\nMember: %s (%s)\nEmail: %s\nCheck-in: %s\nExpires: %s",
                $appName,
                $member['full_name'] ?? 'Unknown',
                $member['member_code'] ?? '—',
                $memberEmail !== '' ? $memberEmail : '(no email)',
                $now,
                $member['membership_end_date'] ?? '—'
            );

            $this->send(
                $adminEmail,
                $adminSubject,
                $adminHtml,
                $adminPlain,
                ['event_type' => 'checkin_admin_alert', 'member_id' => $member['id'] ?? null],
                $extraImages
            );
        } else {
            Logger::warning('sendCheckInAlert: admin alert skipped — ADMIN_ALERT_EMAIL not set');
        }

        // ── 2. Member confirmation — fresh connection, own mailer ──────────
        if ($memberEmail === '') {
            Logger::warning('sendCheckInAlert: member confirmation skipped — no email on profile', [
                'member_id'   => $member['id'] ?? null,
                'member_code' => $member['member_code'] ?? '—',
            ]);
            return;
        }

        // Brief pause so Gmail does not see the two authentications as one
        // duplicate submission (avoids silent drop of the second message).
        sleep(1);

        $memberSubject = '[' . $appName . '] Check-In Confirmation';

        $memberHtml = $this->renderTemplate('checkin_alert', [
            'memberName'     => $member['full_name'] ?? 'Unknown',
            'memberCode'     => $member['member_code'] ?? '—',
            'checkInDate'    => $now,
            'expiryDate'     => $member['membership_end_date'] ?? '—',
            'appName'        => $appName,
            'appUrl'         => $appUrl,
            'logoUrl'        => $logoUrl,
            'memberPhotoUrl' => $memberPhotoUrl,
        ]);

        $memberPlain = sprintf(
            "Hi %s,\n\nYou have successfully checked in at %s.\n"
            . "Member code: %s\nCheck-in time: %s\nMembership expires: %s\n\n— %s",
            $member['full_name'] ?? 'Unknown',
            $appName,
            $member['member_code'] ?? '—',
            $now,
            $member['membership_end_date'] ?? '—',
            $appName
        );

        $this->send(
            $memberEmail,
            $memberSubject,
            $memberHtml,
            $memberPlain,
            ['event_type' => 'checkin_alert', 'member_id' => $member['id'] ?? null],
            $extraImages
        );
    }

    public function sendExpiryReminders(int $days): int
    {
        $pdo  = Database::connection();
        $from = new DateTimeImmutable('today');
        $to   = $from->modify('+' . $days . ' days');

        $stmt = $pdo->prepare(
            'SELECT id, full_name, email, member_code, membership_end_date
             FROM members
             WHERE email IS NOT NULL AND email <> ""
               AND membership_end_date BETWEEN :from_date AND :to_date'
        );
        $stmt->execute([
            ':from_date' => $from->format('Y-m-d'),
            ':to_date'   => $to->format('Y-m-d'),
        ]);

        $appName = (string) Config::get('APP_NAME', 'REP CORE FITNESS');
        $appUrl  = rtrim((string) Config::get('APP_URL', ''), '/');
        $logoUrl = 'cid:logo';
        $count   = 0;

        foreach ($stmt->fetchAll() as $member) {
            $expiryDate = (string) $member['membership_end_date'];
            $expiryDt   = new DateTimeImmutable($expiryDate);
            $daysLeft   = (int) $from->diff($expiryDt)->days;

            $subject = '[' . $appName . '] Membership Expiry Reminder — '
                . $daysLeft . ' day' . ($daysLeft !== 1 ? 's' : '') . ' left';

            $htmlBody = $this->renderTemplate('expiry_reminder', [
                'memberName'      => $member['full_name'],
                'memberCode'      => $member['member_code'],
                'expiryDate'      => $expiryDate,
                'daysUntilExpiry' => $daysLeft,
                'appName'         => $appName,
                'appUrl'          => $appUrl,
                'logoUrl'         => $logoUrl,
            ]);

            $plainBody = sprintf(
                "Hi %s,\n\nYour gym membership (code: %s) will expire on %s (%d day%s remaining).\n\n"
                . "Please visit the gym front desk to renew your membership and keep your access.\n\n— %s",
                $member['full_name'],
                $member['member_code'],
                $expiryDate,
                $daysLeft,
                $daysLeft !== 1 ? 's' : '',
                $appName
            );

            $sent = $this->send(
                (string) $member['email'],
                $subject,
                $htmlBody,
                $plainBody,
                ['event_type' => 'expiry_reminder', 'member_id' => $member['id']]
            );

            if ($sent) {
                $count++;
            }
        }

        return $count;
    }

    // ----------------------------------------------------------------
    // Private helpers
    // ----------------------------------------------------------------

    /**
     * Build a pre-configured PHPMailer instance (no recipient/subject set yet).
     */
    private function buildMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = (string) Config::get('SMTP_HOST');
        $mail->Port       = Config::int('SMTP_PORT', 587);
        $mail->SMTPAuth   = true;
        $mail->Username   = (string) Config::get('SMTP_USERNAME');
        $mail->Password   = (string) Config::get('SMTP_PASSWORD');
        $mail->SMTPSecure = (string) Config::get('SMTP_ENCRYPTION', 'tls');
        $mail->Timeout    = 15; // seconds — prevent long hangs
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';

        $mail->setFrom(
            (string) Config::get('MAIL_FROM_ADDRESS'),
            (string) Config::get('MAIL_FROM_NAME', 'REP CORE FITNESS')
        );

        return $mail;
    }

    /**
     * Send one email using an existing PHPMailer instance (supports SMTPKeepAlive).
     * Clears recipients and attachments between sends so the same mailer can be reused.
     */
    private function sendViaMailer(
        PHPMailer $mail,
        string    $to,
        string    $subject,
        string    $htmlBody,
        string    $plainBody,
        array     $extraImages,
        array     $context
    ): bool {
        $sent  = false;
        $error = null;

        try {
            $mail->clearAddresses();
            $mail->clearAttachments();

            $mail->addAddress($to);
            $mail->Subject = $subject;

            // Logo
            $logoPath = dirname(__DIR__, 2) . '/public/assets/img/repcore-removebg-preview.png';
            if (is_file($logoPath)) {
                try {
                    $mail->addEmbeddedImage($logoPath, 'logo', 'logo.png');
                } catch (\Throwable) {
                    // non-fatal
                }
            }

            foreach ($extraImages as $img) {
                if (!empty($img['path']) && is_file($img['path'])) {
                    try {
                        $mail->addEmbeddedImage($img['path'], $img['cid'], $img['name'] ?? basename($img['path']));
                    } catch (\Throwable) {
                        // non-fatal
                    }
                }
            }

            if ($htmlBody !== '') {
                $mail->isHTML(true);
                $mail->Body    = $htmlBody;
                $mail->AltBody = $plainBody;
            } else {
                $mail->isHTML(false);
                $mail->Body = $plainBody;
            }

            $mail->send();
            $sent = true;

            Logger::info('Email sent successfully', ['to' => $to, 'subject' => $subject]);
        } catch (Exception $exception) {
            $error = $exception->getMessage();
            Logger::error('Failed to send email', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $error,
            ]);
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            Logger::error('Unexpected error sending email', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $error,
            ]);
        }

        $this->logMail($to, $subject, $plainBody, $sent, $error, $context);
        return $sent;
    }

    /**
     * Convenience wrapper — creates a fresh mailer for single-recipient sends.
     */
    private function send(
        string $to,
        string $subject,
        string $htmlBody,
        string $plainBody,
        array  $context,
        array  $extraImages = []
    ): bool {
        $mail = $this->buildMailer();
        return $this->sendViaMailer($mail, $to, $subject, $htmlBody, $plainBody, $extraImages, $context);
    }

    private function renderTemplate(string $name, array $variables): string
    {
        $templatePath = dirname(__DIR__, 2) . '/views/emails/' . $name . '.php';

        if (!is_file($templatePath)) {
            Logger::error('Email template not found', ['template' => $name]);
            return '';
        }

        extract($variables, EXTR_SKIP);
        ob_start();
        require $templatePath;
        return (string) ob_get_clean();
    }

    private function logMail(
        string  $to,
        string  $subject,
        string  $body,
        bool    $sent,
        ?string $errorMessage,
        array   $context
    ): void {
        try {
            $pdo  = Database::connection();
            $stmt = $pdo->prepare(
                'INSERT INTO email_logs
                    (recipient_email, subject_line, body_text, was_sent, error_message, context_json, created_at)
                 VALUES
                    (:recipient_email, :subject_line, :body_text, :was_sent, :error_message, :context_json, :created_at)'
            );
            $stmt->execute([
                ':recipient_email' => $to,
                ':subject_line'    => $subject,
                ':body_text'       => $body,
                ':was_sent'        => $sent ? 1 : 0,
                ':error_message'   => $errorMessage,
                ':context_json'    => json_encode($context, JSON_UNESCAPED_SLASHES),
                ':created_at'      => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Never let a logging failure abort the request
        }
    }
}
