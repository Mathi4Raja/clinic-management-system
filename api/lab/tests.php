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
        requireCSRF();
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['patient_id']) || empty($input['test_type'])) {
            http_response_code(400);
            echo json_encode(['error' => 'patient_id and test_type are required']);
            exit;
        }

        // Get Doctor ID
        $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $doctor = $stmt->fetch();
        if (!$doctor) {
            http_response_code(403);
            echo json_encode(['error' => 'Doctor profile not found']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO lab_tests (patient_id, doctor_id, test_type, status) VALUES (?, ?, ?, 'pending')");
        $stmt->execute([$input['patient_id'], $doctor['id'], $input['test_type']]);

        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Lab test ordered', 'id' => $pdo->lastInsertId()]);
    } else {
        // GET — List tests. Lab techs see pending by default; ?status=all or ?status=completed for completed
        authorize([ROLE_LAB_TECH, ROLE_DOCTOR, ROLE_RECEPTIONIST, ROLE_ADMIN, ROLE_PATIENT]);

        $params = [];
        $where  = [];

        if ($_SESSION['role'] === ROLE_PATIENT) {
            // Patient may only see their own results (completed)
            $stmt = $pdo->prepare("SELECT id FROM patient_profiles WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $pp = $stmt->fetch();
            if (!$pp) { echo json_encode(['tests' => []]); exit; }
            $where[]  = 'lt.patient_id = ?';
            $params[] = $pp['id'];
            $where[]  = "lt.status = 'completed'";
        } elseif ($_SESSION['role'] === ROLE_DOCTOR) {
            $d = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
            $d->execute([$_SESSION['user_id']]);
            $doc = $d->fetch();
            if ($doc) { $where[] = 'lt.doctor_id = ?'; $params[] = $doc['id']; }
        } elseif ($_SESSION['role'] === ROLE_LAB_TECH) {
            // Default: pending; allow ?status=completed or ?status=all
            $s = $_GET['status'] ?? 'pending';
            if (in_array($s, ['pending', 'completed', 'cancelled'])) {
                $where[] = 'lt.status = ?'; $params[] = $s;
            }
        }

        $sql = "SELECT lt.id, lt.patient_id, lt.doctor_id, lt.test_type, lt.status,
                       lt.report_file_path, lt.report_notes, lt.created_at,
                       p.full_name AS patient_name,
                       COALESCE(sp.name, 'Unknown') AS doctor_name
                FROM lab_tests lt
                JOIN patient_profiles p ON lt.patient_id = p.id
                LEFT JOIN doctors d ON lt.doctor_id = d.id
                LEFT JOIN staff_profiles sp ON d.user_id = sp.user_id";

        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY lt.created_at DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['tests' => $stmt->fetchAll()]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
