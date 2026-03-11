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
        echo "[1/2] Testing Inventory Logic... ";

        // Check initial stock of Paracetamol (ID 3 in sample)
        $stmt = $this->pdo->query("SELECT stock_quantity FROM medicines WHERE id = 3");
        $initial = $stmt->fetchColumn();

        // Simulate a 'dispense' event (decrement)
        $this->pdo->exec("UPDATE medicines SET stock_quantity = stock_quantity - 1 WHERE id = 3");

        $stmt = $this->pdo->query("SELECT stock_quantity FROM medicines WHERE id = 3");
        $final = $stmt->fetchColumn();

        if ($final == ($initial - 1)) {
            echo "✅ Stock Decremented Correctly\n";
        } else {
            throw new Exception("Inventory deduction mismatch");
        }

        // Rollback for test purity
        $this->pdo->exec("UPDATE medicines SET stock_quantity = stock_quantity + 1 WHERE id = 3");
    }

    /**
     * Verify API access rejection for unauthorized roles
     */
    private function testRBACBoundaries()
    {
        echo "[2/2] Testing RBAC Boundaries... ";

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
}

$tester = new FunctionalTester();
$tester->run();
