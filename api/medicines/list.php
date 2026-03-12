<?php
/**
 * Medicines API - List available medicines
 * Used by Doctor (prescribing) and Pharmacist (dispensing).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_DOCTOR, ROLE_PHARMACIST, ROLE_ADMIN, ROLE_RECEPTIONIST]);

header('Content-Type: application/json');

$pdo = getDBConnection();

try {
    $stmt = $pdo->query(
        "SELECT id, name, generic_name, price, stock_quantity
         FROM medicines
         ORDER BY name ASC"
    );
    echo json_encode(['medicines' => $stmt->fetchAll()]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
