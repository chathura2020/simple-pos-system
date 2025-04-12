<?php
/**
 * Simple POS System - Main Entry Point
 *
 * Checks login status and redirects to the appropriate page.
 */

// 1. Include Config & Helpers (Config starts the session)
require_once __DIR__ . '/config/config.php'; // Needed for session start
require_once __DIR__ . '/includes/helpers.php'; // Needed for redirect

// 2. Check Login Status and Redirect

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    // If logged in, redirect to the main Sales POS interface
    redirect('modules/sales/index.php');
} else {
    // If not logged in, redirect to the login page
    redirect('modules/auth/login.php');
}

// No further output should be generated as redirect() includes exit().
?>