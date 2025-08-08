<?php
require_once 'includes/product_operations.php';

// Get product ID from URL
$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    header('Location: product.php');
    exit;
}

// Get product details
function getProductDetails($product_id) {
    global $pdo;
    
    try {
        // Get product details using the view
        $query = "SELECT pdv.*, 
                         COALESCE(pdv.main_image, 'src/images/default-product.jpg') as image_url
                 FROM product_details_view pdv
                 WHERE pdv.id = ? AND pdv.is_active = 1";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found'];
        }
        
        // Get all product images
        $imageQuery = "SELECT image_path, is_main, sort_order FROM product_images WHERE product_id = ? ORDER BY is_main DESC, sort_order ASC";
        $imageStmt = $pdo->prepare($imageQuery);
        $imageStmt->execute([$product_id]);
        $images = $imageStmt->fetchAll();
        
        // Get product sizes with prices
        $sizeQuery = "SELECT size_name, cost_price, profit_margin, selling_price FROM product_sizes WHERE product_id = ? AND is_active = 1 ORDER BY sort_order ASC";
        $sizeStmt = $pdo->prepare($sizeQuery);
        $sizeStmt->execute([$product_id]);
        $sizes = $sizeStmt->fetchAll();
        
        // Get product colors using Product class for proper color code fixing
        require_once __DIR__ . '/manufacture/includes/Product.class.php';
        $productObj = new Product($pdo);
        $colors = $productObj->getProductColors($product_id);
        
        return [
            'success' => true,
            'product' => $product,
            'images' => $images,
            'sizes' => $sizes,
            'colors' => $colors
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

$productResult = getProductDetails($product_id);

if (!$productResult['success']) {
    header('Location: product.php');
    exit;
}

$product = $productResult['product'];
$images = $productResult['images'];
$sizes = $productResult['sizes'];
$colors = $productResult['colors'];

include 'components/header.php';
?>

<style>
    .product-image {
        max-height: 500px;
        object-fit: cover;
    }
    .thumbnail-image {
        width: 80px;
        height: 80px;
        object-fit: cover;
        cursor: pointer;
        border: 2px solid transparent;
    }
    .thumbnail-image.active {
        border-color: #2563eb;
    }
    .size-option, .color-option {
        border: 2px solid #e5e7eb;
        padding: 8px 16px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .size-option:hover, .color-option:hover {
        border-color: #2563eb;
    }
    .size-option.active, .color-option.active {
        border-color: #2563eb;
        background-color: #eff6ff;
    }
    .color-swatch {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: inline-block;
        border: 2px solid #e5e7eb;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.2s ease;
    }
    
    .color-option:hover .color-swatch {
        transform: scale(1.1);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .color-option.active .color-swatch {
        border-color: #2563eb;
        border-width: 3px;
        transform: scale(1.1);
    }
</style>

<div class="container py-4">
    <!-- Breadcrumbs -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="product.php">Products</a></li>
            <li class="breadcrumb-item"><a href="product.php?category=<?php echo $product['category_id']; ?>"><?php echo htmlspecialchars($product['category_name']); ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6">
            <div class="mb-3">
                <img id="mainImage" src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                     class="img-fluid rounded product-image w-100" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>">
            </div>
            
            <?php if (count($images) > 1): ?>
                <div class="d-flex gap-2 flex-wrap">
                    <?php foreach ($images as $index => $image): ?>
                        <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                             class="thumbnail-image rounded <?php echo $index === 0 ? 'active' : ''; ?>" 
                             alt="Product image <?php echo $index + 1; ?>"
                             onclick="changeMainImage(this)">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Product Details -->
        <div class="col-md-6">
            <h1 class="h2 mb-2"><?php echo htmlspecialchars($product['name']); ?></h1>
            <p class="text-muted mb-2">
                <?php echo htmlspecialchars($product['category_name']); ?>
                <?php if ($product['subcategory_name']): ?>
                    > <?php echo htmlspecialchars($product['subcategory_name']); ?>
                <?php endif; ?>
            </p>
            
            <div class="mb-3">
                <h3 class="text-primary" id="productPrice">
                    Rs. <?php echo number_format($sizes[0]['selling_price'] ?? 0, 2); ?>
                </h3>
                <?php if (count($sizes) > 1): ?>
                    <small class="text-muted">Starting from Rs. <?php echo number_format(min(array_column($sizes, 'selling_price')), 2); ?></small>
                <?php endif; ?>
            </div>

            <div class="mb-4">
                <h6>Description</h6>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <?php if ($product['tags']): ?>
                <div class="mb-3">
                    <h6>Tags</h6>
                    <?php foreach (explode(',', $product['tags']) as $tag): ?>
                        <span class="badge bg-light text-dark me-1"><?php echo trim(htmlspecialchars($tag)); ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Size Selection -->
            <?php if (!empty($sizes)): ?>
                <div class="mb-3">
                    <h6>Size</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($sizes as $index => $size): ?>
                            <div class="size-option rounded <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 data-price="<?php echo $size['selling_price']; ?>"
                                 onclick="selectSize(this)">
                                <?php echo htmlspecialchars($size['size_name']); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Color Selection -->
            <?php if (!empty($colors)): ?>
                <div class="mb-3">
                    <h6>Color</h6>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($colors as $index => $color): ?>
                            <div class="color-option rounded d-flex align-items-center gap-2 <?php echo $index === 0 ? 'active' : ''; ?>" 
                                 onclick="selectColor(this)">
                                <span class="color-swatch" style="background-color: <?php echo htmlspecialchars($color['color_code']); ?>;"></span>
                                <span><?php echo htmlspecialchars($color['color_name']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quantity and Add to Cart -->
            <div class="mb-4">
                <div class="row">
                    <div class="col-4">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" value="1" min="1" max="100">
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2 d-md-flex">
                <button class="btn btn-primary btn-lg me-md-2" onclick="addToCart(<?php echo $product['id']; ?>)">
                    <i class="fas fa-shopping-cart me-2"></i>Add to Cart
                </button>
                <button class="btn btn-outline-secondary btn-lg">
                    <i class="fas fa-heart me-2"></i>Wishlist
                </button>
            </div>          
        </div>
    </div>
</div>

<script>
function changeMainImage(thumbnail) {
    const mainImage = document.getElementById('mainImage');
    mainImage.src = thumbnail.src;
    
    // Update active thumbnail
    document.querySelectorAll('.thumbnail-image').forEach(img => img.classList.remove('active'));
    thumbnail.classList.add('active');
}

function selectSize(sizeElement) {
    // Update active size
    document.querySelectorAll('.size-option').forEach(el => el.classList.remove('active'));
    sizeElement.classList.add('active');
    
    // Update price
    const price = parseFloat(sizeElement.dataset.price);
    document.getElementById('productPrice').textContent = 'Rs. ' + price.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function selectColor(colorElement) {
    // Update active color
    document.querySelectorAll('.color-option').forEach(el => el.classList.remove('active'));
    colorElement.classList.add('active');
}

// Add to Cart functionality (same as in product.php)
function addToCart(productId) {
    console.log('addToCart called with productId:', productId);
    
    // Check if user is logged in using PHP variable
    <?php if (isset($_SESSION['user_id'])): ?>
        console.log('User is logged in (PHP check), proceeding with add to cart');
        addToCartAction(productId);
    <?php else: ?>
        console.log('User not logged in (PHP check), redirecting to login');
        window.location.href = 'login.php?tab=signin';
    <?php endif; ?>
}

function addToCartAction(productId) {

    const quantity = document.getElementById('quantity').value;
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);

    // Show loading state
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding...';

    fetch('includes/cart_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showToast('Product added to cart successfully!', 'success');
            // Update cart count
            updateCartCount();
        } else {
            showToast(data.message || 'Error adding product to cart', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error adding product to cart. Please try again.', 'error');
    })
    .finally(() => {
        // Reset button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.className = 'position-fixed bottom-0 end-0 p-3';
    toast.style.zIndex = '5';
    toast.innerHTML = `
        <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${type === 'error' ? 'bg-danger text-white' : ''}">
                <strong class="me-auto">${type === 'error' ? 'Error' : 'Success'}</strong>
                <button type="button" class="btn-close ${type === 'error' ? 'btn-close-white' : ''}" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    document.body.appendChild(toast);
    
    // Remove toast after 3 seconds
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Update cart count function
function updateCartCount() {
    fetch('includes/cart_operations.php?action=get_count')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const cartCountElement = document.getElementById('cartCount');
                if (cartCountElement) {
                    cartCountElement.textContent = data.count;
                }
            }
        })
        .catch(error => console.error('Error:', error));
}
</script>

<?php include 'components/footer.php'; ?>

