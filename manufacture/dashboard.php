<?php
require_once __DIR__ . '/components/header.php';
require_once __DIR__ . '/includes/Order.class.php';
require_once __DIR__ . '/includes/Product.class.php';
require_once __DIR__ . '/includes/Member.class.php';

// Get the current manufacturer ID
$user_id = $_SESSION['manufacturer_id'];

// Initialize classes
$orderObj = new Order($pdo);
$productObj = new Product($pdo);
$memberObj = new Member($pdo);

// Get dashboard data
$order_counts = $orderObj->getOrderCountsByStatus($user_id);
$total_orders = array_sum($order_counts);
$pending_orders = $order_counts['Pending'] ?? 0;
$processing_orders = $order_counts['Processing'] ?? 0;
$delivered_orders = $order_counts['Delivered'] ?? 0;

// Get product statistics
$total_products = $productObj->getProductCount($user_id);
$active_products = $productObj->getActiveProductCount($user_id);

// Get team member statistics
$team_members = $memberObj->getTeamMembers($user_id);
$total_members = count($team_members);
$active_members = 0;
foreach ($team_members as $member) {
    if ($member['is_active']) {
        $active_members++;
    }
}

// Get recent orders (last 5)
$recent_orders = $orderObj->getOrders($user_id, '', 5);

// Get recent team activity
$member_activity = $memberObj->getMemberActivity($user_id, 7); // Last 7 days

// Calculate revenue (simplified - sum of delivered orders)
$revenue_sql = "SELECT SUM(product_price * quantity) as total_revenue 
                FROM orders 
                WHERE manufacturer_id = ? AND status = 'Delivered'";
$stmt = $pdo->prepare($revenue_sql);
$stmt->execute([$user_id]);
$revenue_data = $stmt->fetch(PDO::FETCH_ASSOC);
$total_revenue = $revenue_data['total_revenue'] ?? 0;

// Get monthly revenue
$monthly_revenue_sql = "SELECT SUM(product_price * quantity) as monthly_revenue 
                        FROM orders 
                        WHERE manufacturer_id = ? 
                        AND status = 'Delivered' 
                        AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                        AND YEAR(created_at) = YEAR(CURRENT_DATE())";
$stmt = $pdo->prepare($monthly_revenue_sql);
$stmt->execute([$user_id]);
$monthly_data = $stmt->fetch(PDO::FETCH_ASSOC);
$monthly_revenue = $monthly_data['monthly_revenue'] ?? 0;

// Get low stock products
$low_stock_sql = "SELECT COUNT(*) as low_stock_count 
                  FROM inventory 
                  WHERE available_quantity <= low_stock_threshold 
                  AND available_quantity > 0";
$stmt = $pdo->prepare($low_stock_sql);
$stmt->execute();
$low_stock_data = $stmt->fetch(PDO::FETCH_ASSOC);
$low_stock_count = $low_stock_data['low_stock_count'] ?? 0;

// Get out of stock products
$out_of_stock_sql = "SELECT COUNT(*) as out_of_stock_count 
                     FROM inventory 
                     WHERE available_quantity = 0";
$stmt = $pdo->prepare($out_of_stock_sql);
$stmt->execute();
$out_of_stock_data = $stmt->fetch(PDO::FETCH_ASSOC);
$out_of_stock_count = $out_of_stock_data['out_of_stock_count'] ?? 0;
?>

