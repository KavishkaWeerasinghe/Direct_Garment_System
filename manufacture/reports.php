<?php
require_once __DIR__ . '/components/header.php';

// Handle report download requests first (before any output)
if (isset($_POST['download_report'])) {
    require_once __DIR__ . '/includes/ReportGenerator.class.php';
    
    $user_id = $_SESSION['manufacturer_id'];
    $report_type = $_POST['report_type'];
    $date_range = $_POST['date_range'] ?? 'all';
    
    // Initialize ReportGenerator
    $reportGenerator = new ReportGenerator($pdo, $user_id);
    
    // Generate and download the requested report
    switch ($report_type) {
        case 'sales':
            $reportGenerator->generateSalesReport($date_range);
            break;
        case 'inventory':
            $reportGenerator->generateInventoryReport($date_range);
            break;
        case 'products':
            $reportGenerator->generateProductsReport($date_range);
            break;
        case 'orders':
            $reportGenerator->generateOrdersReport($date_range);
            break;
        case 'revenue':
            $reportGenerator->generateRevenueReport($date_range);
            break;
        default:
            $_SESSION['report_message'] = "Invalid report type!";
            header('Location: reports.php');
            exit();
    }
}

require_once __DIR__ . '/includes/ReportGenerator.class.php';
?>

<!-- Sidebar -->
<?php include 'components/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h1 class="page-title">Reports & Analytics</h1>
                <p>Generate and download comprehensive reports for your business, <?php echo htmlspecialchars($user_data['company_name']); ?>!</p>
                
                <?php if (isset($_SESSION['report_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['report_message']; ?>
                        <?php unset($_SESSION['report_message']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="reports-content">
            
            <div class="report-card">
                <div class="report-header">
                    <div class="report-icon" style="background: #28a745;">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="report-title">Sales Report</h3>
                </div>
                <p class="report-description">
                    Comprehensive sales analysis including order volume, revenue trends, and customer insights.
                </p>
                <form class="report-form" method="POST">
                    <input type="hidden" name="report_type" value="sales">
                    <div class="form-group">
                        <label for="sales_date_range">Date Range</label>
                        <select name="date_range" id="sales_date_range">
                            <option value="all">All Time</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_quarter">This Quarter</option>
                            <option value="this_year">This Year</option>
                        </select>
                    </div>
                    <button type="submit" name="download_report" class="download-btn">
                        <i class="fas fa-download"></i>
                        Download Sales Report
                    </button>
                </form>
            </div>
            
            <div class="report-card">
                <div class="report-header">
                    <div class="report-icon" style="background: #ffc107;">
                        <i class="fas fa-warehouse"></i>
                    </div>
                    <h3 class="report-title">Inventory Report</h3>
                </div>
                <p class="report-description">
                    Detailed inventory status including stock levels, low stock alerts, and product availability.
                </p>
                <form class="report-form" method="POST">
                    <input type="hidden" name="report_type" value="inventory">
                    <div class="form-group">
                        <label for="inventory_date_range">Date Range</label>
                        <select name="date_range" id="inventory_date_range">
                            <option value="all">All Time</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_quarter">This Quarter</option>
                        </select>
                    </div>
                    <button type="submit" name="download_report" class="download-btn">
                        <i class="fas fa-download"></i>
                        Download Inventory Report
                    </button>
                </form>
            </div>
            
            <div class="report-card">
                <div class="report-header">
                    <div class="report-icon" style="background: #17a2b8;">
                        <i class="fas fa-tshirt"></i>
                    </div>
                    <h3 class="report-title">Product Catalog Report</h3>
                </div>
                <p class="report-description">
                    Complete product listing with specifications, pricing, and performance metrics.
                </p>
                <form class="report-form" method="POST">
                    <input type="hidden" name="report_type" value="products">
                    <div class="form-group">
                        <label for="products_date_range">Date Range</label>
                        <select name="date_range" id="products_date_range">
                            <option value="all">All Time</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                        </select>
                    </div>
                    <button type="submit" name="download_report" class="download-btn">
                        <i class="fas fa-download"></i>
                        Download Product Report
                    </button>
                </form>
            </div>
            
            <div class="report-card">
                <div class="report-header">
                    <div class="report-icon" style="background: #6f42c1;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3 class="report-title">Orders Report</h3>
                </div>
                <p class="report-description">
                    Detailed order analysis including order status, fulfillment times, and customer satisfaction.
                </p>
                <form class="report-form" method="POST">
                    <input type="hidden" name="report_type" value="orders">
                    <div class="form-group">
                        <label for="orders_date_range">Date Range</label>
                        <select name="date_range" id="orders_date_range">
                            <option value="all">All Time</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_quarter">This Quarter</option>
                            <option value="this_year">This Year</option>
                        </select>
                    </div>
                    <button type="submit" name="download_report" class="download-btn">
                        <i class="fas fa-download"></i>
                        Download Orders Report
                    </button>
                </form>
            </div>
            
            <div class="report-card">
                <div class="report-header">
                    <div class="report-icon" style="background: #dc3545;">
                        <i class="fas fa-coins"></i>
                    </div>
                    <h3 class="report-title">Revenue Report</h3>
                </div>
                <p class="report-description">
                    Financial analysis including revenue trends, profit margins, and payment processing data.
                </p>
                <form class="report-form" method="POST">
                    <input type="hidden" name="report_type" value="revenue">
                    <div class="form-group">
                        <label for="revenue_date_range">Date Range</label>
                        <select name="date_range" id="revenue_date_range">
                            <option value="all">All Time</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_quarter">This Quarter</option>
                            <option value="this_year">This Year</option>
                        </select>
                    </div>
                    <button type="submit" name="download_report" class="download-btn">
                        <i class="fas fa-download"></i>
                        Download Revenue Report
                    </button>
                </form>
            </div>
            
            <div class="recent-reports">
                <h3>Recent Reports</h3>
                <ul class="report-list">
                    <li>
                        <span>Sales Report - <?php echo date('M d, Y'); ?></span>
                        <a href="#" class="download-btn" style="padding: 5px 10px; font-size: 12px;">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </li>
                    <li>
                        <span>Inventory Report - <?php echo date('M d, Y', strtotime('-1 day')); ?></span>
                        <a href="#" class="download-btn" style="padding: 5px 10px; font-size: 12px;">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </li>
                    <li>
                        <span>Product Catalog - <?php echo date('M d, Y', strtotime('-3 days')); ?></span>
                        <a href="#" class="download-btn" style="padding: 5px 10px; font-size: 12px;">
                            <i class="fas fa-download"></i> Download
                        </a>
                    </li>
                </ul>
            </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/components/footer.php'; ?> 