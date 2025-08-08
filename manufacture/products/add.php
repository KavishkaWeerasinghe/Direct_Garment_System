<?php
// Prevent any output before JSON response
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Suppress warnings that might be output as HTML
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);

// Start output buffering to catch any unexpected output
ob_start();

// Custom error handler to prevent HTML output
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    // Log the error but don't output it
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    return true; // Don't execute PHP's internal error handler
}

// Set custom error handler
set_error_handler('customErrorHandler');

require_once '../../config/database.php';
require_once '../includes/Product.class.php';
require_once '../includes/Manufacturer.class.php';

// Check if user is logged in
if (!Manufacturer::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$manufacturer_id = Manufacturer::getCurrentUserId();

// Check if we're editing a product
$edit_product_id = $_GET['edit'] ?? null;
$edit_product = null;

// Initialize Product class
$productObj = new Product($pdo);

// If editing, get the product data
if ($edit_product_id) {
    try {
        $edit_product = $productObj->getProductById($edit_product_id, $manufacturer_id);
        if (!$edit_product) {
            header('Location: list.php');
            exit;
        }
    } catch (Exception $e) {
        header('Location: list.php');
        exit;
    }
}

// Handle AJAX requests for category search
if (isset($_GET['action'])) {
    // Ensure no output has been sent before this point
    if (headers_sent($file, $line)) {
        error_log("Headers already sent in $file:$line");
        exit;
    }
    
    // Set JSON content type
    header('Content-Type: application/json');
    
    // Log the action being processed
    error_log("Processing AJAX action: " . $_GET['action']);
    
    switch ($_GET['action']) {
        default:
            // Check for any unexpected output
            $unexpected_output = ob_get_contents();
            if (!empty($unexpected_output)) {
                error_log("Unexpected output before JSON response: " . $unexpected_output);
                ob_clean();
            }
            break;
        case 'search_categories':
            $search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
            $categories = $productObj->searchCategoriesForAutocomplete($search_term);
            echo json_encode($categories);
            exit;
            
        case 'search_subcategories':
            $search_term = isset($_GET['q']) ? trim($_GET['q']) : '';
            $category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
            $subcategories = $productObj->searchSubcategoriesForAutocomplete($search_term, $category_id);
            echo json_encode($subcategories);
            exit;
            
        case 'upload_images':
            // Handle image upload
            if (!isset($_FILES['images']) || empty($_FILES['images']['name'][0])) {
                echo json_encode(['success' => false, 'message' => 'No images uploaded']);
                exit;
            }
            
            $uploaded_images = [];
            
            // Create a single product folder for all images
            $manufacturer_dir = __DIR__ . '/uploads/manufacturer_' . $manufacturer_id;
            if (!is_dir($manufacturer_dir)) {
                mkdir($manufacturer_dir, 0755, true);
            }
            
            // Use session to maintain consistent product folder
            if (!isset($_SESSION['current_product_folder'])) {
                $_SESSION['current_product_folder'] = 'product_' . date('Ymd_His') . '_' . uniqid();
            }
            
            $product_folder_name = $_SESSION['current_product_folder'];
            $product_dir = $manufacturer_dir . '/' . $product_folder_name;
            if (!is_dir($product_dir)) {
                mkdir($product_dir, 0755, true);
            }
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = $_FILES['images']['name'][$key];
                    $file_size = $_FILES['images']['size'][$key];
                    $file_type = $_FILES['images']['type'][$key];
                    
                    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($file_type, $allowed_types)) {
                        continue;
                    }
                    
                    if ($file_size > 5 * 1024 * 1024) {
                        continue;
                    }
                    
                    $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                    $unique_filename = uniqid() . '_' . $file_name;
                    
                    $destination = $product_dir . '/' . $unique_filename;
                    if (move_uploaded_file($tmp_name, $destination)) {
                        // Create web-accessible URL
                        $web_url = 'http://localhost/direct-garment/' . 'manufacture/products/uploads/manufacturer_' . $manufacturer_id . '/' . $product_folder_name . '/' . $unique_filename;
                        
                        $uploaded_images[] = [
                            'id' => uniqid(),
                            'filename' => $unique_filename,
                            'path' => $web_url,
                            'relative_path' => 'manufacture/products/uploads/manufacturer_' . $manufacturer_id . '/' . $product_folder_name . '/' . $unique_filename,
                            'full_path' => $destination,
                            'product_dir' => $product_dir,
                            'original_name' => $file_name
                        ];
                    }
                }
            }
            
            if (empty($uploaded_images)) {
                echo json_encode(['success' => false, 'message' => 'No valid images were uploaded']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Images uploaded successfully',
                'images' => $uploaded_images
            ]);
            exit;
            
        case 'test_image_access':
            // Test if an image is accessible
            $image_path = $_GET['path'] ?? '';
            if ($image_path) {
                $full_path = __DIR__ . '/uploads/' . $image_path;
                if (file_exists($full_path)) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Image exists',
                        'path' => $full_path,
                        'size' => filesize($full_path)
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Image not found',
                        'path' => $full_path
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'No image path provided'
                ]);
            }
            exit;
            
        case 'test_json':
            // Simple test endpoint
            // Clear any output buffer
            ob_clean();
            
            echo json_encode([
                'success' => true,
                'message' => 'JSON test successful',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit;
            
        case 'reset_product_folder':
            // Reset the product folder for new product
            unset($_SESSION['current_product_folder']);
            echo json_encode([
                'success' => true,
                'message' => 'Product folder reset successfully'
            ]);
            exit;
            
        case 'save_product':
            // Handle product save
            $action = $_POST['action'] ?? '';
            
            // Add debugging
            error_log("Product save request - Action: $action, Product ID: " . ($_POST['product_id'] ?? 'null'));
            error_log("POST data: " . print_r($_POST, true));
            
            // Ensure we're still sending JSON
            if (headers_sent($file, $line)) {
                error_log("Headers already sent in save_product in $file:$line");
                echo json_encode(['success' => false, 'message' => 'Server error: Headers already sent']);
                exit;
            }
            
            // Process sizes - decode JSON strings to arrays
            $sizes = [];
            if (isset($_POST['sizes']) && is_array($_POST['sizes'])) {
                foreach ($_POST['sizes'] as $size_json) {
                    $size_data = json_decode($size_json, true);
                    if ($size_data) {
                        $sizes[] = $size_data;
                    }
                }
            }
            
            // Process colors - decode JSON strings to arrays
            $colors = [];
            if (isset($_POST['colors']) && is_array($_POST['colors'])) {
                foreach ($_POST['colors'] as $color_json) {
                    $color_data = json_decode($color_json, true);
                    if ($color_data) {
                        $colors[] = $color_data;
                    }
                }
            }
            
            // Process tags - ensure it's an array
            $tags = [];
            if (isset($_POST['tags']) && is_array($_POST['tags'])) {
                $tags = $_POST['tags'];
            }
            
            // Process images - handle both existing and new images
            $images = [];
            $existing_images = [];
            $new_images = [];
            
            if (isset($_POST['images']) && is_array($_POST['images'])) {
                foreach ($_POST['images'] as $image_path) {
                    // Check if this is an existing image (has database ID in path)
                    if (strpos($image_path, 'existing_') === 0) {
                        // Existing image - extract ID and path
                        $parts = explode('|', $image_path);
                        if (count($parts) >= 2) {
                            $image_id = str_replace('existing_', '', $parts[0]);
                            $actual_path = $parts[1];
                            $existing_images[] = [
                                'id' => $image_id,
                                'path' => $actual_path,
                                'is_main' => false
                            ];
                        }
                    } else {
                        // New image
                        $new_images[] = [
                            'path' => $image_path,
                            'is_main' => false
                        ];
                    }
                }
            }
            
            // Combine existing and new images
            $images = array_merge($existing_images, $new_images);
            
            // Mark the main image
            $main_image_path = $_POST['main_image'] ?? '';
            if ($main_image_path && !empty($images)) {
                foreach ($images as &$image) {
                    if ($image['path'] === $main_image_path) {
                        $image['is_main'] = true;
                        break;
                    }
                }
            }
            
            $product_data = [
                'name' => $_POST['name'] ?? '',
                'description' => $_POST['description'] ?? '',
                'category_id' => $_POST['category_id'] ?? '',
                'subcategory_id' => $_POST['subcategory_id'] ?? '',
                'tags' => implode(',', $tags), // Convert array to comma-separated string
                'sizes' => $sizes,
                'colors' => $colors,
                'images' => $images
            ];
            
            try {
                // Check for any unexpected output before processing
                $unexpected_output = ob_get_contents();
                if (!empty($unexpected_output)) {
                    error_log("Unexpected output in save_product: " . $unexpected_output);
                    ob_clean();
                }
                
                $product_id = $_POST['product_id'] ?? null;
                
                if ($product_id && !empty($product_id)) {
                    // Check if product exists and belongs to this manufacturer
                    $existing_product = $productObj->getProductById($product_id, $manufacturer_id);
                    
                    error_log("Existing product check - Product ID: $product_id, Found: " . ($existing_product ? 'yes' : 'no'));
                    
                    if ($existing_product) {
                        // Update existing product
                        $product_data['product_id'] = $product_id;
                        $result = $productObj->updateProduct($product_id, $manufacturer_id, $product_data);
                        
                        error_log("Update result: " . ($result ? 'success' : 'failed'));
                        
                        if ($result) {
                            ob_clean();
                            echo json_encode([
                                'success' => true,
                                'message' => 'Product updated successfully!',
                                'product_id' => $product_id
                            ]);
                        } else {
                            ob_clean();
                            echo json_encode([
                                'success' => false,
                                'message' => 'Failed to update product'
                            ]);
                        }
                    } else {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Product not found or unauthorized access'
                        ]);
                    }
                } else {
                    // Create new product
                    error_log("Creating new product - Action: $action");
                    
                    if ($action === 'save_draft') {
                        // Save as draft
                        $product_id = $productObj->createProduct($manufacturer_id, $product_data);
                        error_log("New product created (draft) - ID: $product_id");
                        ob_clean();
                        echo json_encode([
                            'success' => true,
                            'message' => 'Product saved as draft successfully!',
                            'product_id' => $product_id
                        ]);
                    } elseif ($action === 'publish') {
                        // Publish product
                        $product_id = $productObj->createProduct($manufacturer_id, $product_data);
                        error_log("New product created (publish) - ID: $product_id");
                        ob_clean();
                        echo json_encode([
                            'success' => true,
                            'message' => 'Product published successfully!',
                            'product_id' => $product_id
                        ]);
                    } else {
                        ob_clean();
                        echo json_encode([
                            'success' => false,
                            'message' => 'Invalid action'
                        ]);
                    }
                }
            } catch (Exception $e) {
                error_log("Product save error: " . $e->getMessage());
                error_log("Product save error trace: " . $e->getTraceAsString());
                
                // Check for any output before sending JSON
                $unexpected_output = ob_get_contents();
                if (!empty($unexpected_output)) {
                    error_log("Unexpected output in catch block: " . $unexpected_output);
                    ob_clean();
                }
                
                // Clear any remaining output buffer
                ob_clean();
                
                echo json_encode([
                    'success' => false,
                    'message' => 'Error saving product: ' . $e->getMessage()
                ]);
            }
            exit;
            
        case 'update_main_image':
            $data = json_decode(file_get_contents('php://input'), true);
            $product_id = $data['product_id'] ?? '';
            $image_id = $data['image_id'] ?? '';
            
            try {
                $result = $productObj->updateMainImage($product_id, $image_id, $manufacturer_id);
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Main image updated successfully!',
                        'main_image' => $result
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to update main image'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error updating main image: ' . $e->getMessage()
                ]);
            }
            exit;
            
        case 'delete_image':
            $data = json_decode(file_get_contents('php://input'), true);
            $image_id = $data['image_id'] ?? '';
            
            try {
                $result = $productObj->deleteProductImage($image_id, $manufacturer_id);
                if ($result) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Image deleted successfully!'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Failed to delete image'
                    ]);
                }
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error deleting image: ' . $e->getMessage()
                ]);
            }
            exit;
    }
    
    // Clean up output buffer
    ob_end_clean();
}

