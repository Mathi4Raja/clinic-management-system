<?php
/**
 * Appointment API - Update Status
 * Allows cancellation by patients, and full status updates by receptionist/doctor/admin.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_PATIENT, ROLE_RECEPTIONIST, ROLE_DOCTOR, ROLE_ADMIN]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

requireCSRF();

$input = json_decode(file_get_contents('php://input'), true);
$id     = (int)($input['id'] ?? 0);
$status = $input['status'] ?? '';

$allowed = ['scheduled', 'completed', 'cancelled', 'no_show'];
if (!$id || !in_array($status, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid appointment ID or status']);
    exit;
}

$pdo = getDBConnection();

try {
    // Patients may only cancel their own appointments
    if ($_SESSION['role'] === ROLE_PATIENT) {
        if ($status !== 'cancelled') {
            http_response_code(403);
            echo json_encode(['error' => 'Patients can only cancel appointments']);
            exit;
        }
        $stmt = $pdo->prepare(
            "SELECT a.id FROM appointments a
             JOIN patient_profiles p ON a.patient_id = p.id
             WHERE a.id = ? AND p.user_id = ?"
        );
        $stmt->execute([$id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Appointment not found or access denied']);
            exit;
        }
    }

    // Doctors can only mark completed or cancelled
    if ($_SESSION['role'] === ROLE_DOCTOR) {
        if (!in_array($status, ['completed', 'cancelled'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Doctors can only mark appointments as completed or cancelled']);
            exit;
        }
        $stmt = $pdo->prepare(
            "SELECT a.id FROM appointments a
             JOIN doctors d ON a.doctor_id = d.id
             WHERE a.id = ? AND d.user_id = ?"
        );
        $stmt->execute([$id, $_SESSION['user_id']]);
        if (!$stmt->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Appointment not found or access denied']);
            exit;
        }
    }

    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);

    echo json_encode(['success' => true, 'message' => "Appointment status updated to '$status'"]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
