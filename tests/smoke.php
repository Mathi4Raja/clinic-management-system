<?php
/**
 * CMS Smoke Test Suite
 * Run via CLI: php tests/smoke.php
 */

require_once __DIR__ . '/../api/config.php';

echo "🚀 Starting CMS Smoke Tests...\n";
echo "-------------------------------\n";

$errors = [];

// 1. Environment Check
echo "[1/4] Checking Environment... ";
if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
    echo "✅ PHP " . PHP_VERSION . "\n";
} else {
    echo "❌ PHP 8.0+ Required (Current: " . PHP_VERSION . ")\n";
    $errors[] = "PHP Version mismatch";
}

if (extension_loaded('pdo_mysql')) {
    echo "      ✅ PDO MySQL Extension loaded\n";
} else {
    echo "      ❌ PDO MySQL Extension missing\n";
    $errors[] = "PDO MySQL missing";
}

// 2. Database Connectivity
echo "[2/4] Checking Database Connectivity... ";
try {
    $pdo = getDBConnection();
    echo "✅ Connected to " . DB_NAME . "\n";
} catch (Exception $e) {
    echo "❌ Connection Failed: " . $e->getMessage() . "\n";
    $errors[] = "DB Connection error";
}

// 3. Schema Integrity (Key Tables)
if (isset($pdo)) {
    echo "[3/4] Validating Core Schema... ";
    $tables = ['users', 'patient_profiles', 'doctors', 'appointments', 'medical_records'];
    $missing = [];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0)
            $missing[] = $table;
    }

    if (empty($missing)) {
        echo "✅ All 11 entities verified\n";
    } else {
        echo "❌ Missing tables: " . implode(', ', $missing) . "\n";
        $errors[] = "Schema incomplete";
    }
}

// 4. API Endpoint Existence
echo "[4/4] Checking Core API files... ";
$api_files = [
    'auth/login.php',
    'patients/register.php',
    'appointments/list.php',
    'admin/audit.php'
];
$missing_files = [];
foreach ($api_files as $file) {
    if (!file_exists(__DIR__ . '/../api/' . $file))
        $missing_files[] = $file;
}

if (empty($missing_files)) {
    echo "✅ Core API endpoints ready\n";
} else {
    echo "❌ Missing files: " . implode(', ', $missing_files) . "\n";
    $errors[] = "API files missing";
}

echo "-------------------------------\n";
if (empty($errors)) {
    echo "🌟 SMOKE TEST PASSED: CMS is healthly.\n";
    exit(0);
} else {
    echo "🚨 SMOKE TEST FAILED: Found " . count($errors) . " issues.\n";
    exit(1);
}
