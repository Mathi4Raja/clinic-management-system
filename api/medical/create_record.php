<?php
/**
 * Medical Records API - Create
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_DOCTOR]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$patient_id = $input['patient_id'] ?? null;
$diagnosis = $input['diagnosis'] ?? '';
$clinical_notes = $input['notes'] ?? $input['clinical_notes'] ?? '';
$prescription_items = $input['prescription'] ?? $input['prescriptions'] ?? [];
$lab_tests = $input['lab_tests'] ?? [];

if (!$patient_id || empty($diagnosis)) {
    http_response_code(400);
    echo json_encode(['error' => 'Patient ID and Diagnosis are required']);
    exit;
}

$pdo = getDBConnection();

try {
    $pdo->beginTransaction();

    // 1. Get Doctor ID from user_id
    $stmt = $pdo->prepare("SELECT id FROM doctors WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $doctor = $stmt->fetch();
    $doctor_id = $doctor['id'] ?? null;

    if (!$doctor_id) {
        throw new Exception("Doctor profile not found");
    }

    // 2. Create Medical Record
    $stmt = $pdo->prepare("INSERT INTO medical_records (patient_id, doctor_id, diagnosis, clinical_notes) VALUES (?, ?, ?, ?)");
    $stmt->execute([$patient_id, $doctor_id, $diagnosis, $clinical_notes]);
    $record_id = $pdo->lastInsertId();

    // 3. Create Prescription if items exist
    if (!empty($prescription_items)) {
        $stmt = $pdo->prepare("INSERT INTO prescriptions (record_id) VALUES (?)");
        $stmt->execute([$record_id]);
        $prescription_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO prescription_items (prescription_id, medicine_id, dosage, frequency) VALUES (?, ?, ?, ?)");
        foreach ($prescription_items as $item) {
            $stmt->execute([$prescription_id, $item['medicine_id'], $item['dosage'], $item['duration'] ?? $item['frequency'] ?? '']);
        }
    }

    // 4. Create Lab Tests if provided
    if (!empty($lab_tests)) {
        $stmt = $pdo->prepare("INSERT INTO lab_tests (patient_id, doctor_id, test_type, status) VALUES (?, ?, ?, 'pending')");
        foreach ($lab_tests as $testType) {
            $stmt->execute([$patient_id, $doctor_id, $testType]);
        }
    }

    $pdo->commit();
    http_response_code(201);
    echo json_encode(['success' => true, 'record_id' => $record_id, 'message' => 'Medical record created successfully']);

} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
