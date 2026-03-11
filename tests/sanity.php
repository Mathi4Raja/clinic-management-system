<?php
/**
 * CMS Automated Sanity Test Suite
 * Simulates end-to-end business flows across multiple roles.
 * Run via CLI: php tests/sanity.php
 */

class CMSTester
{
    private $base_url;
    protected $cookies = [];
    protected $csrf_token = '';

    public function __construct($url)
    {
        $this->base_url = rtrim($url, '/');
    }

    /**
     * @return array
     */
    protected function request(string $path, string $method = 'GET', ?array $data = null): array
    {
        $url = $this->base_url . '/api/' . ltrim($path, '/');

        $options = [
            'http' => [
                'method' => $method,
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json'
                ],
                'ignore_errors' => true
            ]
        ];

        if ($this->cookies) {
            $cookie_strings = [];
            foreach ($this->cookies as $name => $value) {
                $cookie_strings[] = "$name=$value";
            }
            $options['http']['header'][] = 'Cookie: ' . implode('; ', $cookie_strings);
        }

        if ($this->csrf_token) {
            $options['http']['header'][] = 'X-CSRF-TOKEN: ' . $this->csrf_token;
        }

        if ($data) {
            if ($method === 'POST') {
                $options['http']['content'] = json_encode($data);
            } else {
                $url .= '?' . http_build_query($data);
            }
        }

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        // Handle Cookies from response
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/i', $header, $matches)) {
                    $this->cookies[trim($matches[1])] = trim($matches[2]);
                }
            }
        }

        $response = json_decode($result, true);

        // Extract CSRF token if present in registration/auth context
        if (isset($response['csrf_token'])) {
            $this->csrf_token = $response['csrf_token'];
        }

        return [
            'status' => explode(' ', $http_response_header[0])[1],
            'data' => $response
        ];
    }

    public function run()
    {
        echo "\n🏁 Starting End-to-End Sanity Automation\n";
        echo "========================================\n";

        try {
            // 1. Auth Sanity (Receptionist)
            echo "[1/5] Testing Receptionist Flow... ";
            $login = $this->request('auth/login.php', 'POST', [
                'email' => 'reception@clinic.com',
                'password' => 'password'
            ]);
            if ($login['status'] != 200)
                throw new Exception("Receptionist Login Failed: " . json_encode($login['data']));

            // Register Patient
            $reg = $this->request('patients/register.php', 'POST', [
                'full_name' => 'Auto Test Patient',
                'email' => 'auto_test_' . time() . '@test.com',
                'password' => 'password',
                'dob' => '1990-01-01',
                'gender' => 'Other',
                'phone' => '555-0000'
            ]);
            if ($reg['status'] != 201)
                throw new Exception("Patient Registration Failed: " . json_encode($reg['data']));
            $patient_id = $reg['data']['patient_id'];
            echo "✅ Patient Registered (ID: $patient_id)\n";

            // 2. Doctor Flow
            echo "[2/5] Testing Doctor Workflow... ";
            $this->cookies = []; // Reset session
            $this->csrf_token = '';
            $this->request('auth/login.php', 'POST', [
                'email' => 'doctor1@clinic.com',
                'password' => 'password'
            ]);

            // Create Medical Record
            $med = $this->request('medical/create_record.php', 'POST', [
                'patient_id' => $patient_id,
                'diagnosis' => 'Automated Sanity Test Check',
                'notes' => 'Patient is healthy in simulation.',
                'prescription' => [
                    ['medicine_id' => 1, 'dosage' => '1 per day', 'duration' => '3 days']
                ],
                'lab_tests' => ['Blood Count']
            ]);
            if ($med['status'] != 201)
                throw new Exception("Clinical Record Creation Failed: " . json_encode($med['data']));
            echo "✅ Record & Prescription Saved\n";

            // 3. Lab Flow
            echo "[3/5] Testing Lab Integration... ";
            $this->cookies = [];
            $this->csrf_token = '';
            $this->request('auth/login.php', 'POST', [
                'email' => 'lab@clinic.com',
                'password' => 'password'
            ]);

            $tests = $this->request('lab/tests.php');
            $test_id = null;
            if (isset($tests['data']['tests'])) {
                foreach ($tests['data']['tests'] as $t) {
                    if ($t['patient_name'] === 'Auto Test Patient') {
                        $test_id = $t['id'];
                        break;
                    }
                }
            }
            if (!$test_id)
                throw new Exception("Lab Test Order not found in queue");
            echo "✅ Test Verified in Queue\n";

            // 4. Pharmacist & Billing
            echo "[4/5] Testing Pharmacy & Billing... ";
            $this->cookies = [];
            $this->csrf_token = '';
            $this->request('auth/login.php', 'POST', [
                'email' => 'pharm@clinic.com',
                'password' => 'password'
            ]);

            $presc = $this->request('pharmacy/prescriptions.php');
            $presc_id = null;
            if (isset($presc['data']['prescriptions'])) {
                foreach ($presc['data']['prescriptions'] as $p) {
                    if ($p['patient_name'] === 'Auto Test Patient') {
                        $presc_id = $p['id'];
                        break;
                    }
                }
            }
            if (!$presc_id)
                throw new Exception("Prescription not found in pharmacy");

            // Generate Invoice
            $inv = $this->request('billing/create_invoice.php', 'POST', [
                'patient_id' => $patient_id,
                'reference_type' => 'prescription',
                'reference_id' => $presc_id,
                'amount' => 150.00
            ]);
            if ($inv['status'] != 201)
                throw new Exception("Invoicing Failed: " . json_encode($inv['data']));
            echo "✅ billing/Invoicing Successful\n";

            // 5. Admin Audit
            echo "[5/5] Verifying System Audit... ";
            $this->cookies = [];
            $this->csrf_token = '';
            $this->request('auth/login.php', 'POST', [
                'email' => 'admin@clinic.com',
                'password' => 'password'
            ]);

            $audit = $this->request('admin/audit.php');
            if ($audit['status'] != 200 || !isset($audit['data']['stats']))
                throw new Exception("Audit Access Failed: " . json_encode($audit['data']));
            echo "✅ Audit Verified. Total Patients: " . $audit['data']['stats']['total_patients'] . "\n";

            echo "========================================\n";
            echo "🎉 ALL SANITY TESTS PASSED\n\n";

        } catch (Exception $e) {
            echo "❌ CRITICAL FAILURE: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
}

// Entry point - adjust URL as needed for environment
$tester = new CMSTester('http://localhost/clinic%20management%20system');
$tester->run();
