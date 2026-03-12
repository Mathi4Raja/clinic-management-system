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

requireCSRF();

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
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO invoices (patient_id, reference_type, reference_id, amount, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$patient_id, $reference_type, $reference_id, $amount]);
    $invoice_id = $pdo->lastInsertId();

    // Decrement medicine stock for prescription dispense
    if ($reference_type === 'prescription') {
        $stmt = $pdo->prepare(
            "UPDATE medicines m
             JOIN prescription_items pi ON m.id = pi.medicine_id
             SET m.stock_quantity = GREATEST(0, m.stock_quantity - 1)
             WHERE pi.prescription_id = ?"
        );
        $stmt->execute([$reference_id]);
    }

    $pdo->commit();
    http_response_code(201);
    echo json_encode(['success' => true, 'invoice_id' => $invoice_id, 'message' => 'Invoice generated and stock updated']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
