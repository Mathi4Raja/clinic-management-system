<?php
/**
 * Patients API - Update
 * PATCH → update patient profile demographics
 * Accessible by: receptionist, admin
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_RECEPTIONIST, ROLE_ADMIN]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

requireCSRF();

$input = json_decode(file_get_contents('php://input'), true);

$id        = (int)($input['id'] ?? 0);
$full_name = trim($input['full_name'] ?? '');
$dob       = $input['dob']    ?? null;
$gender    = $input['gender'] ?? null;
$phone     = trim($input['phone'] ?? '');

if (!$id || !$full_name) {
    http_response_code(400);
    echo json_encode(['error' => 'id and full_name are required']);
    exit;
}

$allowed_genders = ['male', 'female', 'other'];
if ($gender !== null && !in_array(strtolower($gender), $allowed_genders, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid gender value']);
    exit;
}

$pdo = getDBConnection();

try {
    // Verify the patient_profile exists
    $check = $pdo->prepare("SELECT id FROM patient_profiles WHERE id = ?");
    $check->execute([$id]);
    if (!$check->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Patient not found']);
        exit;
    }

    $stmt = $pdo->prepare(
        "UPDATE patient_profiles 
         SET full_name = ?, dob = ?, gender = ?, phone = ?
         WHERE id = ?"
    );
    $stmt->execute([$full_name, $dob ?: null, $gender ? strtolower($gender) : null, $phone ?: null, $id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
}
