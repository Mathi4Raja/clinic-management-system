<?php
/**
 * Patient API - Reviews
 * GET  ?doctor_id=  → list reviews for a doctor (all authenticated roles)
 * POST             → submit a review (patient only)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_PATIENT, ROLE_DOCTOR, ROLE_ADMIN, ROLE_RECEPTIONIST, ROLE_LAB_TECH, ROLE_PHARMACIST]);

header('Content-Type: application/json');

$pdo    = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $doctor_id = (int)($_GET['doctor_id'] ?? 0);
        if (!$doctor_id) {
            http_response_code(400);
            echo json_encode(['error' => 'doctor_id is required']);
            exit;
        }
        $stmt = $pdo->prepare(
            "SELECT r.id, r.rating, r.comment, r.created_at,
                    pp.full_name AS patient_name
             FROM reviews r
             JOIN patient_profiles pp ON r.patient_id = pp.id
             WHERE r.doctor_id = ?
             ORDER BY r.created_at DESC"
        );
        $stmt->execute([$doctor_id]);
        $reviews = $stmt->fetchAll();
        $avg = $reviews ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : null;
        echo json_encode(['reviews' => $reviews, 'average_rating' => $avg, 'count' => count($reviews)]);

    } elseif ($method === 'POST') {
        authorize([ROLE_PATIENT]);
        requireCSRF();

        $input     = json_decode(file_get_contents('php://input'), true);
        $doctor_id = (int)($input['doctor_id'] ?? 0);
        $rating    = (int)($input['rating']    ?? 0);
        $comment   = trim($input['comment']   ?? '');

        if (!$doctor_id || $rating < 1 || $rating > 5) {
            http_response_code(400);
            echo json_encode(['error' => 'doctor_id and a rating (1–5) are required']);
            exit;
        }

        $stmt = $pdo->prepare("SELECT id FROM patient_profiles WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $patient = $stmt->fetch();
        if (!$patient) {
            http_response_code(403);
            echo json_encode(['error' => 'Patient profile not found']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO reviews (patient_id, doctor_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$patient['id'], $doctor_id, $rating, $comment]);

        echo json_encode(['success' => true, 'message' => 'Review submitted']);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to process review']);
}
