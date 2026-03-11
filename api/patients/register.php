<?php
/**
 * Patient API - Register/Create
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

// Only Receptionists and Admins can register new patients
authorize([ROLE_RECEPTIONIST, ROLE_ADMIN]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

// Validation
$required = ['email', 'password', 'full_name', 'dob', 'gender', 'phone'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Field '$field' is required"]);
        exit;
    }
}

$pdo = getDBConnection();

try {
    $pdo->beginTransaction();

    // 1. Create User Account
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, is_email_verified) VALUES (?, ?, ?, 1)");
    $password_hash = password_hash($input['password'], PASSWORD_BCRYPT);
    $stmt->execute([$input['email'], $password_hash, ROLE_PATIENT]);
    $user_id = $pdo->lastInsertId();

    // 2. Init Patient Profile
    $stmt = $pdo->prepare("INSERT INTO patient_profiles (user_id, full_name, dob, gender, phone) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $input['full_name'],
        $input['dob'],
        $input['gender'],
        $input['phone']
    ]);
    $patient_profile_id = $pdo->lastInsertId();

    $pdo->commit();
    http_response_code(201);
    echo json_encode(['success' => true, 'patient_id' => $patient_profile_id, 'message' => 'Patient registered successfully']);

} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    if ($e->getCode() == 23000) {
        echo json_encode(['error' => 'Email already exists']);
    } else {
        echo json_encode(['error' => 'Internal Server Error']);
    }
}
