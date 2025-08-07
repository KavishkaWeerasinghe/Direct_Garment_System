<?php
require_once __DIR__ . '/db_connection.php';

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
if (!isset($conn) || $conn === false) {
    logError("Database connection failed: " . ($conn ? $conn->error : "Connection not established"));
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]));
}

// Get all products with optional filters
function getProducts($category = null, $minPrice = null, $maxPrice = null, $sort = 'featured', $page = 1, $perPage = 12) {
    global $conn;
    $stmt = null;
    $countStmt = null;
    
    try {
        // Build the base query
        $query = "SELECT p.*, c.name as category_name 
                 FROM product p 
                 LEFT JOIN category c ON p.category_id = c.category_id 
                 WHERE 1=1";
        $countQuery = "SELECT COUNT(*) as total FROM product p WHERE 1=1";
        $params = [];
        $types = "";
        
        // Add category filter
        if ($category) {
            $query .= " AND p.category_id = ?";
            $countQuery .= " AND p.category_id = ?";
            $params[] = $category;
            $types .= "i";
        }
        
        // Add price filters
        if ($minPrice !== null) {
            $query .= " AND p.price >= ?";
            $countQuery .= " AND p.price >= ?";
            $params[] = $minPrice;
            $types .= "d";
        }
        if ($maxPrice !== null) {
            $query .= " AND p.price <= ?";
            $countQuery .= " AND p.price <= ?";
            $params[] = $maxPrice;
            $types .= "d";
        }
        
        // Add sorting
        switch ($sort) {
            case 'price_low':
                $query .= " ORDER BY p.price ASC";
                break;
            case 'price_high':
                $query .= " ORDER BY p.price DESC";
                break;
            default: // featured
                $query .= " ORDER BY p.id DESC";
        }
        
        // Add pagination
        $offset = ($page - 1) * $perPage;
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;
        $types .= "ii";
        
        // Get total count
        $countStmt = $conn->prepare($countQuery);
        if ($countStmt === false) {
            throw new Exception("Failed to prepare count statement: " . $conn->error);
        }
        
        if (!empty($params)) {
            // Remove pagination parameters for count query
            $countParams = array_slice($params, 0, -2);
            $countTypes = substr($types, 0, -2);
            if (!empty($countParams)) {
                $countStmt->bind_param($countTypes, ...$countParams);
            }
        }
        
        if (!$countStmt->execute()) {
            throw new Exception("Failed to execute count query: " . $countStmt->error);
        }
        
        $countResult = $countStmt->get_result();
        $total = $countResult->fetch_assoc()['total'];
        
        // Get products
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Failed to prepare products statement: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute products query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $products = [];
        
        while ($row = $result->fetch_assoc()) {
            $products[] = [
                'id' => $row['id'],
                'manufacture_id' => $row['manufacture_id'],
                'product_name' => $row['product_name'],
                'category_id' => $row['category_id'],
                'price' => $row['price'],
                'description' => $row['description'],
                'image_url' => $row['main_image'],
                'category_name' => $row['category_name']
            ];
        }
        
        return [
            'success' => true,
            'products' => $products,
            'total' => $total,
            'pages' => ceil($total / $perPage)
        ];
        
    } catch (Exception $e) {
        logError($e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    } finally {
        if ($stmt !== null && $stmt !== false) {
            $stmt->close();
        }
        if ($countStmt !== null && $countStmt !== false) {
            $countStmt->close();
        }
    }
}

// Get all categories
function getCategories() {
    global $conn;
    $stmt = null;
    
    try {
        $query = "SELECT category_id, name, description FROM category ORDER BY name ASC";
        $stmt = $conn->prepare($query);
        
        if ($stmt === false) {
            throw new Exception("Failed to prepare categories statement: " . $conn->error);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute categories query: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $categories = [];
        
        while ($row = $result->fetch_assoc()) {
            $categories[] = [
                'id' => $row['category_id'],
                'name' => $row['name'],
                'description' => $row['description']
            ];
        }
        
        return [
            'success' => true,
            'categories' => $categories
        ];
        
    } catch (Exception $e) {
        logError($e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    } finally {
        if ($stmt !== null && $stmt !== false) {
            $stmt->close();
        }
    }
}

// Handle product operations
$operation = $_POST['operation'] ?? null;

switch ($operation) {
    case 'delete':
        if (!$user_id) {
            die(json_encode([
                'success' => false,
                'message' => 'Please login to delete products'
            ]));
        }

        $product_id = $_POST['id'] ?? null;

        if (!$product_id) {
            die(json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]));
        }

        try {
            // Start transaction
            $conn->begin_transaction();

            // First verify the product belongs to the manufacturer
            $check_stmt = $conn->prepare("SELECT id FROM product WHERE id = ? AND manufacture_id = ?");
            if ($check_stmt === false) {
                throw new Exception("Failed to prepare product check statement: " . $conn->error);
            }
            $check_stmt->bind_param("ii", $product_id, $user_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Product not found or you don't have permission to delete it");
            }
            $check_stmt->close();

            // Delete all cart items for this product
            $cart_stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ?");
            if ($cart_stmt === false) {
                throw new Exception("Failed to prepare cart delete statement: " . $conn->error);
            }
            $cart_stmt->bind_param("i", $product_id);
            $cart_stmt->execute();
            $cart_stmt->close();

            // Now delete the product
            $product_stmt = $conn->prepare("DELETE FROM product WHERE id = ?");
            if ($product_stmt === false) {
                throw new Exception("Failed to prepare product delete statement: " . $conn->error);
            }
            $product_stmt->bind_param("i", $product_id);
            $product_stmt->execute();
            
            if ($product_stmt->affected_rows === 0) {
                throw new Exception("Failed to delete product");
            }
            $product_stmt->close();

            // Commit transaction
            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Product and related cart items deleted successfully'
            ]);

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            logError($e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    default:
        // Handle other operations
        break;
} 