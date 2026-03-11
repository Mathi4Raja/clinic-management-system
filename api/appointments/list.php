<?php
/**
 * Appointment API - List/Queue
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize(ALL_ROLES);

header('Content-Type: application/json');

$pdo = getDBConnection();
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$status = $_GET['status'] ?? 'scheduled';

try {
    $sql = "SELECT a.*, p.full_name as patient_name, d_profile.name as doctor_name 
            FROM appointments a 
            JOIN patient_profiles p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN staff_profiles d_profile ON d.user_id = d_profile.user_id";

    $params = [];

    if ($role === ROLE_PATIENT) {
        $sql .= " JOIN patient_profiles p2 ON a.patient_id = p2.id WHERE p2.user_id = ?";
        $params[] = $user_id;
    } elseif ($role === ROLE_DOCTOR) {
        $sql .= " WHERE d.user_id = ?";
        $params[] = $user_id;
    } else {
        $sql .= " WHERE 1=1";
    }

    if ($status !== 'all') {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();

    echo json_encode(['appointments' => $appointments]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