<!-- Sidebar -->
<?php include 'components/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <!-- Welcome Header -->
        <div class="welcome-header">
            <div class="welcome-content">
                <h1 class="page-title">Welcome back, <?php echo htmlspecialchars($user_data['company_name']); ?>!</h1>
                <p class="welcome-subtitle">Here's what's happening with your business today</p>
                <div class="current-time">
                    <i class="fas fa-clock"></i>
                    <span id="current-time"></span>
                </div>
            </div>
            <div class="welcome-actions">
                <a href="orders.php" class="btn btn-primary">
                    <i class="fas fa-shopping-cart"></i> View Orders
                </a>
                <a href="products.php" class="btn btn-outline-primary">
                    <i class="fas fa-box"></i> Manage Products
                </a>
            </div>
        </div>

        <!-- Status Panels Grid -->
        <div class="status-panels-grid">
            <!-- Orders Status Panel -->
            <div class="status-panel orders-panel">
                <div class="panel-header">
                    <div class="panel-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div class="panel-title">
                        <h3>Orders</h3>
                        <p>Order Management</p>
                    </div>
                </div>
                <div class="panel-content">
                    <div class="main-stat">
                        <span class="stat-number"><?php echo $total_orders; ?></span>
                        <span class="stat-label">Total Orders</span>
                    </div>
                    <div class="sub-stats">
                        <div class="sub-stat pending">
                            <span class="stat-number"><?php echo $pending_orders; ?></span>
                            <span class="stat-label">Pending</span>
                        </div>
                        <div class="sub-stat processing">
                            <span class="stat-number"><?php echo $processing_orders; ?></span>
                            <span class="stat-label">Processing</span>
                        </div>
                        <div class="sub-stat delivered">
                            <span class="stat-number"><?php echo $delivered_orders; ?></span>
                            <span class="stat-label">Delivered</span>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <a href="orders.php" class="panel-link">View All Orders <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Revenue Status Panel -->
            <div class="status-panel revenue-panel">
                <div class="panel-header">
                    <div class="panel-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="panel-title">
                        <h3>Revenue</h3>
                        <p>Financial Overview</p>
                    </div>
                </div>
                <div class="panel-content">
                    <div class="main-stat">
                        <span class="stat-number">$<?php echo number_format($total_revenue, 2); ?></span>
                        <span class="stat-label">Total Revenue</span>
                    </div>
                    <div class="sub-stats">
                        <div class="sub-stat monthly">
                            <span class="stat-number">$<?php echo number_format($monthly_revenue, 2); ?></span>
                            <span class="stat-label">This Month</span>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <a href="reports.php" class="panel-link">View Reports <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Products Status Panel -->
            <div class="status-panel products-panel">
                <div class="panel-header">
                    <div class="panel-icon">
                        <i class="fas fa-box"></i>
                    </div>
                    <div class="panel-title">
                        <h3>Products</h3>
                        <p>Inventory Status</p>
                    </div>
                </div>
                <div class="panel-content">
                    <div class="main-stat">
                        <span class="stat-number"><?php echo $total_products; ?></span>
                        <span class="stat-label">Total Products</span>
                    </div>
                    <div class="sub-stats">
                        <div class="sub-stat active">
                            <span class="stat-number"><?php echo $active_products; ?></span>
                            <span class="stat-label">Active</span>
                        </div>
                        <div class="sub-stat low-stock">
                            <span class="stat-number"><?php echo $low_stock_count; ?></span>
                            <span class="stat-label">Low Stock</span>
                        </div>
                        <div class="sub-stat out-of-stock">
                            <span class="stat-number"><?php echo $out_of_stock_count; ?></span>
                            <span class="stat-label">Out of Stock</span>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <a href="products.php" class="panel-link">Manage Products <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>

            <!-- Team Status Panel -->
            <div class="status-panel team-panel">
                <div class="panel-header">
                    <div class="panel-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="panel-title">
                        <h3>Team</h3>
                        <p>Member Overview</p>
                    </div>
                </div>
                <div class="panel-content">
                    <div class="main-stat">
                        <span class="stat-number"><?php echo $total_members; ?></span>
                        <span class="stat-label">Team Members</span>
                    </div>
                    <div class="sub-stats">
                        <div class="sub-stat active">
                            <span class="stat-number"><?php echo $active_members; ?></span>
                            <span class="stat-label">Active</span>
                        </div>
                        <div class="sub-stat activity">
                            <span class="stat-number"><?php echo count($member_activity); ?></span>
                            <span class="stat-label">Active (7d)</span>
                        </div>
                    </div>
                </div>
                <div class="panel-footer">
                    <a href="members/team.php" class="panel-link">Manage Team <i class="fas fa-arrow-right"></i></a>
                </div>
            </div>
        </div>

        <!-- Recent Activity Section -->
        <div class="recent-activity-section">
            <div class="section-header">
                <h2>Recent Activity</h2>
                <div class="section-tabs">
                    <button class="tab-btn active" data-tab="orders">Recent Orders</button>
                    <button class="tab-btn" data-tab="team">Team Activity</button>
                </div>
            </div>

            <!-- Recent Orders Tab -->
            <div class="tab-content active" id="orders-tab">
                <div class="activity-list">
                    <?php if (empty($recent_orders)): ?>
                        <div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <h3>No Recent Orders</h3>
                            <p>Orders will appear here as they come in</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-shopping-cart"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        Order #<?php echo htmlspecialchars($order['order_id']); ?>
                                    </div>
                                    <div class="activity-details">
                                        <?php echo htmlspecialchars($order['customer_name']); ?> - 
                                        <?php echo htmlspecialchars($order['product_name']); ?>
                                    </div>
                                    <div class="activity-meta">
                                        <span class="order-status status-<?php echo strtolower(str_replace(' ', '-', $order['status'])); ?>">
                                            <?php echo htmlspecialchars($order['status']); ?>
                                        </span>
                                        <span class="activity-time">
                                            <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="activity-actions">
                                    <a href="orders.php?search=<?php echo urlencode($order['order_id']); ?>" class="btn btn-sm btn-outline-primary">
                                        View
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Team Activity Tab -->
            <div class="tab-content" id="team-tab">
                <div class="activity-list">
                    <?php if (empty($member_activity)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>No Recent Team Activity</h3>
                            <p>Team member activity will appear here</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($member_activity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">
                                        <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                    </div>
                                    <div class="activity-details">
                                        Processed <?php echo $activity['total_orders_processed']; ?> orders
                                    </div>
                                    <div class="activity-meta">
                                        <span class="member-role role-<?php echo strtolower($activity['role']); ?>">
                                            <?php echo htmlspecialchars(ucfirst($activity['role'])); ?>
                                        </span>
                                        <?php if ($activity['last_login']): ?>
                                            <span class="activity-time">
                                                Last login: <?php echo date('M d, Y H:i', strtotime($activity['last_login'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="activity-actions">
                                    <a href="members/team.php" class="btn btn-sm btn-outline-primary">
                                        View Profile
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Welcome Header */
.welcome-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 12px;
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.welcome-content h1 {
    margin: 0 0 5px 0;
    font-size: 28px;
    font-weight: 600;
}

.welcome-subtitle {
    margin: 0 0 15px 0;
    opacity: 0.9;
    font-size: 16px;
}

.current-time {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    opacity: 0.8;
}

.welcome-actions {
    display: flex;
    gap: 15px;
}

/* Status Panels Grid */
.status-panels-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.status-panel {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #e9ecef;
    transition: transform 0.2s, box-shadow 0.2s;
}

.status-panel:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.panel-header {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.panel-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 20px;
    color: white;
}

.orders-panel .panel-icon {
    background: linear-gradient(135deg, #ff6b6b, #ee5a24);
}

.revenue-panel .panel-icon {
    background: linear-gradient(135deg, #2ed573, #1e90ff);
}

.products-panel .panel-icon {
    background: linear-gradient(135deg, #ffa502, #ff6348);
}

.team-panel .panel-icon {
    background: linear-gradient(135deg, #5f27cd, #341f97);
}

.panel-title h3 {
    margin: 0 0 2px 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.panel-title p {
    margin: 0;
    color: #666;
    font-size: 14px;
}

.panel-content {
    margin-bottom: 20px;
}

.main-stat {
    text-align: center;
    margin-bottom: 20px;
}

.stat-number {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #333;
    line-height: 1;
}

.stat-label {
    display: block;
    color: #666;
    font-size: 14px;
    margin-top: 5px;
}

.sub-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}

.sub-stat {
    text-align: center;
    padding: 10px;
    border-radius: 8px;
    background: #f8f9fa;
}

.sub-stat .stat-number {
    font-size: 18px;
    font-weight: 600;
}

.sub-stat .stat-label {
    font-size: 12px;
    margin-top: 2px;
}

.sub-stat.pending {
    background: #fff3cd;
    color: #856404;
}

.sub-stat.processing {
    background: #cce5ff;
    color: #004085;
}

.sub-stat.delivered {
    background: #d4edda;
    color: #155724;
}

.sub-stat.active {
    background: #d4edda;
    color: #155724;
}

.sub-stat.low-stock {
    background: #fff3cd;
    color: #856404;
}

.sub-stat.out-of-stock {
    background: #f8d7da;
    color: #721c24;
}

.sub-stat.monthly {
    background: #e2e3e5;
    color: #383d41;
}

.sub-stat.activity {
    background: #d1ecf1;
    color: #0c5460;
}

.panel-footer {
    border-top: 1px solid #e9ecef;
    padding-top: 15px;
}

.panel-link {
    color: #007bff;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.panel-link:hover {
    color: #0056b3;
    text-decoration: none;
}

/* Recent Activity Section */
.recent-activity-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
    border: 1px solid #e9ecef;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
}

.section-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
}

.section-tabs {
    display: flex;
    gap: 10px;
}

.tab-btn {
    padding: 8px 16px;
    border: 1px solid #dee2e6;
    background: white;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s;
}

.tab-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.tab-btn:hover:not(.active) {
    background: #f8f9fa;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 15px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    transition: background-color 0.2s;
}

.activity-item:hover {
    background: #f8f9fa;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #007bff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    color: white;
    font-size: 14px;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 2px;
}

.activity-details {
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}

.activity-meta {
    display: flex;
    gap: 15px;
    align-items: center;
}

.order-status, .member-role {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #cce5ff;
    color: #004085;
}

.status-shipped {
    background: #d1ecf1;
    color: #0c5460;
}

.status-delivered {
    background: #d4edda;
    color: #155724;
}

.status-cancelled {
    background: #f8d7da;
    color: #721c24;
}

.role-admin {
    background: #dc3545;
    color: white;
}

.role-manager {
    background: #fd7e14;
    color: white;
}

.role-member {
    background: #28a745;
    color: white;
}

.activity-time {
    color: #999;
    font-size: 12px;
}

.activity-actions {
    margin-left: 15px;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.empty-state i {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 15px;
}

.empty-state h3 {
    margin: 0 0 10px 0;
    color: #666;
}

.empty-state p {
    margin: 0;
    color: #999;
}

/* Responsive Design */
@media (max-width: 768px) {
    .welcome-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .welcome-actions {
        flex-direction: column;
        width: 100%;
    }
    
    .status-panels-grid {
        grid-template-columns: 1fr;
    }
    
    .sub-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .section-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .activity-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .activity-actions {
        margin-left: 0;
        align-self: flex-end;
    }
}
</style>

<script>
// Update current time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    document.getElementById('current-time').textContent = timeString;
}

// Update time every second
updateTime();
setInterval(updateTime, 1000);

// Tab functionality
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all buttons and contents
            tabBtns.forEach(b => b.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active'));
            
            // Add active class to clicked button and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });
});
</script>

<?php require_once __DIR__ . '/components/footer.php'; ?>