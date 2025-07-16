<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ManufactureHub - Batch Inventory</title>
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
            color: #333;
        }
        .products-header > .d-flex {
            flex-grow: 1;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
        }
        .search-input-container {
            position: relative;
            width: 520px;
            flex-grow: 1;
            max-width: 520px;
        }
        .search-input {
            padding-left: 35px;
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }
        .products-header .btn-primary {
            padding: 8px 15px;
        }
        .batch-table-card {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .batch-status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-available { background-color: #d1fae5; color: #065f46; }
        .status-low { background-color: #fffbeb; color: #92400e; }
        .status-expired { background-color: #fee2e2; color: #991b1b; }
        .action-icons i {
            margin-right: 10px;
            cursor: pointer;
            color: #6c757d;
        }
        .action-icons i.fa-edit { color: #0d6efd; }
        .action-icons i.fa-trash-alt { color: #dc3545; }
        .sidebar-nav .list-group-item.active {
            background-color: #2563eb;
            color: #fff;
        }
        .sidebar-nav .list-group-item.active i {
            color: #fff;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <!-- Sidebar -->
        <?php $active_item = 'inventory'; include 'components/sidebar.php'; ?>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top Header -->
            <?php $title = 'Batch Inventory'; include 'components/top_header.php'; ?>

            <div class="container-fluid py-4 px-4">
                <div class="products-header">
                    <div class="d-flex justify-content-between">
                        <h1>Batch Inventory</h1>
                        <div class="search-input-container me-2">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" class="form-control search-input" placeholder="Search batches...">
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBatchModal">
                            <i class="fas fa-plus me-2"></i> Add Batch
                        </button>
                    </div>
                </div>

                <!-- Add Batch Modal -->
                <div class="modal fade" id="addBatchModal" tabindex="-1" aria-labelledby="addBatchModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addBatchModalLabel">Add New Batch</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="addBatchForm">
                                    <div class="mb-3">
                                        <label for="addProduct" class="form-label">Product</label>
                                        <select class="form-select" id="addProduct" name="product_id" required>
                                            <option value="">Select Product</option>
                                            <!-- Products will be loaded via AJAX -->
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="quantity" class="form-label">Quantity</label>
                                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="manufactureDate" class="form-label">Manufacture Date</label>
                                        <input type="date" class="form-control" id="manufactureDate" name="manufacture_date" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="expiryDate" class="form-label">Expiry Date</label>
                                        <input type="date" class="form-control" id="expiryDate" name="expiry_date" required>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="saveBatchBtn">Save Batch</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Edit Batch Modal -->
                <div class="modal fade" id="editBatchModal" tabindex="-1" aria-labelledby="editBatchModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="editBatchModalLabel">Edit Batch</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="editBatchForm">
                                    <input type="hidden" id="editBatchId" name="batch_id">
                                    <div class="mb-3">
                                        <label for="editProduct" class="form-label">Product</label>
                                        <select class="form-select" id="editProduct" name="product_id" required>
                                            <option value="">Select Product</option>
                                            <!-- Products will be loaded via AJAX -->
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editQuantity" class="form-label">Quantity</label>
                                        <input type="number" class="form-control" id="editQuantity" name="quantity" min="1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editManufactureDate" class="form-label">Manufacture Date</label>
                                        <input type="date" class="form-control" id="editManufactureDate" name="manufacture_date" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="editExpiryDate" class="form-label">Expiry Date</label>
                                        <input type="date" class="form-control" id="editExpiryDate" name="expiry_date" required>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" id="updateBatchBtn">Update Batch</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Delete Confirmation Modal -->
                <div class="modal fade" id="deleteBatchModal" tabindex="-1" aria-labelledby="deleteBatchModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteBatchModalLabel">Confirm Delete</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Are you sure you want to delete this batch? This action cannot be undone.</p>
                                <input type="hidden" id="deleteBatchId">
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <div class="dropdown d-inline-block me-2">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButtonCategories" data-bs-toggle="dropdown" aria-expanded="false">
                                All Products
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButtonCategories">
                                <li><a class="dropdown-item" href="#">Product A</a></li>
                                <li><a class="dropdown-item" href="#">Product B</a></li>
                            </ul>
                        </div>
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButtonSort" data-bs-toggle="dropdown" aria-expanded="false">
                                Sort by
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButtonSort">
                                <li><a class="dropdown-item" href="#">Batch ID</a></li>
                                <li><a class="dropdown-item" href="#">Expiry Date</a></li>
                            </ul>
                        </div>
                    </div>
                    <div>
                        <i class="fas fa-question-circle me-2"></i>
                        <i class="fas fa-list"></i>
                    </div>
                </div>

                <div class="batch-table-card">
                    <table class="table table-borderless">
                        <thead>
                            <tr>
                                <th>Batch ID</th>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Manufactured Date</th>
                                <th>Expiry Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="batchesTableBody">
                            <!-- Batches will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Load products for dropdowns
    loadProducts();
    
    // Load batches for table
    loadBatches();
    
    // Initialize modals
    const addBatchModal = new bootstrap.Modal(document.getElementById('addBatchModal'));
    const editBatchModal = new bootstrap.Modal(document.getElementById('editBatchModal'));
    const deleteBatchModal = new bootstrap.Modal(document.getElementById('deleteBatchModal'));
    
    // Save new batch
    document.getElementById('saveBatchBtn').addEventListener('click', function() {
        saveBatch();
    });
    
    // Update batch
    document.getElementById('updateBatchBtn').addEventListener('click', function() {
        updateBatch();
    });
    
    // Confirm delete
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        confirmDelete();
    });
    
    // Search functionality
    document.querySelector('.search-input').addEventListener('input', function(e) {
        filterBatches(e.target.value);
    });
});

function loadProducts() {
    fetch('includes/inventory_operations.php?action=get_products')
        .then(response => response.json())
        .then(products => {
            const addProductSelect = document.getElementById('addProduct');
            const editProductSelect = document.getElementById('editProduct');
            
            // Clear existing options
            addProductSelect.innerHTML = '<option value="">Select Product</option>';
            editProductSelect.innerHTML = '<option value="">Select Product</option>';
            
            // Add products to dropdowns
            products.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = product.product_name;
                
                addProductSelect.appendChild(option.cloneNode(true));
                editProductSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading products:', error);
        });
}

function loadBatches() {
    fetch('includes/inventory_operations.php?action=get_batches')
        .then(response => response.json())
        .then(batches => {
            const tbody = document.getElementById('batchesTableBody');
            tbody.innerHTML = '';
            
            if (batches.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center">No batches found</td></tr>';
                return;
            }
            
            batches.forEach(batch => {
                const tr = document.createElement('tr');
                
                // Determine status badge class
                let statusClass = '';
                let statusText = '';
                
                switch(batch.status) {
                    case 'available':
                        statusClass = 'status-available';
                        statusText = 'Available';
                        break;
                    case 'low_stock':
                        statusClass = 'status-low';
                        statusText = 'Low Stock';
                        break;
                    case 'expired':
                        statusClass = 'status-expired';
                        statusText = 'Expired';
                        break;
                }
                
                tr.innerHTML = `
                    <td>${batch.batch_id}</td>
                    <td>${batch.product_name}</td>
                    <td>${batch.quantity}</td>
                    <td>${batch.manufacture_date}</td>
                    <td>${batch.expiry_date}</td>
                    <td><span class="batch-status-badge ${statusClass}">${statusText}</span></td>
                    <td>
                        <i class="fas fa-edit action-icons" onclick="editBatchModalHandler('${batch.batch_id}')"></i>
                        <i class="fas fa-trash-alt action-icons" onclick="showDeleteModal('${batch.batch_id}')"></i>
                    </td>
                `;
                
                tbody.appendChild(tr);
            });
        })
        .catch(error => {
            console.error('Error loading batches:', error);
            const tbody = document.getElementById('batchesTableBody');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center text-danger">Error loading batches</td></tr>';
        });
}

function saveBatch() {
    const form = document.getElementById('addBatchForm');
    const formData = new FormData(form);
    formData.append('action', 'add');
    
    const saveBtn = document.getElementById('saveBatchBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.disabled = true;
    saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
    
    fetch('includes/inventory_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and refresh table
            bootstrap.Modal.getInstance(document.getElementById('addBatchModal')).hide();
            loadBatches();
            
            // Reset form
            form.reset();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the batch');
    })
    .finally(() => {
        saveBtn.disabled = false;
        saveBtn.innerHTML = originalText;
    });
}

function editBatchModalHandler(batch_id) {
    fetch(`includes/inventory_operations.php?action=get_batch&batch_id=${encodeURIComponent(batch_id)}`)
        .then(response => response.json())
        .then(batch => {
            if (batch) {
                // Populate form
                document.getElementById('editBatchId').value = batch.batch_id;
                document.getElementById('editProduct').value = batch.product_id;
                document.getElementById('editQuantity').value = batch.quantity;
                document.getElementById('editManufactureDate').value = batch.manufacture_date;
                document.getElementById('editExpiryDate').value = batch.expiry_date;
                
                // Show modal
                bootstrap.Modal.getInstance(document.getElementById('editBatchModal')).show();
            } else {
                alert('Batch not found');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading batch details');
        });
}

function updateBatch() {
    const form = document.getElementById('editBatchForm');
    const formData = new FormData(form);
    formData.append('action', 'update');
    
    const updateBtn = document.getElementById('updateBatchBtn');
    const originalText = updateBtn.innerHTML;
    updateBtn.disabled = true;
    updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
    
    fetch('includes/inventory_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and refresh table
            bootstrap.Modal.getInstance(document.getElementById('editBatchModal')).hide();
            loadBatches();
            
            // Reset form
            form.reset();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the batch');
    })
    .finally(() => {
        updateBtn.disabled = false;
        updateBtn.innerHTML = originalText;
    });
}

function showDeleteModal(batch_id) {
    document.getElementById('deleteBatchId').value = batch_id;
    bootstrap.Modal.getInstance(document.getElementById('deleteBatchModal')).show();
}

function confirmDelete() {
    const batch_id = document.getElementById('deleteBatchId').value;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('batch_id', batch_id);
    
    const deleteBtn = document.getElementById('confirmDeleteBtn');
    const originalText = deleteBtn.innerHTML;
    deleteBtn.disabled = true;
    deleteBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Deleting...';
    
    fetch('includes/inventory_operations.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Close modal and refresh table
            bootstrap.Modal.getInstance(document.getElementById('deleteBatchModal')).hide();
            loadBatches();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the batch');
    })
    .finally(() => {
        deleteBtn.disabled = false;
        deleteBtn.innerHTML = originalText;
    });
}

function filterBatches(searchTerm) {
    const rows = document.querySelectorAll('#batchesTableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm.toLowerCase())) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
</body>
</html>