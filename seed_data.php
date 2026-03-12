<?php
/**
 * Clinic CMS — Rich Seed Data Script
 * Run via PHP CLI:
 *   php seed_data.php
 *
 * This script is idempotent — safe to run multiple times.
 * Password for ALL test accounts: password
 * Hash: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'clinic_cms');
define('DB_USER', 'root');
define('DB_PASS', '');

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);

$pass = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'; // "password"

echo "=== Clinic CMS Seed Data ===\n\n";

// ─── Helper ────────────────────────────────────────────────────────────────

function upsertUser(PDO $pdo, string $email, string $role, string $pass): int {
    $existing = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $existing->execute([$email]);
    $row = $existing->fetch();
    if ($row) {
        // Ensure role and hash are correct
        $pdo->prepare("UPDATE users SET role=?, password_hash=?, is_email_verified=1 WHERE id=?")
            ->execute([$role, $pass, $row['id']]);
        echo "  [OK] User $email already exists (id={$row['id']})\n";
        return $row['id'];
    }
    $stmt = $pdo->prepare("INSERT INTO users (email, password_hash, role, is_email_verified) VALUES (?,?,?,1)");
    $stmt->execute([$email, $pass, $role]);
    $id = (int)$pdo->lastInsertId();
    echo "  [CREATED] User $email (id=$id)\n";
    return $id;
}

function ensureStaffProfile(PDO $pdo, int $userId, string $name, string $roleTitle): int {
    $chk = $pdo->prepare("SELECT id FROM staff_profiles WHERE user_id=?");
    $chk->execute([$userId]);
    $row = $chk->fetch();
    if ($row) {
        $pdo->prepare("UPDATE staff_profiles SET name=?, role_title=? WHERE user_id=?")
            ->execute([$name, $roleTitle, $userId]);
        return $row['id'];
    }
    $pdo->prepare("INSERT INTO staff_profiles (user_id, name, role_title) VALUES (?,?,?)")
        ->execute([$userId, $name, $roleTitle]);
    return (int)$pdo->lastInsertId();
}

function ensureDoctor(PDO $pdo, int $userId, string $spec, string $license): int {
    $chk = $pdo->prepare("SELECT id FROM doctors WHERE user_id=?");
    $chk->execute([$userId]);
    $row = $chk->fetch();
    if ($row) return $row['id'];
    $pdo->prepare("INSERT INTO doctors (user_id, specialization, license_number) VALUES (?,?,?)")
        ->execute([$userId, $spec, $license]);
    return (int)$pdo->lastInsertId();
}

function ensurePatientProfile(PDO $pdo, int $userId, string $name, string $dob, string $gender, string $phone): int {
    $chk = $pdo->prepare("SELECT id FROM patient_profiles WHERE user_id=?");
    $chk->execute([$userId]);
    $row = $chk->fetch();
    if ($row) {
        $pdo->prepare("UPDATE patient_profiles SET full_name=?, dob=?, gender=?, phone=? WHERE user_id=?")
            ->execute([$name, $dob, $gender, $phone, $userId]);
        return $row['id'];
    }
    $pdo->prepare("INSERT INTO patient_profiles (user_id, full_name, dob, gender, phone) VALUES (?,?,?,?,?)")
        ->execute([$userId, $name, $dob, $gender, $phone]);
    return (int)$pdo->lastInsertId();
}

// ─── Step 1: Test Users ──────────────────────────────────────────────────

echo "\n[1] Creating / updating test user accounts...\n";

$adminId   = upsertUser($pdo, 'test.admin@clinic.com',     'admin',        $pass);
$docId     = upsertUser($pdo, 'test.doctor@clinic.com',    'doctor',       $pass);
$patId     = upsertUser($pdo, 'test.patient@clinic.com',   'patient',      $pass);
$recepId   = upsertUser($pdo, 'test.reception@clinic.com', 'receptionist', $pass);
$labId     = upsertUser($pdo, 'test.lab@clinic.com',       'lab_tech',     $pass);
$pharmId   = upsertUser($pdo, 'test.pharm@clinic.com',     'pharmacist',   $pass);

// Secondary patient for richer scenarios
$pat2Id    = upsertUser($pdo, 'jane.test@clinic.com',      'patient',      $pass);
$doc2Id    = upsertUser($pdo, 'test.doctor2@clinic.com',   'doctor',       $pass);

// ─── Step 2: Profiles ────────────────────────────────────────────────────

echo "\n[2] Ensuring profiles...\n";

ensureStaffProfile($pdo, $adminId,  'Admin User',           'System Administrator');
ensureStaffProfile($pdo, $docId,    'Dr. Alex Carter',      'Attending Physician');
ensureStaffProfile($pdo, $recepId,  'Maria Santos',         'Head Receptionist');
ensureStaffProfile($pdo, $labId,    'Leo Reyes',            'Senior Lab Technician');
ensureStaffProfile($pdo, $pharmId,  'Nina Cruz',            'Lead Pharmacist');
ensureStaffProfile($pdo, $doc2Id,   'Dr. Priya Sharma',     'Attending Physician');

$doctorPid  = ensureDoctor($pdo, $docId,   'General Medicine / Internal', 'LIC-2025-001');
$doctor2Pid = ensureDoctor($pdo, $doc2Id,  'Cardiology',                  'LIC-2025-002');

$patientPid = ensurePatientProfile($pdo, $patId,  'Alex Johnson',    '1990-03-15', 'male',   '+1-555-0100');
$patient2Pid = ensurePatientProfile($pdo, $pat2Id, 'Jane Martinez',  '1985-07-22', 'female', '+1-555-0200');

echo "  Doctor profile IDs: $doctorPid, $doctor2Pid\n";
echo "  Patient profile IDs: $patientPid, $patient2Pid\n";

// ─── Step 3: Medicines ───────────────────────────────────────────────────

echo "\n[3] Seeding medicines...\n";

$medicines = [
    ['Amoxicillin 500mg',    'amoxicillin', 12.50, 200],
    ['Ibuprofen 400mg',      'ibuprofen',    5.00, 500],
    ['Lisinopril 10mg',      'lisinopril',  18.00, 150],
    ['Metformin 500mg',      'metformin',   10.00, 300],
    ['Atorvastatin 20mg',    'atorvastatin',25.00, 120],
    ['Omeprazole 20mg',      'omeprazole',   8.75, 400],
    ['Paracetamol 500mg',    'paracetamol',  4.00, 600],
    ['Cetirizine 10mg',      'cetirizine',   6.50, 250],
    ['Azithromycin 250mg',   'azithromycin',22.00,  80],
    ['Losartan 50mg',        'losartan',    20.00, 180],
];

$medIds = [];
foreach ($medicines as [$name, $generic, $price, $stock]) {
    $chk = $pdo->prepare("SELECT id FROM medicines WHERE name=?");
    $chk->execute([$name]);
    $existing = $chk->fetch();
    if ($existing) {
        $medIds[$name] = $existing['id'];
    } else {
        $pdo->prepare("INSERT INTO medicines (name, generic_name, price, stock_quantity) VALUES (?,?,?,?)")
            ->execute([$name, $generic, $price, $stock]);
        $medIds[$name] = (int)$pdo->lastInsertId();
        echo "  [CREATED] Medicine: $name\n";
    }
}

// ─── Step 4: Appointments ────────────────────────────────────────────────

echo "\n[4] Creating appointments...\n";

$today       = date('Y-m-d');
$yesterday   = date('Y-m-d', strtotime('-1 day'));
$twoDaysAgo  = date('Y-m-d', strtotime('-2 days'));
$threeDaysAgo = date('Y-m-d', strtotime('-3 days'));
$tomorrow    = date('Y-m-d', strtotime('+1 day'));
$nextWeek    = date('Y-m-d', strtotime('+7 days'));

function insertAppointment(PDO $pdo, int $patPid, int $docPid, string $date, string $time, string $status): int {
    // Check if already exists to keep idempotent
    $chk = $pdo->prepare("SELECT id FROM appointments WHERE patient_id=? AND doctor_id=? AND appointment_date=? AND start_time=?");
    $chk->execute([$patPid, $docPid, $date, $time]);
    $existing = $chk->fetch();
    if ($existing) return $existing['id'];

    $endHour = (int)substr($time, 0, 2) + 1;
    $endTime = sprintf('%02d:%s', $endHour, substr($time, 3));
    $pdo->prepare("INSERT INTO appointments (patient_id, doctor_id, appointment_date, start_time, end_time, status) VALUES (?,?,?,?,?,?)")
        ->execute([$patPid, $docPid, $date, $time, $endTime, $status]);
    return (int)$pdo->lastInsertId();
}

$apptFuture1  = insertAppointment($pdo, $patientPid,  $doctorPid,  $tomorrow,    '09:00', 'scheduled');
$apptFuture2  = insertAppointment($pdo, $patientPid,  $doctorPid,  $nextWeek,    '14:00', 'scheduled');
$apptFuture3  = insertAppointment($pdo, $patient2Pid, $doctorPid,  $tomorrow,    '10:30', 'scheduled');
$apptFuture4  = insertAppointment($pdo, $patientPid,  $doctor2Pid, $tomorrow,    '11:00', 'scheduled');
$apptDone1    = insertAppointment($pdo, $patientPid,  $doctorPid,  $yesterday,   '09:00', 'completed');
$apptDone2    = insertAppointment($pdo, $patientPid,  $doctorPid,  $twoDaysAgo,  '14:30', 'completed');
$apptDone3    = insertAppointment($pdo, $patient2Pid, $doctorPid,  $threeDaysAgo,'10:00', 'completed');
$apptCancelled = insertAppointment($pdo, $patientPid, $doctor2Pid, $twoDaysAgo,  '15:00', 'cancelled');

echo "  Created/verified appointments: ids $apptFuture1, $apptFuture2, $apptDone1, $apptDone2, $apptDone3, $apptCancelled\n";

// ─── Step 5: Medical Records ──────────────────────────────────────────────

echo "\n[5] Creating medical records...\n";

function insertMedRecord(PDO $pdo, int $patPid, int $docPid, string $diagnosis, string $notes, string $createdAt): int {
    $chk = $pdo->prepare("SELECT id FROM medical_records WHERE patient_id=? AND doctor_id=? AND diagnosis=?");
    $chk->execute([$patPid, $docPid, $diagnosis]);
    $existing = $chk->fetch();
    if ($existing) return $existing['id'];

    $pdo->prepare("INSERT INTO medical_records (patient_id, doctor_id, diagnosis, clinical_notes, created_at) VALUES (?,?,?,?,?)")
        ->execute([$patPid, $docPid, $diagnosis, $notes, $createdAt]);
    return (int)$pdo->lastInsertId();
}

$rec1 = insertMedRecord($pdo, $patientPid, $doctorPid,
    'Acute Upper Respiratory Tract Infection',
    'Patient presents with sore throat, mild fever (38.1°C), and rhinorrhea for 3 days. Advised rest, fluids, and antipyretics. Follow up in one week if symptoms persist.',
    date('Y-m-d H:i:s', strtotime('-2 days'))
);

$rec2 = insertMedRecord($pdo, $patientPid, $doctorPid,
    'Hypertension Stage 1 — Initial Assessment',
    'BP 148/92 on two readings. Patient denies chest pain or dyspnea. ECG normal. Initiated lifestyle modifications plus Losartan 50mg QD. Recheck in 4 weeks.',
    date('Y-m-d H:i:s', strtotime('-1 day'))
);

$rec3 = insertMedRecord($pdo, $patient2Pid, $doctorPid,
    'Type 2 Diabetes Mellitus — Follow-up',
    'HbA1c at 7.2% (improved from 8.1%). Fasting glucose 126. Continue Metformin 500mg BID. Dietary counseling provided. Refer to nutritionist.',
    date('Y-m-d H:i:s', strtotime('-3 days'))
);

echo "  Medical records: ids $rec1, $rec2, $rec3\n";

// ─── Step 6: Prescriptions ────────────────────────────────────────────────

echo "\n[6] Creating prescriptions...\n";

function insertPrescription(PDO $pdo, int $recordId): int {
    $chk = $pdo->prepare("SELECT id FROM prescriptions WHERE record_id=?");
    $chk->execute([$recordId]);
    $existing = $chk->fetch();
    if ($existing) return $existing['id'];
    $pdo->prepare("INSERT INTO prescriptions (record_id) VALUES (?)")->execute([$recordId]);
    return (int)$pdo->lastInsertId();
}

function insertPrescriptionItem(PDO $pdo, int $rxId, int $medId, string $dosage, string $frequency): void {
    $chk = $pdo->prepare("SELECT id FROM prescription_items WHERE prescription_id=? AND medicine_id=?");
    $chk->execute([$rxId, $medId]);
    if ($chk->fetch()) return;
    $pdo->prepare("INSERT INTO prescription_items (prescription_id, medicine_id, dosage, frequency) VALUES (?,?,?,?)")
        ->execute([$rxId, $medId, $dosage, $frequency]);
}

$rx1 = insertPrescription($pdo, $rec1);
insertPrescriptionItem($pdo, $rx1, $medIds['Amoxicillin 500mg'],  '500mg', 'Three times daily for 7 days');
insertPrescriptionItem($pdo, $rx1, $medIds['Paracetamol 500mg'],  '500mg', 'Every 6 hours as needed for fever');
insertPrescriptionItem($pdo, $rx1, $medIds['Cetirizine 10mg'],    '10mg',  'Once daily at bedtime');

$rx2 = insertPrescription($pdo, $rec2);
insertPrescriptionItem($pdo, $rx2, $medIds['Losartan 50mg'],      '50mg',  'Once daily in the morning');
insertPrescriptionItem($pdo, $rx2, $medIds['Omeprazole 20mg'],    '20mg',  'Once daily before breakfast');

$rx3 = insertPrescription($pdo, $rec3);
insertPrescriptionItem($pdo, $rx3, $medIds['Metformin 500mg'],    '500mg', 'Twice daily with meals');
insertPrescriptionItem($pdo, $rx3, $medIds['Atorvastatin 20mg'],  '20mg',  'Once daily at bedtime');

echo "  Prescriptions: ids $rx1, $rx2, $rx3 with items\n";

// ─── Step 7: Lab Tests ────────────────────────────────────────────────────

echo "\n[7] Creating lab tests...\n";

function insertLabTest(PDO $pdo, int $patPid, int $docPid, string $type, string $status, string $notes = ''): int {
    $chk = $pdo->prepare("SELECT id FROM lab_tests WHERE patient_id=? AND doctor_id=? AND test_type=? AND status=?");
    $chk->execute([$patPid, $docPid, $type, $status]);
    $existing = $chk->fetch();
    if ($existing) return $existing['id'];

    $pdo->prepare("INSERT INTO lab_tests (patient_id, doctor_id, test_type, status, report_notes) VALUES (?,?,?,?,?)")
        ->execute([$patPid, $docPid, $type, $status, $notes ?: null]);
    return (int)$pdo->lastInsertId();
}

// Pending tests (for lab tech worklist)
$lab1 = insertLabTest($pdo, $patientPid, $doctorPid, 'Complete Blood Count (CBC)', 'pending');
$lab2 = insertLabTest($pdo, $patientPid, $doctorPid, 'Fasting Blood Glucose',      'pending');
$lab3 = insertLabTest($pdo, $patient2Pid, $doctorPid, 'HbA1c Test',               'pending');
$lab4 = insertLabTest($pdo, $patientPid, $doctor2Pid, 'Lipid Panel',              'pending');

// Completed tests (visible in patient history + lab completed tab)
$lab5 = insertLabTest($pdo, $patientPid, $doctorPid, 'Chest X-Ray',
    'completed',
    'Chest X-Ray: No acute cardiopulmonary process. Lung fields clear. Heart size within normal limits. No effusion or infiltrate noted.'
);
$lab6 = insertLabTest($pdo, $patientPid, $doctorPid, 'Urinalysis',
    'completed',
    'Urinalysis: Color: Yellow. Clarity: Clear. pH 6.0. No glucose, protein, or blood detected. Normal microscopy.'
);
$lab7 = insertLabTest($pdo, $patient2Pid, $doctorPid, 'Thyroid Function Tests (TSH)',
    'completed',
    'TSH: 2.4 mIU/L (Normal range 0.4-4.0). Free T4: 1.2 ng/dL (Normal). No thyroid dysfunction detected.'
);

echo "  Lab tests: pending=$lab1,$lab2,$lab3,$lab4 | completed=$lab5,$lab6,$lab7\n";

// ─── Step 8: Invoices ─────────────────────────────────────────────────────

echo "\n[8] Creating invoices...\n";

function insertInvoice(PDO $pdo, int $patPid, string $refType, int $refId, float $amount, string $status): int {
    $chk = $pdo->prepare("SELECT id FROM invoices WHERE patient_id=? AND reference_type=? AND reference_id=?");
    $chk->execute([$patPid, $refType, $refId]);
    $existing = $chk->fetch();
    if ($existing) {
        $pdo->prepare("UPDATE invoices SET status=? WHERE id=?")->execute([$status, $existing['id']]);
        return $existing['id'];
    }
    $pdo->prepare("INSERT INTO invoices (patient_id, reference_type, reference_id, amount, status) VALUES (?,?,?,?,?)")
        ->execute([$patPid, $refType, $refId, $amount, $status]);
    return (int)$pdo->lastInsertId();
}

// RX1 cost: Amoxicillin $12.50 + Paracetamol $4.00 + Cetirizine $6.50 = $23.00
$inv1 = insertInvoice($pdo, $patientPid, 'prescription', $rx1, 23.00, 'paid');

// RX2 cost: Losartan $20.00 + Omeprazole $8.75 = $28.75
$inv2 = insertInvoice($pdo, $patientPid, 'prescription', $rx2, 28.75, 'pending');

// Consultation fee for completed appointment
$inv3 = insertInvoice($pdo, $patientPid, 'consultation', $apptDone1, 75.00, 'paid');

// Lab invoice - pending
$inv4 = insertInvoice($pdo, $patientPid, 'lab', $lab5, 45.00, 'paid');
$inv5 = insertInvoice($pdo, $patientPid, 'lab', $lab6, 35.00, 'pending');

echo "  Invoices: paid=$inv1,$inv3,$inv4 | pending=$inv2,$inv5\n";

// ─── Step 9: Reviews ──────────────────────────────────────────────────────

echo "\n[9] Creating reviews...\n";

function insertReview(PDO $pdo, int $patPid, int $docPid, int $rating, string $comment): void {
    $chk = $pdo->prepare("SELECT id FROM reviews WHERE patient_id=? AND doctor_id=?");
    $chk->execute([$patPid, $docPid]);
    if ($chk->fetch()) return;
    $pdo->prepare("INSERT INTO reviews (patient_id, doctor_id, rating, comment) VALUES (?,?,?,?)")
        ->execute([$patPid, $docPid, $rating, $comment]);
}

insertReview($pdo, $patientPid,  $doctorPid,
    5, 'Dr. Carter was incredibly thorough and explained everything clearly. I felt heard and well cared for. Highly recommend!'
);
insertReview($pdo, $patient2Pid, $doctorPid,
    4, 'Very professional and knowledgeable. Waiting time was a little long, but the consultation itself was excellent.'
);
insertReview($pdo, $patientPid,  $doctor2Pid,
    5, 'Dr. Sharma is amazing — she took the time to walk me through the cardiac results step by step. Very reassuring.'
);

echo "  Reviews created.\n";

// ─── Step 10: Summary ─────────────────────────────────────────────────────

echo "\n\n=== SEED COMPLETE ===\n";
echo "Test Credentials (all use password: password)\n";
echo str_repeat('-', 55) . "\n";
$accounts = [
    ['admin',        'test.admin@clinic.com'],
    ['doctor',       'test.doctor@clinic.com'],
    ['patient',      'test.patient@clinic.com'],
    ['receptionist', 'test.reception@clinic.com'],
    ['lab_tech',     'test.lab@clinic.com'],
    ['pharmacist',   'test.pharm@clinic.com'],
    ['patient 2',    'jane.test@clinic.com'],
    ['doctor 2',     'test.doctor2@clinic.com'],
];
foreach ($accounts as [$role, $email]) {
    printf("  %-16s %s\n", "[$role]", $email);
}
echo str_repeat('-', 55) . "\n";
echo "Seeded: appointments, medical records, prescriptions,\n";
echo "        lab tests (pending & completed), invoices, reviews\n\n";
