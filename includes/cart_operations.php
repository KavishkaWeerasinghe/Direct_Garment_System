<?php
require_once __DIR__ . '/db_connection.php';

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
if (!isset($conn) || $conn === false) {
    logError("Database connection failed: " . ($conn ? $conn->error : "Connection not established"));
    die(json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]));
}

// Get user ID from cookie
$user_id = isset($_COOKIE['user_id']) ? $_COOKIE['user_id'] : null;

// Handle different cart operations
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
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
            $stmt = $conn->prepare("SELECT id FROM product WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("Failed to prepare product check statement: " . $conn->error);
            }
            $stmt->bind_param("i", $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception("Product not found");
            }
            $stmt->close();

            // Check if item already exists in cart
            $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
            if ($stmt === false) {
                throw new Exception("Failed to prepare cart check statement: " . $conn->error);
            }
            $stmt->bind_param("ii", $user_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                // Update quantity
                $cart_item = $result->fetch_assoc();
                $new_quantity = $cart_item['quantity'] + $quantity;
                
                $update_stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                if ($update_stmt === false) {
                    throw new Exception("Failed to prepare update statement: " . $conn->error);
                }
                $update_stmt->bind_param("ii", $new_quantity, $cart_item['id']);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                // Add new item
                $insert_stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                if ($insert_stmt === false) {
                    throw new Exception("Failed to prepare insert statement: " . $conn->error);
                }
                $insert_stmt->bind_param("iii", $user_id, $product_id, $quantity);
                $insert_stmt->execute();
                $insert_stmt->close();
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
            $stmt = $conn->prepare("
                SELECT c.id, c.quantity, p.product_name, p.price, p.main_image as image_url 
                FROM cart c 
                JOIN product p ON c.product_id = p.id 
                WHERE c.user_id = ?
            ");
            if ($stmt === false) {
                throw new Exception("Failed to prepare get items statement: " . $conn->error);
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            
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
            $stmt = $conn->prepare("SELECT COUNT(DISTINCT product_id) as count FROM cart WHERE user_id = ?");
            if ($stmt === false) {
                throw new Exception("Failed to prepare count statement: " . $conn->error);
            }
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
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
            $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            if ($stmt === false) {
                throw new Exception("Failed to prepare update quantity statement: " . $conn->error);
            }
            $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
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
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            if ($stmt === false) {
                throw new Exception("Failed to prepare remove statement: " . $conn->error);
            }
            $stmt->bind_param("ii", $cart_id, $user_id);
            $stmt->execute();
            
            if ($stmt->affected_rows === 0) {
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