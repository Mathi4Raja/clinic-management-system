<?php
/**
 * Patient API - Leave Review
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

// Ensure the 'reviews' table exists if not already in schema
// (Adding this to the schema shortly)

authorize([ROLE_PATIENT]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$doctor_id = $input['doctor_id'] ?? null;
$rating = $input['rating'] ?? 5;
$comment = $input['comment'] ?? '';

if (!$doctor_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Doctor ID is required']);
    exit;
}

$pdo = getDBConnection();

try {
    // Check if review table exists, otherwise creating it (lazy migration)
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        doctor_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Get Patient ID
    $stmt = $pdo->prepare("SELECT id FROM patient_profiles WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $patient = $stmt->fetch();

    $stmt = $pdo->prepare("INSERT INTO reviews (patient_id, doctor_id, rating, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$patient['id'], $doctor_id, $rating, $comment]);

    echo json_encode(['success' => true, 'message' => 'Review submitted']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save review']);
}
