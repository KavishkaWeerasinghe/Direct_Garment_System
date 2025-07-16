<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ManufactureHub - Inventory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }
        #wrapper {
            display: flex;
        }
        #page-content-wrapper {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .products-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .products-header h1 {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333; /* Dark grey color */
        }
        .products-header > .d-flex {
            flex-grow: 1;
            justify-content: flex-end; /* Align search and button to the right */
            align-items: center;
            gap: 10px; /* Add some space between search and button */
        }
        .search-input-container {
            position: relative;
            max-width: 520px;
            flex-grow: 1; /* Allow search container to grow */
        }
        .search-input {
            padding-left: 35px; /* Make space for the icon */
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa; /* Adjust color as needed */
        }
        .products-header .btn-primary {
            padding: 8px 15px; /* Add some padding to the button */
        }
        .product-table-card {
             background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
         .stock-badge {
             padding: 5px 10px;
             border-radius: 15px;
             font-size: 0.8rem;
             font-weight: bold;
             background-color: #d1fae5; /* Green background */
             color: #065f46; /* Dark green text */
         }
        .action-icons i {
            margin-right: 10px;
            cursor: pointer;
            color: #6c757d;
        }
        .action-icons i.fa-edit { color: #0d6efd; } /* Bootstrap primary blue */
        .action-icons i.fa-trash-alt { color: #dc3545; } /* Bootstrap danger red */
        .sidebar-nav .list-group-item.active {
            background-color: #2563eb;
            color: #fff;
        }
        .sidebar-nav .list-group-item.active i {
            color: #fff;
        }
        .upload-area {
            border: 2px dashed #ccc;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .upload-area:hover {
            border-color: #0d6efd;
            background-color: #f8f9fa;
        }
        .image-preview {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
        }
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .image-preview .main-image-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            background-color: #0d6efd;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.7rem;
        }
        .image-preview .remove-image {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: #dc3545;
        }
        .image-preview .set-main {
            position: absolute;
            bottom: 5px;
            left: 5px;
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 0.7rem;
            cursor: pointer;
            color: #0d6efd;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <?php $active_item = 'products'; include 'components/sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top Header -->
            <?php $title = 'Products'; include 'components/top_header.php'; ?>

            <div class="container-fluid py-4 px-4">
                <div class="products-header">
                    <div class="d-flex justify-content-between">
                        <h1>Products</h1>
                        <div class="search-input-container me-2">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="form-control search-input" placeholder="Search products...">
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal"><i class="fas fa-plus me-2"></i> Add Product</button>
                    </div>
                </div>

                <!-- Add Product Modal -->
                <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="addProductForm" enctype="multipart/form-data">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="productName" class="form-label">Product Name</label>
                                            <input type="text" class="form-control" id="productName" name="product_name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="productCategory" class="form-label">Category</label>
                                            <select class="form-select" id="productCategory" name="category_id" required>
                                                <option value="">Select Category</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="productPrice" class="form-label">Price</label>
                                            <input type="number" class="form-control" id="productPrice" name="price" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="productDescription" class="form-label">Description</label>
                                        <textarea class="form-control" id="productDescription" name="description" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Product Images</label>
                                        <div class="image-upload-container">
                                            <div class="upload-area mb-3 p-3 border rounded">
                                                <input type="file" class="form-control" id="productImages" name="additional_images[]" multiple accept="image/*" style="display: none;">
                                                <div class="text-center upload-placeholder" id="uploadPlaceholder">
                                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                                    <p class="mb-0">Drag & Drop images here or click to browse</p>
                                                    <small class="text-muted">You can select multiple images</small>
                                                </div>
                                            </div>
                                            <div class="image-preview-container d-flex flex-wrap gap-2" id="imagePreviewContainer">
                                                <!-- Image previews will be added here -->
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="submitProduct()">Add Product</button>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    .upload-area {
                        border: 2px dashed #ccc;
                        border-radius: 8px;
                        cursor: pointer;
                        transition: all 0.3s ease;
                    }
                    .upload-area:hover {
                        border-color: #0d6efd;
                        background-color: #f8f9fa;
                    }
                    .image-preview {
                        position: relative;
                        width: 100px;
                        height: 100px;
                        border-radius: 8px;
                        overflow: hidden;
                    }
                    .image-preview img {
                        width: 100%;
                        height: 100%;
                        object-fit: cover;
                    }
                    .image-preview .main-image-badge {
                        position: absolute;
                        top: 5px;
                        left: 5px;
                        background-color: #0d6efd;
                        color: white;
                        padding: 2px 6px;
                        border-radius: 4px;
                        font-size: 0.7rem;
                    }
                    .image-preview .remove-image {
                        position: absolute;
                        top: 5px;
                        right: 5px;
                        background-color: rgba(255, 255, 255, 0.8);
                        border-radius: 50%;
                        width: 20px;
                        height: 20px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        color: #dc3545;
                    }
                    .image-preview .set-main {
                        position: absolute;
                        bottom: 5px;
                        left: 5px;
                        background-color: rgba(255, 255, 255, 0.8);
                        border-radius: 4px;
                        padding: 2px 6px;
                        font-size: 0.7rem;
                        cursor: pointer;
                        color: #0d6efd;
                    }
                </style>

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Fetch categories when page loads
                        fetchCategories();

                        const uploadArea = document.querySelector('.upload-area');
                        const fileInput = document.getElementById('productImages');
                        const imagePreviewContainer = document.getElementById('imagePreviewContainer');
                        let mainImageIndex = -1;
                        let currentIndex = 0;
                        let selectedFiles = [];

                        // Function to fetch categories
                        function fetchCategories() {
                            fetch('includes/product_operations.php?action=get_categories')
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('Network response was not ok');
                                    }
                                    return response.json();
                                })
                                .then(categories => {
                                    const categorySelect = document.getElementById('productCategory');
                                    categorySelect.innerHTML = '<option value="">Select Category</option>';
                                    
                                    if (categories.length === 0) {
                                        console.error('No categories found');
                                        categorySelect.innerHTML += '<option value="" disabled>No categories available</option>';
                                        return;
                                    }
                                    
                                    categories.forEach(category => {
                                        const option = document.createElement('option');
                                        option.value = category.category_id;
                                        option.textContent = category.name;
                                        categorySelect.appendChild(option);
                                    });
                                })
                                .catch(error => {
                                    console.error('Error fetching categories:', error);
                                    const categorySelect = document.getElementById('productCategory');
                                    categorySelect.innerHTML = '<option value="">Error loading categories</option>';
                                });
                        }

                        // Handle click on upload area
                        uploadArea.addEventListener('click', () => {
                            fileInput.click();
                        });

                        // Handle drag and drop
                        uploadArea.addEventListener('dragover', (e) => {
                            e.preventDefault();
                            uploadArea.style.borderColor = '#0d6efd';
                        });

                        uploadArea.addEventListener('dragleave', () => {
                            uploadArea.style.borderColor = '#ccc';
                        });

                        uploadArea.addEventListener('drop', (e) => {
                            e.preventDefault();
                            uploadArea.style.borderColor = '#ccc';
                            const files = e.dataTransfer.files;
                            handleFiles(files);
                        });

                        // Handle file selection
                        fileInput.addEventListener('change', (e) => {
                            handleFiles(e.target.files);
                        });

                        function handleFiles(files) {
                            Array.from(files).forEach((file) => {
                                if (file.type.startsWith('image/')) {
                                    selectedFiles.push(file);
                                    const reader = new FileReader();
                                    reader.onload = (e) => {
                                        const imagePreview = createImagePreview(e.target.result, currentIndex);
                                        imagePreviewContainer.appendChild(imagePreview);
                                        
                                        // Set first image as main by default
                                        if (mainImageIndex === -1) {
                                            setMainImage(currentIndex);
                                        }
                                        currentIndex++;
                                    };
                                    reader.readAsDataURL(file);
                                }
                            });
                        }

                        function createImagePreview(src, index) {
                            const div = document.createElement('div');
                            div.className = 'image-preview';
                            div.setAttribute('data-index', index);
                            div.innerHTML = `
                                <img src="${src}" alt="Preview">
                                <div class="remove-image" onclick="removeImage(${index})">
                                    <i class="fas fa-times"></i>
                                </div>
                                <div class="set-main" onclick="setMainImage(${index})">
                                    Set as Main
                                </div>
                            `;
                            return div;
                        }

                        // Make functions globally available
                        window.removeImage = function(index) {
                            const preview = document.querySelector(`.image-preview[data-index="${index}"]`);
                            if (preview) {
                                preview.remove();
                                selectedFiles.splice(index, 1);
                                if (mainImageIndex === parseInt(index)) {
                                    mainImageIndex = -1;
                                    // Set new main image if available
                                    const remainingPreviews = document.querySelectorAll('.image-preview');
                                    if (remainingPreviews.length > 0) {
                                        setMainImage(remainingPreviews[0].getAttribute('data-index'));
                                    }
                                }
                            }
                        };

                        window.setMainImage = function(index) {
                            const previews = document.querySelectorAll('.image-preview');
                            mainImageIndex = parseInt(index);
                            
                            // Remove all main image badges
                            previews.forEach(preview => {
                                const badge = preview.querySelector('.main-image-badge');
                                if (badge) badge.remove();
                            });

                            // Add main image badge to selected image
                            const selectedPreview = document.querySelector(`.image-preview[data-index="${index}"]`);
                            if (selectedPreview) {
                                const badge = document.createElement('div');
                                badge.className = 'main-image-badge';
                                badge.textContent = 'Main';
                                selectedPreview.appendChild(badge);
                            }
                        };

                        window.submitProduct = function() {
                            if (selectedFiles.length === 0) {
                                alert('Please select at least one image');
                                return;
                            }

                            const formData = new FormData();
                            formData.append('action', 'add');
                            formData.append('product_name', document.getElementById('productName').value);
                            formData.append('category_id', document.getElementById('productCategory').value);
                            formData.append('price', document.getElementById('productPrice').value);
                            formData.append('description', document.getElementById('productDescription').value);

                            // Add main image
                            if (mainImageIndex !== -1) {
                                formData.append('main_image', selectedFiles[mainImageIndex]);
                            }

                            // Add additional images
                            selectedFiles.forEach((file, index) => {
                                if (index !== mainImageIndex) {
                                    formData.append('additional_images[]', file);
                                }
                            });

                            // Show loading state
                            const submitButton = document.querySelector('.modal-footer .btn-primary');
                            const originalText = submitButton.innerHTML;
                            submitButton.disabled = true;
                            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Adding...';

                            // Log the form data for debugging
                            console.log('Sending form data:');
                            for (let pair of formData.entries()) {
                                console.log(pair[0] + ': ' + pair[1]);
                            }

                            // Submit form
                            fetch('includes/product_operations.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(async response => {
                                const text = await response.text();
                                try {
                                    const data = JSON.parse(text);
                                    if (!response.ok) {
                                        throw new Error(data.message || 'Server error occurred');
                                    }
                                    return data;
                                } catch (e) {
                                    console.error('Server response:', text);
                                    throw new Error('Invalid server response');
                                }
                            })
                            .then(data => {
                                if (data.success) {
                                    alert('Product added successfully!');
                                    location.reload();
                                } else {
                                    alert('Error: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while adding the product: ' + error.message);
                            })
                            .finally(() => {
                                // Reset button state
                                submitButton.disabled = false;
                                submitButton.innerHTML = originalText;
                            });
                        };
                    });
                </script>

                <div class="d-flex justify-content-between align-items-center mb-3">
                     <div>
                          <div class="dropdown d-inline-block me-2">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButtonCategories" data-bs-toggle="dropdown" aria-expanded="false">
                              All Categories
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButtonCategories">
                              <li><a class="dropdown-item" href="#">Electronics</a></li>
                              <li><a class="dropdown-item" href="#">Clothing</a></li>
                            </ul>
                          </div>
                           <div class="dropdown d-inline-block">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButtonSort" data-bs-toggle="dropdown" aria-expanded="false">
                              Sort by
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButtonSort">
                              <li><a class="dropdown-item" href="#">Name</a></li>
                              <li><a class="dropdown-item" href="#">Price</a></li>
                            </ul>
                          </div>
                     </div>
                     <div>
                         <i class="fas fa-question-circle me-2"></i>
                         <i class="fas fa-list"></i>
                     </div>
                </div>

                <div class="product-table-card">
                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <!-- Products will be loaded here -->
                        </tbody>
                    </table>
                </div>

                <!-- Edit Product Modal -->
                <div class="modal fade" id="editProductModal" tabindex="-1" aria-labelledby="editProductModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editProductModalLabel">Edit Product</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editProductForm" enctype="multipart/form-data">
                                    <input type="hidden" id="editProductId" name="id">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="editProductName" class="form-label">Product Name</label>
                                            <input type="text" class="form-control" id="editProductName" name="product_name" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="editProductCategory" class="form-label">Category</label>
                                            <select class="form-select" id="editProductCategory" name="category_id" required>
                                                <option value="">Select Category</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="editProductPrice" class="form-label">Price (Rs)</label>
                                            <input type="number" class="form-control" id="editProductPrice" name="price" step="0.01" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editProductDescription" class="form-label">Description</label>
                                        <textarea class="form-control" id="editProductDescription" name="description" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Product Images</label>
                                        <div class="image-upload-container">
                                            <div class="upload-area mb-3 p-3 border rounded">
                                                <input type="file" class="form-control" id="editProductImages" name="additional_images[]" multiple accept="image/*" style="display: none;">
                                                <div class="text-center upload-placeholder" id="editUploadPlaceholder">
                                                    <i class="fas fa-cloud-upload-alt fa-2x mb-2"></i>
                                                    <p class="mb-0">Drag & Drop images here or click to browse</p>
                                                    <small class="text-muted">You can select multiple images</small>
                                                </div>
                                            </div>
                                            <div class="image-preview-container d-flex flex-wrap gap-2" id="editImagePreviewContainer">
                                                <!-- Image previews will be added here -->
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="updateProduct()">Update Product</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteProductModalLabel">Confirm Delete</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this product? This action cannot be undone.</p>
                                <input type="hidden" id="deleteProductId">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" onclick="confirmDelete()">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                // Initialize modal at the top of your script
                let editModal;

                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize the edit modal
                    editModal = new bootstrap.Modal(document.getElementById('editProductModal'));
                    
                    // Load products when page loads
                    loadProducts();

                    // Setup edit modal image upload handlers
                    const editUploadArea = document.querySelector('#editProductModal .upload-area');
                    const editFileInput = document.getElementById('editProductImages');
                    const editImagePreviewContainer = document.getElementById('editImagePreviewContainer');

                    // Handle click on upload area
                    editUploadArea.addEventListener('click', () => {
                        editFileInput.click();
                    });

                    // Handle drag and drop
                    editUploadArea.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        editUploadArea.style.borderColor = '#0d6efd';
                    });

                    editUploadArea.addEventListener('dragleave', () => {
                        editUploadArea.style.borderColor = '#ccc';
                    });

                    editUploadArea.addEventListener('drop', (e) => {
                        e.preventDefault();
                        editUploadArea.style.borderColor = '#ccc';
                        const files = e.dataTransfer.files;
                        handleEditFiles(files);
                    });

                    // Handle file selection
                    editFileInput.addEventListener('change', (e) => {
                        handleEditFiles(e.target.files);
                    });
                });

                function loadProducts() {
                    fetch('includes/product_operations.php?action=get_products')
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(products => {
                            console.log('Products received:', products);
                            const tbody = document.getElementById('productsTableBody');
                            tbody.innerHTML = '';
                            
                            if (products.length === 0) {
                                const tr = document.createElement('tr');
                                tr.innerHTML = '<td colspan="4" class="text-center">No products found</td>';
                                tbody.appendChild(tr);
                                return;
                            }
                            
                            products.forEach(product => {
                                const tr = document.createElement('tr');
                                tr.innerHTML = `
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="../${product.main_image}" alt="${product.product_name}" class="me-2" style="width: 50px; height: 50px; object-fit: cover;">
                                            <span>${product.product_name}</span>
                                        </div>
                                    </td>
                                    <td>${product.category_name || 'Uncategorized'}</td>
                                    <td>Rs. ${parseFloat(product.price).toFixed(2)}</td>
                                    <td>
                                        <i class="fas fa-edit action-icons" onclick="editProduct(${product.id})" style="cursor: pointer;"></i>
                                        <i class="fas fa-trash-alt action-icons" onclick="showDeleteModal(${product.id})" style="cursor: pointer;"></i>
                                    </td>
                                `;
                                tbody.appendChild(tr);
                            });
                        })
                        .catch(error => {
                            console.error('Error loading products:', error);
                            const tbody = document.getElementById('productsTableBody');
                            tbody.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading products</td></tr>';
                        });
                }

                // Add these functions to handle image management in edit modal
                let editMainImageIndex = -1;
                let editCurrentIndex = 0;
                let editSelectedFiles = [];
                let editExistingImages = [];

                function handleEditFiles(files) {
                    Array.from(files).forEach((file) => {
                        if (file.type.startsWith('image/')) {
                            const fileIndex = editCurrentIndex;
                            editSelectedFiles[fileIndex] = file;
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                const imagePreview = createImagePreview(e.target.result, fileIndex);
                                document.getElementById('editImagePreviewContainer').appendChild(imagePreview);
                                editCurrentIndex++;
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }

                function createImagePreview(src, index, isMain = false) {
                    const div = document.createElement('div');
                    div.className = 'image-preview';
                    div.setAttribute('data-index', index);
                    div.innerHTML = `
                        <img src="${src}" alt="Preview">
                        ${isMain ? '<div class="main-image-badge">Main</div>' : ''}
                        <div class="remove-image" onclick="removeEditImage(${index})">
                            <i class="fas fa-times"></i>
                        </div>
                        <div class="set-main" onclick="setEditMainImage(${index})">
                            Set as Main
                        </div>
                    `;
                    return div;
                }

                function setEditMainImage(index) {
                    console.log('Setting main image index:', index);
                    const previews = document.querySelectorAll('#editImagePreviewContainer .image-preview');
                    editMainImageIndex = parseInt(index);
                    
                    // Remove all main image badges
                    previews.forEach(preview => {
                        const badge = preview.querySelector('.main-image-badge');
                        if (badge) badge.remove();
                    });

                    // Add main image badge to selected image
                    const selectedPreview = document.querySelector(`#editImagePreviewContainer .image-preview[data-index="${index}"]`);
                    if (selectedPreview) {
                        const badge = document.createElement('div');
                        badge.className = 'main-image-badge';
                        badge.textContent = 'Main';
                        selectedPreview.appendChild(badge);
                    }

                    // Log the current state
                    console.log('Current editMainImageIndex:', editMainImageIndex);
                    console.log('Selected files:', editSelectedFiles);
                    console.log('Existing images:', editExistingImages);
                }

                function removeEditImage(index) {
                    const preview = document.querySelector(`#editImagePreviewContainer .image-preview[data-index="${index}"]`);
                    if (preview) {
                        preview.remove();
                        delete editSelectedFiles[index];
                        if (editMainImageIndex === parseInt(index)) {
                            editMainImageIndex = -1;
                            // Set new main image if available
                            const remainingPreviews = document.querySelectorAll('#editImagePreviewContainer .image-preview');
                            if (remainingPreviews.length > 0) {
                                setEditMainImage(remainingPreviews[0].getAttribute('data-index'));
                            }
                        }
                    }
                }

                function editProduct(productId) {
                    console.log('Editing product:', productId);
                    
                    // Reset edit modal state
                    editMainImageIndex = -1;
                    editCurrentIndex = 0;
                    editSelectedFiles = [];
                    editExistingImages = [];
                    
                    // First load categories
                    fetch('includes/product_operations.php?action=get_categories')
                        .then(response => response.json())
                        .then(categories => {
                            const categorySelect = document.getElementById('editProductCategory');
                            categorySelect.innerHTML = '<option value="">Select Category</option>';
                            categories.forEach(category => {
                                const option = document.createElement('option');
                                option.value = category.category_id;
                                option.textContent = category.name;
                                categorySelect.appendChild(option);
                            });

                            // Then fetch product details
                            return fetch('includes/product_operations.php?action=get_products');
                        })
                        .then(response => response.json())
                        .then(products => {
                            console.log('Products received:', products);
                            const product = products.find(p => p.id == productId);
                            console.log('Found product:', product);
                            
                            if (product) {
                                // Populate form
                                document.getElementById('editProductId').value = product.id;
                                document.getElementById('editProductName').value = product.product_name;
                                document.getElementById('editProductPrice').value = product.price;
                                document.getElementById('editProductDescription').value = product.description || '';
                                
                                // Set category
                                const categorySelect = document.getElementById('editProductCategory');
                                categorySelect.value = product.category_id;
                                
                                // Show current images
                                const imageContainer = document.getElementById('editImagePreviewContainer');
                                imageContainer.innerHTML = '';
                                
                                // Add main image
                                if (product.main_image) {
                                    console.log('Adding main image:', product.main_image);
                                    const mainImageDiv = createImagePreview(`../${product.main_image}`, 0, true);
                                    imageContainer.appendChild(mainImageDiv);
                                    editMainImageIndex = 0;
                                    editExistingImages[0] = product.main_image;
                                }

                                // Add sub-images
                                if (product.sub_images && product.sub_images.length > 0) {
                                    console.log('Adding sub images:', product.sub_images);
                                    product.sub_images.forEach((image, index) => {
                                        console.log('Adding sub image:', image);
                                        const imageDiv = createImagePreview(`../${image}`, index + 1, false);
                                        imageContainer.appendChild(imageDiv);
                                        editExistingImages[index + 1] = image;
                                    });
                                } else {
                                    console.log('No sub images found for product');
                                }
                                
                                // Show modal
                                editModal.show();
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }

                function updateProduct() {
                    const formData = new FormData(document.getElementById('editProductForm'));
                    formData.append('action', 'update');
                    
                    // Get the main image preview
                    const mainImagePreview = document.querySelector(`#editImagePreviewContainer .image-preview[data-index="${editMainImageIndex}"] img`);
                    if (!mainImagePreview) {
                        alert('Please select a main image');
                        return;
                    }

                    // Get description value directly from the textarea
                    const description = document.getElementById('editProductDescription').value;
                    formData.set('description', description); // Use set instead of append to ensure only one value
                    // Handle main image
                    if (editSelectedFiles[editMainImageIndex]) {
                        // If it's a new file
                        console.log('Using new file as main image:', editMainImageIndex);
                        formData.append('main_image', editSelectedFiles[editMainImageIndex]);
                    } else {
                        // If it's an existing image
                        console.log('Using existing image as main image:', editMainImageIndex);
                        const imagePath = editExistingImages[editMainImageIndex].split('/').pop(); // Get just the filename
                        formData.append('main_image_path', imagePath);
                    }
                    
                    // Add any new images that aren't the main image
                    Object.entries(editSelectedFiles).forEach(([index, file]) => {
                        if (parseInt(index) !== editMainImageIndex) {
                            formData.append('additional_images[]', file);
                        }
                    });
                    
                    // Show loading state
                    const submitButton = document.querySelector('#editProductModal .btn-primary');
                    const originalText = submitButton.innerHTML;
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
                    
                    // Log the form data for debugging
                    console.log('Sending form data:');
                    for (let pair of formData.entries()) {
                        console.log(pair[0] + ': ' + pair[1]);
                    }
                    
                    fetch('includes/product_operations.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
                            loadProducts();
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the product');
                    })
                    .finally(() => {
                        submitButton.disabled = false;
                        submitButton.innerHTML = originalText;
                    });
                }

                function showDeleteModal(productId) {
                    document.getElementById('deleteProductId').value = productId;
                    new bootstrap.Modal(document.getElementById('deleteProductModal')).show();
                }

                function confirmDelete() {
    const productId = document.getElementById('deleteProductId').value;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', productId);
    
    // Show loading state
    const submitButton = document.querySelector('#deleteProductModal .btn-danger');
    const originalText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
    
    fetch('includes/product_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const text = await response.text();
        try {
            const data = JSON.parse(text);
            if (!response.ok) {
                throw new Error(data.message || 'Server error occurred');
            }
            return data;
        } catch (e) {
            console.error('Server response:', text);
            throw new Error('Failed to delete product');
        }
    })
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('deleteProductModal')).hide();
            loadProducts();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the product: ' + error.message);
    })
    .finally(() => {
        submitButton.disabled = false;
        submitButton.innerHTML = originalText;
    });
}
                </script>

            </div>
        </div>
    </div>

     <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 