<?php
/**
 * Report Generator Class
 * Handles generation of various PDF reports
 */
class ReportGenerator {
    private $pdo;
    private $user_id;
    private $title;
    
    public function __construct($pdo, $user_id) {
        $this->pdo = $pdo;
        $this->user_id = $user_id;
        $this->title = 'ManufactureHub Report';
    }
    
    /**
     * Generate Sales Report
     */
    public function generateSalesReport($date_range = 'all') {
        $this->title = 'Sales Report - ' . ucfirst(str_replace('_', ' ', $date_range));
        
        $data = $this->getSalesData($date_range);
        
        // Prepare headers
        $headers = ['Order ID', 'Date', 'Product', 'Quantity', 'Price', 'Total', 'Status'];
        
        // Prepare summary
        $total_revenue = array_sum(array_column($data, 'total_amount'));
        $total_orders = count(array_unique(array_column($data, 'order_id')));
        $summary = [
            'Total Orders' => $total_orders,
            'Total Revenue' => '$' . number_format($total_revenue, 2),
            'Date Range' => ucfirst(str_replace('_', ' ', $date_range))
        ];
        
        // Prepare data for PDF
        $pdfData = [];
        foreach ($data as $row) {
            $pdfData[] = [
                $row['order_id'],
                date('M d, Y', strtotime($row['order_date'])),
                $row['product_name'],
                $row['quantity'],
                '$' . number_format($row['price'], 2),
                '$' . number_format($row['total_amount'], 2),
                ucfirst($row['status'])
            ];
        }
        
        return $this->generatePDF($pdfData, $headers, $summary, 'sales_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Generate Inventory Report
     */
    public function generateInventoryReport($date_range = 'all') {
        $this->title = 'Inventory Report - ' . ucfirst(str_replace('_', ' ', $date_range));
        
        $data = $this->getInventoryData($date_range);
        
        // Prepare headers
        $headers = ['Product ID', 'Product Name', 'Category', 'Stock Quantity', 'Min Stock Level', 'Price', 'Status'];
        
        // Prepare summary
        $low_stock_count = count(array_filter($data, function($item) {
            return $item['stock_quantity'] <= $item['min_stock_level'];
        }));
        $summary = [
            'Total Products' => count($data),
            'Low Stock Items' => $low_stock_count,
            'Date Range' => ucfirst(str_replace('_', ' ', $date_range))
        ];
        
        // Prepare data for PDF
        $pdfData = [];
        foreach ($data as $row) {
            $status = $row['stock_quantity'] <= $row['min_stock_level'] ? 'Low Stock' : 'In Stock';
            $pdfData[] = [
                $row['product_id'],
                $row['product_name'],
                $row['category'],
                $row['stock_quantity'],
                $row['min_stock_level'],
                '$' . number_format($row['price'], 2),
                $status
            ];
        }
        
        return $this->generatePDF($pdfData, $headers, $summary, 'inventory_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Generate Products Report
     */
    public function generateProductsReport($date_range = 'all') {
        $this->title = 'Product Catalog Report - ' . ucfirst(str_replace('_', ' ', $date_range));
        
        $data = $this->getProductsData($date_range);
        
        // Prepare headers
        $headers = ['Product ID', 'Product Name', 'Description', 'Category', 'Price', 'Stock', 'Created Date'];
        
        // Prepare summary
        $summary = [
            'Total Products' => count($data),
            'Date Range' => ucfirst(str_replace('_', ' ', $date_range))
        ];
        
        // Prepare data for PDF
        $pdfData = [];
        foreach ($data as $row) {
            $pdfData[] = [
                $row['product_id'],
                $row['product_name'],
                substr($row['description'], 0, 50) . (strlen($row['description']) > 50 ? '...' : ''),
                $row['category'],
                '$' . number_format($row['price'], 2),
                $row['stock_quantity'],
                date('M d, Y', strtotime($row['created_at']))
            ];
        }
        
        return $this->generatePDF($pdfData, $headers, $summary, 'products_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Generate Orders Report
     */
    public function generateOrdersReport($date_range = 'all') {
        $this->title = 'Orders Report - ' . ucfirst(str_replace('_', ' ', $date_range));
        
        $data = $this->getOrdersData($date_range);
        
        // Prepare headers
        $headers = ['Order ID', 'Date', 'Total Amount', 'Status', 'Shipping Address', 'Payment Method'];
        
        // Prepare summary
        $total_revenue = array_sum(array_column($data, 'total_amount'));
        $total_orders = count($data);
        $summary = [
            'Total Orders' => $total_orders,
            'Total Revenue' => '$' . number_format($total_revenue, 2),
            'Date Range' => ucfirst(str_replace('_', ' ', $date_range))
        ];
        
        // Prepare data for PDF
        $pdfData = [];
        foreach ($data as $row) {
            $pdfData[] = [
                $row['order_id'],
                date('M d, Y', strtotime($row['order_date'])),
                '$' . number_format($row['total_amount'], 2),
                ucfirst($row['status']),
                substr($row['shipping_address'], 0, 40) . (strlen($row['shipping_address']) > 40 ? '...' : ''),
                $row['payment_method']
            ];
        }
        
        return $this->generatePDF($pdfData, $headers, $summary, 'orders_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Generate Revenue Report
     */
    public function generateRevenueReport($date_range = 'all') {
        $this->title = 'Revenue Report - ' . ucfirst(str_replace('_', ' ', $date_range));
        
        $data = $this->getRevenueData($date_range);
        
        // Prepare headers
        $headers = ['Date', 'Orders', 'Daily Revenue', 'Average Order Value'];
        
        // Prepare summary
        $total_revenue = array_sum(array_column($data, 'daily_revenue'));
        $total_orders = array_sum(array_column($data, 'order_count'));
        $avg_order_value = $total_orders > 0 ? $total_revenue / $total_orders : 0;
        $summary = [
            'Total Revenue' => '$' . number_format($total_revenue, 2),
            'Total Orders' => $total_orders,
            'Average Order Value' => '$' . number_format($avg_order_value, 2),
            'Date Range' => ucfirst(str_replace('_', ' ', $date_range))
        ];
        
        // Prepare data for PDF
        $pdfData = [];
        foreach ($data as $row) {
            $pdfData[] = [
                date('M d, Y', strtotime($row['date'])),
                $row['order_count'],
                '$' . number_format($row['daily_revenue'], 2),
                '$' . number_format($row['avg_order_value'], 2)
            ];
        }
        
        return $this->generatePDF($pdfData, $headers, $summary, 'revenue_report_' . date('Y-m-d') . '.pdf');
    }
    
    /**
     * Get Sales Data
     */
    private function getSalesData($date_range) {
        $where_clause = $this->getDateWhereClause($date_range);
        
        $sql = "SELECT 
                    o.order_id,
                    o.created_at as order_date,
                    (o.product_price * o.quantity) as total_amount,
                    o.status,
                    o.product_name,
                    o.quantity,
                    o.product_price as price
                FROM orders o
                WHERE o.manufacturer_id = ? $where_clause
                ORDER BY o.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->user_id]);
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $data;
    }
    
    /**
     * Get Inventory Data
     */
    private function getInventoryData($date_range) {
        $sql = "SELECT 
                    p.id as product_id,
                    p.name as product_name,
                    p.description,
                    ps.selling_price as price,
                    COALESCE(ps.stock_quantity, 0) as stock_quantity,
                    COALESCE(ps.min_stock_level, 10) as min_stock_level,
                    c.name as category,
                    p.created_at
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_sizes ps ON p.id = ps.product_id
                WHERE p.manufacturer_id = ? AND p.is_active = 1
                ORDER BY COALESCE(ps.stock_quantity, 0) ASC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->user_id]);
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $data;
    }
    
    /**
     * Get Products Data
     */
    private function getProductsData($date_range) {
        $sql = "SELECT 
                    p.id as product_id,
                    p.name as product_name,
                    p.description,
                    ps.selling_price as price,
                    COALESCE(ps.stock_quantity, 0) as stock_quantity,
                    c.name as category,
                    p.created_at,
                    p.updated_at
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_sizes ps ON p.id = ps.product_id
                WHERE p.manufacturer_id = ? AND p.is_active = 1
                ORDER BY p.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->user_id]);
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $data;
    }
    
    /**
     * Get Orders Data
     */
    private function getOrdersData($date_range) {
        $where_clause = $this->getDateWhereClause($date_range);
        
        $sql = "SELECT 
                    order_id,
                    created_at as order_date,
                    (product_price * quantity) as total_amount,
                    status,
                    customer_address as shipping_address,
                    'Online Payment' as payment_method
                FROM orders 
                WHERE manufacturer_id = ? $where_clause
                ORDER BY created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->user_id]);
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $data;
    }
    
