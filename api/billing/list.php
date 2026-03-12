<?php
/**
 * Billing API - List Invoices
 * GET ?patient_id= optional (staff filter by patient)
 * GET ?status=     optional (pending|paid|void)
 * Patient: sees own invoices only (ignores patient_id param)
 * Pharmacist / Receptionist / Admin: sees all or filtered
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_PATIENT, ROLE_PHARMACIST, ROLE_RECEPTIONIST, ROLE_ADMIN]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$pdo = getDBConnection();

try {
    $params = [];
    $where  = [];

    if ($_SESSION['role'] === ROLE_PATIENT) {
        // Patient sees only own invoices
        $stmt = $pdo->prepare("SELECT id FROM patient_profiles WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $pp = $stmt->fetch();
        if (!$pp) { echo json_encode(['invoices' => []]); exit; }
        $where[]  = "i.patient_id = ?";
        $params[] = $pp['id'];
    } elseif (!empty($_GET['patient_id'])) {
        $where[]  = "i.patient_id = ?";
        $params[] = (int)$_GET['patient_id'];
    }

    $allowed_statuses = ['pending', 'paid', 'void'];
    if (!empty($_GET['status']) && in_array($_GET['status'], $allowed_statuses, true)) {
        $where[]  = "i.status = ?";
        $params[] = $_GET['status'];
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    $sql = "SELECT i.id, i.patient_id, i.reference_type, i.reference_id,
                   i.amount, i.status, i.created_at,
                   pp.full_name AS patient_name
            FROM invoices i
            JOIN patient_profiles pp ON i.patient_id = pp.id
            {$whereSQL}
            ORDER BY i.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $invoices = $stmt->fetchAll();

    echo json_encode(['invoices' => $invoices]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
