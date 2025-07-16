<?php
// Prevent PHP errors from being displayed
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../includes/db_connection.php';

// Function to log errors
function logError($message) {
    $log_dir = dirname(__FILE__) . '/../logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $log_file = $log_dir . '/product_errors.log';
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, $log_file);
}

function uploadProductImage($file, $isMain = false) {
    $target_dir = dirname(__FILE__) . "../../../src/images/products/";
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        if (!mkdir($target_dir, 0777, true)) {
            logError("Failed to create directory: " . $target_dir);
            return ["success" => false, "message" => "Failed to create upload directory"];
        }
    }

    // Generate unique filename
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;

    // Check if image file is a actual image
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        logError("Invalid image file: " . $file["name"]);
        return ["success" => false, "message" => "File is not an image."];
    }

    // Check file size (5MB max)
    if ($file["size"] > 5000000) {
        logError("File too large: " . $file["name"]);
        return ["success" => false, "message" => "File is too large."];
    }

    // Allow certain file formats
    if($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg") {
        logError("Invalid file format: " . $file_extension);
        return ["success" => false, "message" => "Only JPG, JPEG & PNG files are allowed."];
    }

    // Upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [
            "success" => true, 
            "filename" => $new_filename,
            "path" => "src/images/products/" . $new_filename
        ];
    } else {
        logError("Failed to upload file: " . $file["name"]);
        return ["success" => false, "message" => "Error uploading file."];
    }
}

function validateCategory($category_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT category_id FROM category WHERE category_id = ?");
    if (!$stmt) {
        logError("Prepare failed for category validation: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    if (!$exists) {
        logError("Invalid category_id: " . $category_id);
    }
    
    return $exists;
}

function addProduct($productData, $mainImage, $additionalImages) {
    global $conn;
    
    try {
        // Validate category exists
        if (!validateCategory($productData['category_id'])) {
            throw new Exception("Invalid category selected. Please select a valid category.");
        }

        // Start transaction
        $conn->query("START TRANSACTION");

        // Upload main image
        $mainImageResult = uploadProductImage($mainImage, true);
        if (!$mainImageResult["success"]) {
            throw new Exception($mainImageResult["message"]);
        }

        // Insert product
        $stmt = $conn->prepare("INSERT INTO product (manufacture_id, category_id, product_name, price, description, main_image) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            logError("Prepare failed: " . $conn->error);
            throw new Exception("Database error: " . $conn->error);
        }

        $stmt->bind_param("iisdss", 
            $productData['manufacture_id'],
            $productData['category_id'],
            $productData['product_name'],
            $productData['price'],
            $productData['description'],
            $mainImageResult["path"]
        );
        
        if (!$stmt->execute()) {
            logError("Execute failed: " . $stmt->error);
            throw new Exception("Error inserting product: " . $stmt->error);
        }
        
        $product_id = $conn->insert_id;

        // Upload and insert additional images
        if (!empty($additionalImages)) {
            $stmt = $conn->prepare("INSERT INTO sub_product_images (product_id, product_url) VALUES (?, ?)");
            if (!$stmt) {
                logError("Prepare failed for additional images: " . $conn->error);
                throw new Exception("Database error: " . $conn->error);
            }
            
            foreach ($additionalImages as $image) {
                $imageResult = uploadProductImage($image);
                if ($imageResult["success"]) {
                    $stmt->bind_param("is", $product_id, $imageResult["path"]);
                    if (!$stmt->execute()) {
                        logError("Failed to insert additional image: " . $stmt->error);
                        throw new Exception("Error inserting additional image: " . $stmt->error);
                    }
                }
            }
        }

        // Commit transaction
        $conn->query("COMMIT");
        return ["success" => true, "message" => "Product added successfully", "product_id" => $product_id];

    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->query("ROLLBACK");
        logError("Error in addProduct: " . $e->getMessage());
        return ["success" => false, "message" => $e->getMessage()];
    }
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $response = ["success" => false, "message" => ""];

    try {
        if (!isset($_POST['action'])) {
            throw new Exception("No action specified");
        }

        switch ($_POST['action']) {
            case 'add':
                // Log incoming request
                logError("Received POST request: " . print_r($_POST, true));
                logError("Received FILES: " . print_r($_FILES, true));

                // Validate required fields
                $required_fields = ['product_name', 'price', 'description', 'category_id'];
                foreach ($required_fields as $field) {
                    if (!isset($_POST[$field]) || empty($_POST[$field])) {
                        throw new Exception("Missing required field: " . $field);
                    }
                }

                // Prepare product data
                $productData = [
                    'manufacture_id' => 1, // Hardcoded as requested
                    'category_id' => intval($_POST['category_id']),
                    'product_name' => $_POST['product_name'],
                    'price' => floatval($_POST['price']),
                    'description' => $_POST['description']
                ];

                // Validate main image
                if (!isset($_FILES['main_image']) || $_FILES['main_image']['error'] !== UPLOAD_ERR_OK) {
                    throw new Exception("Main image is required");
                }

                // Handle additional images
                $additionalImages = [];
                if (isset($_FILES['additional_images'])) {
                    foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                            $additionalImages[] = [
                                'name' => $_FILES['additional_images']['name'][$key],
                                'type' => $_FILES['additional_images']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['additional_images']['error'][$key],
                                'size' => $_FILES['additional_images']['size'][$key]
                            ];
                        }
                    }
                }

                // Add product
                $result = addProduct($productData, $_FILES['main_image'], $additionalImages);
                $response = $result;
                break;
            case 'update':
                $productData = [
                    'product_id' => intval($_POST['id']),
                    'category_id' => intval($_POST['category_id']),
                    'product_name' => $_POST['product_name'],
                    'price' => floatval($_POST['price']),
                    'description' => $_POST['description']
                ];

                // Log the incoming data for debugging
                logError("Update request data: " . print_r($_POST, true));
                logError("Update request files: " . print_r($_FILES, true));

                // Handle main image
                if (isset($_POST['main_image_path'])) {
                    // If using existing image path
                    $mainImage = $_POST['main_image_path'];
                } elseif (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                    // If uploading new image
                    $mainImage = $_FILES['main_image'];
                } else {
                    throw new Exception("Main image is required");
                }

                // Handle additional images
                $additionalImages = [];
                if (isset($_FILES['additional_images'])) {
                    foreach ($_FILES['additional_images']['tmp_name'] as $key => $tmp_name) {
                        if ($_FILES['additional_images']['error'][$key] === UPLOAD_ERR_OK) {
                            $additionalImages[] = [
                                'name' => $_FILES['additional_images']['name'][$key],
                                'type' => $_FILES['additional_images']['type'][$key],
                                'tmp_name' => $tmp_name,
                                'error' => $_FILES['additional_images']['error'][$key],
                                'size' => $_FILES['additional_images']['size'][$key]
                            ];
                        }
                    }
                }

                $response = updateProduct($productData, $mainImage, $additionalImages);
                break;
            case 'delete':
                if (!isset($_POST['id'])) {
                    throw new Exception("Product ID is required");
                }
                $response = deleteProduct(intval($_POST['id']));
                break;
            default:
                throw new Exception("Invalid action");
        }
    } catch (Exception $e) {
        logError("Error in main handler: " . $e->getMessage());
        $response["message"] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}

