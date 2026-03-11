-- Clinic Management System (CMS) Database Schema
-- Optimized and Normalized for MySQL 8.x+

CREATE DATABASE IF NOT EXISTS `clinic_cms`;
USE `clinic_cms`;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- 1. Users Table (Core Auth)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('admin', 'doctor', 'patient', 'receptionist', 'lab_tech', 'pharmacist') NOT NULL,
  `is_email_verified` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Staff Profiles
CREATE TABLE IF NOT EXISTS `staff_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `role_title` VARCHAR(100),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Patient Profiles
CREATE TABLE IF NOT EXISTS `patient_profiles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `full_name` VARCHAR(255) NOT NULL,
  `dob` DATE NOT NULL,
  `gender` ENUM('male', 'female', 'other') NOT NULL,
  `phone` VARCHAR(20),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Doctors Table
CREATE TABLE IF NOT EXISTS `doctors` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `specialization` VARCHAR(255) NOT NULL,
  `license_number` VARCHAR(100) NOT NULL UNIQUE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Medicines Table
CREATE TABLE IF NOT EXISTS `medicines` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `generic_name` VARCHAR(255),
  `price` DECIMAL(10, 2) NOT NULL,
  `stock_quantity` INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Appointments Table
CREATE TABLE IF NOT EXISTS `appointments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `appointment_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `status` ENUM('scheduled', 'completed', 'cancelled', 'no_show') DEFAULT 'scheduled',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patient_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Medical Records Table
CREATE TABLE IF NOT EXISTS `medical_records` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `diagnosis` TEXT NOT NULL,
  `clinical_notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patient_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Prescriptions Table
CREATE TABLE IF NOT EXISTS `prescriptions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `record_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`record_id`) REFERENCES `medical_records`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Prescription Items Table
CREATE TABLE IF NOT EXISTS `prescription_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `prescription_id` INT NOT NULL,
  `medicine_id` INT NOT NULL,
  `dosage` VARCHAR(255) NOT NULL,
  `frequency` VARCHAR(255) NOT NULL,
  FOREIGN KEY (`prescription_id`) REFERENCES `prescriptions`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`medicine_id`) REFERENCES `medicines`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Lab Tests Table
CREATE TABLE IF NOT EXISTS `lab_tests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `doctor_id` INT NOT NULL,
  `test_type` VARCHAR(255) NOT NULL,
  `status` ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
  `report_file_path` VARCHAR(255),
  `report_notes` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patient_profiles`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Invoices Table
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `patient_id` INT NOT NULL,
  `reference_type` ENUM('prescription', 'consultation', 'lab') NOT NULL,
  `reference_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pending', 'paid', 'void') DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`patient_id`) REFERENCES `patient_profiles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- DEDICATED TEST CREDENTIALS
-- ==========================================

-- Test Admin (test.admin@clinic.com / testpass123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `is_email_verified`) VALUES 
('test.admin@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Test Doctor (test.doctor@clinic.com / testpass123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `is_email_verified`) VALUES 
('test.doctor@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 1);
SET @test_doc_uid = LAST_INSERT_ID();
INSERT INTO `staff_profiles` (`user_id`, `name`, `role_title`) VALUES (@test_doc_uid, 'Dr. Test Subject', 'QA Doctor');
INSERT INTO `doctors` (`user_id`, `specialization`, `license_number`) VALUES (@test_doc_uid, 'General Medicine', 'LIC-TEST-001');

-- Test Patient (test.patient@clinic.com / testpass123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `is_email_verified`) VALUES 
('test.patient@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'patient', 1);
SET @test_pat_uid = LAST_INSERT_ID();
INSERT INTO `patient_profiles` (`user_id`, `full_name`, `dob`, `gender`, `phone`) VALUES (@test_pat_uid, 'Patient Alpha', '1995-05-05', 'Male', '555-TEST');

-- Test Receptionist (test.reception@clinic.com / testpass123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `is_email_verified`) VALUES 
('test.reception@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', 1);
SET @test_rec_uid = LAST_INSERT_ID();
INSERT INTO `staff_profiles` (`user_id`, `name`, `role_title`) VALUES (@test_rec_uid, 'Tester Front Desk', 'QA Lead');

-- Test Lab Tech (test.lab@clinic.com / testpass123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `is_email_verified`) VALUES 
('test.lab@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lab_tech', 1);
SET @test_lab_uid = LAST_INSERT_ID();
INSERT INTO `staff_profiles` (`user_id`, `name`, `role_title`) VALUES (@test_lab_uid, 'Tester Laboratory', 'Lab Manager');

-- Test Pharmacist (test.pharm@clinic.com / testpass123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `is_email_verified`) VALUES 
('test.pharm@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pharmacist', 1);
SET @test_phm_uid = LAST_INSERT_ID();
INSERT INTO `staff_profiles` (`user_id`, `name`, `role_title`) VALUES (@test_phm_uid, 'Tester Pharmacy', 'Pharma Lead');

-- ==========================================
-- SAMPLE DATA FOR INITIAL TESTING
-- ==========================================

-- Default Admin (Password: admin123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `is_email_verified`) VALUES 
('admin@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1);

-- Sample Doctor (Password: password123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `is_email_verified`) VALUES 
('doctor1@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'doctor', 1);
SET @doc1_uid = LAST_INSERT_ID();

INSERT INTO `staff_profiles` (`user_id`, `name`, `role_title`) VALUES 
(@doc1_uid, 'Dr. Sarah Connor', 'Senior Cardiologist');

INSERT INTO `doctors` (`user_id`, `specialization`, `license_number`) VALUES 
(@doc1_uid, 'Cardiology', 'LIC-99001');

-- Sample Pharmacist (Password: password123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `is_email_verified`) VALUES 
('pharm@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'pharmacist', 1);
SET @phm_uid = LAST_INSERT_ID();
INSERT INTO `staff_profiles` (`user_id`, `name`, `role_title`) VALUES (@phm_uid, 'James Wilson', 'Chief Pharmacist');

-- Sample Receptionist (Password: password123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `is_email_verified`) VALUES 
('reception@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'receptionist', 1);
SET @rec_uid = LAST_INSERT_ID();
INSERT INTO `staff_profiles` (`user_id`, `name`, `role_title`) VALUES (@rec_uid, 'Clara Oswald', 'Front Desk Lead');

-- Sample Lab Tech (Password: password123)
INSERT INTO `users` (`email`, `password_hash`, `role`, `is_email_verified`) VALUES 
('lab@clinic.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'lab_tech', 1);
SET @lab_uid = LAST_INSERT_ID();
INSERT INTO `staff_profiles` (`user_id`, `name`, `role_title`) VALUES (@lab_uid, 'Barry Allen', 'Diagnostic Technician');

-- Sample Medicines
INSERT INTO `medicines` (`name`, `generic_name`, `price`, `stock_quantity`) VALUES 
('Amoxicillin', 'Amoxil', 45.00, 100),
('Ibuprofen', 'Advil', 12.50, 250),
('Paracetamol', 'Panadol', 8.00, 500),
('Metformin', 'Glucophage', 30.00, 150);
