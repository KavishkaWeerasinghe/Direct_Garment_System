<?php
require_once __DIR__ . '/../config/database.php';

// Function to log errors
function logError($message) {
    $log_dir = dirname(__FILE__) . '/../logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $log_file = $log_dir . '/cart_errors.log';
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

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;

// Handle different cart operations
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'check_auth':
        if (!$user_id) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'User is logged in'
            ]);
        }
        break;

    case 'add':
        if (!$user_id) {
            die(json_encode([
                'success' => false,
                'message' => 'Please login to add items to cart'
            ]));
        }

        $product_id = $_POST['product_id'] ?? null;
        $quantity = $_POST['quantity'] ?? 1;

        if (!$product_id) {
            die(json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]));
        }

        try {
            // Check if product exists
            $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
            $stmt->execute([$product_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Product not found");
            }

            // Check if item already exists in cart
            $stmt = $pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            
            if ($stmt->rowCount() > 0) {
                // Update quantity
                $cart_item = $stmt->fetch();
                $new_quantity = $cart_item['quantity'] + $quantity;
                
                $update_stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                $update_stmt->execute([$new_quantity, $cart_item['id']]);
            } else {
                // Add new item
                $insert_stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $insert_stmt->execute([$user_id, $product_id, $quantity]);
            }

            echo json_encode([
                'success' => true,
                'message' => 'Product added to cart successfully'
            ]);

        } catch (Exception $e) {
            logError($e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'get_items':
        if (!$user_id) {
            die(json_encode([
                'success' => false,
                'message' => 'Please login to view cart'
            ]));
        }

        try {
            $stmt = $pdo->prepare("
                SELECT c.id, c.quantity, p.name as product_name, 
                       COALESCE(ps_min.selling_price, 0) as price,
                       COALESCE(pi.image_path, 'src/images/default-product.jpg') as image_url 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                LEFT JOIN (SELECT product_id, MIN(selling_price) as selling_price 
                          FROM product_sizes WHERE is_active = 1 GROUP BY product_id) ps_min 
                          ON p.id = ps_min.product_id
                LEFT JOIN (SELECT product_id, image_path FROM product_images WHERE is_main = 1) pi 
                          ON p.id = pi.product_id
                WHERE c.user_id = ? AND p.is_active = 1
            ");
            $stmt->execute([$user_id]);
            
            $items = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'items' => $items
            ]);

        } catch (Exception $e) {
            logError($e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'get_count':
        if (!$user_id) {
            die(json_encode([
                'success' => false,
                'message' => 'Please login to view cart'
            ]));
        }

        try {
            $stmt = $pdo->prepare("SELECT COUNT(DISTINCT product_id) as count FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $row = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'count' => $row['count'] ?? 0
            ]);

        } catch (Exception $e) {
            logError($e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'update_quantity':
        if (!$user_id) {
            die(json_encode([
                'success' => false,
                'message' => 'Please login to update cart'
            ]));
        }

        $cart_id = $_POST['cart_id'] ?? null;
        $quantity = $_POST['quantity'] ?? null;

        if (!$cart_id || !$quantity) {
            die(json_encode([
                'success' => false,
                'message' => 'Cart ID and quantity are required'
            ]));
        }

        try {
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$quantity, $cart_id, $user_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Failed to update quantity");
            }

            echo json_encode([
                'success' => true,
                'message' => 'Quantity updated successfully'
            ]);

        } catch (Exception $e) {
            logError($e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    case 'remove':
        if (!$user_id) {
            die(json_encode([
                'success' => false,
                'message' => 'Please login to remove items from cart'
            ]));
        }

        $cart_id = $_POST['cart_id'] ?? null;

        if (!$cart_id) {
            die(json_encode([
                'success' => false,
                'message' => 'Cart ID is required'
            ]));
        }

        try {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $stmt->execute([$cart_id, $user_id]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception("Failed to remove item");
            }

            echo json_encode([
                'success' => true,
                'message' => 'Item removed successfully'
            ]);

        } catch (Exception $e) {
            logError($e->getMessage());
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        break;

    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
} 