// Add new function to get categories
function getCategories() {
    global $conn;
    $categories = [];
    
    $query = "SELECT category_id, name FROM category ORDER BY name ASC";
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    } else {
        logError("Error fetching categories: " . $conn->error);
    }
    
    return $categories;
}

// Handle GET request for categories
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_categories') {
    header('Content-Type: application/json');
    echo json_encode(getCategories());
    exit;
}

function getSubImages($product_id) {
    global $conn;
    $sub_images = array();
    
    $query = "SELECT product_url FROM sub_product_images WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            logError("Error executing sub-images query: " . $stmt->error);
            return $sub_images;
        }
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $sub_images[] = $row['product_url'];
        }
        
        $stmt->close();
    } else {
        logError("Error preparing sub-images query: " . $conn->error);
    }
    
    return $sub_images;
}

function getProducts() {
    global $conn;
    $products = array();
    
    $query = "SELECT p.*, c.name as category_name 
              FROM product p 
              LEFT JOIN category c ON p.category_id = c.category_id 
              ORDER BY p.id DESC";
    
    $result = $conn->query($query);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Get sub-images for each product
            $sub_images = getSubImages($row['id']);
            logError("Sub images for product {$row['id']}: " . print_r($sub_images, true));
            $row['sub_images'] = $sub_images;
            
            // Ensure description is properly set
            if (!isset($row['description'])) {
                $row['description'] = '';
            }
            
            $products[] = $row;
        }
    } else {
        logError("Error fetching products: " . $conn->error);
    }
    
    return $products;
}

