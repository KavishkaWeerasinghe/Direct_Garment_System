<?php
require_once __DIR__ . '/../../config/database.php';

class Order {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Get order counts by status
    public function getOrderCountsByStatus($manufacturer_id) {
        $sql = "SELECT 
                    status,
                    COUNT(*) as count
                FROM orders 
                WHERE manufacturer_id = :manufacturer_id 
                GROUP BY status";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['manufacturer_id' => $manufacturer_id]);
        
        $counts = [
            'Pending' => 0,
            'Processing' => 0,
            'Shipped' => 0,
            'Out for Delivery' => 0,
            'Delivered' => 0,
            'Cancelled' => 0,
            'Returned' => 0,
            'Refunded' => 0
        ];
        
        while ($row = $stmt->fetch()) {
            if (isset($counts[$row['status']])) {
                $counts[$row['status']] = $row['count'];
            }
        }
        
        return $counts;
    }
    
    // Get all orders with search functionality
    public function getOrders($manufacturer_id, $search = '', $limit = null) {
        $sql = "SELECT 
                    o.id,
                    o.order_id,
                    o.customer_name,
                    o.customer_email,
                    o.customer_address,
                    o.product_name,
                    o.product_price,
                    o.quantity,
                    o.status,
                    o.created_at,
                    o.updated_at
                FROM orders o
                WHERE o.manufacturer_id = :manufacturer_id";
        
        $params = ['manufacturer_id' => $manufacturer_id];
        
        if (!empty($search)) {
            $sql .= " AND (o.order_id LIKE :search 
                        OR o.customer_name LIKE :search 
                        OR o.product_name LIKE :search 
                        OR o.status LIKE :search)";
            $params['search'] = "%$search%";
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        if ($limit !== null) {
            $sql .= " LIMIT :limit";
            $params['limit'] = (int)$limit;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    // Get single order by ID
    public function getOrderById($order_id, $manufacturer_id) {
        $sql = "SELECT 
                    o.id,
                    o.order_id,
                    o.customer_name,
                    o.customer_email,
                    o.customer_address,
                    o.product_name,
                    o.product_price,
                    o.quantity,
                    o.status,
                    o.created_at,
                    o.updated_at
                FROM orders o
                WHERE o.id = :order_id AND o.manufacturer_id = :manufacturer_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'order_id' => $order_id,
            'manufacturer_id' => $manufacturer_id
        ]);
        
        return $stmt->fetch();
    }
    
    // Update order status
    public function updateOrderStatus($order_id, $status, $manufacturer_id) {
        $sql = "UPDATE orders 
                SET status = :status, updated_at = NOW() 
                WHERE id = :order_id AND manufacturer_id = :manufacturer_id";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            'status' => $status,
            'order_id' => $order_id,
            'manufacturer_id' => $manufacturer_id
        ]);
    }
    
    // Get available order statuses
    public function getOrderStatuses() {
        return [
            'Pending',
            'Processing',
            'Shipped',
            'Out for Delivery',
            'Delivered',
            'Cancelled',
            'Returned',
            'Refunded'
        ];
    }
    
    // Create sample orders for testing (remove in production)
    public function createSampleOrders($manufacturer_id) {
        $sample_orders = [
            [
                'order_id' => 'ORD-001',
                'customer_name' => 'John Doe',
                'customer_email' => 'john@example.com',
                'customer_address' => '123 Main St, City, State 12345',
                'product_name' => 'Cotton T-Shirt',
                'product_price' => 25.00,
                'quantity' => 5,
                'status' => 'Pending'
            ],
            [
                'order_id' => 'ORD-002',
                'customer_name' => 'Jane Smith',
                'customer_email' => 'jane@example.com',
                'customer_address' => '456 Oak Ave, Town, State 67890',
                'product_name' => 'Denim Jeans',
                'product_price' => 45.00,
                'quantity' => 2,
                'status' => 'Processing'
            ],
            [
                'order_id' => 'ORD-003',
                'customer_name' => 'Mike Johnson',
                'customer_email' => 'mike@example.com',
                'customer_address' => '789 Pine Rd, Village, State 11111',
                'product_name' => 'Hoodie',
                'product_price' => 35.00,
                'quantity' => 3,
                'status' => 'Shipped'
            ],
            [
                'order_id' => 'ORD-004',
                'customer_name' => 'Sarah Wilson',
                'customer_email' => 'sarah@example.com',
                'customer_address' => '321 Elm St, Borough, State 22222',
                'product_name' => 'Polo Shirt',
                'product_price' => 30.00,
                'quantity' => 4,
                'status' => 'Delivered'
            ],
            [
                'order_id' => 'ORD-005',
                'customer_name' => 'David Brown',
                'customer_email' => 'david@example.com',
                'customer_address' => '654 Maple Dr, County, State 33333',
                'product_name' => 'Sweatpants',
                'product_price' => 28.00,
                'quantity' => 2,
                'status' => 'Cancelled'
            ]
        ];
        
        $sql = "INSERT INTO orders (order_id, manufacturer_id, customer_name, customer_email, customer_address, product_name, product_price, quantity, status, created_at, updated_at) 
                VALUES (:order_id, :manufacturer_id, :customer_name, :customer_email, :customer_address, :product_name, :product_price, :quantity, :status, NOW(), NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($sample_orders as $order) {
            $order['manufacturer_id'] = $manufacturer_id;
            $stmt->execute($order);
        }
    }
}
?> 