// Get common colors and sizes
$commonColors = $productObj->getCommonColors();
$commonSizes = $productObj->getCommonSizes();

// Debug: Get all categories for testing
$allCategories = $productObj->searchCategoriesForAutocomplete('');

// Include header
include '../components/header.php';
?>

<!-- Sidebar -->
<?php include '../components/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <h1><i class="fas fa-<?php echo $edit_product ? 'edit' : 'plus-circle'; ?>"></i> <?php echo $edit_product ? 'Edit Product' : 'Add New Product'; ?></h1>
        <p><?php echo $edit_product ? 'Update product information, images, and pricing' : 'Create a new product listing with detailed information, images, and pricing'; ?></p>
    </div>

    <!-- Product Form -->
    <form id="productForm" method="POST" enctype="multipart/form-data">
        <input type="hidden" id="manufacturerId" name="manufacturer_id" value="<?php echo $manufacturer_id; ?>">
        <input type="hidden" id="productId" name="product_id" value="<?php echo $edit_product ? $edit_product['id'] : ''; ?>">
        <input type="hidden" id="mainImage" name="main_image" value="">
        
        <!-- Product Details Section -->
        <div class="form-section">
            <h2><i class="fas fa-info-circle"></i> Product Details</h2>
            
            <div class="form-group">
                <label for="productName" class="form-label">Product Name *</label>
                <input type="text" id="productName" name="name" class="form-control" required 
                       value="<?php echo $edit_product ? htmlspecialchars($edit_product['name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="productDescription" class="form-label">Product Description *</label>
                <textarea id="productDescription" name="description" class="form-control" required><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
            </div>
        </div>

        <!-- Product Images Section -->
        <div class="form-section">
            <h2><i class="fas fa-images"></i> Product Images</h2>
            
            <div class="image-upload-section" id="imageUploadSection">
                <div class="upload-icon">
                    <i class="fas fa-cloud-upload-alt"></i>
                </div>
                <div class="upload-text">Click to upload or drag and drop</div>
                <div class="upload-hint">Supports: JPG, PNG, GIF (Max 5MB each)</div>
                <input type="file" id="productImages" name="images[]" multiple accept="image/*" style="display: none;">
            </div>
            
            <div id="imagePreviewContainer" class="image-preview-container">
                <!-- Image previews will be added here -->
            </div>
        </div>

    <!-- Category Selection Section -->
    <div class="form-section">
        <h2><i class="fas fa-tags"></i> Category & Subcategory</h2>
            
            <div class="form-group">
                <label for="categorySearch" class="form-label">Main Category *</label>
                <div class="category-search-container">
                    <input type="text" id="categorySearch" class="form-control" placeholder="Search for a category...">
                    <div id="categoryResults" class="search-results" style="display: none;"></div>
                </div>
                <input type="hidden" id="selectedCategoryId" name="category_id" required>
                <input type="hidden" id="selectedCategoryName" name="category_name">
                <div id="selectedCategories" class="selected-categories">
                    <!-- Selected categories will be displayed here -->
                </div>
            </div>
            
            <div class="form-group">
                <label for="subcategorySearch" class="form-label">Subcategory *</label>
                <div class="category-search-container">
                    <input type="text" id="subcategorySearch" class="form-control" placeholder="Search for a subcategory..." disabled>
                    <div id="subcategoryResults" class="search-results" style="display: none;"></div>
                </div>
                <input type="hidden" id="selectedSubcategoryId" name="subcategory_id" required>
                <input type="hidden" id="selectedSubcategoryName" name="subcategory_name">
                <div id="selectedSubcategories" class="selected-categories">
                    <!-- Selected subcategories will be displayed here -->
                </div>
            </div>
            
        </div>

        <!-- Sizes and Pricing Section -->
        <div class="form-section">
            <h2><i class="fas fa-ruler"></i> Sizes & Pricing</h2>
            
            <div class="size-selection-container">
                <div class="size-input-group">
                    <select id="sizeSelect" class="form-control">
                        <option value="">Select a size...</option>
                    </select>
                    <input type="number" id="costPrice" class="form-control" placeholder="Cost Price" step="0.01" min="0">
                    <input type="number" id="profitMargin" class="form-control" placeholder="Profit Margin" step="0.01" min="0">
                    <button type="button" id="addSizeBtn" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add
                    </button>
                </div>
            </div>
            
            <div id="sizePriceContainer" class="size-price-container">
                <!-- Added sizes will be displayed here -->
            </div>
        </div>

        <!-- Colors Section -->
        <div class="form-section">
            <h2><i class="fas fa-palette"></i> Product Colors</h2>
            
            <div class="color-selection">
                <?php foreach ($commonColors as $color): ?>
                <div class="color-item" data-color="<?php echo $color['name']; ?>">
                    <div class="color-preview" style="background-color: <?php echo $color['code']; ?>;"></div>
                    <div class="color-name"><?php echo $color['name']; ?></div>
                </div>
                <?php endforeach; ?>
                
                <!-- Add Custom Color Button -->
                <div class="color-item add-color-item" onclick="openColorPopup()">
                    <div class="color-preview add-color-preview">
                        <i class="fas fa-plus"></i>
                    </div>
                    <div class="color-name">Add Color</div>
                </div>
            </div>
            
            <div id="selectedColors" class="selected-categories">
                <!-- Selected colors will be displayed here -->
            </div>
        </div>

        <!-- Color Popup Modal -->
        <div id="colorPopup" class="modal-overlay" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Add Custom Color</h3>
                    <button type="button" class="close-btn" onclick="closeColorPopup()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="popupColorPicker" class="form-label">Choose Color</label>
                        <input type="color" id="popupColorPicker" class="color-picker-large" value="#000000">
                    </div>
                    <div class="form-group">
                        <label for="popupColorName" class="form-label">Color Name</label>
                        <input type="text" id="popupColorName" class="form-control" placeholder="Enter color name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeColorPopup()">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addCustomColor()">Add Color</button>
                </div>
            </div>
        </div>

        <!-- Tags Section -->
        <div class="form-section">
            <h2><i class="fas fa-tags"></i> Product Tags</h2>
            
            <div class="form-group">
                <label for="tagsInput" class="form-label">Add Tags</label>
                <div class="tags-container">
                    <input type="text" id="tagsInput" class="tags-input" placeholder="Type tags and press Enter or comma...">
                    <div id="tagsList" class="tags-list">
                        <!-- Tags will be added here -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="button" class="btn btn-info" onclick="testJsonResponse()" style="margin-right: 10px;">
                <i class="fas fa-bug"></i> Test JSON
            </button>
            <?php if ($edit_product): ?>
            <button type="button" class="btn btn-primary" onclick="publishProduct()">
                <i class="fas fa-save"></i> Update Product
            </button>
            <a href="list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
            <?php else: ?>
            <button type="button" class="btn btn-secondary" onclick="saveDraft()">
                <i class="fas fa-save"></i> Save as Draft
            </button>
            <button type="button" class="btn btn-primary" onclick="publishProduct()">
                <i class="fas fa-paper-plane"></i> Publish Product
            </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Include footer -->
