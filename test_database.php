<?php
require_once 'config/database.php';
require_once 'manufacture/includes/Product.class.php';
require_once 'manufacture/includes/Inventory.class.php';
require_once 'manufacture/includes/Manufacturer.class.php';

// Start session to get manufacturer ID
session_start();

// Get manufacturer ID (assuming it's stored in session)
$manufacturer_id = $_SESSION['manufacturer_id'] ?? 7; // Default to 7 for testing

echo "<h2>Database Test Results</h2>";
echo "<p>Manufacturer ID: $manufacturer_id</p>";

// Initialize classes
$productObj = new Product($pdo);
$inventoryObj = new Inventory($pdo);

// Test 1: Check all products in database
echo "<h3>1. All Products in Database</h3>";
$all_products_sql = "SELECT id, name, is_active, created_at FROM products WHERE manufacturer_id = :manufacturer_id ORDER BY created_at DESC";
$stmt = $pdo->prepare($all_products_sql);
$stmt->execute(['manufacturer_id' => $manufacturer_id]);
$all_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Active</th><th>Created</th></tr>";
foreach ($all_products as $product) {
    echo "<tr>";
    echo "<td>" . $product['id'] . "</td>";
    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
    echo "<td>" . ($product['is_active'] ? 'Yes' : 'No') . "</td>";
    echo "<td>" . $product['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test 2: Check active products only
echo "<h3>2. Active Products Only</h3>";
$active_products_sql = "SELECT id, name, is_active, created_at FROM products WHERE manufacturer_id = :manufacturer_id AND is_active = 1 ORDER BY created_at DESC";
$stmt = $pdo->prepare($active_products_sql);
$stmt->execute(['manufacturer_id' => $manufacturer_id]);
$active_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Active</th><th>Created</th></tr>";
foreach ($active_products as $product) {
    echo "<tr>";
    echo "<td>" . $product['id'] . "</td>";
    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
    echo "<td>" . ($product['is_active'] ? 'Yes' : 'No') . "</td>";
    echo "<td>" . $product['created_at'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test 3: Test getManufacturerProducts method
echo "<h3>3. getManufacturerProducts Method Results</h3>";
$method_products = $productObj->getManufacturerProducts($manufacturer_id, '', null, 20, 0);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Category</th><th>Main Image</th></tr>";
foreach ($method_products as $product) {
    echo "<tr>";
    echo "<td>" . $product['id'] . "</td>";
    echo "<td>" . htmlspecialchars($product['name']) . "</td>";
    echo "<td>" . htmlspecialchars($product['category_name'] ?? 'N/A') . "</td>";
    echo "<td>" . htmlspecialchars($product['main_image'] ?? 'N/A') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test 4: Check inventory stats
echo "<h3>4. Inventory Stats</h3>";
$inventory_stats = $inventoryObj->getInventoryStats($manufacturer_id);
echo "<pre>" . print_r($inventory_stats, true) . "</pre>";

// Test 5: Check categories
echo "<h3>5. Categories Available</h3>";
$categories = $productObj->searchCategoriesForAutocomplete('');
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th></tr>";
foreach ($categories as $category) {
    echo "<tr>";
    echo "<td>" . $category['id'] . "</td>";
    echo "<td>" . htmlspecialchars($category['name']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>6. Error Log Check</h3>";
echo "<p>Check your PHP error log for any error messages related to the list.php page.</p>";
echo "<p>Common error log locations:</p>";
echo "<ul>";
echo "<li>XAMPP: C:\\xampp\\php\\logs\\php_error_log</li>";
echo "<li>Apache: C:\\xampp\\apache\\logs\\error.log</li>";
echo "</ul>";
?> 