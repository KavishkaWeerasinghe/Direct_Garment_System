<?php
require_once '../../config/database.php';
require_once '../includes/Product.class.php';
require_once '../includes/Manufacturer.class.php';

// Check if user is logged in
if (!Manufacturer::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$manufacturer_id = Manufacturer::getCurrentUserId();

// Debug: Log manufacturer ID
error_log("Current manufacturer ID: " . $manufacturer_id);

// Initialize classes
$productObj = new Product($pdo);

// Debug: Check total products in database for this manufacturer
$debug_sql = "SELECT COUNT(*) as total FROM products WHERE manufacturer_id = :manufacturer_id";
$debug_stmt = $pdo->prepare($debug_sql);
$debug_stmt->execute(['manufacturer_id' => $manufacturer_id]);
$debug_result = $debug_stmt->fetch(PDO::FETCH_ASSOC);
error_log("Total products in database for manufacturer $manufacturer_id: " . $debug_result['total']);

// Debug: Check active products
$debug_sql2 = "SELECT COUNT(*) as active FROM products WHERE manufacturer_id = :manufacturer_id AND is_active = 1";
$debug_stmt2 = $pdo->prepare($debug_sql2);
$debug_stmt2->execute(['manufacturer_id' => $manufacturer_id]);
$debug_result2 = $debug_stmt2->fetch(PDO::FETCH_ASSOC);
error_log("Active products in database for manufacturer $manufacturer_id: " . $debug_result2['active']);

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_product_details':
            $product_id = $_GET['product_id'] ?? 0;
            try {
                $product = $productObj->getProductById($product_id, $manufacturer_id);
                $product_images = $productObj->getProductImages($product_id);
                $product_sizes = $productObj->getProductSizes($product_id);
                $product_colors = $productObj->getProductColors($product_id);
                
                // Get inventory details
                require_once '../includes/Inventory.class.php';
                $inventoryObj = new Inventory($pdo);
                $inventory_details = $inventoryObj->getProductInventory($product_id);
                
                // Calculate inventory summary
                $total_available = 0;
                $stock_status = 'No Inventory';
                
                if (!empty($inventory_details)) {
                    foreach ($inventory_details as $item) {
                        $total_available += $item['available_quantity'] ?? 0;
                    }
                    
                    if ($total_available > 0) {
                        $stock_status = 'In Stock';
                    } else {
                        $stock_status = 'Out of Stock';
                    }
                }
                
                $inventory_summary = [
                    'total_available' => $total_available,
                    'stock_status' => $stock_status
                ];
                
                echo json_encode([
                    'success' => true,
                    'product' => $product,
                    'images' => $product_images,
                    'sizes' => $product_sizes,
                    'colors' => $product_colors,
                    'inventory_summary' => $inventory_summary,
                    'inventory_details' => $inventory_details
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            exit;
            
        case 'delete_product':
            $product_id = $_POST['product_id'] ?? 0;
            try {
                $result = $productObj->deleteProduct($product_id, $manufacturer_id);
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Product deleted successfully!'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to delete product'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error deleting product: ' . $e->getMessage()
                ]);
            }
            exit;
            
        case 'update_inventory':
            $inventory_id = $_POST['inventory_id'] ?? 0;
            $quantity = $_POST['quantity'] ?? 0;
            $reason = $_POST['reason'] ?? 'Manual update';
            
            try {
                require_once '../includes/Inventory.class.php';
                $inventoryObj = new Inventory($pdo);
                $inventoryObj->updateInventory($inventory_id, $quantity, $reason);
                echo json_encode([
                    'success' => true,
                    'message' => 'Inventory updated successfully!'
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error updating inventory: ' . $e->getMessage()
                ]);
            }
            exit;
            

    }
}

// Get products with inventory summary
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? null;
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

// Clear any potential caching issues
$pdo->query("SET SESSION sql_mode = ''");

$products = $productObj->getManufacturerProducts($manufacturer_id, $search, $category_id, $limit, $offset);

// Debug: Log the products being fetched
error_log("Products fetched for manufacturer $manufacturer_id: " . count($products));
foreach ($products as $product) {
    error_log("Product: ID=" . $product['id'] . ", Name=" . $product['name'] . ", Active=" . ($product['is_active'] ?? 'N/A'));
}

// Get product statistics
$product_stats = $productObj->getProductStats($manufacturer_id);

