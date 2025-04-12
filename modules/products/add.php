<?php
/**
 * Products Module - Add Product Page
 * Displays a form to add a new product.
 */

// 1. Authentication Check
require_once __DIR__ . '/../../includes/auth_check.php';

// 2. Include necessary files (helpers.php is included via auth_check)
require_once __DIR__ . '/../../includes/template.php'; // For render_header/footer

// --- Page Specific Logic ---
$page_title = "Add New Product";

// 3. Render Header
render_header($page_title);
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Add New Product</h1>

<div class="max-w-lg mx-auto bg-white p-8 rounded-lg shadow-md">
    <form action="save_product.php" method="POST">
        <?php echo get_csrf_input(); // Add CSRF protection input field ?>

        <div class="mb-4">
            <label for="product_name" class="block text-gray-700 text-sm font-bold mb-2">
                Product Name <span class="text-red-500">*</span>
            </label>
            <input type="text" id="product_name" name="product_name"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                   required>
        </div>

        <div class="mb-4">
            <label for="product_sku" class="block text-gray-700 text-sm font-bold mb-2">
                SKU / Barcode <span class="text-red-500">*</span>
            </label>
            <input type="text" id="product_sku" name="product_sku"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                   required>
        </div>

        <div class="mb-4">
            <label for="product_price" class="block text-gray-700 text-sm font-bold mb-2">
                Price (<?php echo CURRENCY_SYMBOL; ?>) <span class="text-red-500">*</span>
            </label>
            <input type="number" id="product_price" name="product_price" step="0.01" min="0"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"
                   placeholder="0.00" required>
        </div>

        <div class="mb-6">
            <label for="product_category" class="block text-gray-700 text-sm font-bold mb-2">
                Category (Optional)
            </label>
            <input type="text" id="product_category" name="product_category"
                   class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
        </div>

        <div class="flex items-center justify-between">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Save Product
            </button>
            <a href="index.php" class="inline-block align-baseline font-bold text-sm text-blue-500 hover:text-blue-800">
                Cancel
            </a>
        </div>

    </form>
</div>
<?php
// 5. Render Footer
render_footer();
?>