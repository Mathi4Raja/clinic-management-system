<?php
/**
 * CMS A/B (A2B) Testing Configuration
 * Defines UI variants for optimization tests.
 */

session_start();

function getABVariant($experiment_name)
{
    if (!isset($_SESSION['ab_tests'][$experiment_name])) {
        // Randomly assign A or B
        $variant = (rand(0, 1) === 0) ? 'A' : 'B';
        $_SESSION['ab_tests'][$experiment_name] = $variant;
    }
    return $_SESSION['ab_tests'][$experiment_name];
}

/**
 * Example: Dashboard Header Variant
 * A: Standard Blue
 * B: Gradient Emerald
 */
$header_variant = getABVariant('dashboard_theme');

// This would be consumed by the frontend injection or a global config API
// For simplicity, we'll expose an API endpoint for it.