function updateProduct($productData, $mainImage, $additionalImages) {
    global $conn;
    
    try {
        $conn->query("START TRANSACTION");
        
        // Get current main image
        $stmt = $conn->prepare("SELECT main_image FROM product WHERE id = ?");
        $stmt->bind_param("i", $productData['product_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentMainImage = $result->fetch_assoc()['main_image'];
        
        // Update product details
        $stmt = $conn->prepare("UPDATE product SET 
            category_id = ?, 
            product_name = ?, 
            price = ?, 
            description = ? 
            WHERE id = ?");
            
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }

        // Fix: Use $productData['description'] directly instead of $description['description']
        $stmt->bind_param("isdsi", 
            $productData['category_id'],
            $productData['product_name'],
            $productData['price'],
            $productData['description'], // This was the main issue - using wrong variable
            $productData['product_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating product: " . $stmt->error);
        }

        // Rest of the function remains the same...
        // Handle main image
        if (isset($_POST['main_image_path'])) {
            // If using existing image path
            $newMainImagePath = "src/images/products/" . $_POST['main_image_path'];
            logError("Updating main image path to: " . $newMainImagePath);
            
            // Update main image
            $stmt = $conn->prepare("UPDATE product SET main_image = ? WHERE id = ?");
            $stmt->bind_param("si", $newMainImagePath, $productData['product_id']);
            if (!$stmt->execute()) {
                throw new Exception("Error updating main image: " . $stmt->error);
            }

            // Remove the new main image from sub_product_images if it exists
            $stmt = $conn->prepare("DELETE FROM sub_product_images WHERE product_id = ? AND product_url = ?");
            $stmt->bind_param("is", $productData['product_id'], $newMainImagePath);
            $stmt->execute();

            // Add the old main image to sub_product_images if it's different
            if ($currentMainImage !== $newMainImagePath) {
                $stmt = $conn->prepare("INSERT INTO sub_product_images (product_id, product_url) VALUES (?, ?)");
                $stmt->bind_param("is", $productData['product_id'], $currentMainImage);
                $stmt->execute();
            }
        } elseif (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
            // If uploading new image
            $mainImageResult = uploadProductImage($_FILES['main_image'], true);
            if (!$mainImageResult["success"]) {
                throw new Exception($mainImageResult["message"]);
            }
            
            // Update main image
            $stmt = $conn->prepare("UPDATE product SET main_image = ? WHERE id = ?");
            $stmt->bind_param("si", $mainImageResult["path"], $productData['product_id']);
            if (!$stmt->execute()) {
                throw new Exception("Error updating main image: " . $stmt->error);
            }

            // Add the old main image to sub_product_images
            $stmt = $conn->prepare("INSERT INTO sub_product_images (product_id, product_url) VALUES (?, ?)");
            $stmt->bind_param("is", $productData['product_id'], $currentMainImage);
            $stmt->execute();
        }

        // Handle additional images if provided
        if (!empty($additionalImages)) {
            $stmt = $conn->prepare("INSERT INTO sub_product_images (product_id, product_url) VALUES (?, ?)");
            foreach ($additionalImages as $image) {
                $imageResult = uploadProductImage($image);
                if ($imageResult["success"]) {
                    $stmt->bind_param("is", $productData['product_id'], $imageResult["path"]);
                    if (!$stmt->execute()) {
                        throw new Exception("Error adding additional image: " . $stmt->error);
                    }
                }
            }
        }

        $conn->query("COMMIT");
        return ["success" => true, "message" => "Product updated successfully"];

    } catch (Exception $e) {
        $conn->query("ROLLBACK");
        logError("Error in updateProduct: " . $e->getMessage());
        return ["success" => false, "message" => $e->getMessage()];
    }
}

function deleteProduct($product_id) {
    global $conn;
    
    try {
        $conn->query("START TRANSACTION");
        
        // First delete cart items for this product
        $cart_stmt = $conn->prepare("DELETE FROM cart WHERE product_id = ?");
        if (!$cart_stmt) {
            throw new Exception("Prepare failed for cart deletion: " . $conn->error);
        }
        $cart_stmt->bind_param("i", $product_id);
        if (!$cart_stmt->execute()) {
            throw new Exception("Error deleting cart items: " . $cart_stmt->error);
        }
        $cart_stmt->close();
        
        // Then delete additional images
        $stmt = $conn->prepare("DELETE FROM sub_product_images WHERE product_id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed for sub images: " . $conn->error);
        }
        $stmt->bind_param("i", $product_id);
        if (!$stmt->execute()) {
            throw new Exception("Error deleting additional images: " . $stmt->error);
        }
        $stmt->close();
        
        // Finally delete the main product
        $product_stmt = $conn->prepare("DELETE FROM product WHERE id = ?");
        if (!$product_stmt) {
            throw new Exception("Prepare failed for product: " . $conn->error);
        }
        $product_stmt->bind_param("i", $product_id);
        if (!$product_stmt->execute()) {
            throw new Exception("Error deleting product: " . $product_stmt->error);
        }
        $product_stmt->close();
        
        $conn->query("COMMIT");
        return ["success" => true, "message" => "Product and related items deleted successfully"];
        
    } catch (Exception $e) {
        $conn->query("ROLLBACK");
        logError("Error in deleteProduct: " . $e->getMessage());
        return ["success" => false, "message" => $e->getMessage()];
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json');
        
        switch ($_GET['action']) {
            case 'get_categories':
                echo json_encode(getCategories());
                break;
            case 'get_products':
                $products = getProducts();
                logError("Products fetched: " . print_r($products, true));
                echo json_encode($products);
                break;
            default:
                echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
        exit;
    }
}
?> 