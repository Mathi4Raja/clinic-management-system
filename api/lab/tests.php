<?php
/**
 * Lab API - Order/List Tests
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

header('Content-Type: application/json');

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'POST') {
        authorize([ROLE_DOCTOR]);
        $input = json_decode(file_get_contents('php://input'), true);

        // Get Doctor ID
        $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $doctor = $stmt->fetch();

        $stmt = $pdo->prepare("INSERT INTO lab_tests (patient_id, doctor_id, test_type, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$input['patient_id'], $doctor['id'], $input['test_type']]);

        echo json_encode(['success' => true, 'message' => 'Lab test ordered']);
    } else {
        // List tests (Staff, Lab Tech, Doctor, Admin)
        authorize([ROLE_LAB_TECH, ROLE_DOCTOR, ROLE_RECEPTIONIST, ROLE_ADMIN]);

        $sql = "SELECT lt.*, p.full_name as patient_name 
                FROM lab_tests lt 
                JOIN patient_profiles p ON lt.patient_id = p.id";

        if ($_SESSION['role'] === ROLE_LAB_TECH) {
            $sql .= " WHERE lt.status = 'pending'";
        }

        $stmt = $pdo->query($sql);
        echo json_encode(['tests' => $stmt->fetchAll()]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
