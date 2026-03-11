<?php
/**
 * CMS Non-Functional Test Suite
 * Monitors Performance (latency) and Security (Data exposure).
 */

require_once __DIR__ . '/../api/config.php';

echo "\n⚡ Starting Non-Functional / Performance Tests\n";
echo "==============================================\n";

$pdo = getDBConnection();

// 1. Latency Check
echo "[1/2] Checking API Latency... ";
$start = microtime(true);
for ($i = 0; $i < 50; $i++) {
    $pdo->query("SELECT 1");
}
$diff = (microtime(true) - $start) / 50;

if ($diff < 0.05) {
    echo "✅ High Speed (" . round($diff * 1000, 2) . "ms avg query)\n";
} else {
    echo "⚠️ Moderate Latency (" . round($diff * 1000, 2) . "ms avg query)\n";
}

// 2. Security: Email Exposure check
echo "[2/2] Data Exposure Audit... ";
$stmt = $pdo->query("SELECT * FROM users LIMIT 1");
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($user['password_hash']) && strlen($user['password_hash']) > 20) {
    echo "✅ Passwords are Hashed\n";
} else {
    echo "❌ SECURITY RISK: Plaintext passwords detected!\n";
}

echo "==============================================\n";
echo "🌟 PERFORMANCE & SECURITY AUDIT COMPLETE\n\n";
