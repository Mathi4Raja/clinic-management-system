<?php
/**
 * Patient API - Search/List
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

// Staff and Doctors can list/search patients
authorize([ROLE_RECEPTIONIST, ROLE_DOCTOR, ROLE_ADMIN]);

header('Content-Type: application/json');

$pdo = getDBConnection();
$query = $_GET['q'] ?? '';

try {
    if (!empty($query)) {
        $stmt = $pdo->prepare("SELECT id, full_name, dob, gender, phone FROM patient_profiles WHERE full_name LIKE ? OR phone LIKE ? LIMIT 10");
        $stmt->execute(["%$query%", "%$query%"]);
    } else {
        $stmt = $pdo->query("SELECT id, full_name, dob, gender, phone FROM patient_profiles ORDER BY created_at DESC LIMIT 20");
    }

    $patients = $stmt->fetchAll();
    echo json_encode(['patients' => $patients]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