<?php include '../components/footer.php'; ?>

<!-- Add custom CSS and JS -->
<link href="<?php echo MANUFACTURER_BASE_URL; ?>assets/css/add-product.css" rel="stylesheet">

<script>
// Initialize size selection and edit data
document.addEventListener('DOMContentLoaded', function() {
    initializeSizeSelection();
    
    // If editing, populate form with existing data
    <?php if ($edit_product): ?>
    setTimeout(() => {
        // Wait for all form components to be initialized
        if (document.getElementById('productForm') && 
            document.getElementById('imagePreviewContainer') && 
            document.getElementById('sizePriceContainer') && 
            document.getElementById('selectedColors') && 
            document.getElementById('tagsList')) {
            populateEditData();
        } else {
            console.log('Form components not ready, retrying...');
            setTimeout(() => {
                populateEditData();
            }, 1000);
        }
    }, 500);
    <?php endif; ?>
});

function populateEditData() {
    console.log('Populating edit data...');
    
    // Check if required functions exist
    if (typeof addSizeToContainer !== 'function') {
        console.error('addSizeToContainer function not found');
        return;
    }
    if (typeof addSelectedColor !== 'function') {
        console.error('addSelectedColor function not found');
        return;
    }
    if (typeof addTag !== 'function') {
        console.error('addTag function not found');
        return;
    }
    if (typeof addImagePreview !== 'function') {
        console.error('addImagePreview function not found');
        return;
    }
    if (typeof setMainImage !== 'function') {
        console.error('setMainImage function not found');
        return;
    }
    
    // Populate category and subcategory
    const categoryId = '<?php echo $edit_product['category_id'] ?? ''; ?>';
    const subcategoryId = '<?php echo $edit_product['subcategory_id'] ?? ''; ?>';
    
    if (categoryId) {
        // Set category directly
        document.getElementById('selectedCategoryId').value = categoryId;
        document.getElementById('selectedCategoryName').value = '<?php echo addslashes($edit_product['category_name'] ?? ''); ?>';
        
        // Update category display
        const categoryDisplay = document.getElementById('selectedCategories');
        if (categoryDisplay) {
            categoryDisplay.innerHTML = `
                <div class="selected-category">
                    <span><?php echo addslashes($edit_product['category_name'] ?? ''); ?></span>
                    <button type="button" class="remove-category" onclick="removeCategory()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        }
        
        // Set category search input value
        const categorySearch = document.getElementById('categorySearch');
        if (categorySearch) {
            categorySearch.value = '<?php echo addslashes($edit_product['category_name'] ?? ''); ?>';
        }
        
        // Enable subcategory search
        document.getElementById('subcategorySearch').disabled = false;
    }
    
    if (subcategoryId) {
        // Set subcategory directly
        document.getElementById('selectedSubcategoryId').value = subcategoryId;
        document.getElementById('selectedSubcategoryName').value = '<?php echo addslashes($edit_product['subcategory_name'] ?? ''); ?>';
        
        // Update subcategory display
        const subcategoryDisplay = document.getElementById('selectedSubcategories');
        if (subcategoryDisplay) {
            subcategoryDisplay.innerHTML = `
                <div class="selected-category">
                    <span><?php echo addslashes($edit_product['subcategory_name'] ?? ''); ?></span>
                    <button type="button" class="remove-category" onclick="removeSubcategory()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
        }
        
        // Set subcategory search input value
        const subcategorySearch = document.getElementById('subcategorySearch');
        if (subcategorySearch) {
            subcategorySearch.value = '<?php echo addslashes($edit_product['subcategory_name'] ?? ''); ?>';
        }
    }
    
    // Populate sizes
    <?php 
    if ($edit_product) {
        $sizes = $productObj->getProductSizes($edit_product['id']);
        if ($sizes) {
            echo "const existingSizes = " . json_encode($sizes) . ";\n";
            echo "console.log('Loading sizes:', existingSizes);\n";
            echo "existingSizes.forEach(size => {\n";
            echo "    addSizeToContainer(size.size_name, parseFloat(size.cost_price), parseFloat(size.profit_margin));\n";
            echo "});\n";
        }
    }
    ?>
    
    // Populate colors
    <?php 
    if ($edit_product) {
        $colors = $productObj->getProductColors($edit_product['id']);
        if ($colors) {
            echo "const existingColors = " . json_encode($colors) . ";\n";
            echo "console.log('Loading colors:', existingColors);\n";
            echo "existingColors.forEach(color => {\n";
            echo "    addSelectedColor(color.color_name, color.color_code, false);\n";
            echo "});\n";
        }
    }
    ?>
    
    // Populate tags
    <?php 
    if ($edit_product && !empty($edit_product['tags'])) {
        echo "const existingTags = '" . addslashes($edit_product['tags']) . "'.split(',');\n";
        echo "console.log('Loading tags:', existingTags);\n";
        echo "existingTags.forEach(tag => {\n";
        echo "    if (tag.trim()) addTag(tag.trim());\n";
        echo "});\n";
    }
    ?>
    
    // Load existing images
    <?php 
    if ($edit_product) {
        $images = $productObj->getProductImages($edit_product['id']);
        if ($images) {
            echo "const existingImages = " . json_encode($images) . ";\n";
            echo "console.log('Loading images:', existingImages);\n";
            echo "existingImages.forEach(image => {\n";
            echo "    addImagePreview({\n";
            echo "        id: image.id,\n";
            echo "        filename: image.image_path.split('/').pop(),\n";
            echo "        path: 'existing_' + image.id + '|' + image.image_path,\n";
            echo "        original_name: image.image_path.split('/').pop()\n";
            echo "    });\n";
            echo "    if (image.is_main == 1) {\n";
            echo "        setTimeout(() => {\n";
            echo "            setMainImage(image.id);\n";
            echo "        }, 100);\n";
            echo "    }\n";
            echo "});\n";
        }
    }
    ?>
}

