<?php
/**
 * CMS Security Penetration Suite
 * Automates basic vulnerability probing: CSRF, IDOR, SQLi
 */

function testSecurityBoundary($name, $url, $postData, $headers, $expectedHttpStatus)
{
    echo "Running Probe: $name\n";
    echo "Target: $url\n";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    if (!empty($postData)) {
        curl_setopt($ch, CURLOPT_POST, true);
        // Important: CMS backend (`file_get_contents('php://input')`) expects raw JSON
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    } else {
        curl_setopt($ch, CURLOPT_HTTPGET, true);
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_HEADER, true);
    // Remove CURLOPT_NOBODY to allow standard body sending

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === $expectedHttpStatus) {
        echo "✅ PASS (Received expected HTTP $httpCode)\n\n";
        return true;
    } else {
        echo "❌ FAIL (Expected HTTP $expectedHttpStatus, but got $httpCode)\n\n";
        return false;
    }
}

$baseUrl = "http://localhost/clinic%20management%20system/api";
$allPassed = true;

echo "=== Starting Security Probes ===\n\n";


// 1. CSRF/Auth Boundary Probing
// Attempt to login with bad creds / missing token.
// The server SHOULD reject the POST request cleanly with a 401 Unauthorized.
$csrfTest = testSecurityBoundary(
    "CSRF/Auth Boundary Bypass Attempt",
    "$baseUrl/auth/login.php",
    ['email' => 'test@example.com', 'password' => 'password'],
    ['Content-Type: application/x-www-form-urlencoded'],
    401
);
if (!$csrfTest)
    $allPassed = false;


// 2. IDOR / Access Control Probing
// Try to hit the admin audit endpoint without an admin session cookie.
// The RBAC middleware should catch this and deny access (401).
$idorTest = testSecurityBoundary(
    "IDOR Privilege Escalation on Admin Endpoint",
    "$baseUrl/admin/audit.php",
    [], // GET request essentially
    [],
    401 // Expecting 401 Unauthorized since there is no session at all
);
if (!$idorTest)
    $allPassed = false;


// 3. SQLi Probing
// Send malformed SQL literals into the auth endpoint.
// It should fail cleanly (401) rather than throwing a 500 DB error.
$sqliTest = testSecurityBoundary(
    "Basic SQL Injection Payload on Login",
    "$baseUrl/auth/login.php",
    ['email' => "' OR '1'='1", 'password' => "' OR '1'='1"],
    ['Content-Type: application/x-www-form-urlencoded'],
    401 // Fails cleanly with generic Unauthorized, never 500.
);
if (!$sqliTest)
    $allPassed = false;

if ($allPassed) {
    echo "🎉 All Security Boundaries Hold.\n";
    exit(0);
} else {
    echo "💥 Warning: Security Boundaries Failed.\n";
    exit(1);
}
