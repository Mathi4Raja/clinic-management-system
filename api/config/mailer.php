<?php
/**
 * Shared Mailer Utility — uses PHPMailer for real SMTP delivery.
 *
 * SMTP settings are read from the clinic_settings DB table (configured via
 * Admin → Settings → Email/SMTP). Falls back to PHP mail() if no SMTP host
 * is configured (useful for MailHog in local XAMPP dev).
 *
 * Returns:
 *   true       — message accepted
 *   false      — send failed
 *   'disabled' — email_notifications is off
 */

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

function sendClinicEmail(PDO $pdo, string $to, string $subject, string $body): bool|string
{
    // Load settings from DB
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM clinic_settings");
    $settings = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'setting_value', 'setting_key');

    // Respect the notifications toggle
    if (($settings['email_notifications'] ?? '1') === '0') {
        return 'disabled';
    }

    $fromName  = $settings['smtp_from_name'] ?? 'Clinic CMS';
    $fromEmail = filter_var($settings['clinic_email'] ?? '', FILTER_VALIDATE_EMAIL)
                 ?: 'noreply@clinic.com';
    $smtpHost  = trim($settings['smtp_host'] ?? '');
    $smtpPort  = (int)($settings['smtp_port'] ?? 587);
    $smtpUser  = $settings['smtp_user'] ?? '';
    $smtpPass  = $settings['smtp_pass'] ?? '';
    $smtpEnc   = $settings['smtp_encryption'] ?? 'tls';

    $mail = new PHPMailer(true);

    try {
        if ($smtpHost !== '') {
            // ── Real SMTP (Gmail, SendGrid, etc.) ───────────────────────
            $mail->isSMTP();
            $mail->Host       = $smtpHost;
            $mail->SMTPAuth   = ($smtpUser !== '');
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->Port       = $smtpPort;

            if ($smtpEnc === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } elseif ($smtpEnc === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } else {
                $mail->SMTPAutoTLS = false;
            }
        } else {
            // ── Fallback: PHP mail() / sendmail / MailHog ───────────────
            $mail->isMail();
        }

        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->CharSet  = 'UTF-8';
        $mail->Subject  = $subject;
        $mail->Body     = $body;
        $mail->AltBody  = strip_tags($body);

        $mail->send();
        return true;

    } catch (MailerException $e) {
        error_log('ClinicCMS Mailer: ' . $mail->ErrorInfo);
        return false;
    }
}
