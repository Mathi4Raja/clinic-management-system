<?php
/**
 * Authentication API - Login
 */

require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Session Check — also return active CSRF token so JS can re-hydrate after page reload
    initSecureSession();
    if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare(
            "SELECT u.email, COALESCE(sp.name, pp.full_name, u.email) AS display_name
             FROM users u
             LEFT JOIN staff_profiles sp ON u.id = sp.user_id
             LEFT JOIN patient_profiles pp ON u.id = pp.user_id
             WHERE u.id = ?"
        );
        $stmt->execute([$_SESSION['user_id']]);
        $profile = $stmt->fetch();

        echo json_encode([
            'authenticated' => true,
            'user' => [
                'id'           => $_SESSION['user_id'],
                'email'        => $profile['email']        ?? '',
                'display_name' => $profile['display_name'] ?? '',
                'role'         => $_SESSION['role']
            ],
            'csrf_token' => generateCSRFToken()
        ]);
    } else {
        echo json_encode(['authenticated' => false]);
    }
    exit;
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email and password are required']);
    exit;
}

$pdo = getDBConnection();

try {
    $stmt = $pdo->prepare(
        "SELECT u.id, u.email, u.password_hash, u.role,
                COALESCE(sp.name, pp.full_name, u.email) AS display_name
         FROM users u
         LEFT JOIN staff_profiles sp ON u.id = sp.user_id
         LEFT JOIN patient_profiles pp ON u.id = pp.user_id
         WHERE u.email = ?"
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        initSecureSession();
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        echo json_encode([
            'success' => true,
            'user' => [
                'id'           => $user['id'],
                'email'        => $user['email'],
                'display_name' => $user['display_name'],
                'role'         => $user['role']
            ],
            'csrf_token' => generateCSRFToken()
        ]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid email or password']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal Server Error']);
    // error_log($e->getMessage());
}
