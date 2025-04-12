<?php
/**
 * Auth Module - Process Login Script
 * Handles POST request from login.php, verifies credentials, and starts session.
 */

// 1. Include Config & Helpers (Config starts session and defines credentials)
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/helpers.php'; // For CSRF, redirect, flash messages, sanitize

// 2. Check Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    set_flash_message('error', 'Invalid request method.');
    redirect('login.php');
}

// 3. CSRF Token Validation
$submitted_token = $_POST['csrf_token'] ?? null;
if (!validate_csrf_token($submitted_token)) {
    set_flash_message('error', 'Invalid security token. Please log in again.');
    redirect('login.php');
}

// 4. Get and Sanitize Input
// Use basic sanitization; specific validation happens during comparison
$username = sanitize_input($_POST['username'] ?? '', false); // Don't encode html chars for comparison
$password = $_POST['password'] ?? ''; // Don't sanitize password before password_verify

// 5. Validate Inputs (Basic check)
if (empty($username) || empty($password)) {
    set_flash_message('error', 'Username and Password are required.');
    redirect('login.php');
}

// 6. Credential Verification
// Compare against credentials defined in config.php
$is_valid_user = false;
if (defined('ADMIN_USERNAME') && defined('ADMIN_PASSWORD_HASH')) {
    // Case-insensitive username comparison is common
    if (strcasecmp($username, ADMIN_USERNAME) === 0) {
        // Verify password against the stored hash
        if (password_verify($password, ADMIN_PASSWORD_HASH)) {
            $is_valid_user = true;
        }
    }
} else {
    // Configuration error - credentials not defined
    error_log("Authentication error: ADMIN_USERNAME or ADMIN_PASSWORD_HASH not defined in config.php");
    set_flash_message('error', 'System configuration error. Cannot log in.');
    redirect('login.php');
}

// 7. Session Management & Redirect
if ($is_valid_user) {
    // --- Login Successful ---
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    // Set session variables to mark user as logged in
    $_SESSION['user_logged_in'] = true;
    $_SESSION['username'] = ADMIN_USERNAME; // Store the canonical username
    $_SESSION['login_time'] = time();

    // Redirect to the main application page (e.g., POS interface)
    redirect('../sales/index.php'); // Or '../index.php'

} else {
    // --- Login Failed ---
    set_flash_message('error', 'Invalid username or password.');
    redirect('login.php'); // Redirect back to login form
}

?>