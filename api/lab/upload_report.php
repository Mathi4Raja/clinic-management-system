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

requireCSRF();

$test_id      = (int)($_POST['test_id'] ?? 0);
$report_notes = trim($_POST['report_notes'] ?? '');
$file         = (!empty($_FILES['report_file']['name'])) ? $_FILES['report_file'] : null;

if (!$test_id || empty($report_notes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Test ID and report notes are required']);
    exit;
}

$file_path = null;

if ($file) {
    // Validate file type — use server-side finfo (not spoofable user-supplied MIME)
    $allowed_mimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
    $allowed_exts  = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
    $ext           = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $finfo         = new finfo(FILEINFO_MIME_TYPE);
    $detected_mime = $finfo->file($file['tmp_name']);

    if (!in_array($detected_mime, $allowed_mimes) || !in_array($ext, $allowed_exts)) {
        http_response_code(400);
        echo json_encode(['error' => 'Only PDF and image files are allowed']);
        exit;
    }

    if ($file['size'] > 10 * 1024 * 1024) { // 10 MB limit
        http_response_code(400);
        echo json_encode(['error' => 'File size exceeds 10 MB limit']);
        exit;
    }

    $upload_dir = __DIR__ . '/../../uploads/lab_reports/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $safe_name  = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $target     = $upload_dir . $safe_name;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save uploaded file']);
        exit;
    }
    $file_path = 'uploads/lab_reports/' . $safe_name;
}

$pdo = getDBConnection();
try {
    $stmt = $pdo->prepare("UPDATE lab_tests SET status = 'completed', report_file_path = ?, report_notes = ? WHERE id = ?");
    $stmt->execute([$file_path, $report_notes, $test_id]);

    echo json_encode(['success' => true, 'message' => 'Report submitted successfully']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database Error']);
}
