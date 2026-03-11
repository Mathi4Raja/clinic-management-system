<?php
/**
 * Core Configuration and Database Connection
 * Strictly using PDO with prepared statements.
 */

// Deployment Configuration (Adjust for production)
define('DB_HOST', 'localhost');
define('DB_NAME', 'clinic_cms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Security Configurations
define('SESSION_USE_STRICT_MODE', true);
define('SESSION_SECURE_COOKIE', false); // Set to true if using HTTPS
define('SESSION_HTTPONLY', true);
define('SESSION_SAMESITE', 'Strict');

/**
 * Get PDO Database Connection
 * @return PDO
 */
function getDBConnection() {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (\PDOException $e) {
            // In production, avoid leaking details and use error_log()
            die("Database Connection Failed: " . $e->getMessage());
        }
    }

    return $pdo;
}

/**
 * Initialize Secure Session
 */
function initSecureSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.use_strict_mode', SESSION_USE_STRICT_MODE);
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => SESSION_SECURE_COOKIE,
            'httponly' => SESSION_HTTPONLY,
            'samesite' => SESSION_SAMESITE
        ]);
        session_start();
    }
}

/**
 * CSRF Token Generation
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF Token Validation
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
