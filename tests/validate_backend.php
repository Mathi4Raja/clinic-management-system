<?php
/**
 * CMS Backend Validator
 * Automates PHP syntax checking and basic endpoint reachability.
 */

// 1. Syntax Linter
function lintDirectory($dir)
{
    if (!is_dir($dir)) {
        echo "Directory not found: $dir\n";
        return false;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    $phpFiles = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

    $hasErrors = false;
    $filesChecked = 0;

    echo "--- PHP Syntax Linter ---\n";
    foreach ($phpFiles as $file) {
        $filePath = $file[0];
        $filesChecked++;

        // Execute PHP linter
        $phpPath = 'C:\\Program Files\\XAMPP\\php\\php.exe';
        $output = [];
        $returnVar = 0;
        exec("\"$phpPath\" -l \"" . $filePath . "\"", $output, $returnVar);

        if ($returnVar !== 0) {
            echo "❌ Syntax Error in: $filePath\n";
            echo implode("\n", $output) . "\n\n";
            $hasErrors = true;
        }
    }

    echo "Checked $filesChecked PHP files.\n";
    if (!$hasErrors) {
        echo "✅ All PHP files passed syntax validation.\n\n";
    }

    return !$hasErrors;
}

// 2. Mock Endpoint Tester (Internal fetch)
function testEndpointReachability($endpoint)
{
    $url = "http://localhost/clinic%20management%20system/api/" . ltrim($endpoint, '/');

    echo "Testing Endpoint: $url\n";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Suppress SSL errors for local testing if needed
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // Capture headers
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

    curl_close($ch);

    if ($httpCode === 404) {
        echo "❌ Endpoint Not Found (404)\n";
        return false;
    }

    // Even if it's 401/403 (unauthorized), it means the endpoint exists and logic is running
    if ($httpCode >= 200 && $httpCode < 500) {
        echo "✅ Endpoint Reachable (HTTP $httpCode)\n";
        return true;
    } else {
        echo "⚠️ Endpoint threw Server Error (HTTP $httpCode)\n";
        return false;
    }
}

// Execution Block
$apiDir = __DIR__ . '/../api';
$syntaxPassed = lintDirectory($apiDir);

echo "--- Endpoint Reachability ---\n";
$endpoints = [
    'doctors/list.php',
    'auth/login.php'
];

$endpointsReachable = true;
foreach ($endpoints as $ep) {
    if (!testEndpointReachability($ep)) {
        $endpointsReachable = false;
    }
}

if ($syntaxPassed && $endpointsReachable) {
    echo "\n🎉 Backend Validation Complete: All checks passed.\n";
    exit(0);
} else {
    echo "\n💥 Backend Validation Failed: Review errors above.\n";
    exit(1);
}
