<?php
/**
 * Reports Module - Index Page
 * Displays a simple daily sales report.
 */

// 1. Authentication Check
require_once __DIR__ . '/../../includes/auth_check.php'; // Includes Config, Helpers

// 2. Include necessary files
require_once __DIR__ . '/../../includes/db_json.php';   // For get_sales_for_day
require_once __DIR__ . '/../../includes/template.php'; // For render_header/footer

// --- Get Data for Report ---

// 3. Determine Date (Default to today)
// TODO: Add date selection in the future for viewing past reports
$report_date_string = get_sales_file_date_string(); // Get today's date (e.g., 2025-04-11)
try {
    $report_date_obj = new DateTime($report_date_string);
    $formatted_report_date = $report_date_obj->format('F j, Y'); // e.g., April 11, 2025
} catch (Exception $e) {
    $formatted_report_date = $report_date_string; // Fallback
}


// 4. Fetch Sales Data for the determined date
$sales_for_day = get_sales_for_day($report_date_string);

// 5. Calculate Summary Metrics
$total_sales_revenue = 0;
$transaction_count = 0;

if (!empty($sales_for_day)) {
    $transaction_count = count($sales_for_day);
    foreach ($sales_for_day as $sale) {
        if (isset($sale['total_amount']) && is_numeric($sale['total_amount'])) {
            $total_sales_revenue += floatval($sale['total_amount']);
        }
    }
}

// --- Page Title ---
$page_title = "Daily Sales Report - " . $formatted_report_date;

// --- Render Header ---
render_header($page_title);
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Daily Sales Report</h1>
<p class="text-lg text-gray-600 mb-6">Date: <?php echo htmlspecialchars($formatted_report_date); ?></p>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Total Revenue</h2>
        <p class="text-3xl font-bold text-green-600">
            <?php echo htmlspecialchars(format_currency($total_sales_revenue)); ?>
        </p>
    </div>
    <div class="bg-white p-6 rounded-lg shadow-md text-center">
        <h2 class="text-xl font-semibold text-gray-700 mb-2">Number of Transactions</h2>
        <p class="text-3xl font-bold text-blue-600">
            <?php echo $transaction_count; ?>
        </p>
    </div>
</div>

<h2 class="text-2xl font-semibold text-gray-800 mb-4">Transactions for <?php echo htmlspecialchars($formatted_report_date); ?></h2>
<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Time
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Transaction ID
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Total Amount
                </th>
                 <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Action
                </th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($sales_for_day)): ?>
                <tr>
                    <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No transactions found for this date.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($sales_for_day as $sale): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?php
                            try {
                                $time_obj = new DateTime($sale['timestamp'] ?? 'now');
                                echo $time_obj->format('h:i:s A'); // e.g., 07:30:00 AM
                            } catch (Exception $e) {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo isset($sale['transaction_id']) ? htmlspecialchars($sale['transaction_id']) : 'N/A'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 text-right">
                            <?php echo isset($sale['total_amount']) ? htmlspecialchars(format_currency($sale['total_amount'])) : htmlspecialchars(format_currency(0)); ?>
                        </td>
                         <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                            <?php if (isset($sale['transaction_id'])): ?>
                                <a href="../sales/receipt.php?id=<?php echo urlencode($sale['transaction_id']); ?>"
                                   class="text-blue-600 hover:text-blue-800 text-xs"
                                   target="_blank"> View Receipt
                                </a>
                            <?php else: echo 'N/A'; endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
// --- Render Footer ---
render_footer();
?>