<?php
/**
 * Billing API - Update Invoice
 * PATCH → update invoice status (paid | void)
 * Accessible by: pharmacist, receptionist, admin
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_PHARMACIST, ROLE_RECEPTIONIST, ROLE_ADMIN]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

requireCSRF();

$input  = json_decode(file_get_contents('php://input'), true);
$id     = (int)($input['id'] ?? 0);
$status = $input['status'] ?? '';

if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invoice id is required']);
    exit;
}

$allowed = ['paid', 'void'];
if (!in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'status must be "paid" or "void"']);
    exit;
}

$pdo = getDBConnection();

try {
    $check = $pdo->prepare("SELECT id, status FROM invoices WHERE id = ?");
    $check->execute([$id]);
    $invoice = $check->fetch();

    if (!$invoice) {
        http_response_code(404);
        echo json_encode(['error' => 'Invoice not found']);
        exit;
    }

    if ($invoice['status'] === $status) {
        echo json_encode(['success' => true, 'message' => 'No change']);
        exit;
    }

    $stmt = $pdo->prepare("UPDATE invoices SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
