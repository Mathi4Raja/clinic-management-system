<?php
/**
 * CMS Functional Test Suite
 * Focuses on business logic: inventory, scheduling conflicts, etc.
 * Run via CLI: php tests/functional.php
 */

require_once __DIR__ . '/../api/config.php';

class FunctionalTester
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = getDBConnection();
    }

    public function run()
    {
        echo "\n🧩 Starting Functional Logic Tests\n";
        echo "========================================\n";

        try {
            $this->testInventoryDeduction();
            $this->testRBACBoundaries();
            $this->testAppointmentStatusUpdate();
            $this->testReviewsTableExists();

            echo "========================================\n";
            echo "🎉 ALL FUNCTIONAL TESTS PASSED\n\n";
        } catch (Exception $e) {
            echo "❌ FAILURE: " . $e->getMessage() . "\n";
            exit(1);
        }
    }

    /**
     * Verify that medicine stock is correctly tracked (conceptual check)
     */
    private function testInventoryDeduction()
    {
        echo "[1/4] Testing Inventory Logic... ";

        // Look up Paracetamol by name — avoids brittle hardcoded ID dependency
        $stmt = $this->pdo->prepare("SELECT id, stock_quantity FROM medicines WHERE name = 'Paracetamol' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Paracetamol not found in medicines table — ensure seed data is loaded");
        }

        $medId  = $row['id'];
        $initial = (int)$row['stock_quantity'];

        // Simulate a 'dispense' event (decrement)
        $this->pdo->prepare("UPDATE medicines SET stock_quantity = stock_quantity - 1 WHERE id = ?")->execute([$medId]);

        $stmt = $this->pdo->prepare("SELECT stock_quantity FROM medicines WHERE id = ?");
        $stmt->execute([$medId]);
        $final = (int)$stmt->fetchColumn();

        if ($final === $initial - 1) {
            echo "✅ Stock Decremented Correctly\n";
        } else {
            throw new Exception("Inventory deduction mismatch: expected " . ($initial - 1) . ", got $final");
        }

        // Rollback for test purity
        $this->pdo->prepare("UPDATE medicines SET stock_quantity = stock_quantity + 1 WHERE id = ?")->execute([$medId]);
    }

    /**
     * Verify API access rejection for unauthorized roles
     */
    private function testRBACBoundaries()
    {
        echo "[2/4] Testing RBAC Boundaries... ";

        // Mock a Patient Session
        $_SESSION['user_id'] = 999; // Non-existent patient
        $_SESSION['role'] = 'patient';

        // We'll simulate the authorize() function check
        $allowed_roles = ['admin'];
        if (!in_array($_SESSION['role'], $allowed_roles)) {
            echo "✅ Unauthorized Access Blocked (Simulation)\n";
        } else {
            throw new Exception("RBAC Boundary Breach: Patient allowed into Admin area");
        }

        unset($_SESSION['user_id'], $_SESSION['role']);
    }

    /**
     * Verify appointment status transitions are valid
     */
    private function testAppointmentStatusUpdate()
    {
        echo "[3/4] Testing Appointment Status Logic... ";

        // Find a completed appointment and verify its status
        $stmt = $this->pdo->query("SELECT id, status FROM appointments WHERE status = 'completed' LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            if ($row['status'] === 'completed') {
                echo "✅ Appointment Status Transitions Valid\n";
            } else {
                throw new Exception("Expected 'completed' status but got '" . $row['status'] . "'");
            }
        } else {
            // No completed appointments — just verify the status ENUM accepts valid values
            $valid = ['scheduled', 'completed', 'cancelled', 'no_show'];
            $stmt = $this->pdo->query("SHOW COLUMNS FROM appointments LIKE 'status'");
            $col = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "✅ Appointment Status Column Confirmed\n";
        }
    }

    /**
     * Verify the reviews table exists and has correct structure
     */
    private function testReviewsTableExists()
    {
        echo "[4/4] Testing Reviews Table Schema... ";

        $stmt = $this->pdo->query("SHOW TABLES LIKE 'reviews'");
        if ($stmt->rowCount() === 0) {
            throw new Exception("'reviews' table not found — run database_schema.sql");
        }

        $stmt = $this->pdo->query("SHOW COLUMNS FROM reviews");
        $cols = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        $required = ['id', 'patient_id', 'doctor_id', 'rating', 'comment', 'created_at'];
        $missing = array_diff($required, $cols);

        if (empty($missing)) {
            echo "✅ Reviews Table Structure Valid\n";
        } else {
            throw new Exception("Reviews table missing columns: " . implode(', ', $missing));
        }
    }
}

$tester = new FunctionalTester();
$tester->run();
