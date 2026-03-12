<?php
/**
 * Admin API — Send Test Email
 * POST body: { "to": "recipient@example.com" }
 *
 * Uses PHP mail() which requires sendmail configured in php.ini.
 * For local dev with XAMPP, install a relay like MailHog (port 1025)
 * and set sendmail_path = "C:/path/to/mailhog.exe sendmail" in php.ini.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';
require_once __DIR__ . '/../config/mailer.php';

authorize([ROLE_ADMIN]);
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

requireCSRF();

$input = json_decode(file_get_contents('php://input'), true);
$to    = filter_var($input['to'] ?? '', FILTER_VALIDATE_EMAIL);

if (!$to) {
    http_response_code(400);
    echo json_encode(['error' => 'A valid recipient email address is required']);
    exit;
}

$pdo = getDBConnection();

$subject = 'Clinic CMS — Test Email';
$body    = '
<html><body style="font-family:Inter,sans-serif;color:#0f172a;padding:32px;max-width:540px;margin:auto">
  <div style="background:#fff7ed;border-radius:12px;padding:24px;border:1px solid #fed7aa;margin-bottom:24px;text-align:center">
    <p style="font-size:32px;margin:0">✅</p>
    <h2 style="margin:8px 0 4px;color:#ea580c">Email is working!</h2>
    <p style="color:#64748b;margin:0;font-size:14px">Your Clinic CMS email configuration is correctly set up.</p>
  </div>
  <p style="color:#475569;font-size:14px">This test was triggered from the Admin Settings panel.</p>
  <p style="color:#94a3b8;font-size:12px;border-top:1px solid #e2e8f0;padding-top:16px;margin-top:24px">
    Sent: ' . date('D, d M Y H:i:s') . '
  </p>
</body></html>';

$result = sendClinicEmail($pdo, $to, $subject, $body);

if ($result === true) {
    echo json_encode([
        'success' => true,
        'message' => "Test email sent to {$to} — check your inbox (and spam folder).",
    ]);
} elseif ($result === 'disabled') {
    echo json_encode([
        'success' => false,
        'message' => 'Email notifications are disabled in settings. Enable them and try again.',
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'PHP mail() returned false. Configure sendmail in php.ini or use a local relay like MailHog (port 1025).',
    ]);
}
