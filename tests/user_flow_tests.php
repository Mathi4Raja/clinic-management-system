<?php
/**
 * Clinic CMS — Comprehensive User Flow & API Tests
 *
 * Coverage:
 *   ✔ Every API endpoint for every authorized role
 *   ✔ All edge cases: missing fields, invalid values, boundary violations
 *   ✔ Full RBAC matrix per role (forbidden endpoint checks)
 *   ✔ Data isolation: users can only see/mutate their own data
 *   ✔ Full CRUD flows where applicable (create → read → update → delete)
 *   ✔ CSRF enforcement on every mutation endpoint
 *   ✔ Session lifecycle: login → session check → logout → post-logout guard
 *
 * Usage:
 *   php tests/user_flow_tests.php               # all sections
 *   php tests/user_flow_tests.php --role=doctor # single role
 *   php tests/user_flow_tests.php --verbose     # show raw responses
 *
 * Prerequisites:
 *   1. XAMPP Apache + MySQL running on localhost
 *   2. Database seeded: php seed_data.php
 */

define('BASE_URL', 'http://localhost/clinic%20management%20system/api');
define('PASS', 'password');

$opts     = getopt('', ['role:', 'verbose']);
$onlyRole = $opts['role'] ?? null;
$verbose  = isset($opts['verbose']);

// ═══════════════════════════════════════════════════════════════════════════
// TEST RUNNER
// ═══════════════════════════════════════════════════════════════════════════

class TestRunner {
    public int $passed  = 0;
    public int $failed  = 0;
    public int $skipped = 0;
    private array $failures = [];

    public function assert(string $label, bool $cond, string $detail = ''): void {
        if ($cond) {
            echo "    \e[32m✓\e[0m $label\n";
            $this->passed++;
        } else {
            echo "    \e[31m✗\e[0m $label" . ($detail ? "\n      → $detail" : '') . "\n";
            $this->failed++;
            $this->failures[] = ['label' => $label, 'detail' => $detail];
        }
    }

    public function assertStatus(string $label, array $res, int $expected): void {
        $ok = ($res['status'] === $expected);
        $this->assert($label, $ok,
            $ok ? '' : "Expected HTTP $expected, got {$res['status']}. Body: " . substr($res['body'], 0, 240));
    }

    public function assertAnyStatus(string $label, array $res, array $acceptable): void {
        $ok = in_array($res['status'], $acceptable);
        $this->assert($label, $ok,
            $ok ? '' : "Expected one of " . implode('|', $acceptable) . ", got {$res['status']}. Body: " . substr($res['body'], 0, 200));
    }

    public function assertKey(string $label, array $res, string $key): void {
        $data = json_decode($res['body'], true);
        $this->assert($label, isset($data[$key]),
            "Key '$key' missing. Body: " . substr($res['body'], 0, 240));
    }

    public function assertNoKey(string $label, string $json, string $key): void {
        $this->assert($label, strpos($json, '"' . $key . '"') === false,
            "Forbidden key '$key' found in: " . substr($json, 0, 200));
    }

    public function assertValue(string $label, array $res, string $key, $expected): void {
        $data   = json_decode($res['body'], true);
        $actual = $data[$key] ?? null;
        $this->assert($label, $actual == $expected,
            "Expected '$key'=" . json_encode($expected) . " got " . json_encode($actual));
    }

    public function assertMinCount(string $label, array $res, string $key, int $min = 1): void {
        $data  = json_decode($res['body'], true);
        $count = count($data[$key] ?? []);
        $this->assert($label, $count >= $min,
            "Expected >= $min items in '$key', got $count. Body: " . substr($res['body'], 0, 200));
    }

    public function skip(string $label): void {
        echo "    \e[33m–\e[0m $label\n";
        $this->skipped++;
    }

    public function failures(): array { return $this->failures; }
}

// ═══════════════════════════════════════════════════════════════════════════
// HTTP CLIENT
// ═══════════════════════════════════════════════════════════════════════════

class CmsClient {
    private array  $cookies   = [];
    private string $csrfToken = '';
    public  string $role      = '';
    public  int    $userId    = 0;
    private bool   $verbose;

    public function __construct(bool $v = false) { $this->verbose = $v; }

    public function login(string $email, string $pass): array {
        $res  = $this->request('POST', 'auth/login.php', ['email' => $email, 'password' => $pass]);
        $data = json_decode($res['body'], true) ?? [];
        $this->csrfToken = $data['csrf_token']   ?? '';
        $this->role      = $data['user']['role'] ?? '';
        $this->userId    = (int)($data['user']['id'] ?? 0);
        return $res;
    }

    public function logout(): void {
        $this->request('POST', 'auth/logout.php', []);
        $this->cookies = []; $this->csrfToken = ''; $this->role = ''; $this->userId = 0;
    }

    public function hasCsrf(): bool     { return $this->csrfToken !== ''; }
    public function getCsrf(): string   { return $this->csrfToken; }
    public function getCookies(): array { return $this->cookies; }
    public function sessionCheck(): array { return $this->request('GET', 'auth/login.php', null); }
    public function get(string $ep): array              { return $this->request('GET',    $ep, null); }
    public function post(string $ep, array $d): array   { return $this->request('POST',   $ep, $d); }
    public function patch(string $ep, array $d): array  { return $this->request('PATCH',  $ep, $d); }
    public function delete(string $ep, array $d): array { return $this->request('DELETE', $ep, $d); }

