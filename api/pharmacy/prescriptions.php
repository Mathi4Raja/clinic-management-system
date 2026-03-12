<?php
/**
 * Pharmacy API - Prescriptions for dispensing
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_PHARMACIST, ROLE_ADMIN]);

header('Content-Type: application/json');

$pdo = getDBConnection();

try {
    // NOTE: diagnosis is intentionally excluded — pharmacists must not see clinical data
    $stmt = $pdo->query("SELECT p.id, mr.patient_id, pr.full_name AS patient_name,
                                COALESCE(sp.name, 'Unknown Doctor') AS doctor_name, p.created_at,
                                EXISTS(SELECT 1 FROM invoices i
                                       WHERE i.reference_type='prescription' AND i.reference_id=p.id
                                         AND i.status IN ('pending','paid')) AS is_billed
                         FROM prescriptions p
                         JOIN medical_records mr ON p.record_id = mr.id
                         JOIN patient_profiles pr ON mr.patient_id = pr.id
                         LEFT JOIN doctors d ON mr.doctor_id = d.id
                         LEFT JOIN staff_profiles sp ON d.user_id = sp.user_id
                         ORDER BY p.created_at DESC");

    $prescriptions = $stmt->fetchAll();

    // For each prescription, getting items
    foreach ($prescriptions as &$p) {
        $stmt_items = $pdo->prepare("SELECT pi.*, m.name as medicine_name, m.price 
                                     FROM prescription_items pi 
                                     JOIN medicines m ON pi.medicine_id = m.id 
                                     WHERE pi.prescription_id = ?");
        $stmt_items->execute([$p['id']]);
        $p['items'] = $stmt_items->fetchAll();
    }

    echo json_encode(['prescriptions' => $prescriptions]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
