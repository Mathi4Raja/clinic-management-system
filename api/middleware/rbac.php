<?php
/**
 * Role-Based Access Control (RBAC) Middleware
 */

require_once __DIR__ . '/../config.php';

/**
 * Verify if current user has the required roles
 * @param array $allowedRoles Array of strings (e.g., ['admin', 'doctor'])
 * @return void
 */
function authorize($allowedRoles)
{
    initSecureSession();

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized: Please login.']);
        exit;
    }

    if (!in_array($_SESSION['role'], $allowedRoles)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden: You do not have permission to access this resource.']);
        exit;
    }
}

/**
 * Common Authorization Presets
 */
const ROLE_ADMIN = 'admin';
const ROLE_DOCTOR = 'doctor';
const ROLE_PATIENT = 'patient';
const ROLE_RECEPTIONIST = 'receptionist';
const ROLE_LAB_TECH = 'lab_tech';
const ROLE_PHARMACIST = 'pharmacist';

const ALL_ROLES = [ROLE_ADMIN, ROLE_DOCTOR, ROLE_PATIENT, ROLE_RECEPTIONIST, ROLE_LAB_TECH, ROLE_PHARMACIST];
const STAFF_ROLES = [ROLE_ADMIN, ROLE_DOCTOR, ROLE_RECEPTIONIST, ROLE_LAB_TECH, ROLE_PHARMACIST];
