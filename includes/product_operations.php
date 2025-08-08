<?php
require_once __DIR__ . '/../config/database.php';

// Function to log errors
function logError($message) {
    $log_dir = dirname(__FILE__) . '/../logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $log_file = $log_dir . '/product_errors.log';
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, $log_file);
}

// Verify database connection
if (!isset($pdo) || $pdo === false) {
    logError("Database connection failed: PDO not available");
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]));
}

// Get all products with optional filters
function getProducts($category = null, $minPrice = null, $maxPrice = null, $sort = 'featured', $page = 1, $perPage = 12, $color = null) {
    global $pdo;
    
    try {
        // Use the product_details_view for efficient querying
        $query = "SELECT DISTINCT pdv.*, 
                         COALESCE(pdv.main_image, 'src/images/default-product.jpg') as image_url,
                         COALESCE(ps_min.selling_price, 0) as min_price,
                         COALESCE(ps_max.selling_price, 0) as max_price,
                         COALESCE(ps_avg.avg_price, 0) as price
                 FROM product_details_view pdv
                 LEFT JOIN (SELECT product_id, MIN(selling_price) as selling_price 
                           FROM product_sizes WHERE is_active = 1 GROUP BY product_id) ps_min 
                           ON pdv.id = ps_min.product_id
                 LEFT JOIN (SELECT product_id, MAX(selling_price) as selling_price 
                           FROM product_sizes WHERE is_active = 1 GROUP BY product_id) ps_max 
                           ON pdv.id = ps_max.product_id
                 LEFT JOIN (SELECT product_id, AVG(selling_price) as avg_price 
                           FROM product_sizes WHERE is_active = 1 GROUP BY product_id) ps_avg 
                           ON pdv.id = ps_avg.product_id";
        
        // Add color join if color filter is specified
        if ($color) {
            $query .= " LEFT JOIN product_colors pc ON pdv.id = pc.product_id";
        }
        
        $query .= " WHERE pdv.is_active = 1";
        
        $countQuery = "SELECT COUNT(DISTINCT pdv.id) as total 
                      FROM product_details_view pdv 
                      LEFT JOIN product_sizes ps ON pdv.id = ps.product_id AND ps.is_active = 1";
        
        // Add color join to count query if needed
        if ($color) {
            $countQuery .= " LEFT JOIN product_colors pc ON pdv.id = pc.product_id";
        }
        
        $countQuery .= " WHERE pdv.is_active = 1";
        $params = [];
        
        // Add category filter
        if ($category) {
            $query .= " AND pdv.category_id = ?";
            $countQuery .= " AND pdv.category_id = ?";
            $params[] = $category;
        }
        
        // Add color filter
        if ($color) {
            $query .= " AND pc.color_name = ?";
            $countQuery .= " AND pc.color_name = ?";
            $params[] = $color;
        }
        
        // Add price filters using product_sizes table
        if ($minPrice !== null && $minPrice > 0) {
            $query .= " AND ps_avg.avg_price >= ?";
            $countQuery .= " AND EXISTS (SELECT 1 FROM product_sizes ps2 WHERE ps2.product_id = pdv.id AND ps2.selling_price >= ? AND ps2.is_active = 1)";
            $params[] = $minPrice;
        }
        if ($maxPrice !== null && $maxPrice > 0) {
            $query .= " AND ps_avg.avg_price <= ?";
            $countQuery .= " AND EXISTS (SELECT 1 FROM product_sizes ps3 WHERE ps3.product_id = pdv.id AND ps3.selling_price <= ? AND ps3.is_active = 1)";
            $params[] = $maxPrice;
        }
        
        // Group by product for the main query
        $query .= " GROUP BY pdv.id";
        
        // Add sorting
        switch ($sort) {
            case 'price_low':
                $query .= " ORDER BY price ASC";
                break;
            case 'price_high':
                $query .= " ORDER BY price DESC";
                break;
            case 'name_asc':
                $query .= " ORDER BY pdv.name ASC";
                break;
            case 'name_desc':
                $query .= " ORDER BY pdv.name DESC";
                break;
            case 'newest':
                $query .= " ORDER BY pdv.created_at DESC";
                break;
            case 'oldest':
                $query .= " ORDER BY pdv.created_at ASC";
                break;
            default: // featured
                $query .= " ORDER BY pdv.created_at DESC";
        }
        
        // Get total count first
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();
        
        // Add pagination to main query
        $offset = ($page - 1) * $perPage;
        $query .= " LIMIT ? OFFSET ?";
        $paginationParams = array_merge($params, [$perPage, $offset]);
        
        // Get products
        $stmt = $pdo->prepare($query);
        $stmt->execute($paginationParams);
        
        $products = [];
        while ($row = $stmt->fetch()) {
            $products[] = [
                'id' => $row['id'],
                'manufacturer_id' => $row['manufacturer_id'],
                'product_name' => $row['name'],
                'category_id' => $row['category_id'],
                'price' => (float)$row['price'],
                'min_price' => (float)$row['min_price'],
                'max_price' => (float)$row['max_price'],
                'description' => $row['description'],
                'image_url' => $row['image_url'],
                'category_name' => $row['category_name'] ?? 'Uncategorized',
                'subcategory_name' => $row['subcategory_name'] ?? '',
                'tags' => $row['tags'],
                'image_count' => $row['image_count'],
                'size_count' => $row['size_count'],
                'color_count' => $row['color_count'],
                'created_at' => $row['created_at']
            ];
        }
        
        return [
            'success' => true,
            'products' => $products,
            'total' => (int)$total,
            'pages' => ceil($total / $perPage),
            'current_page' => $page,
            'per_page' => $perPage
        ];
        
    } catch (Exception $e) {
        logError("getProducts error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Get all categories
function getCategories() {
    global $pdo;
    
    try {
        $query = "SELECT id, name, description FROM categories WHERE is_active = 1 ORDER BY name ASC";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $categories = [];
        while ($row = $stmt->fetch()) {
            $categories[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'description' => $row['description']
            ];
        }
        
        return [
            'success' => true,
            'categories' => $categories
        ];
        
    } catch (Exception $e) {
        logError("getCategories error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Get all available colors
function getAvailableColors() {
    global $pdo;
    
    try {
        // Use Product class to get colors with proper color code fixing
        require_once __DIR__ . '/../manufacture/includes/Product.class.php';
        $productObj = new Product($pdo);
        $colors = $productObj->getAvailableColors();
        
        return [
            'success' => true,
            'colors' => $colors
        ];
        
    } catch (Exception $e) {
        logError("getAvailableColors error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Get price range from all products
function getPriceRange() {
    global $pdo;
    
    try {
        $query = "SELECT 
                    MIN(ps.selling_price) as min_price,
                    MAX(ps.selling_price) as max_price
                  FROM product_sizes ps
                  JOIN products p ON ps.product_id = p.id
                  WHERE ps.is_active = 1 AND p.is_active = 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch();
        
        return [
            'success' => true,
            'min_price' => (float)($result['min_price'] ?? 0),
            'max_price' => (float)($result['max_price'] ?? 1000)
        ];
        
    } catch (Exception $e) {
        logError("getPriceRange error: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'min_price' => 0,
            'max_price' => 1000
        ];
    }
}

// Handle product operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Check if user is logged in (assuming session-based auth)
    session_start();
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Please login to perform this operation'
        ]);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    $operation = $_POST['operation'] ?? null;

    switch ($operation) {
        case 'delete':
            $product_id = $_POST['id'] ?? null;

            if (!$product_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product ID is required'
                ]);
                exit;
            }

            try {
                // Start transaction
                $pdo->beginTransaction();

                // First verify the product belongs to the manufacturer
                $check_stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND manufacturer_id = ?");
                $check_stmt->execute([$product_id, $user_id]);
                
                if ($check_stmt->rowCount() === 0) {
                    throw new Exception("Product not found or you don't have permission to delete it");
                }

                // Delete all cart items for this product
                $cart_stmt = $pdo->prepare("DELETE FROM cart WHERE product_id = ?");
                $cart_stmt->execute([$product_id]);

                // Delete product images
                $images_stmt = $pdo->prepare("DELETE FROM product_images WHERE product_id = ?");
                $images_stmt->execute([$product_id]);

                // Delete product sizes and colors
                $sizes_stmt = $pdo->prepare("DELETE FROM product_sizes WHERE product_id = ?");
                $sizes_stmt->execute([$product_id]);
                
                $colors_stmt = $pdo->prepare("DELETE FROM product_colors WHERE product_id = ?");
                $colors_stmt->execute([$product_id]);

                // Delete inventory records
                $inventory_stmt = $pdo->prepare("DELETE FROM inventory WHERE product_id = ?");
                $inventory_stmt->execute([$product_id]);

                // Finally delete the product
                $product_stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
                $product_stmt->execute([$product_id]);
                
                if ($product_stmt->rowCount() === 0) {
                    throw new Exception("Failed to delete product");
                }

                // Commit transaction
                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Product and related data deleted successfully'
                ]);

            } catch (Exception $e) {
                // Rollback transaction on error
                if ($pdo->inTransaction()) {
                    $pdo->rollback();
                }
                logError("Product deletion error: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid operation'
            ]);
            break;
    }
    exit;
} 