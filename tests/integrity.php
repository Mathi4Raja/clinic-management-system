<?php
/**
 * CMS Deep Database Integrity Auditor
 * Verifies foreign keys, orphan records, and status consistency.
 */

require_once __DIR__ . '/../api/config.php';

echo "\n💎 Starting Deep Integrity Audit...\n";
echo "========================================\n";

$pdo = getDBConnection();
$issues = [];

// 1. Check for Patients without Users
$stmt = $pdo->query("SELECT id FROM patient_profiles WHERE user_id NOT IN (SELECT id FROM users)");
if ($stmt->rowCount() > 0)
    $issues[] = "Orphaned Patient Profiles found";

// 2. Check for Prescriptions without Medical Records
$stmt = $pdo->query("SELECT id FROM prescriptions WHERE record_id NOT IN (SELECT id FROM medical_records)");
if ($stmt->rowCount() > 0)
    $issues[] = "Prescriptions without parent medical records";

// 3. Check for Invoices without Patients
$stmt = $pdo->query("SELECT id FROM invoices WHERE patient_id NOT IN (SELECT id FROM patient_profiles)");
if ($stmt->rowCount() > 0)
    $issues[] = "Invoices missing patient references";

// 4. Role consistency check
$stmt = $pdo->query("SELECT u.email FROM users u JOIN doctors d ON u.id = d.user_id WHERE u.role != 'doctor'");
if ($stmt->rowCount() > 0)
    $issues[] = "Inconsistent User/Doctor roles detected";

echo "[1/1] Scanning Referential Integrity... ";
if (empty($issues)) {
    echo "✅ Perfect State\n";
} else {
    echo "❌ Anomalies Found:\n";
    foreach ($issues as $i)
        echo "     - $i\n";
}

echo "========================================\n";
echo "🌟 INTEGRITY AUDIT COMPLETE\n\n";
