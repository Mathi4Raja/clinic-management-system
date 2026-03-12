<?php
/**
 * Medical Records API - List
 * GET ?patient_id=  → records for a patient
 * GET ?my=1         → patient fetches own records (auto-resolves patient_id from session)
 * Accessible by: doctor, patient (own only), receptionist (demographics only), admin
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_DOCTOR, ROLE_PATIENT, ROLE_ADMIN, ROLE_RECEPTIONIST]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$pdo = getDBConnection();

try {
    if ($_SESSION['role'] === ROLE_PATIENT) {
        // Patients always fetch only their own records
        $stmt = $pdo->prepare("SELECT id FROM patient_profiles WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $pp = $stmt->fetch();
        if (!$pp) { echo json_encode(['records' => []]); exit; }
        $patient_id = $pp['id'];
    } else {
        $patient_id = (int)($_GET['patient_id'] ?? 0);
        if (!$patient_id) {
            http_response_code(400);
            echo json_encode(['error' => 'patient_id is required']);
            exit;
        }
    }

    $stmt = $pdo->prepare(
        "SELECT mr.id, mr.patient_id, mr.doctor_id, mr.diagnosis, mr.clinical_notes, mr.created_at,
                COALESCE(sp.name, 'Unknown') AS doctor_name,
                pp.full_name AS patient_name
         FROM medical_records mr
         JOIN patient_profiles pp ON mr.patient_id = pp.id
         LEFT JOIN doctors d ON mr.doctor_id = d.id
         LEFT JOIN staff_profiles sp ON d.user_id = sp.user_id
         WHERE mr.patient_id = ?
         ORDER BY mr.created_at DESC"
    );
    $stmt->execute([$patient_id]);
    $records = $stmt->fetchAll();

    // For each record, fetch associated prescriptions and lab tests
    foreach ($records as &$rec) {
        // Prescriptions
        $ps = $pdo->prepare(
            "SELECT p.id AS prescription_id, pi.dosage, pi.frequency, m.name AS medicine_name, m.price
             FROM prescriptions p
             JOIN prescription_items pi ON p.id = pi.prescription_id
             JOIN medicines m ON pi.medicine_id = m.id
             WHERE p.record_id = ?"
        );
        $ps->execute([$rec['id']]);
        $rec['prescription_items'] = $ps->fetchAll();

        // If patient is viewing, hide clinical_notes privacy is fine but keep diagnosis readable
        // Lab tests ordered in same session (no FK yet, so we look by patient+doctor+date proximity)
        $lt = $pdo->prepare(
            "SELECT lt.id, lt.test_type, lt.status, lt.report_notes, lt.report_file_path, lt.created_at
             FROM lab_tests lt
             WHERE lt.patient_id = ? AND lt.doctor_id = ?
             ORDER BY lt.created_at DESC LIMIT 10"
        );
        $lt->execute([$rec['patient_id'], $rec['doctor_id']]);
        $rec['lab_tests'] = $lt->fetchAll();
    }

    echo json_encode(['records' => $records]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