    /**
     * Get Revenue Data
     */
    private function getRevenueData($date_range) {
        $where_clause = $this->getDateWhereClause($date_range);
        
        $sql = "SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as order_count,
                    SUM(product_price * quantity) as daily_revenue,
                    AVG(product_price * quantity) as avg_order_value
                FROM orders 
                WHERE manufacturer_id = ? AND status = 'Delivered' $where_clause
                GROUP BY DATE(created_at)
                ORDER BY date DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$this->user_id]);
        
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return $data;
    }
    
    /**
     * Get Date Where Clause
     */
    private function getDateWhereClause($date_range) {
        switch ($date_range) {
            case 'this_month':
                return "AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            case 'last_month':
                return "AND MONTH(created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH)) AND YEAR(created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
            case 'this_quarter':
                return "AND QUARTER(created_at) = QUARTER(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            case 'this_year':
                return "AND YEAR(created_at) = YEAR(CURRENT_DATE())";
            default:
                return "";
        }
    }
    

    

    
    /**
     * Generate PDF from data
     */
    private function generatePDF($data, $headers, $summary, $filename) {
        require_once __DIR__ . '/pdf/PDFGenerator.php';
        
        $pdf = new PDFGenerator($this->title, $filename);
        
        // Clear any existing output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        // Set proper headers for file download
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.html"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Generate and output the PDF
        $pdf->output($data, $headers, $summary);
    }
}
?> 