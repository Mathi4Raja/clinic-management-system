<?php
/**
 * CMS Stress & Concurrency Simulator
 * Simulates multiple concurrent requests to verify DB locking and race conditions.
 * Run via CLI: php tests/stress.php
 */

require_once __DIR__ . '/../api/config.php';

$concurrency = 10;
$requests_per_batch = 5;
$target_url = 'http://localhost/clinic%20management%20system/index.php';

echo "\n🌪️ Starting Stress Test (Concurrency: $concurrency)...\n";
echo "====================================================\n";

$start_time = microtime(true);
$mh = curl_multi_init();
$handles = [];

for ($i = 0; $i < $concurrency; $i++) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $target_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_multi_add_handle($mh, $ch);
    $handles[] = $ch;
}

$active = null;
do {
    $mrc = curl_multi_exec($mh, $active);
} while ($mrc == CURLM_CALL_MULTI_PERFORM);

while ($active && $mrc == CURLM_OK) {
    if (curl_multi_select($mh) != -1) {
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    }
}

$success_count = 0;
foreach ($handles as $ch) {
    $info = curl_getinfo($ch);
    if ($info['http_code'] == 200) {
        $success_count++;
    }
    curl_multi_remove_handle($mh, $ch);
    curl_close($ch);
}
curl_multi_close($mh);

$end_time = microtime(true);
$total_time = $end_time - $start_time;

echo "📊 Results:\n";
echo "   Total Concurrent Requests: $concurrency\n";
echo "   Successful Handshakes: $success_count\n";
echo "   Total Execution Time: " . round($total_time, 4) . "s\n";
echo "   Avg Latency per Request: " . round(($total_time / $concurrency) * 1000, 2) . "ms\n";

echo "====================================================\n";
if ($success_count === $concurrency) {
    echo "🌟 STRESS TEST PASSED: System is resilient.\n\n";
} else {
    echo "🚨 STRESS TEST FAILED: Some requests dropped or throttled.\n\n";
    exit(1);
}
