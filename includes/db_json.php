<?php
/**
 * Simple POS System JSON Database Functions
 *
 * Handles reading from and writing to JSON data files.
 */

// Ensure configuration is loaded
if (file_exists(__DIR__ . '/../config/config.php')) {
    require_once __DIR__ . '/../config/config.php';
} else {
    die('Critical Error: config.php not found!');
}
// Ensure helpers are loaded (might be needed indirectly, e.g., for date strings)
if (file_exists(__DIR__ . '/helpers.php')) {
    require_once __DIR__ . '/helpers.php';
} else {
    die('Critical Error: helpers.php not found!');
}


// --- Internal Helper Functions ---

/**
 * Reads data from a JSON file.
 * Includes basic file existence check and JSON decoding.
 * Uses shared lock for reading to allow multiple reads simultaneously.
 *
 * @param string $file_path The full path to the JSON file.
 * @return array|null Returns the decoded array or null on failure.
 */
function read_json_file(string $file_path): ?array
{
    if (!file_exists($file_path)) {
        // If the file doesn't exist, return an empty array,
        // especially useful for sales files starting empty.
        return [];
    }

    $file_handle = fopen($file_path, 'r');
    if (!$file_handle) {
        error_log("Error: Could not open file for reading: " . $file_path);
        return null;
    }

    // Shared lock - allow other reads, block exclusive locks (writes)
    if (flock($file_handle, LOCK_SH)) {
        $json_data = fread($file_handle, filesize($file_path) ?: 1); // Read content (handle empty file)
        flock($file_handle, LOCK_UN); // Release lock
    } else {
        error_log("Error: Could not acquire shared lock for reading: " . $file_path);
        fclose($file_handle);
        return null;
    }

    fclose($file_handle);

    if ($json_data === false || $json_data === '') {
        // Handle empty file or read error
        return [];
    }

    $data = json_decode($json_data, true); // Decode as associative array

    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error decoding JSON from file: " . $file_path . " - " . json_last_error_msg());
        return null; // Return null to indicate a decoding error
    }

    return $data ?? []; // Return data or empty array if decoding resulted in null (e.g., empty file with 'null' content)
}

/**
 * Writes data to a JSON file.
 * Encodes data to JSON and uses exclusive lock for writing.
 *
 * @param string $file_path The full path to the JSON file.
 * @param array $data The array data to encode and write.
 * @return bool True on success, false on failure.
 */
function write_json_file(string $file_path, array $data): bool
{
    $file_handle = fopen($file_path, 'w'); // Open for writing, create if not exists, truncate content
    if (!$file_handle) {
        error_log("Error: Could not open file for writing: " . $file_path);
        return false;
    }

    // Exclusive lock - prevent other processes from reading or writing
    if (flock($file_handle, LOCK_EX)) {
        $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Error encoding data to JSON for file: " . $file_path . " - " . json_last_error_msg());
            flock($file_handle, LOCK_UN); // Release lock before returning
            fclose($file_handle);
            return false;
        }

        $write_result = fwrite($file_handle, $json_data);
        fflush($file_handle); // Ensure buffer is written to disk
        flock($file_handle, LOCK_UN); // Release lock

        if ($write_result === false) {
            error_log("Error: Failed to write to file: " . $file_path);
            fclose($file_handle);
            return false;
        }
    } else {
        error_log("Error: Could not acquire exclusive lock for writing: " . $file_path);
        fclose($file_handle);
        return false;
    }

    fclose($file_handle);
    return true;
}

// --- Product Data Functions ---

/**
 * Gets all products from the products JSON file.
 *
 * @return array An array of product arrays, or empty array on failure/no products.
 */
function get_products(): array
{
    $products = read_json_file(PRODUCTS_FILE);
    return $products ?? []; // Return empty array if read failed or file was empty/invalid
}

/**
 * Saves the entire products array back to the JSON file.
 * Used after adding, updating, or deleting products.
 *
 * @param array $products_array The complete array of product data.
 * @return bool True on success, false on failure.
 */
