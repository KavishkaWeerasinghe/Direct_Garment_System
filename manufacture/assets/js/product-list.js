// Product List Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    initializeSidebar();
    initializeSearch();
});

function initializeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    const toggleBtn = document.getElementById('sidebarToggle');

    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
        });
    }

    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
        mainContent.classList.add('collapsed');
    }
}

function initializeSearch() {
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            // Form will submit normally, no need for custom handling
        });
    }
}

// View Product Details
function viewProduct(productId) {
    showLoadingModal();
    
    fetch(`list.php?action=get_product_details&product_id=${productId}`)
    .then(response => response.json())
    .then(data => {
        hideLoadingModal();
        if (data.success) {
            displayProductModal(data.product, data.inventory_summary, data.inventory_details);
        } else {
            showNotification('Error loading product details: ' + data.message, 'error');
        }
    })
    .catch(error => {
        hideLoadingModal();
        console.error('Error:', error);
        showNotification('Error loading product details', 'error');
    });
}

// Display Product Modal
function displayProductModal(product, inventorySummary, inventoryDetails) {
    const modalBody = document.getElementById('productModalBody');
    
    let imagesHtml = '';
    if (product.images && product.images.length > 0) {
        imagesHtml = '<div class="product-images">';
        product.images.forEach(image => {
            const isMain = image.is_main ? 'main' : '';
            imagesHtml += `
                <div class="product-image-item ${isMain}">
                    <img src="${image.image_path}" alt="Product Image" 
                         onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjRjVGNUY1Ii8+Cjx0ZXh0IHg9IjUwIiB5PSI1MCIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjEyIiBmaWxsPSIjOTk5IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+SW1hZ2U8L3RleHQ+Cjwvc3ZnPgo='">
                </div>
            `;
        });
        imagesHtml += '</div>';
    } else {
        imagesHtml = '<p>No images available</p>';
    }
    
    let sizesHtml = '';
    if (product.sizes && product.sizes.length > 0) {
        sizesHtml = '<div class="sizes-list">';
        product.sizes.forEach(size => {
            sizesHtml += `
                <span class="size-badge">
                    ${size.size_name} - $${parseFloat(size.selling_price).toFixed(2)}
                </span>
            `;
        });
        sizesHtml += '</div>';
    } else {
        sizesHtml = '<p>No sizes available</p>';
    }
    
    let colorsHtml = '';
    if (product.colors && product.colors.length > 0) {
        colorsHtml = '<div class="colors-list">';
        product.colors.forEach(color => {
            colorsHtml += `
                <span class="color-badge" style="background-color: ${color.color_code};">
                    ${color.color_name}
                </span>
            `;
        });
        colorsHtml += '</div>';
    } else {
        colorsHtml = '<p>No colors available</p>';
    }
    
    let inventoryTableHtml = '';
    if (inventoryDetails && inventoryDetails.length > 0) {
        inventoryTableHtml = `
            <table class="inventory-table">
                <thead>
                    <tr>
                        <th>Size</th>
                        <th>Color</th>
                        <th>Quantity</th>
                        <th>Reserved</th>
                        <th>Available</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
        `;
        
        inventoryDetails.forEach(item => {
            inventoryTableHtml += `
                <tr>
                    <td>${item.size_name || 'N/A'}</td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div class="color-preview" style="background-color: ${item.color_code || '#ccc'};"></div>
                            ${item.color_name || 'N/A'}
                        </div>
                    </td>
                    <td>${item.quantity || 0}</td>
                    <td>${item.reserved_quantity || 0}</td>
                    <td>${item.available_quantity || 0}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-primary" 
                                onclick="editInventory(${item.id}, ${item.quantity || 0})">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                    </td>
                </tr>
            `;
        });
        
        inventoryTableHtml += '</tbody></table>';
    } else {
        inventoryTableHtml = '<p>No inventory records found. <a href="inventory.php">Add inventory</a></p>';
    }
    
    modalBody.innerHTML = `
        <div class="product-details-grid">
            <div class="product-images-section">
                <h4>Product Images</h4>
                ${imagesHtml}
            </div>
            <div class="product-info">
                <h4>${product.name}</h4>
                <p>${product.description}</p>
                
                <div class="product-meta">
                    <div class="meta-item">
                        <div class="meta-label">Category</div>
                        <div class="meta-value">${product.category_name}</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Subcategory</div>
                        <div class="meta-value">${product.subcategory_name || 'N/A'}</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Status</div>
                        <div class="meta-value">
                            <span class="status-badge ${getStatusClass(inventorySummary?.stock_status || 'No Inventory')}">
                                ${inventorySummary?.stock_status || 'No Inventory'}
                            </span>
                        </div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-label">Total Available</div>
                        <div class="meta-value">${inventorySummary?.total_available || 0}</div>
                    </div>
                </div>
                
                <div class="product-details">
                    <h5>Sizes & Pricing</h5>
                    ${sizesHtml}
                    
                    <h5>Colors</h5>
                    ${colorsHtml}
                    
                    ${product.tags ? `
                        <h5>Tags</h5>
                        <div class="tags-list">
                            ${product.tags.split(',').map(tag => `<span class="tag-badge">${tag.trim()}</span>`).join('')}
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
        
        <div class="inventory-details">
            <h4>Inventory Details</h4>
            ${inventoryTableHtml}
        </div>
    `;
    
    document.getElementById('productModal').style.display = 'flex';
}

// Get Status Class
function getStatusClass(status) {
    switch (status) {
        case 'In Stock': return 'status-in-stock';
        case 'Low Stock': return 'status-low-stock';
        case 'Out of Stock': return 'status-out-of-stock';
        default: return 'status-no-inventory';
    }
}

// Close Product Modal
function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
}

// Delete Product
function deleteProduct(productId, productName) {
    if (!confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
        return;
    }
    
    const formData = new FormData();
    formData.append('product_id', productId);
    
    fetch('list.php?action=delete_product', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Product deleted successfully!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification('Error deleting product: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error deleting product', 'error');
    });
}

// Edit Inventory
function editInventory(inventoryId, currentQuantity) {
    const newQuantity = prompt('Enter new quantity:', currentQuantity);
    
    if (newQuantity === null) return; // User cancelled
    
    const quantity = parseInt(newQuantity);
    if (isNaN(quantity) || quantity < 0) {
        showNotification('Please enter a valid quantity', 'error');
        return;
    }
    
    const reason = prompt('Reason for update (optional):', 'Manual update');
    
    const formData = new FormData();
    formData.append('inventory_id', inventoryId);
    formData.append('quantity', quantity);
    formData.append('reason', reason || 'Manual update');
    
    fetch('list.php?action=update_inventory', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Inventory updated successfully!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showNotification('Error updating inventory: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error updating inventory', 'error');
    });
}

// Show Loading Modal
function showLoadingModal() {
    const modal = document.getElementById('productModal');
    const modalBody = document.getElementById('productModalBody');
    
    modalBody.innerHTML = `
        <div style="text-align: center; padding: 40px;">
            <div class="loading-spinner"></div>
            <p>Loading product details...</p>
        </div>
    `;
    
    modal.style.display = 'flex';
}

// Hide Loading Modal
function hideLoadingModal() {
    // Loading will be replaced by actual content
}

// Show Notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'error' ? 'danger' : type}`;
    notification.innerHTML = message;
    notification.style.position = 'fixed';
    notification.style.top = '20px';
    notification.style.right = '20px';
    notification.style.zIndex = '9999';
    notification.style.minWidth = '300px';
    notification.style.maxWidth = '500px';

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 5000);
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('productModal');
    if (e.target === modal) {
        closeProductModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeProductModal();
    }
});

// Add CSS for loading spinner
const productListStyle = document.createElement('style');
productListStyle.textContent = `
    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .sizes-list, .colors-list, .tags-list {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }
    
    .size-badge, .color-badge, .tag-badge {
        padding: 5px 12px;
        border-radius: 15px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .size-badge {
        background: #e9ecef;
        color: #495057;
    }
    
    .color-badge {
        color: white;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }
    
    .tag-badge {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .product-details h5 {
        margin: 20px 0 10px 0;
        color: #2c3e50;
        font-size: 1rem;
        font-weight: 600;
    }
`;
document.head.appendChild(productListStyle); 