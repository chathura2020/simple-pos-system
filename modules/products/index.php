<?php
/**
 * Products Module - Index Page
 * Displays a list of all products.
 */

// 1. Authentication Check: Ensure the user is logged in
require_once __DIR__ . '/../../includes/auth_check.php';

// 2. Include necessary files (db_json.php is already included via auth_check indirectly needing helpers->config)
require_once __DIR__ . '/../../includes/db_json.php'; // For get_products()
require_once __DIR__ . '/../../includes/template.php'; // For render_header() and render_footer()
// helpers.php is also included via auth_check -> template.php

// 3. Fetch Data: Get all products from the database
$products = get_products(); // From db_json.php

// --- Page Specific Logic ---
$page_title = "Product List";

// 4. Render Header
render_header($page_title);
?>

<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-gray-800">Products</h1>
    <a href="add.php" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow">
        + Add New Product
    </a>
</div>

<div class="bg-white shadow-md rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    SKU / Barcode
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Name
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Price
                </th>
                </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            <?php if (empty($products)): ?>
                <tr>
                    <td colspan="3" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No products found. <a href="add.php" class="text-blue-600 hover:underline">Add one!</a>
                    </td>
                     </tr>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo isset($product['sku']) ? htmlspecialchars($product['sku']) : 'N/A'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?php echo isset($product['name']) ? htmlspecialchars($product['name']) : 'N/A'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?php
                            // Use format_currency from helpers.php
                            echo isset($product['price']) ? htmlspecialchars(format_currency($product['price'])) : htmlspecialchars(format_currency(0));
                            ?>
                        </td>
                        </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php
// 7. Render Footer
render_footer();
?>