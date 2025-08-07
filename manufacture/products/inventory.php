<?php
require_once '../../config/database.php';
require_once '../includes/Inventory.class.php';
require_once '../includes/Product.class.php';
require_once '../includes/Category.class.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['manufacturer_id'])) {
    header('Location: login.php');
    exit();
}

$manufacturer_id = $_SESSION['manufacturer_id'];
$inventoryObj = new Inventory($pdo);
$productObj = new Product($pdo);
$categoryObj = new Category($pdo);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_inventory':
            $product_id = $_POST['product_id'] ?? '';
            $size_id = $_POST['size_id'] ?? '';
            $color_id = $_POST['color_id'] ?? '';
            $quantity = $_POST['quantity'] ?? 0;
            $low_stock_threshold = $_POST['low_stock_threshold'] ?? 10;
            
            try {
                $inventory_id = $inventoryObj->addInventory($product_id, $size_id, $color_id, $quantity, $low_stock_threshold);
                echo json_encode(['success' => true, 'message' => 'Inventory added successfully', 'inventory_id' => $inventory_id]);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'update_inventory':
            $inventory_id = $_POST['inventory_id'] ?? '';
            $new_quantity = $_POST['quantity'] ?? 0;
            $reason = $_POST['reason'] ?? 'Manual update';
            
            try {
                $inventoryObj->updateInventory($inventory_id, $new_quantity, $reason);
                echo json_encode(['success' => true, 'message' => 'Inventory updated successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();
            

            
        case 'get_product_sizes':
            $product_id = $_POST['product_id'] ?? '';
            $sizes = $productObj->getProductSizes($product_id);
            echo json_encode(['success' => true, 'sizes' => $sizes]);
            exit();
            
        case 'get_product_colors':
            $product_id = $_POST['product_id'] ?? '';
            $colors = $productObj->getProductColors($product_id);
            echo json_encode(['success' => true, 'colors' => $colors]);
            exit();
            
        case 'update_threshold':
            $inventory_id = $_POST['inventory_id'] ?? '';
            $threshold = $_POST['threshold'] ?? 10;
            
            try {
                $sql = "UPDATE inventory SET low_stock_threshold = :threshold, updated_at = NOW() WHERE id = :inventory_id";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    'threshold' => $threshold,
                    'inventory_id' => $inventory_id
                ]);
                
                if ($result) {
                    echo json_encode(['success' => true, 'message' => 'Threshold updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update threshold']);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Error updating threshold: ' . $e->getMessage()]);
            }
            exit();
            
        case 'reserve_stock':
            $inventory_id = $_POST['inventory_id'] ?? '';
            $reserve_quantity = $_POST['reserve_quantity'] ?? 0;
            $reason = $_POST['reason'] ?? 'Manual reservation';
            
            try {
                $inventoryObj->reserveInventory($inventory_id, $reserve_quantity, $reason);
                echo json_encode(['success' => true, 'message' => 'Stock reserved successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();
            
        case 'release_stock':
            $inventory_id = $_POST['inventory_id'] ?? '';
            $release_quantity = $_POST['release_quantity'] ?? 0;
            $reason = $_POST['reason'] ?? 'Manual release';
            
            try {
                $inventoryObj->releaseReservedInventory($inventory_id, $release_quantity, $reason);
                echo json_encode(['success' => true, 'message' => 'Stock released successfully']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            exit();
    }
}

// Get search term and filters
$search = $_GET['search'] ?? '';
$category_id = $_GET['category_id'] ?? '';
$stock_status = $_GET['stock_status'] ?? '';

// Get inventory statistics
$inventory_stats = $inventoryObj->getInventoryStats($manufacturer_id);



// Get manufacturer's products
$products = $productObj->getManufacturerProducts($manufacturer_id, $search, $category_id, 50, 0);

// Get categories for filter
$categories = $categoryObj->getCategoriesForDropdown();

// Include header
include '../components/header.php';
?>

<!-- Sidebar -->
<?php include '../components/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-warehouse"></i> Inventory Management</h1>
        <p>Manage your product inventory and stock levels</p>
    </div>

    <!-- Inventory Statistics -->
    <?php 
    // Calculate real-time inventory statistics
    $total_products = count($products);
    $in_stock_count = 0;
    $low_stock_count = 0;
    $out_of_stock_count = 0;
    $total_inventory_records = 0;
    
    foreach ($products as $product) {
        $inventory_details = $inventoryObj->getProductInventory($product['id']);
        $total_inventory_records += count($inventory_details);
        
        if (!empty($inventory_details)) {
            $product_has_stock = false;
            $product_low_stock = false;
            
            foreach ($inventory_details as $inventory) {
                if ($inventory['available_quantity'] > 0) {
                    $product_has_stock = true;
                }
                if ($inventory['available_quantity'] <= $inventory['low_stock_threshold'] && $inventory['available_quantity'] > 0) {
                    $product_low_stock = true;
                }
            }
            
            if ($product_has_stock) {
                if ($product_low_stock) {
                    $low_stock_count++;
                } else {
                    $in_stock_count++;
                }
            } else {
                $out_of_stock_count++;
            }
        } else {
            $out_of_stock_count++;
        }
    }
    ?>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_products; ?></h3>
                <p>Total Products</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon in-stock">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $in_stock_count; ?></h3>
                <p>In Stock</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon low-stock">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $low_stock_count; ?></h3>
                <p>Low Stock</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon out-of-stock">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $out_of_stock_count; ?></h3>
                <p>Out of Stock</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-list"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $total_inventory_records; ?></h3>
                <p>Inventory Records</p>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="filters-section">
        <form method="GET" class="filters-form">
            <div class="filter-group">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>" class="form-control">
            </div>
            <div class="filter-group">
                <select name="category_id" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>" <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <select name="stock_status" class="form-control">
                    <option value="">All Stock Status</option>
                    <option value="In Stock" <?php echo $stock_status == 'In Stock' ? 'selected' : ''; ?>>In Stock</option>
                    <option value="Low Stock" <?php echo $stock_status == 'Low Stock' ? 'selected' : ''; ?>>Low Stock</option>
                    <option value="Out of Stock" <?php echo $stock_status == 'Out of Stock' ? 'selected' : ''; ?>>Out of Stock</option>
                    <option value="No Inventory" <?php echo $stock_status == 'No Inventory' ? 'selected' : ''; ?>>No Inventory</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addInventoryModal">
                    <i class="fas fa-plus"></i> Add Inventory
                </button>
            </div>
        </form>
    </div>



    <!-- Products Inventory Table -->
    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Size</th>
                    <th>Color</th>
                    <th>Current Stock</th>
                    <th>Available</th>
                    <th>Reserved</th>
                    <th>Low Stock Threshold</th>
                    <th>Stock Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center">No products found</td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $has_inventory = false;
                    $product_count = 0;
                    foreach ($products as $product): 
                        $inventory_details = $inventoryObj->getProductInventory($product['id']);
                        if (!empty($inventory_details)):
                            $has_inventory = true;
                            $product_count++;
                            $inventory_count = 0;
                            foreach ($inventory_details as $inventory):
                                $inventory_count++;
                                $is_first_row = ($inventory_count === 1);
                    ?>
                        <tr class="<?php echo $is_first_row ? 'product-first-row' : 'product-subsequent-row'; ?>">
                            <td>
                                <?php if ($is_first_row): ?>
                                <div class="product-info">
                                    <div class="product-details">
                                        <h4><?php echo htmlspecialchars($product['name']); ?></h4>
                                        <p class="product-id">ID: <?php echo $product['id']; ?></p>
                                    </div>
                                </div>
                                <?php else: ?>
                                <div class="product-info-subsequent">
                                    <span class="product-continuation">↳ <?php echo htmlspecialchars($product['name']); ?></span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="size-badge"><?php echo htmlspecialchars($inventory['size_name']); ?></span>
                                <?php if (!$is_first_row): ?>
                                <small class="variant-indicator">(Variant <?php echo $inventory_count; ?>)</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="color-info">
                                    <span class="color-swatch" style="background-color: <?php echo htmlspecialchars($inventory['color_code']); ?>"></span>
                                    <span><?php echo htmlspecialchars($inventory['color_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <strong class="stock-quantity <?php echo $inventory['available_quantity'] <= $inventory['low_stock_threshold'] ? 'low-stock' : 'normal-stock'; ?>">
                                    <?php echo $inventory['quantity']; ?>
                                </strong>
                            </td>
                            <td>
                                <span class="available-quantity <?php echo $inventory['available_quantity'] <= $inventory['low_stock_threshold'] ? 'low-stock' : 'normal-stock'; ?>">
                                    <?php echo $inventory['available_quantity']; ?>
                                </span>
                            </td>
                            <td>
                                <span class="reserved-quantity"><?php echo $inventory['reserved_quantity']; ?></span>
                            </td>
                            <td>
                                <span class="threshold-value"><?php echo $inventory['low_stock_threshold']; ?></span>
                            </td>
                                                         <td>
                                 <div class="stock-actions">
                                     <button class="btn btn-sm btn-success" onclick="addStock(<?php echo $inventory['id']; ?>, <?php echo $inventory['quantity']; ?>)" title="Add Stock">
                                         <i class="fas fa-plus"></i>
                                     </button>
                                     <button class="btn btn-sm btn-warning" onclick="removeStock(<?php echo $inventory['id']; ?>, <?php echo $inventory['quantity']; ?>)" title="Remove Stock">
                                         <i class="fas fa-minus"></i>
                                     </button>
                                     <button class="btn btn-sm btn-primary" onclick="reserveStock(<?php echo $inventory['id']; ?>, <?php echo $inventory['reserved_quantity']; ?>)" title="Reserve Stock">
                                         <i class="fas fa-lock"></i>
                                     </button>
                                     <button class="btn btn-sm btn-secondary" onclick="releaseStock(<?php echo $inventory['id']; ?>, <?php echo $inventory['reserved_quantity']; ?>)" title="Release Stock">
                                         <i class="fas fa-unlock"></i>
                                     </button>
                                     <button class="btn btn-sm btn-info" onclick="editThreshold(<?php echo $inventory['id']; ?>, <?php echo $inventory['low_stock_threshold']; ?>)" title="Edit Threshold">
                                         <i class="fas fa-cog"></i>
                                     </button>
                                 </div>
                             </td>
                        </tr>
                    <?php 
                            endforeach;
                        endif;
                    endforeach; 
                    
                    if (!$has_inventory):
                    ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="no-inventory-message">
                                    <i class="fas fa-box-open"></i>
                                    <h4>No Inventory Records</h4>
                                    <p>Start by adding inventory for your products using the "Add Inventory" button above.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Inventory Modal -->
<div class="modal fade" id="addInventoryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Inventory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addInventoryForm">
                    <input type="hidden" id="selected_product_id" name="product_id">
                    
                    <div class="form-group">
                        <label for="product_select">Select Product</label>
                        <select id="product_select" class="form-control" required>
                            <option value="">Choose a product...</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="size_select">Size</label>
                        <select id="size_select" name="size_id" class="form-control" required>
                            <option value="">Select size...</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="color_select">Color</label>
                        <select id="color_select" name="color_id" class="form-control" required>
                            <option value="">Select color...</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="quantity">Quantity</label>
                        <input type="number" id="quantity" name="quantity" class="form-control" min="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="low_stock_threshold">Low Stock Threshold</label>
                        <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-control" min="1" value="10">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitInventory()">Add Inventory</button>
            </div>
        </div>
    </div>
</div>



<!-- Add Stock Modal -->
<div class="modal fade" id="addStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addStockForm">
                    <input type="hidden" id="add_stock_inventory_id" name="inventory_id">
                    <input type="hidden" id="add_stock_current_quantity" name="current_quantity">
                    
                    <div class="form-group">
                        <label for="add_stock_quantity">Quantity to Add</label>
                        <input type="number" id="add_stock_quantity" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="add_stock_reason">Reason (Optional)</label>
                        <input type="text" id="add_stock_reason" name="reason" class="form-control" placeholder="Manual stock addition">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitAddStock()">Add Stock</button>
            </div>
        </div>
    </div>
</div>

<!-- Remove Stock Modal -->
<div class="modal fade" id="removeStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Remove Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="removeStockForm">
                    <input type="hidden" id="remove_stock_inventory_id" name="inventory_id">
                    <input type="hidden" id="remove_stock_current_quantity" name="current_quantity">
                    
                    <div class="form-group">
                        <label for="remove_stock_quantity">Quantity to Remove</label>
                        <input type="number" id="remove_stock_quantity" name="quantity" class="form-control" min="1" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="remove_stock_reason">Reason (Optional)</label>
                        <input type="text" id="remove_stock_reason" name="reason" class="form-control" placeholder="Manual stock removal">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="submitRemoveStock()">Remove Stock</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Threshold Modal -->
<div class="modal fade" id="editThresholdModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Low Stock Threshold</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editThresholdForm">
                    <input type="hidden" id="edit_threshold_inventory_id" name="inventory_id">
                    
                    <div class="form-group">
                        <label for="edit_threshold_value">Low Stock Threshold</label>
                        <input type="number" id="edit_threshold_value" name="threshold" class="form-control" min="0" required>
                        <small class="form-text text-muted">Set the minimum stock level before low stock alerts are triggered.</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-info" onclick="submitEditThreshold()">Update Threshold</button>
            </div>
        </div>
    </div>
</div>

<!-- Reserve Stock Modal -->
<div class="modal fade" id="reserveStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reserve Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="reserveStockForm">
                    <input type="hidden" id="reserve_stock_inventory_id" name="inventory_id">
                    <input type="hidden" id="reserve_stock_current_reserved" name="current_reserved">
                    
                    <div class="form-group">
                        <label for="reserve_stock_quantity">Quantity to Reserve</label>
                        <input type="number" id="reserve_stock_quantity" name="reserve_quantity" class="form-control" min="1" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="reserve_stock_reason">Reason (Optional)</label>
                        <input type="text" id="reserve_stock_reason" name="reason" class="form-control" placeholder="Manual stock reservation">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitReserveStock()">Reserve Stock</button>
            </div>
        </div>
    </div>
</div>

<!-- Release Stock Modal -->
<div class="modal fade" id="releaseStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Release Reserved Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="releaseStockForm">
                    <input type="hidden" id="release_stock_inventory_id" name="inventory_id">
                    <input type="hidden" id="release_stock_current_reserved" name="current_reserved">
                    
                    <div class="form-group">
                        <label for="release_stock_quantity">Quantity to Release</label>
                        <input type="number" id="release_stock_quantity" name="release_quantity" class="form-control" min="1" value="1" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="release_stock_reason">Reason (Optional)</label>
                        <input type="text" id="release_stock_reason" name="reason" class="form-control" placeholder="Manual stock release">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-secondary" onclick="submitReleaseStock()">Release Stock</button>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Custom JS -->
<script>
// Initialize modals when document is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing modals...');
    
    // Initialize Bootstrap modals
    var modals = document.querySelectorAll('.modal');
    modals.forEach(function(modal) {
        console.log('Initializing modal:', modal.id);
        new bootstrap.Modal(modal);
    });
    
    // Prevent form submission when pressing Enter in search form
    const filtersForm = document.querySelector('.filters-form');
    if (filtersForm) {
        filtersForm.addEventListener('submit', function(e) {
            e.preventDefault();
            return false;
        });
    }
    
    // Add event listener to Add Inventory button
    const addInventoryBtn = document.querySelector('[data-bs-target="#addInventoryModal"]');
    if (addInventoryBtn) {
        console.log('Found Add Inventory button, adding click listener');
        addInventoryBtn.addEventListener('click', function(e) {
            console.log('Add Inventory button clicked');
            e.preventDefault();
            e.stopPropagation();
        });
    } else {
        console.log('Add Inventory button not found');
    }
});

// Product selection change
document.getElementById('product_select').addEventListener('change', function() {
    const productId = this.value;
    if (productId) {
        loadProductSizes(productId);
        loadProductColors(productId);
        document.getElementById('selected_product_id').value = productId;
    }
});

// Load product sizes
function loadProductSizes(productId) {
    fetch('inventory.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_product_sizes&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const sizeSelect = document.getElementById('size_select');
            sizeSelect.innerHTML = '<option value="">Select size...</option>';
            data.sizes.forEach(size => {
                sizeSelect.innerHTML += `<option value="${size.id}">${size.size_name}</option>`;
            });
        }
    });
}

// Load product colors
function loadProductColors(productId) {
    fetch('inventory.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_product_colors&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const colorSelect = document.getElementById('color_select');
            colorSelect.innerHTML = '<option value="">Select color...</option>';
            data.colors.forEach(color => {
                colorSelect.innerHTML += `<option value="${color.id}">${color.color_name}</option>`;
            });
        }
    });
}

// Submit inventory
function submitInventory() {
    const form = document.getElementById('addInventoryForm');
    const formData = new FormData(form);
    formData.append('action', 'add_inventory');
    
    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Show loading state
    const submitBtn = document.querySelector('#addInventoryModal .btn-primary');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
    submitBtn.disabled = true;
    
    fetch('inventory.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Inventory added successfully!', 'success');
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addInventoryModal'));
            if (modal) {
                modal.hide();
            }
            location.reload();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
    })
    .finally(() => {
        // Reset button state
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// View inventory details
function viewInventory(productId) {
    fetch('inventory.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_product_inventory&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayInventoryDetails(data.inventory);
            new bootstrap.Modal(document.getElementById('viewInventoryModal')).show();
        }
    });
}

// Display inventory details
function displayInventoryDetails(inventory) {
    const container = document.getElementById('inventoryDetails');
    
    if (inventory.length === 0) {
        container.innerHTML = '<p class="text-center">No inventory records found for this product.</p>';
        return;
    }
    
    let html = `
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Size</th>
                        <th>Color</th>
                        <th>Total Quantity</th>
                        <th>Available</th>
                        <th>Reserved</th>
                        <th>Low Stock Threshold</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    inventory.forEach(item => {
        const stockClass = item.available_quantity <= item.low_stock_threshold ? 'text-warning' : 'text-success';
        html += `
            <tr>
                <td>${item.size_name}</td>
                <td>
                    <span class="color-swatch" style="background-color: ${item.color_code}"></span>
                    ${item.color_name}
                </td>
                <td>${item.quantity}</td>
                <td class="${stockClass}"><strong>${item.available_quantity}</strong></td>
                <td>${item.reserved_quantity}</td>
                <td>${item.low_stock_threshold}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editInventory(${item.id}, ${item.quantity})">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
                </tbody>
            </table>
        </div>
    `;
    
    container.innerHTML = html;
}

// Add inventory for specific product
function addInventory(productId) {
    document.getElementById('product_select').value = productId;
    document.getElementById('selected_product_id').value = productId;
    loadProductSizes(productId);
    loadProductColors(productId);
    new bootstrap.Modal(document.getElementById('addInventoryModal')).show();
}

// Add stock
function addStock(inventoryId, currentQuantity) {
    document.getElementById('add_stock_inventory_id').value = inventoryId;
    document.getElementById('add_stock_current_quantity').value = currentQuantity;
    document.getElementById('add_stock_quantity').value = '1';
    document.getElementById('add_stock_reason').value = 'Manual stock addition';
    new bootstrap.Modal(document.getElementById('addStockModal')).show();
}

// Remove stock
function removeStock(inventoryId, currentQuantity) {
    document.getElementById('remove_stock_inventory_id').value = inventoryId;
    document.getElementById('remove_stock_current_quantity').value = currentQuantity;
    document.getElementById('remove_stock_quantity').value = '1';
    document.getElementById('remove_stock_reason').value = 'Manual stock removal';
    new bootstrap.Modal(document.getElementById('removeStockModal')).show();
}

// Edit threshold
function editThreshold(inventoryId, currentThreshold) {
    document.getElementById('edit_threshold_inventory_id').value = inventoryId;
    document.getElementById('edit_threshold_value').value = currentThreshold;
    new bootstrap.Modal(document.getElementById('editThresholdModal')).show();
}

// Submit add stock
function submitAddStock() {
    const form = document.getElementById('addStockForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const inventoryId = formData.get('inventory_id');
    const currentQuantity = parseInt(formData.get('current_quantity'));
    const addQuantity = parseInt(formData.get('quantity'));
    const reason = formData.get('reason') || 'Manual stock addition';
    
    if (isNaN(addQuantity) || addQuantity <= 0) {
        showNotification('Please enter a valid positive number', 'error');
        return;
    }
    
    const newQuantity = currentQuantity + addQuantity;
    updateInventoryQuantity(inventoryId, newQuantity, reason);
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('addStockModal')).hide();
}

// Submit remove stock
function submitRemoveStock() {
    const form = document.getElementById('removeStockForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const inventoryId = formData.get('inventory_id');
    const currentQuantity = parseInt(formData.get('current_quantity'));
    const removeQuantity = parseInt(formData.get('quantity'));
    const reason = formData.get('reason') || 'Manual stock removal';
    
    if (isNaN(removeQuantity) || removeQuantity <= 0) {
        showNotification('Please enter a valid positive number', 'error');
        return;
    }
    
    if (removeQuantity > currentQuantity) {
        showNotification('Cannot remove more stock than available', 'error');
        return;
    }
    
    const newQuantity = currentQuantity - removeQuantity;
    updateInventoryQuantity(inventoryId, newQuantity, reason);
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('removeStockModal')).hide();
}

// Submit edit threshold
function submitEditThreshold() {
    const form = document.getElementById('editThresholdForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const inventoryId = formData.get('inventory_id');
    const newThreshold = parseInt(formData.get('threshold'));
    
    if (isNaN(newThreshold) || newThreshold < 0) {
        showNotification('Please enter a valid non-negative number', 'error');
        return;
    }
    
    updateInventoryThreshold(inventoryId, newThreshold);
    
    // Close modal
    bootstrap.Modal.getInstance(document.getElementById('editThresholdModal')).hide();
}

// Update inventory threshold
function updateInventoryThreshold(inventoryId, newThreshold) {
    const formData = new FormData();
    formData.append('action', 'update_threshold');
    formData.append('inventory_id', inventoryId);
    formData.append('threshold', newThreshold);
    
    fetch('inventory.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Threshold updated successfully!', 'success');
            location.reload();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
    });
}

// Update inventory quantity
function updateInventoryQuantity(inventoryId, newQuantity, reason) {
    const formData = new FormData();
    formData.append('action', 'update_inventory');
    formData.append('inventory_id', inventoryId);
    formData.append('quantity', newQuantity);
    formData.append('reason', reason);
    
    fetch('inventory.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Stock updated successfully!', 'success');
            location.reload();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
    });
}

// Show low stock products
function showLowStockProducts() {
    // This could open a modal or navigate to a filtered view
    window.location.href = 'inventory.php?stock_status=Low Stock';
}

// Show notification function (like in add.php)
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type}`;
    notification.innerHTML = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.style.maxWidth = '500px';

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Reserve stock
function reserveStock(inventoryId, currentReserved) {
    document.getElementById('reserve_stock_inventory_id').value = inventoryId;
    document.getElementById('reserve_stock_current_reserved').value = currentReserved;
    document.getElementById('reserve_stock_quantity').value = '1';
    document.getElementById('reserve_stock_reason').value = 'Manual stock reservation';
    new bootstrap.Modal(document.getElementById('reserveStockModal')).show();
}

// Release stock
function releaseStock(inventoryId, currentReserved) {
    document.getElementById('release_stock_inventory_id').value = inventoryId;
    document.getElementById('release_stock_current_reserved').value = currentReserved;
    document.getElementById('release_stock_quantity').value = '1';
    document.getElementById('release_stock_reason').value = 'Manual stock release';
    new bootstrap.Modal(document.getElementById('releaseStockModal')).show();
}

// Submit reserve stock
function submitReserveStock() {
    const form = document.getElementById('reserveStockForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const inventoryId = formData.get('inventory_id');
    const reserveQuantity = parseInt(formData.get('reserve_quantity'));
    const reason = formData.get('reason') || 'Manual stock reservation';
    
    if (isNaN(reserveQuantity) || reserveQuantity <= 0) {
        showNotification('Please enter a valid positive number', 'error');
        return;
    }
    
    const data = new FormData();
    data.append('action', 'reserve_stock');
    data.append('inventory_id', inventoryId);
    data.append('reserve_quantity', reserveQuantity);
    data.append('reason', reason);
    
    fetch('inventory.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Stock reserved successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('reserveStockModal')).hide();
            location.reload();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
    });
}

// Submit release stock
function submitReleaseStock() {
    const form = document.getElementById('releaseStockForm');
    const formData = new FormData(form);
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const inventoryId = formData.get('inventory_id');
    const releaseQuantity = parseInt(formData.get('release_quantity'));
    const reason = formData.get('reason') || 'Manual stock release';
    
    if (isNaN(releaseQuantity) || releaseQuantity <= 0) {
        showNotification('Please enter a valid positive number', 'error');
        return;
    }
    
    const data = new FormData();
    data.append('action', 'release_stock');
    data.append('inventory_id', inventoryId);
    data.append('release_quantity', releaseQuantity);
    data.append('reason', reason);
    
    fetch('inventory.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Stock released successfully!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('releaseStockModal')).hide();
            location.reload();
        } else {
            showNotification('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showNotification('Error: ' + error.message, 'error');
    });
}
</script>

<!-- Custom CSS -->
<style>
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    background: #6c757d;
}

.stat-icon.in-stock { background: #28a745; }
.stat-icon.low-stock { background: #ffc107; }
.stat-icon.out-of-stock { background: #dc3545; }

.stat-content h3 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: bold;
}

.stat-content p {
    margin: 0;
    color: #6c757d;
}

.filters-section {
    background: white;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filters-form {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.product-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.product-details h4 {
    margin: 0;
    font-size: 1rem;
}

.product-id {
    margin: 0;
    color: #6c757d;
    font-size: 0.875rem;
}

.stock-status {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
}

.stock-status.in-stock {
    background: #d4edda;
    color: #155724;
}

.stock-status.low-stock {
    background: #fff3cd;
    color: #856404;
}

.stock-status.out-of-stock {
    background: #f8d7da;
    color: #721c24;
}

.stock-status.no-inventory {
    background: #e2e3e5;
    color: #383d41;
}

.stock-actions {
    display: flex;
    gap: 0.25rem;
}

.stock-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.size-badge {
    display: inline-block;
    padding: 0.25rem 0.5rem;
    background: #e9ecef;
    color: #495057;
    border-radius: 4px;
    font-size: 0.875rem;
    font-weight: 500;
}

.color-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.color-swatch {
    display: inline-block;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 1px solid #dee2e6;
}

.stock-quantity, .available-quantity {
    font-weight: 600;
}

.stock-quantity.low-stock, .available-quantity.low-stock {
    color: #dc3545;
}

.stock-quantity.normal-stock, .available-quantity.normal-stock {
    color: #28a745;
}

.reserved-quantity {
    color: #6c757d;
    font-size: 0.875rem;
}

.threshold-value {
    color: #495057;
    font-weight: 500;
}

.no-inventory-message {
    padding: 2rem;
    text-align: center;
}

.no-inventory-message i {
    font-size: 3rem;
    color: #dee2e6;
    margin-bottom: 1rem;
}

.no-inventory-message h4 {
    color: #6c757d;
    margin-bottom: 0.5rem;
}

.no-inventory-message p {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Product row grouping styles */
.product-first-row {
    background-color: #f8f9fa;
    border-left: 4px solid #007bff;
}

.product-subsequent-row {
    background-color: #ffffff;
    border-left: 4px solid #e9ecef;
}

.product-subsequent-row:hover {
    background-color: #f8f9fa;
}

.product-info-subsequent {
    padding-left: 1rem;
}

.product-continuation {
    color: #6c757d;
    font-size: 0.875rem;
    font-style: italic;
}

/* Add spacing between product groups */
.product-first-row:not(:first-child) {
    border-top: 2px solid #dee2e6;
    margin-top: 0.5rem;
}

.variant-indicator {
    display: block;
    color: #6c757d;
    font-size: 0.75rem;
    margin-top: 0.25rem;
}

@media (max-width: 768px) {
    .filters-form {
        flex-direction: column;
    }
    
    .filter-group {
        min-width: auto;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<?php include '../components/footer.php'; ?> 