function initializeSizeSelection() {
    const commonSizes = <?php echo json_encode($commonSizes); ?>;
    const sizeSelect = document.getElementById('sizeSelect');
    const addSizeBtn = document.getElementById('addSizeBtn');
    
    // Populate size dropdown
    commonSizes.forEach(size => {
        const option = document.createElement('option');
        option.value = size;
        option.textContent = size;
        sizeSelect.appendChild(option);
    });
    
    // Add size button click handler
    addSizeBtn.addEventListener('click', function() {
        addSelectedSize();
    });
    
    // Enter key handler for inputs
    document.getElementById('costPrice').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') addSelectedSize();
    });
    
    document.getElementById('profitMargin').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') addSelectedSize();
    });
}

function addSelectedSize() {
    const sizeSelect = document.getElementById('sizeSelect');
    const costPrice = document.getElementById('costPrice');
    const profitMargin = document.getElementById('profitMargin');
    
    const selectedSize = sizeSelect.value;
    const cost = parseFloat(costPrice.value) || 0;
    const profit = parseFloat(profitMargin.value) || 0;
    
    if (!selectedSize) {
        showNotification('Please select a size', 'error');
        return;
    }
    
    if (cost <= 0) {
        showNotification('Please enter a valid cost price', 'error');
        return;
    }
    
    if (profit < 0) {
        showNotification('Profit margin cannot be negative', 'error');
        return;
    }
    
    // Check if size already exists
    const existingSize = document.querySelector(`[data-size="${selectedSize}"]`);
    if (existingSize) {
        showNotification('This size has already been added', 'error');
        return;
    }
    
    // Add size to container
    addSizeToContainer(selectedSize, cost, profit);
    
    // Clear inputs but keep size selected for quick adding
    costPrice.value = '';
    profitMargin.value = '';
    costPrice.focus();
    
    showNotification('Size added successfully!', 'success');
}

