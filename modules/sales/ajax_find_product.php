<?php
/**
 * Sales Module - AJAX Find Product (Search Functionality)
 * Handles AJAX requests to find products matching a search term (SKU or Name).
 * Expects JSON input: {"search_term": "..."}
 * Returns JSON output: {"success": true, "products": [...]} or {"success": false, "message": "..."}
 */

// Set header to return JSON
header('Content-Type: application/json');

// Basic Error Reporting (disable in production)
// error_reporting(E_ALL);
// ini_set('display_errors', 0);
// ini_set('log_errors', 1);

// --- Initial Setup & Security ---
require_once __DIR__ . '/../../includes/auth_check.php'; // Ensures user is logged in, includes config, helpers
require_once __DIR__ . '/../../includes/db_json.php';   // For get_products()

// Initialize response array
$response = ['success' => false, 'products' => [], 'message' => 'An unknown error occurred.'];

// --- Request Handling ---

// 1. Check Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// 2. Get and Decode JSON Input
$json_payload = file_get_contents('php://input');
$request_data = json_decode($json_payload, true);

// Check for JSON decoding errors
if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Invalid JSON input: ' . json_last_error_msg();
    echo json_encode($response);
    exit;
}

// 3. Validate Input Data
if (!isset($request_data['search_term'])) { // Expect 'search_term' now
    $response['message'] = 'Search term is missing.';
    echo json_encode($response);
    exit;
}

// 4. Sanitize Search Term
$search_term = sanitize_input(trim($request_data['search_term']), false); // Don't encode html chars for comparison

// --- Data Lookup (Search Logic) ---

// Only search if the term is not empty
if (!empty($search_term)) {
    try {
        $all_products = get_products(); // Get all products
        $matching_products = [];

        if ($all_products) {
            foreach ($all_products as $product) {
                $sku_match = false;
                $name_match = false;

                // Check if SKU starts with or contains the search term (case-insensitive)
                if (isset($product['sku']) && stripos($product['sku'], $search_term) !== false) {
                    $sku_match = true;
                }

                // Check if Name contains the search term (case-insensitive)
                if (isset($product['name']) && stripos($product['name'], $search_term) !== false) {
                    $name_match = true;
                }

                if ($sku_match || $name_match) {
                    // Add essential product info to the results array
                    $matching_products[] = [
                        'sku' => $product['sku'],
                        'name' => $product['name'],
                        'price' => $product['price']
                    ];
                }
            }
        }

        $response['success'] = true;
        $response['products'] = $matching_products; // Send back array of matches (can be empty)
        unset($response['message']); // Remove default error message

    } catch (Exception $e) {
        error_log("Error in ajax_find_product.php (search): " . $e->getMessage());
        $response['success'] = false;
        $response['products'] = []; // Ensure products is empty on error
        $response['message'] = 'Server error while searching for products.';
        // http_response_code(500);
    }
} else {
    // If search term is empty, return success with empty products array
    $response['success'] = true;
    $response['products'] = [];
     unset($response['message']);
}


// --- Output Response ---
echo json_encode($response);
exit; // Terminate script execution

?>