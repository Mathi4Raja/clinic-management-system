<?php
/**
 * Lab API - Upload Report (Lab Tech)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_LAB_TECH, ROLE_ADMIN]);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$test_id = $_POST['test_id'] ?? null;
$report_notes = $_POST['report_notes'] ?? '';
$file = $_FILES['report_file'] ?? null;

if (!$test_id || !$file) {
    http_response_code(400);
    echo json_encode(['error' => 'Test ID and Report File are required']);
    exit;
}

$upload_dir = __DIR__ . '/../../uploads/lab_reports/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$filename = time() . '_' . basename($file['name']);
$target_path = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $target_path)) {
    $pdo = getDBConnection();
    try {
        $stmt = $pdo->prepare("UPDATE lab_tests SET status = 'completed', report_file_path = ?, report_notes = ? WHERE id = ?");
        $stmt->execute(['uploads/lab_reports/' . $filename, $report_notes, $test_id]);

        echo json_encode(['success' => true, 'message' => 'Report uploaded successfully']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database Error']);
    }
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save uploaded file']);
}
