<?php
/**
 * Sales Module - Process Sale Script
 * Handles AJAX POST request from sales/index.php to finalize and save a sale.
 * Expects JSON input: { items: [...], subtotal: ..., tax_amount: ..., total_amount: ... }
 * Returns JSON output: {"success": true, "transaction_id": "...", "receipt_url": "..."} or {"success": false, "message": "..."}
 */

// Set header to return JSON
header('Content-Type: application/json');

// --- Initial Setup & Security ---
require_once __DIR__ . '/../../includes/auth_check.php'; // Auth, Config, Helpers
require_once __DIR__ . '/../../includes/db_json.php';   // For add_sale_transaction

// Initialize response array
$response = ['success' => false, 'message' => 'An unknown error occurred processing the sale.'];

// --- Request Handling ---

// 1. Check Request Method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Invalid request method.';
    echo json_encode($response);
    exit;
}

// 2. Get and Decode JSON Input
$json_payload = file_get_contents('php://input');
$sale_data = json_decode($json_payload, true);

// Check for JSON decoding errors
if (json_last_error() !== JSON_ERROR_NONE) {
    $response['message'] = 'Invalid JSON input for sale data: ' . json_last_error_msg();
    echo json_encode($response);
    exit;
}

// --- Input Validation ---

// 3. Validate received data structure and content
if (!isset($sale_data['items']) || !is_array($sale_data['items']) || empty($sale_data['items'])) {
    $response['message'] = 'Sale items are missing or invalid.';
    echo json_encode($response);
    exit;
}
if (!isset($sale_data['subtotal']) || !is_numeric($sale_data['subtotal'])) {
    $response['message'] = 'Invalid subtotal received.';
    echo json_encode($response);
    exit;
}
if (!isset($sale_data['tax_amount']) || !is_numeric($sale_data['tax_amount'])) {
    $response['message'] = 'Invalid tax amount received.';
    echo json_encode($response);
    exit;
}
if (!isset($sale_data['total_amount']) || !is_numeric($sale_data['total_amount'])) {
    $response['message'] = 'Invalid total amount received.';
    echo json_encode($response);
    exit;
}

// Note: For simplicity, we are trusting the client-calculated totals.
// A more robust system would recalculate totals server-side based on item SKUs and quantities fetched from the product database
// to prevent potential manipulation, but that adds complexity (multiple product lookups).

// --- Prepare and Save Transaction ---

try {
    // 4. Prepare Transaction Data
    $transaction_id = generate_transaction_id(); // From helpers.php
    $timestamp = date('c'); // ISO 8601 timestamp (e.g., 2025-04-11T10:30:00+05:30)
    $payment_method = 'Cash'; // Default for this simple version

    // Sanitize item details within the items array (important!)
    $sanitized_items = [];
    foreach ($sale_data['items'] as $item) {
        if (isset($item['sku'], $item['quantity'], $item['price_at_sale']) &&
            is_numeric($item['quantity']) && $item['quantity'] > 0 &&
            is_numeric($item['price_at_sale'])) {

            // Fetch product name based on SKU to store in transaction record
            // This makes receipts easier to generate later without needing product lookup again
            $product_info = find_product_by_sku(sanitize_input($item['sku'], false));
            $product_name = $product_info ? $product_info['name'] : 'Unknown Item';

            $sanitized_items[] = [
                'sku' => sanitize_input($item['sku'], false), // Don't encode SKU
                'name' => sanitize_input($product_name),      // Sanitize name
                'quantity' => intval($item['quantity']),
                'price_at_sale' => floatval($item['price_at_sale'])
            ];
        } else {
            // Skip invalid items or throw error
            // For simplicity, we skip here. A real system might reject the whole sale.
            error_log("Skipping invalid item in transaction processing: " . json_encode($item));
        }
    }

    // Ensure we still have items after sanitization
    if (empty($sanitized_items)) {
         $response['message'] = 'No valid sale items found after processing.';
         echo json_encode($response);
         exit;
    }


    $transaction_data = [
        'transaction_id' => $transaction_id,
        'timestamp' => $timestamp,
        'items' => $sanitized_items, // Use sanitized items
        'subtotal' => floatval($sale_data['subtotal']),
        'tax_amount' => floatval($sale_data['tax_amount']),
        'total_amount' => floatval($sale_data['total_amount']),
        'payment_method' => $payment_method
        // Add amount_tendered, change_due later if needed
    ];

    // 5. Save Transaction
    $save_result = add_sale_transaction($transaction_data); // From db_json.php

    // 6. Generate Response
    if ($save_result) {
        $response['success'] = true;
        $response['transaction_id'] = $transaction_id;
        // Generate URL for the receipt page (relative to this script location)
        $response['receipt_url'] = 'receipt.php?id=' . urlencode($transaction_id);
        unset($response['message']); // Remove default error message
    } else {
        $response['success'] = false;
        $response['message'] = 'Failed to save the transaction. Please check system logs.';
    }

} catch (Exception $e) {
    error_log("Error in process_sale.php: " . $e->getMessage());
    $response['success'] = false;
    $response['message'] = 'Server error while processing sale.';
    // http_response_code(500); // Optional: Set 500 status
}

// --- Output Response ---
echo json_encode($response);
exit; // Terminate script execution

?>