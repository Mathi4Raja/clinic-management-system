<?php
/**
 * Admin API - System Audit / Analytics
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_ADMIN]);

header('Content-Type: application/json');

$pdo = getDBConnection();

try {
    $stats = [
        'total_patients' => $pdo->query("SELECT COUNT(*) FROM patient_profiles")->fetchColumn(),
        'total_doctors' => $pdo->query("SELECT COUNT(*) FROM doctors")->fetchColumn(),
        'total_appointments' => $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn(),
        'pending_lab_tests' => $pdo->query("SELECT COUNT(*) FROM lab_tests WHERE status = 'pending'")->fetchColumn(),
        'total_revenue' => $pdo->query("SELECT SUM(amount) FROM invoices WHERE status = 'paid'")->fetchColumn() ?: 0
    ];

    $recent_logs = $pdo->query("SELECT email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

    echo json_encode([
        'stats' => $stats,
        'recent_users' => $recent_logs
    ]);

} catch (PDOException $e) {
    http_response_code(500);
}
