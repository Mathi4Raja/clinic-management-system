<?php
/**
 * Public/Patient API - Doctors List
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize(ALL_ROLES);

header('Content-Type: application/json');

$pdo = getDBConnection();

try {
    $stmt = $pdo->query("SELECT d.id, sp.name, d.specialization 
                         FROM doctors d 
                         JOIN staff_profiles sp ON d.user_id = sp.user_id");

    echo json_encode(['doctors' => $stmt->fetchAll()]);
} catch (PDOException $e) {
    http_response_code(500);
}
