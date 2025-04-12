<?php
/**
 * Products Module - Save Product Script
 * Handles POST request from add.php to save a new product.
 */

// 1. Authentication Check: Ensure the user is logged in
// This also includes config.php and helpers.php
require_once __DIR__ . '/../../includes/auth_check.php';

// 2. Include DB functions
require_once __DIR__ . '/../../includes/db_json.php';

// 3. Check Request Method: Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // If not POST, redirect to the product list or show an error
    set_flash_message('error', 'Invalid request method.');
    redirect('index.php');
    // Or alternatively: die('Invalid request method.');
}

// --- Process Form Submission ---

// 4. CSRF Token Validation
$submitted_token = $_POST['csrf_token'] ?? null;
if (!validate_csrf_token($submitted_token)) {
    // CSRF token is invalid or missing
    set_flash_message('error', 'Invalid security token. Please try submitting the form again.');
    redirect('add.php'); // Redirect back to the form
}

// 5. Input Validation & Sanitization
$errors = [];
$product_name = sanitize_input($_POST['product_name'] ?? '');
$product_sku = sanitize_input($_POST['product_sku'] ?? '');
$product_price_raw = $_POST['product_price'] ?? ''; // Get raw price for numeric check
$product_category = sanitize_input($_POST['product_category'] ?? ''); // Optional field

// Check required fields
if (empty($product_name)) {
    $errors[] = 'Product Name is required.';
}
if (empty($product_sku)) {
    $errors[] = 'Product SKU / Barcode is required.';
}
if ($product_price_raw === '') { // Check explicitly for empty string as '0' is valid
    $errors[] = 'Product Price is required.';
} elseif (!is_numeric($product_price_raw) || floatval($product_price_raw) < 0) {
    $errors[] = 'Product Price must be a non-negative number.';
} else {
    // If valid, convert to float
    $product_price = floatval($product_price_raw);
}

// Check SKU uniqueness
if (empty($errors) && !empty($product_sku)) {
    $existing_product = find_product_by_sku($product_sku); // From db_json.php
    if ($existing_product !== null) {
        $errors[] = 'This SKU / Barcode (' . htmlspecialchars($product_sku) . ') is already in use.';
    }
}

// 6. Handle Validation Results
if (!empty($errors)) {
    // If there are errors, store them in flash messages and redirect back to the form
    foreach ($errors as $error) {
        set_flash_message('error', $error);
    }
    // Optional: Store submitted data in session to repopulate form (Sticky Form) - Skipped for simplicity
    // $_SESSION['form_data'] = $_POST;
    redirect('add.php');
} else {
    // --- Data is valid, proceed to save ---

    // 7. Prepare New Product Data
    $new_product = [
        'sku' => $product_sku,
        'name' => $product_name,
        'price' => $product_price, // Use the validated float value
        'category' => $product_category // Optional, could be empty
    ];

    // 8. Save Data
    $products = get_products(); // Get current list
    $products[] = $new_product; // Append the new product

    $save_result = save_products($products); // Save the updated list

    // 9. Handle Save Result
    if ($save_result) {
        set_flash_message('success', 'Product "' . htmlspecialchars($product_name) . '" added successfully!');
        redirect('index.php'); // Redirect to the product list on success
    } else {
        set_flash_message('error', 'Failed to save product due to a system error. Please check logs or try again.');
        redirect('add.php'); // Redirect back to the form on failure
    }
}

?>