<?php
/**
 * Admin API — Clinic Settings
 * GET  → return all settings (SMTP password masked)
 * POST → batch-upsert settings (skips masked password placeholder)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_ADMIN]);
header('Content-Type: application/json');

$pdo    = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

$allowed_keys = [
    'clinic_name', 'clinic_address', 'clinic_phone', 'clinic_email',
    'smtp_from_name', 'smtp_host', 'smtp_port', 'smtp_user', 'smtp_pass',
    'smtp_encryption', 'email_notifications',
];

try {
    if ($method === 'GET') {
        $stmt = $pdo->query("SELECT setting_key, setting_value FROM clinic_settings");
        $settings = [];
        foreach ($stmt->fetchAll() as $row) {
            // Mask stored SMTP password
            $val = ($row['setting_key'] === 'smtp_pass' && $row['setting_value'] !== '')
                ? '••••••••'
                : $row['setting_value'];
            $settings[$row['setting_key']] = $val;
        }
        echo json_encode(['settings' => $settings]);

    } elseif ($method === 'POST') {
        requireCSRF();
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            exit;
        }

        $stmt = $pdo->prepare(
            "INSERT INTO clinic_settings (setting_key, setting_value)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = CURRENT_TIMESTAMP"
        );

        foreach ($input as $key => $value) {
            if (!in_array($key, $allowed_keys)) continue;
            // Never overwrite the real password with the masked UI placeholder
            if ($key === 'smtp_pass' && $value === '••••••••') continue;
            $stmt->execute([$key, (string)$value]);
        }

        echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
