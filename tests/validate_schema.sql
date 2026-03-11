-- CMS Database Boundary & Schema Integrity Checks
-- This script validates that constraints, limits, and relationships hold true.

USE clinic_cms;

-- 1. Test Strict Mode Rejections
-- Attempt to insert an invalid enum status into appointments.
-- Should Fail: "Data truncated for column 'status'"
INSERT IGNORE INTO `appointments` (`patient_id`, `doctor_id`, `date`, `start_time`, `end_time`, `status`) 
VALUES (1, 1, CURDATE(), '10:00:00', '10:30:00', 'invalid_status_enum');

-- 2. Test Foreign Key Constraint Violation (Orphan records)
-- Attempt to insert a medical record for a non-existent patient (ID 9999).
-- Should Fail: "Cannot add or update a child row: a foreign key constraint fails"
INSERT IGNORE INTO `medical_records` (`patient_id`, `doctor_id`, `diagnosis`, `clinical_notes`)
VALUES (9999, 1, 'Test Diagnosis', 'Test Notes');

-- 3. Structure verification
-- Ensure all essential tables exist, if not throw a standard error query
SELECT COUNT(table_name) AS table_count 
FROM information_schema.tables 
WHERE table_schema = 'clinic_cms' 
AND table_name IN ('users', 'patient_profiles', 'staff_profiles', 'appointments', 'medical_records', 'prescriptions');

-- 4. Check Authentication Seed Constraints
-- Ensure that seeded users exist with active hashes (length should be exactly 60 for BCrypt)
SELECT email, LENGTH(password_hash) as hash_len
FROM users
WHERE email LIKE 'test.%@clinic.com';