function save_products(array $products_array): bool
{
    return write_json_file(PRODUCTS_FILE, $products_array);
}

/**
 * Finds a product by its SKU.
 *
 * @param string $sku The SKU to search for.
 * @param array|null $products Optional: Provide the products array to search in (optimization).
 * If null, reads products from the file.
 * @return array|null The product array if found, otherwise null.
 */
function find_product_by_sku(string $sku, ?array $products = null): ?array
{
    if ($products === null) {
        $products = get_products();
    }

    foreach ($products as $product) {
        // Case-insensitive comparison might be useful depending on requirements
        if (isset($product['sku']) && strcasecmp($product['sku'], $sku) === 0) {
            return $product;
        }
    }
    return null;
}


// --- Sales Data Functions ---

/**
 * Constructs the full path for a daily sales file.
 *
 * @param string $date_string Date in 'Y-m-d' format.
 * @return string The full path to the sales file.
 */
function get_sales_file_path(string $date_string): string
{
    return sprintf(SALES_FILE_PATTERN, $date_string);
}

/**
 * Gets all sales transactions for a specific day.
 *
 * @param string|null $date_string Date in 'Y-m-d' format. If null, uses today's date.
 * @return array An array of sales transaction arrays, or empty array on failure/no sales.
 */
function get_sales_for_day(?string $date_string = null): array
{
    if ($date_string === null) {
        $date_string = get_sales_file_date_string(); // Get today's date string from helpers
    }
    $file_path = get_sales_file_path($date_string);
    $sales = read_json_file($file_path);
    return $sales ?? []; // Return empty array if read failed or file was empty/invalid
}

/**
 * Adds a new sales transaction to the appropriate daily sales file.
 * Appends to existing data and handles file creation.
 *
 * @param array $transaction_data The associative array representing the sale.
 * @return bool True on success, false on failure.
 */
function add_sale_transaction(array $transaction_data): bool
{
    $date_string = get_sales_file_date_string(); // Sale always recorded for today
    $file_path = get_sales_file_path($date_string);

    // Need to read existing data, append, then write back (within a lock)

    $file_handle = fopen($file_path, 'c+'); // Open for reading & writing, create if not exists, pointer at beginning
    if (!$file_handle) {
        error_log("Error: Could not open file for read/write: " . $file_path);
        return false;
    }

    // Exclusive lock for the read-modify-write operation
    if (flock($file_handle, LOCK_EX)) {
        // Read existing content
        $json_data = stream_get_contents($file_handle);
        $sales_today = [];
        if ($json_data !== false && $json_data !== '') {
            $sales_today = json_decode($json_data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("Error decoding existing sales data: " . $file_path . " - " . json_last_error_msg());
                flock($file_handle, LOCK_UN);
                fclose($file_handle);
                return false; // Avoid overwriting corrupted data
            }
            // Ensure it's an array
             $sales_today = is_array($sales_today) ? $sales_today : [];
        }

        // Append the new transaction
        $sales_today[] = $transaction_data;

        // Prepare JSON data
        $new_json_data = json_encode($sales_today, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
         if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Error encoding updated sales data: " . $file_path . " - " . json_last_error_msg());
            flock($file_handle, LOCK_UN);
            fclose($file_handle);
            return false;
        }

        // Move pointer to beginning and truncate the file before writing
        rewind($file_handle);
        ftruncate($file_handle, 0);

        // Write the updated data
        $write_result = fwrite($file_handle, $new_json_data);
        fflush($file_handle); // Ensure buffer is written

        flock($file_handle, LOCK_UN); // Release lock

        if ($write_result === false) {
             error_log("Error: Failed to write updated sales data to file: " . $file_path);
             fclose($file_handle);
             return false;
        }

    } else {
        error_log("Error: Could not acquire exclusive lock for sales update: " . $file_path);
        fclose($file_handle);
        return false;
    }

    fclose($file_handle);
    return true;
}

?>