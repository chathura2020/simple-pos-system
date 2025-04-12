<?php
/**
 * Simple POS System Helper Functions
 *
 * Contains utility functions used across the application.
 */

// Ensure configuration is loaded first if this file is included directly sometimes.
// It's generally better to include config.php once in the main script entry points.
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
} else {
    die('Critical Error: config.php not found!');
}

/**
 * Redirects the browser to a specified URL.
 *
 * @param string $url The relative or absolute URL to redirect to.
 * @return void
 */
function redirect(string $url): void
{
    header("Location: " . $url);
    exit; // Stop script execution after redirect
}

/**
 * Formats a numeric value as currency using the defined symbol.
 *
 * @param float|int $amount The amount to format.
 * @param int $decimals Number of decimal places (default is 2).
 * @return string The formatted currency string.
 */
function format_currency($amount, int $decimals = 2): string
{
    // Ensure it's treated as a float for number_format
    $amount = floatval($amount);
    return CURRENCY_SYMBOL . number_format($amount, $decimals);
}

/**
 * Basic input sanitizer.
 * Removes tags and optionally encodes special characters.
 * For more robust validation, consider filter_var or libraries.
 *
 * @param mixed $data The input data to sanitize.
 * @param bool $encode_special_chars Whether to encode HTML special characters.
 * @return mixed The sanitized data.
 */
function sanitize_input($data, bool $encode_special_chars = true)
{
    if (is_array($data)) {
        // Recursively sanitize array elements
        return array_map(function($item) use ($encode_special_chars) {
            // Pass the flag down recursively
            return sanitize_input($item, $encode_special_chars);
        }, $data);
    } elseif (is_string($data)) {
        // Remove leading/trailing whitespace
        $data = trim($data);
        // Remove potentially harmful tags
        $data = strip_tags($data);
        if ($encode_special_chars) {
            // Convert special characters to HTML entities
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        return $data;
    }
    // Return non-string, non-array data as is (e.g., numbers, booleans)
    return $data;
}


/**
 * Generates the date string part for the daily sales filename.
 * Uses the timezone set in config.php.
 *
 * @return string Date in 'Y-m-d' format.
 */
function get_sales_file_date_string(): string
{
    return date('Y-m-d'); // Format: 2025-04-11
}

/**
 * Generates a simple unique transaction ID.
 * Combines date with a sequential number (requires reading current sales file).
 * For simplicity here, we'll use date and microtime - less robust against collisions
 * under high load but okay for a minimal system.
 * A better approach might involve locking and reading the last ID.
 *
 * @return string A unique-enough transaction ID.
 */
function generate_transaction_id(): string
{
    // Format: YYYYMMDD-HHMMSS-microseconds
    return date('Ymd-His') . '-' . substr((string)microtime(true), -4);
}

/**
 * Sets a flash message in the session to be displayed on the next request.
 *
 * @param string $key The type of message (e.g., 'success', 'error', 'info').
 * @param string $message The message text.
 * @return void
 */
function set_flash_message(string $key, string $message): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        $_SESSION['flash_messages'][$key] = $message;
    }
}

/**
 * Retrieves and clears flash messages from the session.
 *
 * @return array An array of flash messages ['key' => 'message'].
 */
function get_flash_messages(): array
{
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['flash_messages'])) {
        $messages = $_SESSION['flash_messages'];
        unset($_SESSION['flash_messages']); // Clear after retrieval
        return $messages;
    }
    return [];
}


// --- CSRF Protection Functions ---

/**
 * Generates a CSRF token, stores it in the session, and returns it.
 * If a token already exists in the session, it returns the existing one.
 *
 * @return string The CSRF token.
 */
function generate_csrf_token(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Should not happen if config.php is included, but defensive check
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        try {
            // Generate a random, URL-safe token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            // Fallback if random_bytes fails (less secure)
             $_SESSION['csrf_token'] = md5(uniqid(rand(), true));
             error_log("CSRF generation fallback used: " . $e->getMessage());
        }
    }
    return $_SESSION['csrf_token'];
}

/**
 * Returns an HTML hidden input field containing the CSRF token.
 * Generates a token if one doesn't exist.
 *
 * @return string HTML input tag.
 */
function get_csrf_input(): string
{
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Validates a submitted CSRF token against the one stored in the session.
 * Unsets the token after validation attempt (prevents reuse).
 *
 * @param string|null $token_from_form The token received from the form submission (usually $_POST['csrf_token']).
 * @return bool True if the token is valid, false otherwise.
 */
function validate_csrf_token(?string $token_from_form): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token']) || empty($token_from_form)) {
        // No token in session or form, validation fails
        if (isset($_SESSION['csrf_token'])) unset($_SESSION['csrf_token']); // Clear session token if form token missing
        return false;
    }

    $session_token = $_SESSION['csrf_token'];
    // Unset the session token immediately after retrieving it
    // This ensures a token can only be used once per session state.
    unset($_SESSION['csrf_token']);

    // Use hash_equals for timing-attack safe comparison
    return hash_equals($session_token, $token_from_form);
}

?>