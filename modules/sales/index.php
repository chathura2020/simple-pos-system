<?php
/**
 * Sales Module - Index Page (POS Interface) - With Search
 * Main interface for processing sales transactions with product search.
 */

// 1. Authentication Check
require_once __DIR__ . '/../../includes/auth_check.php';

// 2. Include necessary files
require_once __DIR__ . '/../../includes/db_json.php';
require_once __DIR__ . '/../../includes/template.php';

// --- Page Specific Logic ---
$page_title = "Point of Sale";

// Pass PHP constants/config needed by JavaScript
$js_config = json_encode([
    'taxRate' => defined('TAX_RATE') ? TAX_RATE : 0,
    'currencySymbol' => defined('CURRENCY_SYMBOL') ? CURRENCY_SYMBOL : '$',
    // Use the updated AJAX script URL (though name might be same)
    'ajaxSearchProductUrl' => 'ajax_find_product.php',
    'processSaleUrl' => 'process_sale.php'
]);

// 3. Render Header
render_header($page_title);
?>

<div x-data="posApp(<?php echo htmlspecialchars($js_config, ENT_QUOTES, 'UTF-8'); ?>)" class="grid grid-cols-1 md:grid-cols-3 gap-6">

    <div class="md:col-span-1 bg-white p-6 rounded-lg shadow-md h-fit">
        <h2 class="text-xl font-semibold mb-4">Add Item</h2>
        <div class="relative" @click.outside="showResults = false"> <label for="search-input" class="block text-sm font-medium text-gray-700 mb-1">Search by SKU or Name:</label>
            <input type="text" id="search-input"
                   x-model="searchTerm"
                   @input.debounce.300ms="searchProducts" @focus="showResults = (searchResults.length > 0 || loadingSearch)" @keydown.escape="showResults = false; searchTerm = ''; searchResults = [];" @keydown.down.prevent="selectNextResult" @keydown.up.prevent="selectPreviousResult" @keydown.enter.prevent="addSelectedResult" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline mb-1"
                   placeholder="Type SKU or Name..." autocomplete="off">

            <div x-show="showResults"
                 class="absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg mt-1 max-h-60 overflow-y-auto">
                <div x-show="loadingSearch" class="p-3 text-gray-500 text-center">Loading...</div>
                <div x-show="!loadingSearch && searchResults.length === 0 && searchTerm.length > 1" class="p-3 text-gray-500 text-center">No matches found.</div>
                <template x-for="(product, index) in searchResults" :key="product.sku">
                    <div @click="addItemFromList(product)"
                         @mouseenter="selectedIndex = index"
                         :class="{ 'bg-blue-500 text-white': index === selectedIndex, 'hover:bg-blue-100': index !== selectedIndex }"
                         class="px-4 py-2 cursor-pointer">
                        <span class="font-semibold" x-text="product.name"></span>
                        <span class="text-sm ml-2" x-text="'(SKU: ' + product.sku + ')'"></span>
                    </div>
                </template>
            </div>
        </div>
         <p x-show="ajaxError" class="text-red-500 text-sm mt-2">Error searching for items.</p>
    </div>

    <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-semibold mb-4">Current Sale</h2>
        <div class="overflow-x-auto mb-4 max-h-96 overflow-y-auto">
             <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="cart-items">
                    <template x-if="cart.length === 0">
                        <tr>
                            <td colspan="5" class="px-4 py-3 text-center text-gray-500">Cart is empty</td>
                        </tr>
                    </template>
                    <template x-for="(item, index) in cart" :key="item.sku">
                        <tr>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-800" x-text="item.name"></td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-600 text-center">
                                <button @click="updateQuantity(item.sku, -1)" class="text-red-500 px-1">-</button>
                                <span x-text="item.quantity" class="mx-1"></span>
                                <button @click="updateQuantity(item.sku, 1)" class="text-green-500 px-1">+</button>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-600 text-right" x-text="formatCurrency(item.price)"></td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-800 text-right" x-text="formatCurrency(item.price * item.quantity)"></td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-center">
                                <button @click="removeItem(item.sku)" class="text-red-600 hover:text-red-800 text-xs">Remove</button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-200 pt-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">Subtotal:</span>
                <span class="font-semibold text-gray-800" x-text="formatCurrency(subtotal)"></span>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">Tax (<span x-text="(config.taxRate * 100).toFixed(1)"></span>%):</span>
                <span class="font-semibold text-gray-800" x-text="formatCurrency(taxAmount)"></span>
            </div>
            <div class="flex justify-between items-center text-xl font-bold mb-4">
                <span class="text-gray-900">Total:</span>
                <span class="text-blue-700" x-text="formatCurrency(total)"></span>
            </div>
        </div>
        <div class="flex justify-between items-center border-t border-gray-200 pt-4">
             <button id="clear-cart-btn" @click="clearCart" :disabled="cart.length === 0"
                    class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline disabled:opacity-50">
                Clear Cart
            </button>
            <button id="process-sale-btn" @click="processSale" :disabled="cart.length === 0 || processingSale"
                    class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline disabled:opacity-50">
                <span x-show="!processingSale">Process Sale</span>
                <span x-show="processingSale">Processing...</span>
            </button>
        </div>
         <p x-show="saleError" class="text-red-500 text-sm mt-2" x-text="saleErrorMessage"></p>
    </div>
</div>

