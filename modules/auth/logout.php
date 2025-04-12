<?php
/**
 * Auth Module - Logout Script
 * Destroys the user session and redirects to the login page.
 */

// 1. Include Config & Helpers (Config starts session if needed)
require_once __DIR__ . '/../../config/config.php'; // Ensures session handling is configured
require_once __DIR__ . '/../../includes/helpers.php'; // For redirect, set_flash_message

// 2. Ensure session is started (should be by config.php, but double-check)
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 3. Unset all session variables
$_SESSION = array();

// 4. Destroy the session cookie (if used)
// This step is crucial for completely clearing the session from the browser side.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, // Set expiration in the past
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 5. Destroy the session data on the server
session_destroy();

// 6. Set confirmation message and redirect
set_flash_message('success', 'You have been logged out successfully.');
redirect('login.php'); // Redirect to the login page

// The script execution stops after redirect() is called.
?>