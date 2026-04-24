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

        $subject = '[' . $appName . '] Expired Membership Scan Alert';

        $htmlBody = $this->renderTemplate('expired_scan_alert', [
            'memberName' => $member['full_name'] ?? 'Unknown',
            'memberCode' => $member['member_code'] ?? '—',
            'expiryDate' => $member['membership_end_date'] ?? '—',
            'scannedAt'  => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
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
            (new DateTimeImmutable())->format('Y-m-d H:i:s')
        );

        $this->send(
            (string) Config::get('ADMIN_ALERT_EMAIL'),
            $subject,
            $htmlBody,
            $plainBody,
            ['event_type' => 'expired_scan_alert', 'member_id' => $member['id'] ?? null]
        );
    }

    public function sendCheckInAlert(array $member): void
    {
        $email = (string) ($member['email'] ?? '');
        if ($email === '') {
            return;
        }

        $appName = (string) Config::get('APP_NAME', 'REP CORE FITNESS');
        $appUrl  = rtrim((string) Config::get('APP_URL', ''), '/');
        $logoUrl = 'cid:logo';

        $subject = '[' . $appName . '] Check-In Confirmation';

        $memberPhotoUrl = '';
        $extraImages    = [];
        if (!empty($member['photo_path'])) {
            $photoFullPath = dirname(__DIR__, 2) . '/public' . $member['photo_path'];
            if (is_file($photoFullPath)) {
                $memberPhotoUrl = 'cid:memberphoto';
                $extraImages[] = [
                    'path' => $photoFullPath,
                    'cid'  => 'memberphoto',
                    'name' => 'member_photo.png',
                ];
            }
        }

        $htmlBody = $this->renderTemplate('checkin_alert', [
            'memberName'     => $member['full_name'] ?? 'Unknown',
            'memberCode'     => $member['member_code'] ?? '—',
            'checkInDate'    => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            'expiryDate'     => $member['membership_end_date'] ?? '—',
            'appName'        => $appName,
            'appUrl'         => $appUrl,
            'logoUrl'        => $logoUrl,
            'memberPhotoUrl' => $memberPhotoUrl,
        ]);

        $plainBody = sprintf(
            "Hi %s,\n\n"
            . "You have successfully checked in at %s.\n"
            . "Member code: %s\n"
            . "Check-in time: %s\n"
            . "Membership expires: %s\n\n"
            . "— %s",
            $member['full_name'] ?? 'Unknown',
            $appName,
            $member['member_code'] ?? '—',
            (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            $member['membership_end_date'] ?? '—',
            $appName
        );

        $this->send(
            $email,
            $subject,
            $htmlBody,
            $plainBody,
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

            $subject = '[' . $appName . '] Membership Expiry Reminder — ' . $daysLeft . ' day' . ($daysLeft !== 1 ? 's' : '') . ' left';

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
                "Hi %s,\n\n"
                . "Your gym membership (code: %s) will expire on %s (%d day%s remaining).\n\n"
                . "Please visit the gym front desk to renew your membership and keep your access.\n\n"
                . "— %s",
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
     * Render an email view template to a string.
     */
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

    /**
     * Send a single email with HTML + plain-text alternative bodies.
     */
    private function send(
        string $to,
        string $subject,
        string $htmlBody,
        string $plainBody,
        array  $context,
        array  $extraImages = []
    ): bool {
        $mail  = new PHPMailer(true);
        $sent  = false;
        $error = null;

        try {
            $mail->isSMTP();
            $mail->Host       = (string) Config::get('SMTP_HOST');
            $mail->Port       = Config::int('SMTP_PORT', 587);
            $mail->SMTPAuth   = true;
            $mail->Username   = (string) Config::get('SMTP_USERNAME');
            $mail->Password   = (string) Config::get('SMTP_PASSWORD');
            $mail->SMTPSecure = (string) Config::get('SMTP_ENCRYPTION', 'tls');

            $mail->setFrom(
                (string) Config::get('MAIL_FROM_ADDRESS'),
                (string) Config::get('MAIL_FROM_NAME', 'REP CORE FITNESS')
            );
            $mail->addAddress($to);

            $mail->Subject  = $subject;
            $mail->CharSet  = 'UTF-8';
            $mail->Encoding = 'base64';

            $logoPath = dirname(__DIR__, 2) . '/public/assets/img/repcore-removebg-preview.png';
            if (is_file($logoPath)) {
                try {
                    $mail->addEmbeddedImage($logoPath, 'logo', 'logo.png');
                } catch (\Throwable) {
                    // silently ignore if logo embedding fails
                }
            }

            foreach ($extraImages as $img) {
                if (!empty($img['path']) && is_file($img['path'])) {
                    try {
                        $mail->addEmbeddedImage($img['path'], $img['cid'], $img['name'] ?? basename($img['path']));
                    } catch (\Throwable) {
                        // ignore single image failure
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
        } catch (Exception $exception) {
            $error = $exception->getMessage();
            Logger::error('Failed to send email alert', [
                'to'      => $to,
                'subject' => $subject,
                'error'   => $error,
            ]);
        }

        $this->logMail($to, $subject, $plainBody, $sent, $error, $context);
        return $sent;
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
            // Avoid interrupting request flow if email log table is unavailable.
        }
    }
}
