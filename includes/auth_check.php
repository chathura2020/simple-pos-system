<?php
/**
 * Simple POS System Authentication Check
 *
 * This script checks if a user session exists and is marked as logged in.
 * If not, it redirects the user to the login page.
 * Include this at the very top of any PHP file that requires authentication.
 */

// Ensure configuration is loaded first (it also starts the session)
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
} else {
    die('Critical Error: config.php not found!');
}

// Ensure helpers are loaded (needed for redirect and flash messages)
if (file_exists(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
} else {
    die('Critical Error: helpers.php not found!');
}

// Define where to redirect if not logged in
// Adjust the path based on the location of auth_check.php relative to login.php
$login_page_url = '../modules/auth/login.php'; // Assumes auth_check is in /includes/

// Check if the 'user_logged_in' session variable is set and true
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    // Optional: Set a flash message to inform the user
    set_flash_message('error', 'Please log in to access this page.');

    // Redirect to the login page
    redirect($login_page_url);
}

// If the script reaches here, the user is considered authenticated.
// You might want to add checks for user roles or permissions here in a more complex system.

// Optionally, store user info retrieved during login for easy access
// $current_user = $_SESSION['user_info'] ?? null; // Example

?>