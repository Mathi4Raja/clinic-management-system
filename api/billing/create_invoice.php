<?php
/**
 * Billing API - Create Invoice
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_PHARMACIST, ROLE_RECEPTIONIST, ROLE_ADMIN]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$patient_id = $input['patient_id'] ?? null;
$reference_type = $input['reference_type'] ?? ''; // prescription, lab, consultation
$reference_id = $input['reference_id'] ?? null;
$amount = $input['amount'] ?? 0;

if (!$patient_id || !$reference_type || !$reference_id || $amount <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid invoice data']);
    exit;
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare("INSERT INTO invoices (patient_id, reference_type, reference_id, amount, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$patient_id, $reference_type, $reference_id, $amount]);

    http_response_code(201);
    echo json_encode(['success' => true, 'invoice_id' => $pdo->lastInsertId(), 'message' => 'Invoice generated successfully']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