// Debug: Log product stats
error_log("Product stats: " . json_encode($product_stats));

// Get categories for filter
$categories = $productObj->searchCategoriesForAutocomplete('');

// Include header
include '../components/header.php';
?>

<!-- Sidebar -->
<?php include '../components/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-tshirt"></i> My Products</h1>
        <p>Manage your product catalog and listings</p>
    </div>

    <!-- Product Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-tshirt"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $product_stats['total_products']; ?></h3>
                <p>Total Products</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon new-products">
                <i class="fas fa-star"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $product_stats['new_this_week']; ?></h3>
                <p>New This Week</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon recent-products">
                <i class="fas fa-calendar-plus"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $product_stats['new_this_month']; ?></h3>
                <p>New This Month</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon active-products">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $product_stats['total_products']; ?></h3>
                <p>Active Products</p>
            </div>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="search-filter-section">
        <form method="GET" class="search-form">
            <div class="search-input-group">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                       class="form-control" placeholder="Search products...">
                <select name="category_id" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" 
                            <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Search
                </button>
                <a href="add.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add Product
                </a>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="table-container">
        <table class="products-table">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Created Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="6" class="empty-state">
                        <div class="empty-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <h3>No Products Found</h3>
                        <p>Start by adding your first product to the inventory.</p>
                        <a href="add.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Product
                        </a>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td class="product-image">
                        <?php if (!empty($product['main_image'])): ?>
                        <img src="<?php echo htmlspecialchars($product['main_image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHJlY3Qgd2lkdGg9IjYwIiBoZWlnaHQ9IjYwIiBmaWxsPSIjRjVGNUY1Ii8+Cjx0ZXh0IHg9IjMwIiB5PSIzMCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEwIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SW1hZ2U8L3RleHQ+Cjwvc3ZnPgo='">
                        <?php else: ?>
                        <div class="no-image">
                            <i class="fas fa-image"></i>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="product-name">
                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                        <p class="product-description">
                            <?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>
                            <?php if (strlen($product['description']) > 100): ?>...<?php endif; ?>
                        </p>
                    </td>
                    <td class="product-category">
                        <span class="category-badge">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </span>
                        <?php if ($product['subcategory_name']): ?>
                        <br><small><?php echo htmlspecialchars($product['subcategory_name']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="product-created">
                        <div class="created-info">
                            <span class="created-date">
                                <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                            </span>
                            <span class="created-time">
                                <?php echo date('g:i A', strtotime($product['created_at'])); ?>
                            </span>
                        </div>
                    </td>
                    <td class="product-status">
                        <?php 
                        $status_class = $product['is_active'] ? 'status-active' : 'status-inactive';
                        $status_icon = $product['is_active'] ? 'fas fa-check-circle' : 'fas fa-times-circle';
                        $status_text = $product['is_active'] ? 'Active' : 'Inactive';
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <i class="<?php echo $status_icon; ?>"></i>
                            <?php echo $status_text; ?>
                        </span>
                    </td>
                    <td class="product-actions">
                        <div class="action-buttons">
                            <button type="button" class="btn btn-sm btn-info" 
                                    onclick="viewProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <a href="add.php?edit=<?php echo $product['id']; ?>" 
                               class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" 
                                    onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if (!empty($products)): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_id; ?>" 
           class="btn btn-secondary">
            <i class="fas fa-chevron-left"></i> Previous
        </a>
        <?php endif; ?>
        
        <span class="page-info">Page <?php echo $page; ?></span>
        
        <?php if (count($products) == $limit): ?>
        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category_id=<?php echo $category_id; ?>" 
           class="btn btn-secondary">
            Next <i class="fas fa-chevron-right"></i>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Product Details Modal -->
<div id="productModal" class="modal-overlay" style="display: none;">
    <div class="modal-content large-modal">
        <div class="modal-header">
            <h3>Product Details</h3>
            <button type="button" class="close-btn" onclick="closeProductModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="modal-body" id="productModalBody">
            <!-- Product details will be loaded here -->
        </div>
    </div>
</div>

<!-- Include footer -->
<?php include '../components/footer.php'; ?>

<!-- Add custom CSS and JS -->
<link href="<?php echo MANUFACTURER_BASE_URL; ?>assets/css/product-list.css" rel="stylesheet">
<script src="<?php echo MANUFACTURER_BASE_URL; ?>assets/js/product-list.js"></script> 