<?php
/**
 * Admin API - Staff Account Management
 * GET    → list all non-patient users with profiles
 * POST   → create new staff account
 * DELETE → delete staff account (id in body)
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/rbac.php';

authorize([ROLE_ADMIN]);

header('Content-Type: application/json');

$pdo    = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->query(
            "SELECT u.id, u.email, u.role, u.created_at,
                    COALESCE(sp.name, pp.full_name) AS display_name,
                    COALESCE(sp.role_title, '') AS role_title,
                    COALESCE(d.specialization, '') AS specialization
             FROM users u
             LEFT JOIN staff_profiles sp ON u.id = sp.user_id
             LEFT JOIN patient_profiles pp ON u.id = pp.user_id
             LEFT JOIN doctors d ON u.id = d.user_id
             ORDER BY u.created_at DESC"
        );
        echo json_encode(['staff' => $stmt->fetchAll()]);

    } elseif ($method === 'POST') {
        requireCSRF();
        $input = json_decode(file_get_contents('php://input'), true);

        $required = ['email', 'password', 'name', 'role'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Field '$field' is required"]);
                exit;
            }
        }

        $valid_roles = [ROLE_DOCTOR, ROLE_RECEPTIONIST, ROLE_LAB_TECH, ROLE_PHARMACIST, ROLE_ADMIN];
        if (!in_array($input['role'], $valid_roles)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid role specified']);
            exit;
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            "INSERT INTO users (email, password_hash, role, is_email_verified) VALUES (?, ?, ?, 1)"
        );
        $stmt->execute([$input['email'], password_hash($input['password'], PASSWORD_BCRYPT), $input['role']]);
        $user_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare(
            "INSERT INTO staff_profiles (user_id, name, role_title) VALUES (?, ?, ?)"
        );
        $stmt->execute([$user_id, $input['name'], $input['role_title'] ?? $input['role']]);

        // If creating a doctor, insert doctor record too
        if ($input['role'] === ROLE_DOCTOR) {
            if (empty($input['specialization']) || empty($input['license_number'])) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Doctors require specialization and license_number']);
                exit;
            }
            $stmt = $pdo->prepare(
                "INSERT INTO doctors (user_id, specialization, license_number) VALUES (?, ?, ?)"
            );
            $stmt->execute([$user_id, $input['specialization'], $input['license_number']]);
        }

        $pdo->commit();
        http_response_code(201);
        echo json_encode(['success' => true, 'user_id' => $user_id, 'message' => 'Staff account created']);

    } elseif ($method === 'DELETE') {
        requireCSRF();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID required']);
            exit;
        }

        // Prevent self-deletion
        if ($id === (int)$_SESSION['user_id']) {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete your own account']);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Staff account deleted']);

    } elseif ($method === 'PATCH') {
        // Edit user: name, email, role, and optionally password
        requireCSRF();
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'User ID required']);
            exit;
        }

        // Prevent changing role of your own account
        $isSelf = ($id === (int)$_SESSION['user_id']);

        $pdo->beginTransaction();

        // Update users table
        $fields = [];
        $args   = [];
        if (!empty($input['email'])) {
            $fields[] = 'email = ?';
            $args[]   = $input['email'];
        }
        if (!empty($input['role']) && !$isSelf) {
            $valid_roles = [ROLE_DOCTOR, ROLE_RECEPTIONIST, ROLE_LAB_TECH, ROLE_PHARMACIST, ROLE_ADMIN];
            if (!in_array($input['role'], $valid_roles)) {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(['error' => 'Invalid role']);
                exit;
            }
            $fields[] = 'role = ?';
            $args[]   = $input['role'];
        }
        if (!empty($input['password'])) {
            $fields[] = 'password_hash = ?';
            $args[]   = password_hash($input['password'], PASSWORD_BCRYPT);
        }
        if ($fields) {
            $args[] = $id;
            $stmt = $pdo->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
            $stmt->execute($args);
        }

        // Update staff_profiles name
        if (!empty($input['name'])) {
            $stmt = $pdo->prepare('UPDATE staff_profiles SET name = ? WHERE user_id = ?');
            $stmt->execute([$input['name'], $id]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Account updated']);

    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    if ($e->getCode() == 23000) {
        echo json_encode(['error' => 'Email already exists']);
    } else {
        echo json_encode(['error' => 'Internal Server Error']);
    }
}
