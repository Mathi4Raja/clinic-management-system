<?php
/**
 * CMS Unified Test Handler
 * Orchestrates all PHP test suites and provides a summary report.
 */

class TestHandler {
    private $phpBinary;
    private $testDir;
    private $suites = [
        'smoke.php'          => 'Environment & Connectivity',
        'validate_backend.php' => 'Syntax Lint & Reachability',
        'sanity.php'         => 'End-to-End clinical Flow',
        'full_journey.php'   => 'Master User-Flow Journey',
        'functional.php'     => 'Business Logic & RBAC',
        'integrity.php'      => 'Database Referential Integrity',
        'non_functional.php' => 'Performance & Security Audit',
        'security_audit.php' => 'Security Penetration Suite',
        'fuzzer.php'         => 'Security Input Fuzzing',
        'stress.php'         => 'Concurrency & Load Resilience'
    ];

    public function __construct() {
        $this->testDir = __DIR__;
        // Search for PHP binary in common XAMPP paths
        $paths = [
            'C:/Program Files/XAMPP/php/php.exe',
            'C:/xampp/php/php.exe',
            'php'
        ];
        
        foreach ($paths as $path) {
            $output = [];
            $returnVar = 0;
            exec(escapeshellarg($path) . ' -v', $output, $returnVar);
            if ($returnVar === 0) {
                $this->phpBinary = $path;
                break;
            }
        }

        if (!$this->phpBinary) {
            die("❌ Error: PHP binary not found. Please ensure PHP is in your PATH.\n");
        }
    }

    public function runAll() {
        $start = microtime(true);
        $results = [];
        
        echo "\n🚀 Starting CMS Unified Test Suite\n";
        echo "===========================================================\n";
        echo "PHP Binary: {$this->phpBinary}\n";
        echo "Test Directory: {$this->testDir}\n";
        echo "===========================================================\n\n";

        foreach ($this->suites as $file => $description) {
            $filePath = $this->testDir . DIRECTORY_SEPARATOR . $file;
            echo "Running [{$description}] ($file)...\n";
            
            if (!file_exists($filePath)) {
                echo "  ⚠️  Skipping: File not found.\n\n";
                $results[$file] = ['status' => 'SKIPPED', 'output' => 'File missing'];
                continue;
            }

            $output = [];
            $returnVar = 0;
            exec(escapeshellarg($this->phpBinary) . " " . escapeshellarg($filePath), $output, $returnVar);
            
            $status = ($returnVar === 0) ? 'PASSED' : 'FAILED';
            $results[$file] = [
                'status' => $status,
                'output' => implode("\n", $output)
            ];

            if ($status === 'PASSED') {
                echo "  ✅ PASSED\n\n";
            } else {
                echo "  ❌ FAILED\n";
                echo "-----------------------------------------------------------\n";
                echo implode("\n", array_slice($output, -10)) . "\n"; // Show last 10 lines of error
                echo "-----------------------------------------------------------\n\n";
            }
        }

        $end = microtime(true);
        $this->printSummary($results, $end - $start);

        // Exit with 1 if any test failed
        foreach ($results as $res) {
            if ($res['status'] === 'FAILED') exit(1);
        }
    }

    private function printSummary($results, $duration) {
        $passed = 0;
        $failed = 0;
        $skipped = 0;

        echo "===========================================================\n";
        echo "📋 TEST EXECUTION SUMMARY\n";
        echo "===========================================================\n";
        
        foreach ($results as $file => $data) {
            $indicator = $data['status'] === 'PASSED' ? '✅' : ($data['status'] === 'FAILED' ? '❌' : '⚠️');
            printf("%-30s [%-8s] %s\n", $this->suites[$file], $data['status'], $indicator);
            
            if ($data['status'] === 'PASSED') $passed++;
            elseif ($data['status'] === 'FAILED') $failed++;
            else $skipped++;
        }

        echo "-----------------------------------------------------------\n";
        echo "Total: " . count($results) . " | Passed: $passed | Failed: $failed | Skipped: $skipped\n";
        echo "Duration: " . round($duration, 2) . "s\n";
        echo "===========================================================\n\n";

        if ($failed > 0) {
            echo "🚨 SYSTEM ALERT: One or more test suites failed.\n";
        } else {
            echo "🏆 SYSTEM STABLE: All suites passed.\n";
        }
    }
}

$handler = new TestHandler();
$handler->runAll();