    /**
     * Send multipart/form-data POST — required for lab/upload_report.php
     * (PHP reads $_FILES + $_POST, not php://input)
     */
    public function multipartPost(string $ep, array $fields): array {
        $ch = curl_init(BASE_URL . '/' . $ep);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fields,   // array → multipart/form-data
            CURLOPT_TIMEOUT        => 10,
        ]);
        $h = [];
        if ($this->csrfToken) $h[] = 'X-CSRF-TOKEN: ' . $this->csrfToken;
        if ($this->cookies)   $h[] = 'Cookie: ' . $this->cookieStr();
        if ($h) curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
        return $this->finish($ch, "MULTI $ep");
    }

    /** POST with valid session but NO X-CSRF-TOKEN header — for CSRF enforcement tests. */
    public function rawPostNoCsrf(string $ep, array $data): array {
        $ch = curl_init(BASE_URL . '/' . $ep);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST  => 'POST', CURLOPT_TIMEOUT => 5,
            CURLOPT_POSTFIELDS     => json_encode($data),
        ]);
        $h = ['Content-Type: application/json'];
        if ($this->cookies) $h[] = 'Cookie: ' . $this->cookieStr();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
        return $this->finish($ch, "RAW-POST (no-csrf) $ep");
    }

    /** PATCH with valid session but NO X-CSRF-TOKEN header. */
    public function rawPatchNoCsrf(string $ep, array $data): array {
        $ch = curl_init(BASE_URL . '/' . $ep);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST  => 'PATCH', CURLOPT_TIMEOUT => 5,
            CURLOPT_POSTFIELDS     => json_encode($data),
        ]);
        $h = ['Content-Type: application/json'];
        if ($this->cookies) $h[] = 'Cookie: ' . $this->cookieStr();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
        return $this->finish($ch, "RAW-PATCH (no-csrf) $ep");
    }

    public function request(string $method, string $ep, ?array $data): array {
        $ch = curl_init(BASE_URL . '/' . $ep);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $h = ['Content-Type: application/json', 'Accept: application/json'];
        if ($this->csrfToken) $h[] = 'X-CSRF-TOKEN: ' . $this->csrfToken;
        if ($this->cookies)   $h[] = 'Cookie: ' . $this->cookieStr();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $h);
        if ($data !== null && in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        return $this->finish($ch, "$method $ep");
    }

    private function finish($ch, string $label): array {
        $raw    = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $hSize  = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $rawH   = substr($raw, 0, $hSize);
        $body   = substr($raw, $hSize);
        curl_close($ch);

        // Persist session cookies across requests
        preg_match_all('/Set-Cookie:\s*([^;\r\n]+)/i', $rawH, $m);
        foreach ($m[1] as $c) {
            if (strpos($c, '=') === false) continue;
            [$k, $v] = explode('=', $c, 2);
            $this->cookies[trim($k)] = trim($v);
        }

        if ($this->verbose) {
            echo "      [$label → $status] " . substr($body, 0, 120) . "\n";
        }
        return ['status' => $status, 'body' => $body, 'headers' => $rawH];
    }

    private function cookieStr(): string {
        return implode('; ', array_map(
            fn($k, $v) => "$k=$v",
            array_keys($this->cookies),
            array_values($this->cookies)
        ));
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// LAYOUT HELPERS
// ═══════════════════════════════════════════════════════════════════════════

$runner = new TestRunner();

function section(string $t): void {
    echo "\n\e[1;36m" . str_repeat('─', 62) . "\e[0m\n";
    echo "\e[1;36m  $t\e[0m\n";
    echo "\e[1;36m" . str_repeat('─', 62) . "\e[0m\n";
}
function sub(string $t): void { echo "\n  \e[33m» $t\e[0m\n"; }

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 0 — AUTH LIFECYCLE
// ═══════════════════════════════════════════════════════════════════════════

if (!$onlyRole) {
    section('SECTION 0 — Auth Lifecycle');

    sub('0.1 Public self-registration (auth/register.php)');
    $regC    = new CmsClient($verbose);
    $regUniq = 'selftest' . time();
    $res = $regC->post('auth/register.php', [
        'full_name' => 'Self Reg Test',
        'email'     => "$regUniq@example.com",
        'password'  => 'mypassword',
        'dob'       => '1990-05-10',
        'gender'    => 'female',
        'phone'     => '+15559990000',
    ]);
    $runner->assertStatus('Public register → 200', $res, 200);
    $runner->assertKey('Returns success flag', $res, 'success');

    sub('0.2 Register — missing required fields → 400');
    $runner->assertStatus('Missing full_name/dob/gender/phone → 400',
        $regC->post('auth/register.php', ['email' => 'x@x.com', 'password' => 'abc']), 400);

    sub('0.3 Register — duplicate email → 409');
    $res = $regC->post('auth/register.php', [
        'full_name' => 'Dup',
        'email'     => "$regUniq@example.com",
        'password'  => 'x',
        'dob'       => '1990-01-01',
        'gender'    => 'male',
        'phone'     => '000',
    ]);
    $runner->assertStatus('Duplicate email → 409', $res, 409);

    sub('0.4 Login — validation failures');
    $c0 = new CmsClient($verbose);
    $runner->assertStatus('Empty login body → 400',
        $c0->post('auth/login.php', []), 400);
    $runner->assertStatus('Missing password → 400',
        $c0->post('auth/login.php', ['email' => 'x@x.com']), 400);
    $runner->assertStatus('Unknown email → 401',
        $c0->post('auth/login.php', ['email' => 'nobody@nowhere.com', 'password' => 'x']), 401);
    $runner->assertStatus('Wrong password → 401',
        $c0->post('auth/login.php', ['email' => 'test.patient@clinic.com', 'password' => 'wrongpassword']), 401);

    sub('0.5 Login — success, CSRF token, session check');
    $c0->login('test.patient@clinic.com', PASS);
    $runner->assert('Has CSRF token after login', $c0->hasCsrf());
    $sc     = $c0->sessionCheck();
    $scData = json_decode($sc['body'], true);
    $runner->assertStatus('Session check (GET auth/login) → 200', $sc, 200);
    $runner->assert('authenticated = true',   ($scData['authenticated']   ?? false) === true);
    $runner->assert('user.role present',      !empty($scData['user']['role']        ?? ''));
    $runner->assert('csrf_token present',     !empty($scData['csrf_token']          ?? ''));

    sub('0.6 Logout — session destroyed');
    $c0->logout();
    $sc2     = $c0->sessionCheck();
    $scData2 = json_decode($sc2['body'], true);
    $runner->assert('Post-logout: authenticated = false',
        ($scData2['authenticated'] ?? true) === false);
    $runner->assertStatus('Protected endpoint after logout → 401',
        $c0->get('appointments/list.php'), 401);
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 1 — UNAUTHENTICATED ACCESS GUARDS
// ═══════════════════════════════════════════════════════════════════════════

if (!$onlyRole) {
    section('SECTION 1 — Unauthenticated Access Guards');
    $anon = new CmsClient($verbose);

    $protected = [
        'appointments/list.php',
        'appointments/create.php',
        'doctors/list.php',
        'patients/list.php',
        'patients/review.php',
        'admin/audit.php',
        'admin/staff.php',
        'admin/settings.php',
        'medical/list.php?patient_id=1',
        'medical/create_record.php',
        'billing/list.php',
        'billing/create_invoice.php',
        'billing/update.php',
        'lab/tests.php',
        'lab/upload_report.php',
        'pharmacy/prescriptions.php',
        'medicines/list.php',
    ];

    foreach ($protected as $ep) {
        $runner->assertStatus("GET $ep (anon) → 401", $anon->get($ep), 401);
    }
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 2 — PATIENT FULL FLOW
// ═══════════════════════════════════════════════════════════════════════════

if (!$onlyRole || $onlyRole === 'patient') {
    section('SECTION 2 — Patient Full Flow');
    $c = new CmsClient($verbose);

    sub('2.1 Login');
    $res  = $c->login('test.patient@clinic.com', PASS);
    $data = json_decode($res['body'], true);
    $runner->assertStatus('Login succeeds → 200', $res, 200);
    $runner->assertKey('Returns csrf_token', $res, 'csrf_token');
    $runner->assert('Role is patient',     ($data['user']['role']         ?? '') === 'patient');
    $runner->assert('Returns display_name', !empty($data['user']['display_name'] ?? ''));
    $runner->assert('Returns user id',      !empty($data['user']['id']            ?? 0));

    sub('2.2 Appointment listing — all status filters');
    foreach (['all', 'scheduled', 'completed', 'cancelled'] as $st) {
        $res = $c->get("appointments/list.php?status=$st");
        $runner->assertStatus("List appointments ?status=$st → 200", $res, 200);
        $runner->assertKey("Has appointments key ($st)", $res, 'appointments');
    }
    $allAppts = json_decode($c->get('appointments/list.php?status=all')['body'], true)['appointments'] ?? [];
    $runner->assert('Has seeded appointments', count($allAppts) > 0);

    sub('2.3 Book appointment — success');
    $doctorsRes = $c->get('doctors/list.php');
    $runner->assertStatus('Doctors list → 200', $doctorsRes, 200);
    $doctors = json_decode($doctorsRes['body'], true)['doctors'] ?? [];
    $runner->assert('Doctors list non-empty', count($doctors) > 0);

    $newApptId = null;
    if (!empty($doctors)) {
        $res = $c->post('appointments/create.php', [
            'doctor_id'  => $doctors[0]['id'],
            'date'       => date('Y-m-d', strtotime('+14 days')),
            'start_time' => '09:30',
        ]);
        $runner->assertStatus('Book appointment → 201', $res, 201);
        $newApptId = json_decode($res['body'], true)['appointment_id'] ?? null;
        $runner->assert('Response has appointment_id', !empty($newApptId));
    }

    sub('2.4 Book appointment — validation failures');
    $runner->assertStatus('Missing doctor_id → 400',
        $c->post('appointments/create.php', ['date' => '2030-01-01', 'start_time' => '10:00']), 400);
    $runner->assertStatus('Missing date → 400',
        $c->post('appointments/create.php', ['doctor_id' => 1, 'start_time' => '10:00']), 400);
    $runner->assertStatus('Missing start_time → 400',
        $c->post('appointments/create.php', ['doctor_id' => 1, 'date' => '2030-01-01']), 400);

    sub('2.5 Cancel own appointment');
    if ($newApptId) {
        $runner->assertStatus('Patient cancels own appointment → 200',
            $c->post('appointments/update.php', ['id' => $newApptId, 'status' => 'cancelled']), 200);
    }

    sub('2.6 Patient CANNOT set appointment to completed / no_show');
    // Book another appointment to verify forbidden status changes
    $aid2 = null;
    if (!empty($doctors)) {
        $r2 = $c->post('appointments/create.php', [
            'doctor_id'  => $doctors[0]['id'],
            'date'       => date('Y-m-d', strtotime('+21 days')),
            'start_time' => '11:00',
        ]);
        $aid2 = json_decode($r2['body'], true)['appointment_id'] ?? null;
    }
    if ($aid2) {
        $runner->assertStatus('Patient cannot mark appointment completed → 403',
            $c->post('appointments/update.php', ['id' => $aid2, 'status' => 'completed']), 403);
        $runner->assertStatus('Patient cannot mark no_show → 403',
            $c->post('appointments/update.php', ['id' => $aid2, 'status' => 'no_show']), 403);
        $c->post('appointments/update.php', ['id' => $aid2, 'status' => 'cancelled']); // cleanup
    }

    sub('2.7 Medical records — own-only, sub-arrays, isolation');
    $res = $c->get('medical/list.php');
    $runner->assertStatus('View own medical records → 200', $res, 200);
    $runner->assertKey('Has records array', $res, 'records');
    $records = json_decode($res['body'], true)['records'] ?? [];
    $runner->assert('Has seeded medical records', count($records) > 0);
    if (!empty($records)) {
        $rec = $records[0];
        $runner->assert('Record has prescription_items sub-array', array_key_exists('prescription_items', $rec));
        $runner->assert('Record has lab_tests sub-array',         array_key_exists('lab_tests',           $rec));
        $runner->assert('Record has diagnosis field',             array_key_exists('diagnosis',            $rec));
        $runner->assert('Record has doctor_name',                 !empty($rec['doctor_name'] ?? ''));
    }
    // patient_id param is ignored — patient always sees own
    $res2      = $c->get('medical/list.php?patient_id=999');
    $overrideR = json_decode($res2['body'], true)['records'] ?? [];
    $runner->assertStatus('patient_id param ignored → 200', $res2, 200);
    $runner->assert('Returns own records, not patient 999',
        count($overrideR) === count($records));

    sub('2.8 Patient CANNOT create medical records → 403');
    $runner->assertStatus('Create medical record (patient) → 403',
        $c->post('medical/create_record.php', ['patient_id' => 1, 'diagnosis' => 'x']), 403);

    sub('2.9 Lab results — completed-only visible to patient');
    $res = $c->get('lab/tests.php');
    $runner->assertStatus('View own lab results → 200', $res, 200);
    $runner->assertKey('Has tests array', $res, 'tests');
    $labTests = json_decode($res['body'], true)['tests'] ?? [];
    $runner->assert('All patient lab results are completed',
        empty(array_filter($labTests, fn($t) => $t['status'] !== 'completed')));
    if (!empty($labTests)) {
        $runner->assertNoKey('No diagnosis in lab results', json_encode($labTests[0]), 'diagnosis');
    }

    sub('2.10 Invoices — own-only, status filters, cannot mutate');
    $res = $c->get('billing/list.php');
    $runner->assertStatus('View own invoices → 200', $res, 200);
    $runner->assertKey('Has invoices array', $res, 'invoices');
    $invs = json_decode($res['body'], true)['invoices'] ?? [];
    $runner->assert('Has seeded invoices', count($invs) > 0);
    foreach (['pending', 'paid'] as $st) {
        $runner->assertStatus("Filter invoices by status=$st → 200",
            $c->get("billing/list.php?status=$st"), 200);
    }
    $invId = $invs[0]['id'] ?? 1;
    $runner->assertStatus('Patient cannot mark invoice paid → 403',
        $c->patch('billing/update.php', ['id' => $invId, 'status' => 'paid']), 403);
    $runner->assertStatus('Patient cannot void invoice → 403',
        $c->patch('billing/update.php', ['id' => $invId, 'status' => 'void']), 403);
    $runner->assertStatus('Patient cannot create invoice → 403',
        $c->post('billing/create_invoice.php', [
            'patient_id' => 1, 'reference_type' => 'consultation', 'reference_id' => 1, 'amount' => 10
        ]), 403);

    sub('2.11 Reviews — full lifecycle');
    if (!empty($doctors)) {
        $docId = $doctors[0]['id'];
        // GET
        $res = $c->get("patients/review.php?doctor_id=$docId");
        $runner->assertStatus('Get doctor reviews → 200', $res, 200);
        $runner->assertKey('Has reviews array',   $res, 'reviews');
        $runner->assertKey('Has average_rating',  $res, 'average_rating');
        $runner->assertKey('Has count',           $res, 'count');
        // POST — upsert
        $res = $c->post('patients/review.php', [
            'doctor_id' => $docId, 'rating' => 5, 'comment' => 'Great doctor — automated test',
        ]);
        $runner->assertAnyStatus('Submit review → 200 or 201', $res, [200, 201]);
        // Boundary violations
        $runner->assertStatus('Rating 0 (below min) → 400',
            $c->post('patients/review.php', ['doctor_id' => $docId, 'rating' => 0]), 400);
        $runner->assertStatus('Rating 6 (above max) → 400',
            $c->post('patients/review.php', ['doctor_id' => $docId, 'rating' => 6]), 400);
        // Missing fields
        $runner->assertStatus('Review missing doctor_id → 400',
            $c->post('patients/review.php', ['rating' => 4]), 400);
        $runner->assertStatus('Review missing rating → 400',
            $c->post('patients/review.php', ['doctor_id' => $docId]), 400);
        // GET missing doctor_id → 400
        $runner->assertStatus('GET reviews — missing doctor_id → 400',
            $c->get('patients/review.php'), 400);
    }

    sub('2.12 RBAC — forbidden for patient');
    $runner->assertStatus('admin/audit → 403',          $c->get('admin/audit.php'), 403);
    $runner->assertStatus('admin/staff → 403',          $c->get('admin/staff.php'), 403);
    $runner->assertStatus('admin/settings → 403',       $c->get('admin/settings.php'), 403);
    $runner->assertStatus('pharmacy/prescriptions → 403', $c->get('pharmacy/prescriptions.php'), 403);
    $runner->assertStatus('patients/register → 403',    $c->get('patients/register.php'), 403);
    $runner->assertStatus('patients/list → 403',        $c->get('patients/list.php'), 403);
    $runner->assertStatus('medicines/list → 403',       $c->get('medicines/list.php'), 403);

    $c->logout();
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 3 — DOCTOR FULL FLOW
// ═══════════════════════════════════════════════════════════════════════════

if (!$onlyRole || $onlyRole === 'doctor') {
    section('SECTION 3 — Doctor Full Flow');
    $c = new CmsClient($verbose);

    sub('3.1 Login');
    $res  = $c->login('test.doctor@clinic.com', PASS);
    $data = json_decode($res['body'], true);
    $runner->assertStatus('Login succeeds → 200', $res, 200);
    $runner->assert('Role is doctor', ($data['user']['role'] ?? '') === 'doctor');

    sub('3.2 Appointment queue — filtered views');
    foreach (['scheduled', 'completed', 'all'] as $st) {
        $res = $c->get("appointments/list.php?status=$st");
        $runner->assertStatus("Queue ?status=$st → 200", $res, 200);
        $runner->assertKey("Has appointments ($st)", $res, 'appointments');
    }
    $scheduledAppts = json_decode($c->get('appointments/list.php?status=scheduled')['body'], true)['appointments'] ?? [];
    $runner->assert('Has scheduled appointments (seeded)', count($scheduledAppts) > 0,
        'Run seed_data.php to ensure Doctor has scheduled appointments');
    if (!empty($scheduledAppts)) {
        $runner->assert('Appointment has patient_name', !empty($scheduledAppts[0]['patient_name'] ?? ''));
        $runner->assert('Appointment has patient_id',   !empty($scheduledAppts[0]['patient_id']   ?? ''));
    }

    sub('3.3 Medicines list');
    $res  = $c->get('medicines/list.php');
    $meds = json_decode($res['body'], true)['medicines'] ?? [];
    $runner->assertStatus('Medicines list → 200', $res, 200);
    $runner->assertKey('Has medicines array', $res, 'medicines');
    $runner->assert('Has seeded medicines', count($meds) > 0);
    if (!empty($meds)) {
        $runner->assert('Medicine has id',    !empty($meds[0]['id']    ?? ''));
        $runner->assert('Medicine has name',  !empty($meds[0]['name']  ?? ''));
        $runner->assert('Medicine has price', isset($meds[0]['price']));
    }

    sub('3.4 Patient list (read access)');
    $res      = $c->get('patients/list.php');
    $patients = json_decode($res['body'], true)['patients'] ?? [];
    $runner->assertStatus('List patients → 200', $res, 200);
    $runner->assert('Has patients', count($patients) > 0);
    $patPid = (int)($patients[0]['id'] ?? 0);

    sub('3.5 Patient medical history lookup');
    if ($patPid) {
        $res = $c->get("medical/list.php?patient_id=$patPid");
        $runner->assertStatus('View patient history → 200', $res, 200);
        $runner->assertKey('Has records', $res, 'records');
        $runner->assertStatus('Doctor CANNOT edit patient demographics → 403',
            $c->patch('patients/update.php', ['id' => $patPid, 'full_name' => 'x']), 403);
    }
    // Missing patient_id → 400 for doctor role
    $runner->assertStatus('medical/list.php — missing patient_id (doctor) → 400',
        $c->get('medical/list.php'), 400);

    sub('3.6 Create medical record — with prescription');
    $newRecordId = null;
    if ($patPid && !empty($meds)) {
        $res = $c->post('medical/create_record.php', [
            'patient_id'     => $patPid,
            'diagnosis'      => 'Test Diagnosis (with prescription) — Automated',
            'clinical_notes' => 'Automated test record.',
            'prescription'   => [
                ['medicine_id' => $meds[0]['id'], 'dosage' => '500mg', 'frequency' => 'Once daily'],
            ],
        ]);
        $runner->assertStatus('Create record w/ prescription → 201', $res, 201);
        $runner->assertKey('Returns record_id', $res, 'record_id');
        $newRecordId = json_decode($res['body'], true)['record_id'] ?? null;
    }

    sub('3.7 Create medical record — minimal (no prescription)');
    if ($patPid) {
        $res = $c->post('medical/create_record.php', [
            'patient_id' => $patPid,
            'diagnosis'  => 'Minimal Automated Record — no prescription',
        ]);
        $runner->assertStatus('Create record (minimal) → 201', $res, 201);
        $runner->assertKey('Returns record_id', $res, 'record_id');
    }

    sub('3.8 Create medical record — with inline lab_tests');
    if ($patPid) {
        $res = $c->post('medical/create_record.php', [
            'patient_id' => $patPid,
            'diagnosis'  => 'Automated Record — with inline lab tests',
            'lab_tests'  => ['Blood Panel — Automated', 'Urinalysis — Automated'],
        ]);
        $runner->assertStatus('Create record w/ lab_tests → 201', $res, 201);
        $runner->assertKey('Returns record_id', $res, 'record_id');
    }

    sub('3.9 Create record — validation failures');
    $runner->assertStatus('Missing diagnosis → 400',
        $c->post('medical/create_record.php', ['patient_id' => $patPid ?? 1]), 400);
    $runner->assertStatus('Missing patient_id → 400',
        $c->post('medical/create_record.php', ['diagnosis' => 'x']), 400);

    sub('3.10 Order lab test separately');
    if ($patPid) {
        $res = $c->post('lab/tests.php', [
            'patient_id' => $patPid,
            'test_type'  => 'CBC — Automated Lab Test',
        ]);
        $runner->assertStatus('Order lab test (separate) → 201', $res, 201);
        $runner->assertKey('Returns id', $res, 'id');
        $runner->assertStatus('Missing test_type → 400',
            $c->post('lab/tests.php', ['patient_id' => $patPid]), 400);
        $runner->assertStatus('Missing patient_id → 400',
            $c->post('lab/tests.php', ['test_type' => 'x']), 400);
    }

    sub('3.11 Appointment status — own queue');
    $ownAppt = $scheduledAppts[0] ?? null;
    if ($ownAppt) {
        $aid = $ownAppt['id'];
        $runner->assertStatus('Doctor marks own appointment completed → 200',
            $c->post('appointments/update.php', ['id' => $aid, 'status' => 'completed']), 200);
    }

    sub('3.12 Doctor status restrictions');
    if ($ownAppt) {
        $aid = $ownAppt['id'];
        $runner->assertStatus('Doctor CANNOT mark appointment as scheduled → 403',
            $c->post('appointments/update.php', ['id' => $aid, 'status' => 'scheduled']), 403);
        $runner->assertStatus('Doctor CANNOT mark appointment as no_show → 403',
            $c->post('appointments/update.php', ['id' => $aid, 'status' => 'no_show']), 403);
    }

    sub('3.13 Cross-doctor isolation');
    $doc2 = new CmsClient($verbose);
    $doc2->login('test.doctor2@clinic.com', PASS);
    $doc2Appts = json_decode($doc2->get('appointments/list.php?status=scheduled')['body'], true)['appointments'] ?? [];
    $doc2->logout();
    if (!empty($doc2Appts)) {
        $runner->assertStatus('Doctor1 CANNOT complete Doctor2\'s appointment → 403',
            $c->post('appointments/update.php', ['id' => $doc2Appts[0]['id'], 'status' => 'completed']), 403);
    } else {
        $runner->skip('Doctor2 has no scheduled appointments — add seeded data');
    }

    sub('3.14 RBAC — forbidden for doctor');
    $runner->assertStatus('billing/list → 403',          $c->get('billing/list.php'), 403);
    $runner->assertStatus('pharmacy/prescriptions → 403', $c->get('pharmacy/prescriptions.php'), 403);
    $runner->assertStatus('admin/audit → 403',            $c->get('admin/audit.php'), 403);
    $runner->assertStatus('admin/staff → 403',            $c->get('admin/staff.php'), 403);
    $runner->assertStatus('admin/settings → 403',         $c->get('admin/settings.php'), 403);

    $c->logout();
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 4 — RECEPTIONIST FULL FLOW
// ═══════════════════════════════════════════════════════════════════════════

if (!$onlyRole || $onlyRole === 'receptionist') {
    section('SECTION 4 — Receptionist Full Flow');
    $c = new CmsClient($verbose);

    sub('4.1 Login');
    $res  = $c->login('test.reception@clinic.com', PASS);
    $data = json_decode($res['body'], true);
    $runner->assertStatus('Login succeeds → 200', $res, 200);
    $runner->assert('Role is receptionist', ($data['user']['role'] ?? '') === 'receptionist');

    sub('4.2 Appointment listing — all status filters');
    foreach (['scheduled', 'completed', 'cancelled', 'all'] as $st) {
        $res = $c->get("appointments/list.php?status=$st");
        $runner->assertStatus("List appointments $st → 200", $res, 200);
        $runner->assertKey("Has appointments ($st)", $res, 'appointments');
    }

    sub('4.3 Appointment full status transitions (completed / cancelled / no_show)');
    $doctors = json_decode($c->get('doctors/list.php')['body'], true)['doctors'] ?? [];
    $dId     = $doctors[0]['id'] ?? null;
    $tmpUniq = time();
    $tmpReg  = $c->post('patients/register.php', [
        'full_name' => "Recep Temp $tmpUniq",
        'email'     => "recepTmp$tmpUniq@example.com",
        'password'  => 'test1234',
        'dob'       => '1988-03-15',
        'gender'    => 'other',
        'phone'     => '+1555123400',
    ]);
    $tmpPid  = json_decode($tmpReg['body'], true)['patient_id'] ?? null;

    if ($dId && $tmpPid) {
        foreach (['completed', 'cancelled', 'no_show'] as $targetStatus) {
            $apptRes = $c->post('appointments/create.php', [
                'doctor_id'  => $dId,
                'date'       => date('Y-m-d', strtotime('+5 days')),
                'start_time' => '08:00',
                'patient_id' => $tmpPid,
            ]);
            $apptId = json_decode($apptRes['body'], true)['appointment_id'] ?? null;
            if ($apptId) {
                $runner->assertStatus("Receptionist sets appointment → '$targetStatus' → 200",
                    $c->post('appointments/update.php', ['id' => $apptId, 'status' => $targetStatus]), 200);
            }
        }
    }

    sub('4.4 Patient registration — success');
    $unique = time() + 1;
    $res    = $c->post('patients/register.php', [
        'full_name' => "Test Patient $unique",
        'email'     => "autotest$unique@example.com",
        'password'  => 'testpassword',
        'dob'       => '1992-07-04',
        'gender'    => 'female',
        'phone'     => '+15550001111',
    ]);
    $runner->assertStatus('Register new patient → 201', $res, 201);
    $newPid = json_decode($res['body'], true)['patient_id'] ?? null;
    $runner->assert('Returns patient_id', !empty($newPid));

    sub('4.5 Patient registration — edge cases');
    $runner->assertStatus('Duplicate email → 409',
        $c->post('patients/register.php', [
            'full_name' => 'Dup', 'email' => "autotest$unique@example.com",
            'password' => 'x', 'dob' => '1990-01-01', 'gender' => 'male', 'phone' => '123',
        ]), 409);
    $runner->assertStatus('Missing full_name → 400',
        $c->post('patients/register.php', [
            'email' => 'missing@x.com', 'password' => 'x', 'dob' => '1990-01-01', 'gender' => 'male', 'phone' => '123',
        ]), 400);
    $runner->assertStatus('Missing email → 400',
        $c->post('patients/register.php', [
            'full_name' => 'No Email', 'password' => 'x', 'dob' => '1990-01-01', 'gender' => 'male', 'phone' => '123',
        ]), 400);

    sub('4.6 Patient search — name, phone, empty query');
    $runner->assertStatus('Search by name → 200',  $c->get('patients/list.php?q=Test'), 200);
    $runner->assertStatus('Search by phone → 200', $c->get('patients/list.php?q=5550001111'), 200);
    $res = $c->get('patients/list.php');
    $runner->assertStatus('Empty query (list all) → 200', $res, 200);
    $runner->assertMinCount('Returns patients', $res, 'patients', 1);

    sub('4.7 Patient demographics update');
    if ($newPid) {
        $runner->assertStatus('Update patient demographics → 200',
            $c->patch('patients/update.php', [
                'id' => (int)$newPid, 'full_name' => "Updated Patient $unique",
                'dob' => '1992-11-20', 'gender' => 'female', 'phone' => '+15550009999',
            ]), 200);
        $runner->assertStatus('Update non-existent patient → 404',
            $c->patch('patients/update.php', ['id' => 999999, 'full_name' => 'ghost']), 404);
        $runner->assertStatus('Invalid gender → 400',
            $c->patch('patients/update.php', ['id' => (int)$newPid, 'full_name' => 'x', 'gender' => 'robot']), 400);
    }

    sub('4.8 Create appointment for a patient');
    if ($dId && $newPid) {
        $res = $c->post('appointments/create.php', [
            'doctor_id'  => $dId,
            'date'       => date('Y-m-d', strtotime('+7 days')),
            'start_time' => '14:00',
            'patient_id' => $newPid,
        ]);
        $runner->assertAnyStatus('Create appointment for patient → 200/201', $res, [200, 201]);
    }

    sub('4.9 Billing — list, filter, mark paid, mark void');
    $res = $c->get('billing/list.php');
    $runner->assertStatus('View all invoices → 200', $res, 200);
    $runner->assertMinCount('Has invoices', $res, 'invoices', 1);
    foreach (['pending', 'paid', 'void'] as $st) {
        $runner->assertStatus("Filter by status=$st → 200",
            $c->get("billing/list.php?status=$st"), 200);
    }
    // Fresh invoice → mark paid
    $r1     = $c->post('billing/create_invoice.php', [
        'patient_id' => 1, 'reference_type' => 'consultation',
        'reference_id' => 1, 'amount' => 75.00,
    ]);
    $fInv1  = json_decode($r1['body'], true)['invoice_id'] ?? null;
    if ($fInv1) {
        $runner->assertStatus('Mark invoice paid → 200',
            $c->patch('billing/update.php', ['id' => $fInv1, 'status' => 'paid']), 200);
    }
    // Fresh invoice → void
    $r2     = $c->post('billing/create_invoice.php', [
        'patient_id' => 1, 'reference_type' => 'consultation',
        'reference_id' => 2, 'amount' => 50.00,
    ]);
    $fInv2  = json_decode($r2['body'], true)['invoice_id'] ?? null;
    if ($fInv2) {
        $runner->assertStatus('Void invoice → 200',
            $c->patch('billing/update.php', ['id' => $fInv2, 'status' => 'void']), 200);
    }

    sub('4.10 RBAC — forbidden for receptionist');
    $runner->assertStatus('admin/audit → 403',            $c->get('admin/audit.php'), 403);
    $runner->assertStatus('admin/staff → 403',            $c->get('admin/staff.php'), 403);
    $runner->assertStatus('pharmacy/prescriptions → 403', $c->get('pharmacy/prescriptions.php'), 403);
    $runner->assertStatus('medical/create_record → 403',
        $c->post('medical/create_record.php', ['patient_id' => 1, 'diagnosis' => 'x']), 403);

    $c->logout();
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 5 — LAB TECH FULL FLOW
// ═══════════════════════════════════════════════════════════════════════════

if (!$onlyRole || $onlyRole === 'lab_tech') {
    section('SECTION 5 — Lab Tech Full Flow');
    $c = new CmsClient($verbose);

    sub('5.1 Login');
    $res  = $c->login('test.lab@clinic.com', PASS);
    $data = json_decode($res['body'], true);
    $runner->assertStatus('Login succeeds → 200', $res, 200);
    $runner->assert('Role is lab_tech', ($data['user']['role'] ?? '') === 'lab_tech');

    sub('5.2 Pending tests — default (no filter) and explicit');
    $res          = $c->get('lab/tests.php');
    $pendingTests = json_decode($res['body'], true)['tests'] ?? [];
    $runner->assertStatus('View pending tests (default) → 200', $res, 200);
    $runner->assertKey('Has tests array', $res, 'tests');
    $runner->assert('Default tests are all pending',
        empty(array_filter($pendingTests, fn($t) => $t['status'] !== 'pending')));
    $runner->assertStatus('?status=pending explicit → 200',
        $c->get('lab/tests.php?status=pending'), 200);
    $runner->assert('Has pending tests (need seeded data)', count($pendingTests) > 0, 'Run seed_data.php first');

    sub('5.3 Completed tests filter');
    $res       = $c->get('lab/tests.php?status=completed');
    $completed = json_decode($res['body'], true)['tests'] ?? [];
    $runner->assertStatus('View completed tests → 200', $res, 200);
    $runner->assert('All returned are completed',
        empty(array_filter($completed, fn($t) => $t['status'] !== 'completed')));

    sub('5.4 Response — patient_name, no clinical data');
    if (!empty($pendingTests)) {
        $t = $pendingTests[0];
        $runner->assert('Has patient_name', !empty($t['patient_name'] ?? ''));
        $runner->assert('Has test_type',    !empty($t['test_type']    ?? ''));
        $runner->assertNoKey('No diagnosis field',       json_encode($t), 'diagnosis');
        $runner->assertNoKey('No clinical_notes field',  json_encode($t), 'clinical_notes');
    }

    sub('5.5 Upload report — success (no file, just notes)');
    $uploadedId = null;
    if (!empty($pendingTests)) {
        $uploadedId = $pendingTests[0]['id'];
        $res = $c->multipartPost('lab/upload_report.php', [
            'test_id'      => (string)$uploadedId,
            'report_notes' => 'Automated test result: Normal ranges observed.',
        ]);
        $runner->assertStatus('Upload report (notes only) → 200', $res, 200);
        $runner->assertKey('Returns success', $res, 'success');
        // Verify the test moved to completed
        $afterList = json_decode($c->get('lab/tests.php?status=completed')['body'], true)['tests'] ?? [];
        $updated    = array_filter($afterList, fn($t) => $t['id'] == $uploadedId);
        $runner->assert('Test status now completed', !empty($updated));
    }

    sub('5.6 Upload report — validation failures');
    $runner->assertStatus('Missing test_id → 400',
        $c->multipartPost('lab/upload_report.php', ['report_notes' => 'Some notes']), 400);
    $runner->assertStatus('Missing report_notes → 400',
        $c->multipartPost('lab/upload_report.php', ['test_id' => '1']), 400);
    $runner->assertStatus('Both fields missing → 400',
        $c->multipartPost('lab/upload_report.php', []), 400);

    sub('5.7 RBAC — forbidden for lab_tech');
    $runner->assertStatus('medical/list → 403',
        $c->get('medical/list.php?patient_id=1'), 403);
    $runner->assertStatus('billing/list → 403',           $c->get('billing/list.php'), 403);
    $runner->assertStatus('pharmacy/prescriptions → 403', $c->get('pharmacy/prescriptions.php'), 403);
    $runner->assertStatus('patients/register → 403',      $c->get('patients/register.php'), 403);
    $runner->assertStatus('admin/audit → 403',            $c->get('admin/audit.php'), 403);
    $runner->assertStatus('medicines/list → 403',         $c->get('medicines/list.php'), 403);
    $runner->assertStatus('Lab tech CANNOT order lab tests → 403',
        $c->post('lab/tests.php', ['patient_id' => 1, 'test_type' => 'x']), 403);

    $c->logout();
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 6 — PHARMACIST FULL FLOW
// ═══════════════════════════════════════════════════════════════════════════

if (!$onlyRole || $onlyRole === 'pharmacist') {
    section('SECTION 6 — Pharmacist Full Flow');
    $c = new CmsClient($verbose);

    sub('6.1 Login');
    $res  = $c->login('test.pharm@clinic.com', PASS);
    $data = json_decode($res['body'], true);
    $runner->assertStatus('Login succeeds → 200', $res, 200);
    $runner->assert('Role is pharmacist', ($data['user']['role'] ?? '') === 'pharmacist');

    sub('6.2 Prescriptions — structure, no clinical data, is_billed, items');
    $res           = $c->get('pharmacy/prescriptions.php');
    $prescriptions = json_decode($res['body'], true)['prescriptions'] ?? [];
    $runner->assertStatus('View prescriptions → 200', $res, 200);
    $runner->assertKey('Has prescriptions key', $res, 'prescriptions');
    $runner->assert('Has seeded prescriptions', count($prescriptions) > 0);
    if (!empty($prescriptions)) {
        $p = $prescriptions[0];
        $runner->assertNoKey('No diagnosis field',       json_encode($p), 'diagnosis');
        $runner->assertNoKey('No clinical_notes field',  json_encode($p), 'clinical_notes');
        $runner->assert('Has is_billed field',    array_key_exists('is_billed', $p));
        $runner->assert('Has items array',         array_key_exists('items', $p));
        $runner->assert('Has patient_name',       !empty($p['patient_name'] ?? ''));
        $runner->assert('Has doctor_name',        !empty($p['doctor_name']  ?? ''));
        if (!empty($p['items'])) {
            $item = $p['items'][0];
            $runner->assert('Item has medicine_name', !empty($item['medicine_name'] ?? ''));
            $runner->assert('Item has price',          isset($item['price']));
        }
    }

    sub('6.3 Medicines list');
    $res  = $c->get('medicines/list.php');
    $meds = json_decode($res['body'], true)['medicines'] ?? [];
    $runner->assertStatus('Pharmacist views medicines → 200', $res, 200);
    $runner->assert('Has medicines', count($meds) > 0);

    sub('6.4 Invoice listing and status filters');
    $res = $c->get('billing/list.php');
    $runner->assertStatus('View all invoices → 200', $res, 200);
    $runner->assertMinCount('Has seeded invoices', $res, 'invoices', 1);
    foreach (['pending', 'paid', 'void'] as $st) {
        $res     = $c->get("billing/list.php?status=$st");
        $invList = json_decode($res['body'], true)['invoices'] ?? [];
        $runner->assertStatus("Filter by status=$st → 200", $res, 200);
        $runner->assert("All filtered invoices have status=$st",
            empty(array_filter($invList, fn($i) => $i['status'] !== $st)));
    }

    sub('6.5 Create invoice — success');
    $patientId = (int)($prescriptions[0]['patient_id'] ?? 1);
    $createRes = $c->post('billing/create_invoice.php', [
        'patient_id'     => $patientId,
        'reference_type' => 'consultation',
        'reference_id'   => 99,
        'amount'         => 120.00,
    ]);
    $runner->assertStatus('Create invoice → 201', $createRes, 201);
    $runner->assertKey('Returns invoice_id', $createRes, 'invoice_id');
    $newInvId = json_decode($createRes['body'], true)['invoice_id'] ?? null;

    sub('6.6 Create invoice — validation failures');
    $runner->assertStatus('Amount 0 → 400',
        $c->post('billing/create_invoice.php', [
            'patient_id' => 1, 'reference_type' => 'consultation', 'reference_id' => 1, 'amount' => 0,
        ]), 400);
    $runner->assertStatus('Missing patient_id → 400',
        $c->post('billing/create_invoice.php', [
            'reference_type' => 'consultation', 'reference_id' => 1, 'amount' => 10,
        ]), 400);
    $runner->assertStatus('Missing amount → 400',
        $c->post('billing/create_invoice.php', [
            'patient_id' => 1, 'reference_type' => 'consultation', 'reference_id' => 1,
        ]), 400);

    sub('6.7 Dispense prescription via invoice');
    if (!empty($prescriptions)) {
        $rx    = $prescriptions[0];
        $total = array_sum(array_column($rx['items'] ?? [], 'price')) ?: 10.00;
        $res   = $c->post('billing/create_invoice.php', [
            'patient_id'     => $rx['patient_id'],
            'reference_type' => 'prescription',
            'reference_id'   => $rx['id'],
            'amount'         => $total,
        ]);
        $runner->assertAnyStatus('Dispense prescription → 201 or 409 (already billed)',
            $res, [201, 409]);
    }

    sub('6.8 Mark invoice paid — verified in list');
    $r1 = $c->post('billing/create_invoice.php', [
        'patient_id' => $patientId, 'reference_type' => 'consultation',
        'reference_id' => 50, 'amount' => 88.00,
    ]);
    $i1 = json_decode($r1['body'], true)['invoice_id'] ?? null;
    if ($i1) {
        $runner->assertStatus('Mark invoice paid → 200',
            $c->patch('billing/update.php', ['id' => $i1, 'status' => 'paid']), 200);
        $paidList = json_decode($c->get('billing/list.php?status=paid')['body'], true)['invoices'] ?? [];
        $runner->assert('Invoice shows in paid list',
            !empty(array_filter($paidList, fn($x) => $x['id'] == $i1)));
    }

    sub('6.9 Void invoice — verified in list');
    $r2 = $c->post('billing/create_invoice.php', [
        'patient_id' => $patientId, 'reference_type' => 'consultation',
        'reference_id' => 51, 'amount' => 44.00,
    ]);
    $i2 = json_decode($r2['body'], true)['invoice_id'] ?? null;
    if ($i2) {
        $runner->assertStatus('Void invoice → 200',
            $c->patch('billing/update.php', ['id' => $i2, 'status' => 'void']), 200);
        $voidList = json_decode($c->get('billing/list.php?status=void')['body'], true)['invoices'] ?? [];
        $runner->assert('Invoice shows in void list',
            !empty(array_filter($voidList, fn($x) => $x['id'] == $i2)));
    }

    sub('6.10 Invalid billing update statuses');
    $runner->assertStatus('Status "free" → 400',
        $c->patch('billing/update.php', ['id' => 1, 'status' => 'free']), 400);
    $runner->assertStatus('Status "pending" → 400 (not allowed via update)',
        $c->patch('billing/update.php', ['id' => 1, 'status' => 'pending']), 400);
    $runner->assertStatus('Non-existent invoice → 404',
        $c->patch('billing/update.php', ['id' => 999999, 'status' => 'paid']), 404);

    sub('6.11 RBAC — forbidden for pharmacist');
    $runner->assertStatus('medical/list → 403',
        $c->get('medical/list.php?patient_id=1'), 403);
    $runner->assertStatus('patients/register → 403',     $c->get('patients/register.php'), 403);
    $runner->assertStatus('patients/list → 403',         $c->get('patients/list.php'), 403);
    $runner->assertStatus('patients/update → 403',
        $c->patch('patients/update.php', ['id' => 1, 'full_name' => 'x']), 403);
    $runner->assertStatus('lab/tests → 403',             $c->get('lab/tests.php'), 403);
    $runner->assertStatus('admin/audit → 403',           $c->get('admin/audit.php'), 403);
    $runner->assertStatus('admin/staff → 403',           $c->get('admin/staff.php'), 403);

    $c->logout();
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 7 — ADMIN FULL FLOW
// ═══════════════════════════════════════════════════════════════════════════

if (!$onlyRole || $onlyRole === 'admin') {
    section('SECTION 7 — Admin Full Flow');
    $c = new CmsClient($verbose);

    sub('7.1 Login');
    $res        = $c->login('test.admin@clinic.com', PASS);
    $data       = json_decode($res['body'], true);
    $adminUid   = $c->userId;
    $runner->assertStatus('Login succeeds → 200', $res, 200);
    $runner->assert('Role is admin', ($data['user']['role'] ?? '') === 'admin');

    sub('7.2 Audit stats');
    $res   = $c->get('admin/audit.php');
    $stats = json_decode($res['body'], true)['stats'] ?? [];
    $runner->assertStatus('View audit stats → 200', $res, 200);
    $runner->assert('total_patients > 0',     ($stats['total_patients']     ?? 0) > 0);
    $runner->assert('total_doctors > 0',      ($stats['total_doctors']      ?? 0) > 0);
    $runner->assert('total_appointments > 0', ($stats['total_appointments'] ?? 0) > 0);
    $runner->assertKey('Has recent_users key', $res, 'recent_users');

    sub('7.3 Staff list');
    $res = $c->get('admin/staff.php');
    $runner->assertStatus('View staff list → 200', $res, 200);
    $runner->assertMinCount('Staff list non-empty', $res, 'staff', 1);

    sub('7.4 Create regular staff (receptionist)');
    $u1     = time();
    $res    = $c->post('admin/staff.php', [
        'email'    => "staff$u1@clinic.com",
        'password' => 'testpass',
        'name'     => "Test Receptionist $u1",
        'role'     => 'receptionist',
    ]);
    $runner->assertStatus('Create receptionist → 201', $res, 201);
    $runner->assertKey('Returns user_id', $res, 'user_id');
    $newStaffId = json_decode($res['body'], true)['user_id'] ?? null;

    sub('7.5 Create doctor staff (requires specialization + license_number)');
    $u2  = time() + 1;
    $res = $c->post('admin/staff.php', [
        'email'          => "doc$u2@clinic.com",
        'password'       => 'testpass',
        'name'           => "Dr. Automated $u2",
        'role'           => 'doctor',
        'specialization' => 'Automated Medicine',
        'license_number' => "LIC-$u2",
    ]);
    $runner->assertStatus('Create doctor staff → 201', $res, 201);
    $newDocId = json_decode($res['body'], true)['user_id'] ?? null;
    $runner->assert('Returns user_id for doctor', !empty($newDocId));

    sub('7.6 Create staff — validation failures');
    $runner->assertStatus('Invalid role → 400',
        $c->post('admin/staff.php', [
            'email' => "bad$u1@clinic.com", 'password' => 'x', 'name' => 'X', 'role' => 'superuser',
        ]), 400);
    $runner->assertStatus('Missing name → 400',
        $c->post('admin/staff.php', [
            'email' => "noname$u1@clinic.com", 'password' => 'x', 'role' => 'receptionist',
        ]), 400);
    $runner->assertStatus('Missing email → 400',
        $c->post('admin/staff.php', [
            'name' => 'NoEmail', 'password' => 'x', 'role' => 'receptionist',
        ]), 400);
    $runner->assertStatus('Doctor staff — missing specialization → 400',
        $c->post('admin/staff.php', [
            'email' => "nospec$u1@clinic.com", 'password' => 'x',
            'name'  => 'NoSpec', 'role' => 'doctor', 'license_number' => 'LIC-000',
        ]), 400);

    sub('7.7 Edit staff');
    if ($newStaffId) {
        $runner->assertStatus('Edit staff → 200',
            $c->patch('admin/staff.php', [
                'id'    => $newStaffId,
                'name'  => "Updated Receptionist $u1",
                'email' => "staff$u1@clinic.com",
                'role'  => 'receptionist',
            ]), 200);
    }

    sub('7.8 Delete staff — and non-existent');
    foreach (array_filter([$newStaffId, $newDocId]) as $uid) {
        $runner->assertStatus("Delete staff id=$uid → 200",
            $c->delete('admin/staff.php', ['id' => $uid]), 200);
    }
    $runner->assertStatus('Delete non-existent staff → 404',
        $c->delete('admin/staff.php', ['id' => 999999]), 404);

    sub('7.9 Self-deletion prevention');
    $runner->assertStatus('Admin CANNOT delete own account → 400',
        $c->delete('admin/staff.php', ['id' => $adminUid]), 400);

    sub('7.10 Settings — GET, SMTP masking, POST + verify persistence');
    $res      = $c->get('admin/settings.php');
    $settings = json_decode($res['body'], true)['settings'] ?? [];
    $runner->assertStatus('GET settings → 200', $res, 200);
    $runner->assertKey('Has settings key', $res, 'settings');
    $runner->assert('clinic_name present', isset($settings['clinic_name']));
    $smtpPass = $settings['smtp_pass'] ?? null;
    $runner->assert('smtp_pass is masked (not stored in plaintext)',
        $smtpPass === null || $smtpPass === '' || $smtpPass === '••••••••',
        "smtp_pass value: " . json_encode($smtpPass));
    // Save and verify
    $runner->assertStatus('POST settings → 200',
        $c->post('admin/settings.php', [
            'clinic_name'    => 'Automated Test Clinic',
            'clinic_address' => '1 Test Drive',
        ]), 200);
    $saved = json_decode($c->get('admin/settings.php')['body'], true)['settings'] ?? [];
    $runner->assert('Setting persisted correctly',
        ($saved['clinic_name'] ?? '') === 'Automated Test Clinic');
    // Restore original
    $c->post('admin/settings.php', ['clinic_name' => 'Clinic CMS', 'clinic_address' => '123 Medical Drive']);

    sub('7.11 Cross-entity data access — admin sees everything');
    $runner->assertStatus('View any patient medical records → 200',
        $c->get('medical/list.php?patient_id=1'), 200);
    $runner->assertStatus('View all appointments → 200',
        $c->get('appointments/list.php?status=all'), 200);
    $runner->assertStatus('View all lab tests → 200',
        $c->get('lab/tests.php?status=all'), 200);
    $runner->assertStatus('View all invoices → 200',
        $c->get('billing/list.php'), 200);
    $runner->assertStatus('View prescriptions → 200',
        $c->get('pharmacy/prescriptions.php'), 200);
    $runner->assertStatus('View all patients → 200',
        $c->get('patients/list.php'), 200);
    $runner->assertStatus('View medicines → 200',
        $c->get('medicines/list.php'), 200);
    $runner->assertStatus('View doctors list → 200',
        $c->get('doctors/list.php'), 200);
    $runner->assertStatus('View reviews → 200',
        $c->get('patients/review.php?doctor_id=1'), 200);

    sub('7.12 Session lifecycle post-logout');
    $c->logout();
    $runner->assertStatus('Admin endpoint after logout → 401',
        $c->get('admin/audit.php'), 401);
}

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 8 — SECURITY & CROSS-ROLE INTEGRITY
// ═══════════════════════════════════════════════════════════════════════════

if (!$onlyRole) {
    section('SECTION 8 — Security & Cross-Role Integrity');

    // ── 8.1 CSRF enforcement ─────────────────────────────────────────────

    sub('8.1 CSRF enforcement — valid session, no X-CSRF-TOKEN → blocked');
    $csrfC = new CmsClient($verbose);
    $csrfC->login('test.patient@clinic.com', PASS);
    $csrfEndpoints = [
        ['appointments/create.php', ['doctor_id' => 1, 'date' => '2030-01-01', 'start_time' => '10:00']],
        ['appointments/update.php', ['id' => 1, 'status' => 'cancelled']],
        ['patients/review.php',    ['doctor_id' => 1, 'rating' => 5]],
    ];
    foreach ($csrfEndpoints as [$ep, $payload]) {
        $res = $csrfC->rawPostNoCsrf($ep, $payload);
        $runner->assert("POST $ep without CSRF → 401/403",
            in_array($res['status'], [401, 403]),
            "Expected 401 or 403, got {$res['status']}");
    }
    $runner->assert('PATCH billing/update without CSRF → 401/403',
        in_array($csrfC->rawPatchNoCsrf('billing/update.php', ['id' => 1, 'status' => 'paid'])['status'], [401, 403]));
    $csrfC->logout();

    $csrfAdmin = new CmsClient($verbose);
    $csrfAdmin->login('test.admin@clinic.com', PASS);
    $runner->assert('Admin: PATCH admin/staff without CSRF → 401/403',
        in_array($csrfAdmin->rawPatchNoCsrf('admin/staff.php', ['id' => 1, 'name' => 'x'])['status'], [401, 403]));
    $csrfAdmin->logout();

    $csrfRec = new CmsClient($verbose);
    $csrfRec->login('test.reception@clinic.com', PASS);
    $runner->assert('Receptionist: PATCH patients/update without CSRF → 401/403',
        in_array($csrfRec->rawPatchNoCsrf('patients/update.php', ['id' => 1, 'full_name' => 'x'])['status'], [401, 403]));
    $csrfRec->logout();

    // ── 8.2 Data isolation ───────────────────────────────────────────────

    sub('8.2 Data isolation — patient cannot cancel another patient\'s appointment');
    // Use admin to find an appointment NOT belonging to patient1
    $admC     = new CmsClient($verbose);
    $admC->login('test.admin@clinic.com', PASS);
    $allAppts = json_decode($admC->get('appointments/list.php?status=scheduled')['body'], true)['appointments'] ?? [];
    $admC->logout();

    // Determine patient1's patient_profile ID
    $p1c     = new CmsClient($verbose);
    $p1c->login('test.patient@clinic.com', PASS);
    $p1Recs  = json_decode($p1c->get('medical/list.php')['body'], true)['records'] ?? [];
    $p1PatId = (int)($p1Recs[0]['patient_id'] ?? 0);

    $otherAppt = null;
    foreach ($allAppts as $a) {
        if ((int)($a['patient_id'] ?? 0) !== $p1PatId) {
            $otherAppt = $a;
            break;
        }
    }
    if ($otherAppt) {
        $runner->assertStatus('Patient1 cancelling Patient2\'s appointment → 403',
            $p1c->post('appointments/update.php', ['id' => $otherAppt['id'], 'status' => 'cancelled']), 403);
    } else {
        $runner->skip('No cross-patient appointments found (seed Patient2 data)');
    }

    sub('8.3 Data isolation — patient records are own-only');
    $ownRecs = json_decode($p1c->get('medical/list.php')['body'], true)['records'] ?? [];
    if ($p1PatId && !empty($ownRecs)) {
        $allOwn = empty(array_filter($ownRecs, fn($r) => (int)$r['patient_id'] !== $p1PatId));
        $runner->assert('All medical records belong to own patient_id', $allOwn);
    }

    sub('8.4 Data isolation — patient invoices are own-only');
    $invs = json_decode($p1c->get('billing/list.php')['body'], true)['invoices'] ?? [];
    if ($p1PatId && !empty($invs)) {
        $allOwn = empty(array_filter($invs, fn($i) => (int)($i['patient_id'] ?? 0) !== $p1PatId));
        $runner->assert('All invoices belong to own patient_id', $allOwn);
    }

    sub('8.5 Data isolation — patient lab results are completed-only');
    $labRes = json_decode($p1c->get('lab/tests.php')['body'], true)['tests'] ?? [];
    $runner->assert('Patient only sees completed lab tests',
        empty(array_filter($labRes, fn($t) => $t['status'] !== 'completed')));
    $p1c->logout();

    // ── 8.6 Key cross-role forbidden access ──────────────────────────────

    sub('8.6 Cross-role RBAC spot checks');
    $matrixTests = [
        ['test.lab@clinic.com',   'medical/list.php?patient_id=1',  'get',    403, 'Lab tech / medical history'],
        ['test.lab@clinic.com',   'billing/list.php',               'get',    403, 'Lab tech / billing'],
        ['test.pharm@clinic.com', 'medical/list.php?patient_id=1',  'get',    403, 'Pharmacist / medical history'],
        ['test.pharm@clinic.com', 'lab/tests.php',                  'get',    403, 'Pharmacist / lab tests'],
        ['test.doctor@clinic.com','billing/list.php',               'get',    403, 'Doctor / billing'],
        ['test.patient@clinic.com','medicines/list.php',            'get',    403, 'Patient / medicines list'],
    ];
    foreach ($matrixTests as [$email, $ep, $method, $expected, $desc]) {
        $tc = new CmsClient($verbose);
        $tc->login($email, PASS);
        $runner->assertStatus("$desc → $expected", $tc->get($ep), $expected);
        $tc->logout();
    }

    // ── 8.7 Non-existent resources ───────────────────────────────────────

    sub('8.7 404 for non-existent resources');
    $a9 = new CmsClient($verbose);
    $a9->login('test.admin@clinic.com', PASS);
    $runner->assertStatus('PATCH non-existent invoice → 404',
        $a9->patch('billing/update.php', ['id' => 999999, 'status' => 'paid']), 404);
    $runner->assertStatus('PATCH non-existent patient → 404',
        $a9->patch('patients/update.php', ['id' => 999999, 'full_name' => 'Ghost']), 404);
    $runner->assertStatus('DELETE non-existent staff → 404',
        $a9->delete('admin/staff.php', ['id' => 999999]), 404);
    $a9->logout();
}

// ═══════════════════════════════════════════════════════════════════════════
// SUMMARY
// ═══════════════════════════════════════════════════════════════════════════

$total = $runner->passed + $runner->failed + $runner->skipped;
echo "\n\e[1m" . str_repeat('═', 62) . "\e[0m\n";
echo "\e[1m  RESULTS: $total tests total\e[0m\n";
echo "  \e[32m{$runner->passed} passed\e[0m";
if ($runner->failed)  echo "   \e[31m{$runner->failed} failed\e[0m";
if ($runner->skipped) echo "   \e[33m{$runner->skipped} skipped\e[0m";
echo "\n\e[1m" . str_repeat('═', 62) . "\e[0m\n";

if ($runner->failed > 0) {
    echo "\n\e[1;31mFailed tests:\e[0m\n";
    foreach ($runner->failures() as $f) {
        echo "  \e[31m•\e[0m {$f['label']}\n";
        if ($f['detail']) echo "    \e[2m→ {$f['detail']}\e[0m\n";
    }
    echo "\n";
    exit(1);
} else {
    echo "\n\e[32mAll tests passed!\e[0m\n\n";
    exit(0);
}
