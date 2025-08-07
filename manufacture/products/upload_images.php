<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/Product.class.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['manufacturer_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$manufacturer_id = $_SESSION['manufacturer_id'];

try {
    // Check if files were uploaded
    if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
        echo json_encode(['success' => false, 'message' => 'No images uploaded']);
        exit;
    }

    $product = new Product($pdo);
    $uploaded_images = [];

    // Process each uploaded file
    foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['images']['name'][$key];
            $file_size = $_FILES['images']['size'][$key];
            $file_type = $_FILES['images']['type'][$key];

            // Validate file type
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($file_type, $allowed_types)) {
                continue; // Skip invalid file types
            }

            // Validate file size (max 5MB)
            if ($file_size > 5 * 1024 * 1024) {
                continue; // Skip files larger than 5MB
            }

            // Generate unique filename
            $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
            $unique_filename = uniqid() . '_' . $file_name;

            // Create manufacturer directory if it doesn't exist
            $manufacturer_dir = __DIR__ . '/uploads/manufacturer_' . $manufacturer_id;
            if (!is_dir($manufacturer_dir)) {
                mkdir($manufacturer_dir, 0755, true);
            }

            // Create temporary product directory for uploads
            $temp_product_dir = $manufacturer_dir . '/temp_' . uniqid();
            if (!is_dir($temp_product_dir)) {
                mkdir($temp_product_dir, 0755, true);
            }

            // Move uploaded file
            $destination = $temp_product_dir . '/' . $unique_filename;
            if (move_uploaded_file($tmp_name, $destination)) {
                $uploaded_images[] = [
                    'filename' => $unique_filename,
                    'path' => $destination,
                    'temp_dir' => $temp_product_dir,
                    'original_name' => $file_name
                ];
            }
        }
    }

    if (empty($uploaded_images)) {
        echo json_encode(['success' => false, 'message' => 'No valid images were uploaded']);
        exit;
    }

    // Return success with uploaded image information
    echo json_encode([
        'success' => true,
        'message' => 'Images uploaded successfully',
        'images' => $uploaded_images
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error uploading images: ' . $e->getMessage()]);
}
?> 