<script>
    function posApp(config) {
        return {
            // Config and Cart State (same as before)
            config: config,
            cart: [],
            processingSale: false,
            saleError: false,
            saleErrorMessage: '',

            // Search State
            searchTerm: '',
            searchResults: [],
            showResults: false,
            loadingSearch: false,
            ajaxError: false,
            selectedIndex: -1, // For keyboard navigation of results

            // Computed properties (same as before)
            get subtotal() { return this.cart.reduce((sum, item) => sum + (item.price * item.quantity), 0); },
            get taxAmount() { return this.subtotal * this.config.taxRate; },
            get total() { return this.subtotal + this.taxAmount; },

            // --- Methods ---

            formatCurrency(amount) { return this.config.currencySymbol + amount.toFixed(2); },

            searchProducts() {
                const term = this.searchTerm.trim();
                this.selectedIndex = -1; // Reset keyboard selection

                if (term.length < 2) { // Minimum search length
                    this.searchResults = [];
                    this.showResults = false;
                    this.loadingSearch = false;
                    return;
                }

                this.loadingSearch = true;
                this.ajaxError = false;
                this.showResults = true; // Show dropdown while loading

                fetch(this.config.ajaxSearchProductUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ search_term: term })
                })
                .then(response => {
                    if (!response.ok) { throw new Error(`HTTP error! status: ${response.status}`); }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        this.searchResults = data.products;
                    } else {
                        this.searchResults = [];
                        this.ajaxError = true; // Or display data.message if available
                        console.error("Search failed:", data.message);
                    }
                })
                .catch(error => {
                    console.error('Error searching products:', error);
                    this.ajaxError = true;
                    this.searchResults = [];
                })
                .finally(() => {
                    this.loadingSearch = false;
                    // Keep showing results if there are any, otherwise hide (unless loading/error)
                    this.showResults = (this.searchResults.length > 0 || this.loadingSearch || this.ajaxError) && term.length >= 2;
                });
            },

            addItemFromList(product) {
                if(product) {
                    this.addItemToCart(product);
                    this.searchTerm = ''; // Clear search term
                    this.searchResults = []; // Clear results
                    this.showResults = false; // Hide results dropdown
                    this.selectedIndex = -1;
                    // Optional: Focus back on input after selection
                    this.$nextTick(() => { this.$refs.searchInput?.focus(); }); // Use $refs if needed
                }
            },

             // Keyboard navigation for search results
            selectNextResult() {
                if (this.searchResults.length > 0) {
                    this.selectedIndex = (this.selectedIndex + 1) % this.searchResults.length;
                }
            },
            selectPreviousResult() {
                if (this.searchResults.length > 0) {
                    this.selectedIndex = (this.selectedIndex - 1 + this.searchResults.length) % this.searchResults.length;
                }
            },
            addSelectedResult() {
                 if (this.selectedIndex >= 0 && this.selectedIndex < this.searchResults.length) {
                    this.addItemFromList(this.searchResults[this.selectedIndex]);
                } else if (this.searchResults.length === 1) {
                    // If only one result, Enter adds it directly
                    this.addItemFromList(this.searchResults[0]);
                }
                 // Optional: Handle case where Enter is pressed with no selection / no results
            },


            // --- Cart Methods (Mostly same as before) ---
            addItemToCart(product) { // This is called by addItemFromList now
                const existingItem = this.cart.find(item => item.sku === product.sku);
                if (existingItem) {
                    existingItem.quantity++;
                } else {
                    this.cart.push({
                        sku: product.sku,
                        name: product.name,
                        price: parseFloat(product.price),
                        quantity: 1
                    });
                }
            },

            removeItem(sku) { this.cart = this.cart.filter(item => item.sku !== sku); },

            updateQuantity(sku, change) {
                const item = this.cart.find(item => item.sku === sku);
                if (item) {
                    const newQuantity = item.quantity + change;
                    if (newQuantity > 0) { item.quantity = newQuantity; }
                    else { this.removeItem(sku); }
                }
            },

            clearCart() {
                if(confirm('Are you sure you want to clear the cart?')) {
                    this.cart = [];
                    this.searchTerm = '';
                    this.searchResults = [];
                    this.showResults = false;
                    this.ajaxError = false;
                    this.saleError = false;
                }
            },

            processSale() { // (Logic remains the same as before)
                if (this.cart.length === 0 || this.processingSale) return;
                this.processingSale = true;
                this.saleError = false;
                this.saleErrorMessage = '';
                const saleData = {
                    items: this.cart.map(item => ({ sku: item.sku, quantity: item.quantity, price_at_sale: item.price })),
                    subtotal: this.subtotal, tax_amount: this.taxAmount, total_amount: this.total
                };
                fetch(this.config.processSaleUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(saleData)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.receipt_url) {
                         alert('Sale successful! Transaction ID: ' + data.transaction_id);
                         window.open(data.receipt_url, '_blank');
                         this.clearCart();
                    } else {
                        this.saleError = true;
                        this.saleErrorMessage = data.message || 'Failed to process sale.';
                    }
                })
                .catch(error => {
                    console.error('Error processing sale:', error);
                    this.saleError = true;
                    this.saleErrorMessage = 'An error occurred while communicating with the server.';
                })
                .finally(() => { this.processingSale = false; });
            }
        }
    }
</script>

<?php
// 4. Render Footer
render_footer();
?>