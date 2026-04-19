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
    public function sendExpiredScanAlert(array $member): void
    {
        $subject = 'Expired Membership Scan Alert';
        $body = sprintf(
            "Member %s (%s) attempted to check in with expired membership. Membership end date: %s",
            $member['full_name'],
            $member['member_code'],
            $member['membership_end_date']
        );

        $this->send((string) Config::get('ADMIN_ALERT_EMAIL'), $subject, $body, [
            'event_type' => 'expired_scan_alert',
            'member_id' => $member['id'] ?? null,
        ]);
    }

    public function sendExpiryReminders(int $days): int
    {
        $pdo = Database::connection();
        $from = new DateTimeImmutable('today');
        $to = $from->modify('+' . $days . ' days');

        $stmt = $pdo->prepare('SELECT id, full_name, email, member_code, membership_end_date FROM members WHERE email IS NOT NULL AND email <> "" AND membership_end_date BETWEEN :from_date AND :to_date');
        $stmt->execute([
            ':from_date' => $from->format('Y-m-d'),
            ':to_date' => $to->format('Y-m-d'),
        ]);

        $count = 0;
        foreach ($stmt->fetchAll() as $member) {
            $subject = 'Membership Expiry Reminder';
            $body = sprintf(
                "Hi %s, your gym membership (code %s) will expire on %s. Please renew to continue your access.",
                $member['full_name'],
                $member['member_code'],
                $member['membership_end_date']
            );

            $sent = $this->send((string) $member['email'], $subject, $body, [
                'event_type' => 'expiry_reminder',
                'member_id' => $member['id'],
            ]);

            if ($sent) {
                $count++;
            }
        }

        return $count;
    }

    private function send(string $to, string $subject, string $body, array $context): bool
    {
        $mail = new PHPMailer(true);
        $sent = false;
        $errorMessage = null;

        try {
            $mail->isSMTP();
            $mail->Host = (string) Config::get('SMTP_HOST');
            $mail->Port = Config::int('SMTP_PORT', 587);
            $mail->SMTPAuth = true;
            $mail->Username = (string) Config::get('SMTP_USERNAME');
            $mail->Password = (string) Config::get('SMTP_PASSWORD');
            $mail->SMTPSecure = (string) Config::get('SMTP_ENCRYPTION', 'tls');

            $mail->setFrom((string) Config::get('MAIL_FROM_ADDRESS'), (string) Config::get('MAIL_FROM_NAME'));
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);

            $mail->send();
            $sent = true;
        } catch (Exception $exception) {
            $errorMessage = $exception->getMessage();
            Logger::error('Failed to send email alert', ['to' => $to, 'subject' => $subject, 'error' => $errorMessage]);
        }

        $this->logMail($to, $subject, $body, $sent, $errorMessage, $context);
        return $sent;
    }

    private function logMail(string $to, string $subject, string $body, bool $sent, ?string $errorMessage, array $context): void
    {
        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('INSERT INTO email_logs (recipient_email, subject_line, body_text, was_sent, error_message, context_json, created_at) VALUES (:recipient_email, :subject_line, :body_text, :was_sent, :error_message, :context_json, :created_at)');
            $stmt->execute([
                ':recipient_email' => $to,
                ':subject_line' => $subject,
                ':body_text' => $body,
                ':was_sent' => $sent ? 1 : 0,
                ':error_message' => $errorMessage,
                ':context_json' => json_encode($context, JSON_UNESCAPED_SLASHES),
                ':created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable) {
            // Avoid interrupting request flow if email log table is unavailable.
        }
    }
}
