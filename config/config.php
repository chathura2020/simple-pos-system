<?php
/**
 * Simple POS System Configuration File
 *
 * Define basic application settings here.
 */

// --- Basic Settings ---

// Shop Name displayed on receipts etc.
define('SHOP_NAME', 'Chathura\'s Grocery');

// --- Data Storage ---

// Define the absolute path to the data directory.
// __DIR__ gives the directory of the current file (config),
// so '../data' goes one level up and then into 'data'.
define('DATA_PATH', realpath(__DIR__ . '/../data'));

// Check if the data directory exists and is writable
if (!is_dir(DATA_PATH) || !is_writable(DATA_PATH)) {
    // Attempt to create it if it doesn't exist
    if (!is_dir(DATA_PATH) && !@mkdir(DATA_PATH, 0755, true)) {
         error_log("Error: Data directory '" . DATA_PATH . "' does not exist and could not be created.");
         die("Configuration Error: Data directory is missing or could not be created. Please check permissions.");
    } elseif (!is_writable(DATA_PATH)) {
        error_log("Error: Data directory '" . DATA_PATH . "' is not writable.");
        die("Configuration Error: Data directory is not writable. Please check permissions.");
    }
}

// Define the filenames for our JSON data stores
define('PRODUCTS_FILE', DATA_PATH . '/products.json');
// Sales file name will be dynamic based on date, pattern defined here for reference if needed
define('SALES_FILE_PATTERN', DATA_PATH . '/sales_%s.json'); // %s will be replaced by YYYY-MM-DD

// --- Currency ---

// Define the currency symbol to use
define('CURRENCY_SYMBOL', '$'); // Change to your local currency (e.g., '£', '€', '₹', 'LKR')

// --- Tax ---

// Define a simple flat tax rate (e.g., 0.05 for 5%). Set to 0 if no tax.
define('TAX_RATE', 0.00); // Example: 0% tax

// --- Security ---
// Basic session settings (can be enhanced)
ini_set('session.use_only_cookies', 1); // Prevent session fixation
ini_set('session.cookie_httponly', 1);   // Prevent JS access to session cookie
// Consider 'session.cookie_secure' => 1 if using HTTPS (recommended for production)

// Start the session if it's not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Timezone ---
// Set the default timezone for date/time functions
// Find your timezone here: https://www.php.net/manual/en/timezones.php
date_default_timezone_set('Asia/Colombo'); // Set to your shop's timezone

// --- Optional: Basic Authentication ---
// For extremely simple setup, you could hardcode credentials here,
// BUT THIS IS NOT RECOMMENDED FOR PRODUCTION.
// Better approach is handled in the auth module using hashed passwords.
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD_HASH', password_hash('password123', PASSWORD_DEFAULT)); // Example HASH

?>