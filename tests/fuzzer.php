<?php
/**
 * CMS Automated Security Fuzzer
 * Tests input validation and sanitization for SQLi and XSS payloads.
 */

class CMSFuzzer
{
    private $payloads = [
        'sql_injection' => ["' OR '1'='1", "'; DROP TABLE users; --", "1' WAITFOR DELAY '0:0:5' --"],
        'xss' => ["<script>alert('xss')</script>", "javascript:alert(1)", "<img src=x onerror=alert(1)>"],
        'path_traversal' => ["../../config.php", "/etc/passwd"]
    ];

    private $target_endpoint = 'http://localhost/clinic management system/api/auth/login.php';

    public function run()
    {
        echo "\n🛡️ Starting Security Fuzzing Audit...\n";
        echo "========================================\n";

        $vulnerabilities = 0;

        foreach ($this->payloads as $type => $list) {
            echo "Testing $type... ";
            $type_fail = false;
            foreach ($list as $payload) {
                if ($this->testPayload($payload)) {
                    $type_fail = true;
                    $vulnerabilities++;
                }
            }
            if ($type_fail) {
                echo "❌ High Risk Detected\n";
            } else {
                echo "✅ Sanitized\n";
            }
        }

        echo "========================================\n";
        if ($vulnerabilities === 0) {
            echo "🏆 FUZZ TEST PASSED: No immediate vulnerabilities found.\n\n";
        } else {
            echo "🚨 FUZZ TEST FAILED: $vulnerabilities sensitive points detected.\n\n";
        }
    }

    private function testPayload($p)
    {
        $ch = curl_init($this->target_endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $p, 'password' => 'test']));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // A basic check: if a payload leads to a 500 error, it likely broke a query (SQLi risk)
        // If the payload is reflected raw in the JSON response, it's an XSS risk.
        if ($code == 500)
            return true;
        if (strpos($resp, $p) !== false)
            return true;

        return false;
    }
}

$fuzzer = new CMSFuzzer();
$fuzzer->run();
