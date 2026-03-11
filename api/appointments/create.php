<?php
/**
 * Appointment API - Create
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

// Patients and Receptionists can book
authorize([ROLE_PATIENT, ROLE_RECEPTIONIST, ROLE_ADMIN]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$patient_id = $input['patient_id'] ?? null;
// If the user is a patient, they can only book for themselves
if ($_SESSION['role'] === ROLE_PATIENT) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id FROM patient_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $profile = $stmt->fetch();
    $patient_id = $profile['id'] ?? null;
}

if (!$patient_id || empty($input['doctor_id']) || empty($input['date']) || empty($input['start_time'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, 'scheduled')");

    // Calculate end time (default 30 mins for demo)
    $start = new DateTime($input['start_time']);
    $end = clone $start;
    $end->modify('+30 minutes');

    $stmt->execute([
        $patient_id,
        $input['doctor_id'],
        $input['date'],
        $start->format('H:i:s'),
        $end->format('H:i:s')
    ]);

    echo json_encode(['success' => true, 'message' => 'Appointment scheduled']);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
