v<?php
/**
 * Auth Module - Login Page
 * Displays the login form.
 */

// 1. Include Config & Helpers (Config starts the session)
// We don't need the full auth_check.php here, as this IS the login page.
require_once __DIR__ . '/../../config/config.php'; // Needed for session start
require_once __DIR__ . '/../../includes/helpers.php'; // Needed for CSRF, flash messages, redirect
require_once __DIR__ . '/../../includes/template.php';// Needed for header/footer

// 2. Redirect if already logged in
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    // Redirect to the main sales page or a dashboard
    redirect('../sales/index.php'); // Or perhaps '../index.php'
}

// --- Page Settings ---
$page_title = "Login";

// --- Render Header ---
// The header function in template.php should handle showing appropriate navigation
// (i.e., no main nav links if not logged in)
render_header($page_title);

?>

<div class="flex items-center justify-center min-h-[calc(100vh-250px)]"> <?php // Adjust min-height as needed ?>
    <div class="w-full max-w-xs">
        <form action="process_login.php" method="POST" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">POS Login</h1>

            <?php
            // Display any specific login error messages passed via flash session
            // (General flash messages are handled in the header)
            // Example: If process_login redirects back here with an error
            // display_flash_messages(); // Already called in header, might cause duplicates if not careful
            ?>

            <?php echo get_csrf_input(); // Add CSRF protection input field ?>

            <div class="mb-4">
                <label for="username" class="block text-gray-700 text-sm font-bold mb-2">
                    Username
                </label>
                <input type="text" id="username" name="username"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                       required autofocus>
            </div>

            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">
                    Password
                </label>
                <input type="password" id="password" name="password"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline"
                       required>
                </div>

            <div class="flex items-center justify-between">
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                    Sign In
                </button>
            </div>
        </form>
         <p class="text-center text-gray-500 text-xs">
            &copy; <?php echo date('Y'); ?> <?php echo defined('SHOP_NAME') ? htmlspecialchars(SHOP_NAME) : 'POS System'; ?>. All rights reserved.
        </p>
    </div>
</div>
<?php
// --- Render Footer ---
render_footer();
?>