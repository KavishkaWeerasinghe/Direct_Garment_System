// Inventory Management JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize inventory functionality
    initializeInventory();
});

function initializeInventory() {
    // Product selection change handler
    const productSelect = document.getElementById('product_select');
    if (productSelect) {
        productSelect.addEventListener('change', function() {
            const productId = this.value;
            if (productId) {
                loadProductSizes(productId);
                loadProductColors(productId);
                document.getElementById('selected_product_id').value = productId;
            }
        });
    }

    // Initialize tooltips and other Bootstrap components
    if (typeof bootstrap !== 'undefined') {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Load product sizes via AJAX
function loadProductSizes(productId) {
    showLoading('size_select');
    
    fetch('inventory.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_product_sizes&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading('size_select');
        if (data.success) {
            const sizeSelect = document.getElementById('size_select');
            sizeSelect.innerHTML = '<option value="">Select size...</option>';
            data.sizes.forEach(size => {
                sizeSelect.innerHTML += `<option value="${size.id}">${size.size_name}</option>`;
            });
        } else {
            showError('Failed to load sizes: ' + data.message);
        }
    })
    .catch(error => {
        hideLoading('size_select');
        showError('Error loading sizes: ' + error.message);
    });
}

// Load product colors via AJAX
function loadProductColors(productId) {
    showLoading('color_select');
    
    fetch('inventory.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_product_colors&product_id=${productId}`
    })
    .then(response => response.json())
    .then(data => {
        hideLoading('color_select');
        if (data.success) {
            const colorSelect = document.getElementById('color_select');
            colorSelect.innerHTML = '<option value="">Select color...</option>';
            data.colors.forEach(color => {
                colorSelect.innerHTML += `<option value="${color.id}">${color.color_name}</option>`;
            });
        } else {
            showError('Failed to load colors: ' + data.message);
        }
    })
    .catch(error => {
        hideLoading('color_select');
        showError('Error loading colors: ' + error.message);
    });
}

// Submit inventory form
function submitInventory() {
    const form = document.getElementById('addInventoryForm');
    if (!form) return;

    // Validate form
    if (!validateInventoryForm(form)) {
        return;
    }

    const formData = new FormData(form);
    formData.append('action', 'add_inventory');
    
    // Show loading state
    const submitBtn = document.querySelector('#addInventoryModal .btn-primary');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Adding...';
    submitBtn.disabled = true;
    
    fetch('inventory.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        
        if (data.success) {
            showSuccess('Inventory added successfully!');
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('addInventoryModal'));
            if (modal) {
                modal.hide();
            }
            // Reload page to show updated data
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showError('Error: ' + data.message);
        }
    })
    .catch(error => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
        showError('Error: ' + error.message);
    });
}

// Validate inventory form
function validateInventoryForm(form) {
    const productId = form.querySelector('[name="product_id"]').value;
    const sizeId = form.querySelector('[name="size_id"]').value;
    const colorId = form.querySelector('[name="color_id"]').value;
    const quantity = form.querySelector('[name="quantity"]').value;

    if (!productId) {
        showError('Please select a product');
        return false;
    }

    if (!sizeId) {
        showError('Please select a size');
        return false;
    }

    if (!colorId) {
        showError('Please select a color');
        return false;
    }

    if (!quantity || quantity <= 0) {
        showError('Please enter a valid quantity');
        return false;
    }

    return true;
}

// Add inventory for specific product
function addInventory(productId) {
    const productSelect = document.getElementById('product_select');
    const selectedProductId = document.getElementById('selected_product_id');
    
    if (productSelect) {
        productSelect.value = productId;
    }
    if (selectedProductId) {
        selectedProductId.value = productId;
    }
    
    loadProductSizes(productId);
    loadProductColors(productId);
    
    const modal = new bootstrap.Modal(document.getElementById('addInventoryModal'));
    modal.show();
}

// Utility functions
function showLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.add('loading');
        element.disabled = true;
    }
}

function hideLoading(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.remove('loading');
        element.disabled = false;
    }
}

function showSuccess(message) {
    showMessage(message, 'success');
}

function showError(message) {
    showMessage(message, 'error');
}

function showMessage(message, type) {
    // Remove existing messages
    const existingMessages = document.querySelectorAll('.message');
    existingMessages.forEach(msg => msg.remove());
    
    // Create new message
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}`;
    messageDiv.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
        ${message}
    `;
    
    // Insert at top of main content
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.insertBefore(messageDiv, mainContent.firstChild);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            messageDiv.remove();
        }, 5000);
    }
}

// Export functions for global access
window.loadProductSizes = loadProductSizes;
window.loadProductColors = loadProductColors;
window.submitInventory = submitInventory;
window.addInventory = addInventory; 