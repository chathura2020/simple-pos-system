<?php
/**
 * Sales Module - Receipt Page
 * Displays details for a specific transaction.
 * Expects transaction ID via GET parameter: receipt.php?id=...
 */

// 1. Authentication Check (Ensure logged-in user can view receipts)
require_once __DIR__ . '/../../includes/auth_check.php'; // Includes Config, Helpers

// 2. Include necessary files
require_once __DIR__ . '/../../includes/db_json.php';   // For get_sales_for_day
require_once __DIR__ . '/../../includes/template.php'; // For render_header/footer

// --- Get Transaction ID ---
$transaction_id = isset($_GET['id']) ? sanitize_input(trim($_GET['id']), false) : null; // Sanitize without encoding for lookup
$transaction_data = null;
$error_message = '';

// --- Find Transaction Data ---
if (empty($transaction_id)) {
    $error_message = "No Transaction ID provided.";
} else {
    // Extract date part (YYYYMMDD) from the transaction ID (format: Ymd-His-xxxx)
    $id_parts = explode('-', $transaction_id);
    if (count($id_parts) >= 2 && strlen($id_parts[0]) === 8) {
        $date_ymd_part = $id_parts[0]; // e.g., 20250411

        // Convert YYYYMMDD to YYYY-MM-DD format for file lookup
        try {
            $date_obj = DateTime::createFromFormat('Ymd', $date_ymd_part);
            if (!$date_obj) {
                throw new Exception("Failed to parse date from transaction ID.");
            }
            $date_string_for_file = $date_obj->format('Y-m-d'); // e.g., 2025-04-11

            // Load sales data for that specific day
            $sales_for_day = get_sales_for_day($date_string_for_file);

            // Find the specific transaction within that day's sales
            foreach ($sales_for_day as $sale) {
                if (isset($sale['transaction_id']) && $sale['transaction_id'] === $transaction_id) {
                    $transaction_data = $sale;
                    break; // Found it
                }
            }

            if ($transaction_data === null) {
                $error_message = "Transaction with ID '" . htmlspecialchars($transaction_id) . "' not found for date " . htmlspecialchars($date_string_for_file) . ".";
            }

        } catch (Exception $e) {
            error_log("Error parsing date from Transaction ID '$transaction_id': " . $e->getMessage());
            $error_message = "Invalid Transaction ID format.";
        }

    } else {
        $error_message = "Invalid Transaction ID format provided.";
    }
}


// --- Page Title ---
$page_title = $transaction_data ? "Receipt - " . htmlspecialchars($transaction_id) : "Receipt Not Found";

// --- Render Header ---
render_header($page_title);
?>

<div class="max-w-sm mx-auto bg-white p-6 shadow-lg rounded-lg border border-gray-200 mt-6 print:shadow-none print:border-none print:m-0 print:p-0">

    <?php if ($transaction_data): ?>
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold uppercase"><?php echo defined('SHOP_NAME') ? htmlspecialchars(SHOP_NAME) : 'Your Shop'; ?></h1>
            <p class="text-gray-600 text-sm">Panadura, Sri Lanka</p> <?php // Location specific ?>
            <p class="text-xs text-gray-500 mt-2">Receipt</p>
        </div>

        <div class="mb-4 text-sm text-gray-700">
            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($transaction_data['transaction_id']); ?></p>
            <p><strong>Date:</strong>
                <?php
                try {
                    // Format timestamp nicely
                    $receipt_date = new DateTime($transaction_data['timestamp']);
                    echo $receipt_date->format('Y-m-d h:i:s A'); // e.g., 2025-04-11 07:25:00 AM
                } catch (Exception $e) {
                    echo htmlspecialchars($transaction_data['timestamp']); // Fallback
                }
                ?>
            </p>
            </div>

        <hr class="my-4 border-dashed">

        <table class="w-full text-sm mb-4">
            <thead>
                <tr class="border-b border-dashed">
                    <th class="pb-1 text-left font-semibold">Item</th>
                    <th class="pb-1 text-center font-semibold">Qty</th>
                    <th class="pb-1 text-right font-semibold">Price</th>
                    <th class="pb-1 text-right font-semibold">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transaction_data['items'] as $item): ?>
                <tr>
                    <td class="py-1"><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="py-1 text-center"><?php echo htmlspecialchars($item['quantity']); ?></td>
                    <td class="py-1 text-right"><?php echo htmlspecialchars(format_currency($item['price_at_sale'])); ?></td>
                    <td class="py-1 text-right"><?php echo htmlspecialchars(format_currency($item['price_at_sale'] * $item['quantity'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <hr class="my-4 border-dashed">

        <div class="text-sm space-y-1 mb-4">
            <div class="flex justify-between">
                <span>Subtotal:</span>
                <span><?php echo htmlspecialchars(format_currency($transaction_data['subtotal'])); ?></span>
            </div>
            <div class="flex justify-between">
                <span>Tax:</span>
                <span><?php echo htmlspecialchars(format_currency($transaction_data['tax_amount'])); ?></span>
            </div>
            <div class="flex justify-between font-bold text-base">
                <span>TOTAL:</span>
                <span><?php echo htmlspecialchars(format_currency($transaction_data['total_amount'])); ?></span>
            </div>
        </div>

         <hr class="my-4 border-dashed">

         <div class="text-sm mb-6">
             <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($transaction_data['payment_method']); ?></p>
              </div>

        <div class="text-center text-sm text-gray-600">
            <p>Thank you for shopping with us!</p>
        </div>

    <?php elseif ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Error!</strong>
            <span class="block sm:inline"><?php echo htmlspecialchars($error_message); ?></span>
        </div>
         <div class="mt-6 text-center">
             <a href="index.php" class="text-blue-600 hover:underline print:hidden">&larr; Back to POS</a>
        </div>
    <?php else: ?>
         <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative" role="alert">
            <strong class="font-bold">Info:</strong>
            <span class="block sm:inline">Could not display receipt. Please check the Transaction ID or contact support.</span>
        </div>
         <div class="mt-6 text-center">
             <a href="index.php" class="text-blue-600 hover:underline print:hidden">&larr; Back to POS</a>
        </div>
    <?php endif; ?>

    <div class="mt-8 text-center print:hidden">
         <button onclick="window.print();" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mr-2">
            Print Receipt
        </button>
         <a href="index.php" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded">
            Back to POS
        </a>
    </div>

</div><?php
// --- Render Footer ---
render_footer();
?>