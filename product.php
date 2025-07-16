<?php
require_once 'includes/product_operations.php';

// Get filter parameters
$category = $_GET['category'] ?? null;
$minPrice = isset($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$sort = $_GET['sort'] ?? 'featured';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Get products and categories
$productsResult = getProducts($category, $minPrice, $maxPrice, $sort, $page);
$categoriesResult = getCategories();

// Handle errors
if (!$productsResult['success']) {
    $error = $productsResult['message'];
}
if (!$categoriesResult['success']) {
    $error = $categoriesResult['message'];
}

include 'components/header.php';
?>

<style>
    /* Custom styles for product page */
    .sort-button {
        border: 1px solid #ced4da !important;
        /* Use !important to override existing Bootstrap styles if necessary */
    }

    /* Styling for the range slider track (the line) */
    input[type="range"]::-webkit-slider-runnable-track {
        background: #e0e0e0; /* Light grey for the unfilled part */
        border-radius: 5px;
        height: 8px;
    }

    input[type="range"]::-moz-range-track {
        background: #e0e0e0; /* Light grey for the unfilled part */
        border-radius: 5px;
        height: 8px;
    }

    /* Styling for the range slider thumb (the circle) */
    input[type="range"]::-webkit-slider-thumb {
        -webkit-appearance: none;
        appearance: none;
        width: 20px;
        height: 20px;
        background: #2563eb; /* Blue thumb color */
        cursor: pointer;
        border-radius: 50%;
        margin-top: -6px; /* Center the thumb vertically */
        z-index: 1; /* Ensure thumb is above track */
    }

    input[type="range"]::-moz-range-thumb {
        width: 20px;
        height: 20px;
        background: #2563eb; /* Blue thumb color */
        cursor: pointer;
        border-radius: 50%;
        z-index: 1; /* Ensure thumb is above track */
    }

    /* Styling for the filled part of the range slider using linear gradient on track */
    input[type="range"]::-webkit-slider-runnable-track {
        background: linear-gradient(to right, #2563eb var(--range-progress, 0%), #e0e0e0 var(--range-progress, 0%));
        border-radius: 5px;
        height: 8px;
    }

    input[type="range"]::-moz-range-track {
        background: linear-gradient(to right, #2563eb var(--range-progress, 0%), #e0e0e0 var(--range-progress, 0%));
        border-radius: 5px;
        height: 8px;
    }

    /* Remove default progress styling as we are using gradient on track */
    input[type="range"]::-webkit-progress-value {
        display: none;
    }

    input[type="range"]::-moz-range-progress {
        display: none;
    }

    /* Fallback for browsers that don't support progress pseudo-elements */
    input[type="range"] {
        background: none; /* Remove default background */
    }

    /* Ensure thumb is visible */
    input[type="range"]::-webkit-slider-thumb,
    input[type="range"]::-moz-range-thumb {
        /* Existing thumb styles */
    }

    /* Custom Pagination Styles */
    .pagination .page-item .page-link {
        border-radius: 8px !important; /* Rounded corners */
        margin: 0 4px; /* Space between items */
        border: 1px solid #e0e0e0; /* Light border for inactive */
        color: #212529; /* Default text color */
        padding: 8px 16px; /* Adjust padding */
    }

    .pagination .page-item.active .page-link {
        background-color: #2563eb; /* Blue background for active */
        border-color: #2563eb; /* Blue border for active */
        color: #fff; /* White text for active */
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d; /* Grey out disabled link */
        pointer-events: none; /* Disable clicks */
        background-color: #fff; /* White background */
        border-color: #e0e0e0; /* Light border */
    }

    .pagination .page-item:first-child .page-link,
    .pagination .page-item:last-child .page-link {
        border-radius: 8px !important; /* Ensure rounded corners on ends */
    }

</style>

<div class="container py-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <!-- Breadcrumbs -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Products</li>
                </ol>
            </nav>
            
            <!-- Categories -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Categories</h6>
                    <?php if ($categoriesResult['success']): ?>
                        <?php foreach ($categoriesResult['categories'] as $cat): ?>
                            <div class="mb-2">
                                <a href="?category=<?php echo $cat['id']; ?>" 
                                   class="text-decoration-none <?php echo $category == $cat['id'] ? 'text-primary fw-bold' : 'text-dark'; ?>">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Price Range -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Price Range</h6>
                    <input type="range" class="form-range" min="0" max="1000" id="priceRange" 
                           value="<?php echo $minPrice ?? 0; ?>">
                    <div class="d-flex justify-content-between">
                        <span id="minPriceDisplay">$<?php echo $minPrice ?? 0; ?></span>
                        <span id="maxPriceDisplay">$1000</span>
                    </div>
                </div>
            </div>

            <!-- Colors -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Colors</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <span class="rounded-circle d-inline-block" style="width: 25px; height: 25px; background-color: red;"></span>
                        <span class="rounded-circle d-inline-block" style="width: 25px; height: 25px; background-color: blue;"></span>
                        <span class="rounded-circle d-inline-block" style="width: 25px; height: 25px; background-color: green;"></span>
                        <span class="rounded-circle d-inline-block" style="width: 25px; height: 25px; background-color: yellow;"></span>
                        <span class="rounded-circle d-inline-block" style="width: 25px; height: 25px; background-color: purple;"></span>
                        <span class="rounded-circle d-inline-block" style="width: 25px; height: 25px; background-color: black;"></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Product Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5>
                    <?php if ($productsResult['success']): ?>
                        Showing <?php echo ($page - 1) * 12 + 1; ?>-<?php echo min($page * 12, $productsResult['total']); ?> 
                        of <?php echo $productsResult['total']; ?> products
                    <?php endif; ?>
                </h5>
                <div class="d-flex align-items-center">
                    <div class="dropdown me-2">
                        <button class="btn btn-light dropdown-toggle sort-button" type="button" id="dropdownMenuButton1" 
                                data-bs-toggle="dropdown" aria-expanded="false">
                            Sort by: <?php echo ucfirst(str_replace('_', ' ', $sort)); ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                            <li><a class="dropdown-item" href="?sort=featured<?php echo $category ? '&category=' . urlencode($category) : ''; ?>">Featured</a></li>
                            <li><a class="dropdown-item" href="?sort=price_low<?php echo $category ? '&category=' . urlencode($category) : ''; ?>">Price: Low to High</a></li>
                            <li><a class="dropdown-item" href="?sort=price_high<?php echo $category ? '&category=' . urlencode($category) : ''; ?>">Price: High to Low</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Product Grid -->
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php if ($productsResult['success'] && !empty($productsResult['products'])): ?>
                    <?php foreach ($productsResult['products'] as $product): ?>
                        <div class="col">
                            <div class="card h-100">
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     class="card-img-top" 
                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>">
                                <div class="card-body">
                                    <h6 class="card-title mb-1"><?php echo htmlspecialchars($product['product_name']); ?></h6>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <p class="card-text mb-0">Rs. <?php echo number_format($product['price'], 2); ?></p>
                                        <button onclick="addToCart(<?php echo $product['id']; ?>)" 
                                                class="btn btn-primary rounded-pill">Add to Cart</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info" role="alert">
                            No products found matching your criteria.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($productsResult['success'] && $productsResult['pages'] > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center custom-pagination">
                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" 
                               aria-label="Previous">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $productsResult['pages']; $i++): ?>
                            <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo $page >= $productsResult['pages'] ? 'disabled' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $category ? '&category=' . urlencode($category) : ''; ?><?php echo $sort ? '&sort=' . $sort : ''; ?>" 
                               aria-label="Next">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form>
          <div class="mb-3">
            <label for="productName" class="form-label">Product Name</label>
            <input type="text" class="form-control" id="productName">
          </div>
          <div class="mb-3">
            <label for="productDescription" class="form-label">Description</label>
            <textarea class="form-control" id="productDescription" rows="3"></textarea>
          </div>
          <div class="mb-3">
            <label for="productPrice" class="form-label">Price</label>
            <input type="number" class="form-control" id="productPrice" step="0.01">
          </div>
          <div class="mb-3">
            <label for="productCategory" class="form-label">Category</label>
            <input type="text" class="form-control" id="productCategory">
          </div>
           <div class="mb-3">
            <label for="productStock" class="form-label">Stock Quantity</label>
            <input type="number" class="form-control" id="productStock">
          </div>
           <div class="mb-3">
            <label for="productImage" class="form-label">Product Image</label>
            <input type="file" class="form-control" id="productImage">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary">Save Product</button>
      </div>
    </div>
  </div>
</div>

<script>
    const priceRange = document.getElementById('priceRange');
    const minPriceDisplay = document.getElementById('minPriceDisplay');
    const maxPriceDisplay = document.getElementById('maxPriceDisplay');
    
    function updatePriceDisplay(value) {
        minPriceDisplay.textContent = `$${value}`; // Update minimum price display
        // maxPriceDisplay.textContent = `$1000`; // Maximum price is static
    }

    function updateRangeProgress(rangeInput) {
        const value = (rangeInput.value - rangeInput.min) / (rangeInput.max - rangeInput.min) * 100;
        rangeInput.style.setProperty('--range-progress', value + '%');
    }

    if (priceRange) {
        // Initial update on page load
        updateRangeProgress(priceRange);
        // Update on slider move
        priceRange.addEventListener('input', (event) => {
            updateRangeProgress(event.target);
            updatePriceDisplay(event.target.value); // Update price display
        });

         // Set initial text value based on default slider value
         updatePriceDisplay(priceRange.value);
    }

    // Add to Cart functionality
    function addToCart(productId) {
        if (!document.cookie.includes('user_id')) {
            window.location.href = 'login.php?tab=signin';
            return;
        }

        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', 1);

        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';

        fetch('includes/cart_operations.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = '5';
                toast.innerHTML = `
                    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header">
                            <strong class="me-auto">Success</strong>
                            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            Product added to cart successfully!
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                
                // Remove toast after 3 seconds
                setTimeout(() => {
                    toast.remove();
                }, 3000);

                // Update cart count in header
                updateCartCount();
            } else {
                // Show error message
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-0 end-0 p-3';
                toast.style.zIndex = '5';
                toast.innerHTML = `
                    <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                        <div class="toast-header bg-danger text-white">
                            <strong class="me-auto">Error</strong>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                        <div class="toast-body">
                            ${data.message || 'Error adding product to cart'}
                        </div>
                    </div>
                `;
                document.body.appendChild(toast);
                
                // Remove toast after 3 seconds
                setTimeout(() => {
                    toast.remove();
                }, 3000);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Show error message
            const toast = document.createElement('div');
            toast.className = 'position-fixed bottom-0 end-0 p-3';
            toast.style.zIndex = '5';
            toast.innerHTML = `
                <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="toast-header bg-danger text-white">
                        <strong class="me-auto">Error</strong>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                    <div class="toast-body">
                        Error adding product to cart. Please try again.
                    </div>
                </div>
            `;
            document.body.appendChild(toast);
            
            // Remove toast after 3 seconds
            setTimeout(() => {
                toast.remove();
            }, 3000);
        })
        .finally(() => {
            // Reset button state
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }

    // Update cart count in header
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

    // Initial cart count update
    document.addEventListener('DOMContentLoaded', function() {
        updateCartCount();
    });
</script> 