<?php
/**
 * Simple POS System Template Functions
 *
 * Functions for rendering common HTML parts like header and footer.
 */

// Ensure configuration is loaded (might need SHOP_NAME)
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
} else {
    die('Critical Error: config.php not found!');
}
// Ensure helpers are loaded (needed for flash messages)
if (file_exists(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
} else {
    die('Critical Error: helpers.php not found!');
}

/**
 * Renders the HTML header, including doctype, head section, and opening body tag.
 * Includes Tailwind CSS CDN and basic navigation placeholder.
 *
 * @param string $page_title The title for the specific page.
 * @return void Outputs HTML directly.
 */
function render_header(string $page_title = 'Simple POS'): void
{
    // Basic check for logged-in status (can be enhanced in auth_check.php)
    // This simple check assumes a session variable 'user_logged_in' is set upon successful login.
    // We might need to adjust this based on the actual auth module implementation.
    $is_logged_in = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
    $current_script = basename($_SERVER['PHP_SELF']); // Get the name of the current script

    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($page_title); ?> - <?php echo defined('SHOP_NAME') ? htmlspecialchars(SHOP_NAME) : 'POS'; ?></title>
        <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
        <link rel="stylesheet" href="../assets/css/custom.css">
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <style>
            /* Simple style for flash messages if needed */
            .flash-message { padding: 0.75rem 1rem; margin-bottom: 1rem; border-radius: 0.25rem; }
            .flash-success { background-color: #d1fae5; color: #065f46; border: 1px solid #6ee7b7; }
            .flash-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
            .flash-info { background-color: #dbeafe; color: #1e40af; border: 1px solid #93c5fd; }
        </style>
    </head>
    <body class="bg-gray-100 font-sans antialiased">

        <nav class="bg-blue-600 text-white p-4 shadow-md">
            <div class="container mx-auto flex justify-between items-center">
                <a href="../index.php" class="text-xl font-bold"><?php echo defined('SHOP_NAME') ? htmlspecialchars(SHOP_NAME) : 'POS System'; ?></a>
                <div class="space-x-4">
                    <?php if ($is_logged_in): ?>
                        <a href="../sales/index.php" class="hover:text-blue-200">Sales</a>
                        <a href="../products/index.php" class="hover:text-blue-200">Products</a>
                        <a href="../reports/index.php" class="hover:text-blue-200">Reports</a>
                        <a href="../auth/logout.php" class="hover:text-blue-200">Logout</a>
                    <?php elseif ($current_script !== 'login.php'): ?>
                        <a href="../auth/login.php" class="hover:text-blue-200">Login</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>

        <div class="container mx-auto p-4 mt-4">
            <?php display_flash_messages(); // Display flash messages right below nav ?>
            <main> <?php
}

/**
 * Renders the HTML footer, including closing body and html tags.
 * Includes link to custom JS file.
 *
 * @return void Outputs HTML directly.
 */
function render_footer(): void
{
    ?>
            </main> </div> <footer class="text-center text-gray-500 text-sm mt-8 mb-4">
            <p>&copy; <?php echo date('Y'); ?> <?php echo defined('SHOP_NAME') ? htmlspecialchars(SHOP_NAME) : 'POS System'; ?>. Panadura, Sri Lanka.</p>
            </footer>

        <script src="../assets/js/main.js"></script>
    </body>
    </html>
    <?php
}

/**
 * Displays flash messages stored in the session.
 * Retrieves messages using get_flash_messages() from helpers.php.
 *
 * @return void Outputs HTML directly.
 */
function display_flash_messages(): void
{
    $messages = get_flash_messages(); // From helpers.php
    if (!empty($messages)) {
        echo '<div class="flash-messages mb-4">';
        foreach ($messages as $key => $message) {
            // Basic mapping of key to CSS class
            $class = 'flash-info'; // Default
            if ($key === 'success') {
                $class = 'flash-success';
            } elseif ($key === 'error' || $key === 'danger') {
                $class = 'flash-error';
            }
            // Output the message
            echo '<div class="flash-message ' . htmlspecialchars($class) . '" role="alert">';
            echo htmlspecialchars($message);
            echo '</div>';
        }
        echo '</div>';
    }
}

?>