function addSizeToContainer(size, cost, profit) {
    const container = document.getElementById('sizePriceContainer');
    const sellingPrice = cost + profit;
    const sizeId = 'size_' + Date.now();
    
    const sizeItem = document.createElement('div');
    sizeItem.className = 'size-price-item';
    sizeItem.dataset.size = size;
    sizeItem.id = sizeId;
    
    sizeItem.innerHTML = `
        <div class="size-info">
            <span class="size-name">${size}</span>
        </div>
        <div class="price-details">
            <div class="price-row">
                <span class="price-label">Cost:</span>
                <span class="price-value">$${cost.toFixed(2)}</span>
            </div>
            <div class="price-row">
                <span class="price-label">Profit:</span>
                <span class="price-value">$${profit.toFixed(2)}</span>
            </div>
            <div class="price-row total">
                <span class="price-label">Total:</span>
                <span class="price-value">$${sellingPrice.toFixed(2)}</span>
            </div>
        </div>
        <button type="button" class="remove-size-btn" onclick="removeSize('${sizeId}')" title="Remove size">
            <i class="fas fa-times"></i>
        </button>
        <input type="hidden" name="sizes[]" value='${JSON.stringify({name: size, cost_price: cost, profit_margin: profit, selling_price: sellingPrice})}'>
    `;
    
    container.appendChild(sizeItem);
}

function removeSize(sizeId) {
    const sizeItem = document.getElementById(sizeId);
    if (sizeItem) {
        sizeItem.remove();
        showNotification('Size removed successfully!', 'success');
    }
}
</script> 