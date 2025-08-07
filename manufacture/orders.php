<?php
require_once '../config/database.php';
require_once 'includes/Order.class.php';

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
$orderObj = new Order($pdo);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_status':
            $order_id = $_POST['order_id'] ?? '';
            $status = $_POST['status'] ?? '';
            
            if ($orderObj->updateOrderStatus($order_id, $status, $manufacturer_id)) {
                echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
            exit();
            
        case 'get_order':
            $order_id = $_POST['order_id'] ?? '';
            $order = $orderObj->getOrderById($order_id, $manufacturer_id);
            if ($order) {
                echo json_encode(['success' => true, 'order' => $order]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Order not found']);
            }
            exit();
            
        case 'get_counts':
            $counts = $orderObj->getOrderCountsByStatus($manufacturer_id);
            echo json_encode(['success' => true, 'counts' => $counts]);
            exit();
    }
}

// Get search term
$search = $_GET['search'] ?? '';

// Get order counts and orders
$order_counts = $orderObj->getOrderCountsByStatus($manufacturer_id);
$orders = $orderObj->getOrders($manufacturer_id, $search);

// Create sample orders if no orders exist (for testing - remove in production)
if (empty($orders)) {
    $orderObj->createSampleOrders($manufacturer_id);
    $order_counts = $orderObj->getOrderCountsByStatus($manufacturer_id);
    $orders = $orderObj->getOrders($manufacturer_id, $search);
}

$statuses = $orderObj->getOrderStatuses();

// Include header
include 'components/header.php';
?>

<!-- Sidebar -->
<?php include 'components/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Status Boxes -->
    <div class="status-boxes">
        <div class="status-box pending">
            <h3>Pending</h3>
            <div class="count"><?php echo $order_counts['Pending']; ?></div>
        </div>
        <div class="status-box processing">
            <h3>Processing</h3>
            <div class="count"><?php echo $order_counts['Processing']; ?></div>
        </div>
        <div class="status-box shipped">
            <h3>Shipped</h3>
            <div class="count"><?php echo $order_counts['Shipped']; ?></div>
        </div>
        <div class="status-box out-for-delivery">
            <h3>Out for Delivery</h3>
            <div class="count"><?php echo $order_counts['Out for Delivery']; ?></div>
        </div>
        <div class="status-box delivered">
            <h3>Delivered</h3>
            <div class="count"><?php echo $order_counts['Delivered']; ?></div>
        </div>
        <div class="status-box cancelled">
            <h3>Cancelled</h3>
            <div class="count"><?php echo $order_counts['Cancelled']; ?></div>
        </div>
        <div class="status-box returned">
            <h3>Returned</h3>
            <div class="count"><?php echo $order_counts['Returned']; ?></div>
        </div>
        <div class="status-box refunded">
            <h3>Refunded</h3>
            <div class="count"><?php echo $order_counts['Refunded']; ?></div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <form id="searchForm" class="search-box">
            <input type="text" id="searchInput" placeholder="Search orders by ID, customer name, product, or status..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">
                <i class="fas fa-search"></i> Search
            </button>
        </form>
    </div>

    <!-- Orders Table -->
    <div class="orders-table">
        <div class="table-header">
            <h2>Orders</h2>
        </div>
        
        <?php if (!empty($orders)): ?>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Item Name</th>
                        <th>Item Count</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr data-order-id="<?php echo $order['id']; ?>">
                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['product_name']); ?></td>
                        <td><?php echo $order['quantity']; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-view" onclick="viewOrder(<?php echo $order['id']; ?>)">View</button>
                            <button class="btn btn-update" onclick="editOrder(<?php echo $order['id']; ?>, '<?php echo htmlspecialchars($order['status']); ?>')">Update</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>No Orders</h3>
            <p>No orders available at the moment.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Order Modal -->
<div id="viewModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">Order Details</h2>
            <span class="close" onclick="closeModal('viewModal')">&times;</span>
        </div>
        <div id="modalBody">
            <!-- Order details will be loaded here -->
        </div>
    </div>
</div>

<!-- Pass PHP data to JavaScript -->
<script>
    window.orderStatuses = <?php echo json_encode($statuses); ?>;
</script>

<?php
// Include footer
include 'components/footer.php';
?> 