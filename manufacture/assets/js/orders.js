// Orders Page JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Initialize sidebar functionality
    initializeSidebar();
    
    // Initialize search functionality
    initializeSearch();
    
    // Initialize modal functionality
    initializeModals();
    
    // Initialize responsive table
    initializeResponsiveTable();
});

// Sidebar functionality
function initializeSidebar() {
    const toggleBtn = document.querySelector('.toggle-btn');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (toggleBtn && sidebar && mainContent) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('collapsed');
            
            // Save state to localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
        });
        
        // Load saved state
        const savedState = localStorage.getItem('sidebarCollapsed');
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('collapsed');
        }
    }
}

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchForm = document.getElementById('searchForm');
    
    if (searchInput && searchForm) {
        // Real-time search
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            if (searchTerm.length >= 2 || searchTerm.length === 0) {
                performSearch(searchTerm);
            }
        });
        
        // Form submission
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const searchTerm = searchInput.value.trim();
            performSearch(searchTerm);
        });
    }
}

// Perform search
function performSearch(searchTerm) {
    const tableBody = document.querySelector('tbody');
    const rows = tableBody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        row.style.display = matches ? '' : 'none';
    });
    
    // Show/hide empty state
    const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
    const emptyState = document.querySelector('.empty-state');
    
    if (visibleRows.length === 0 && searchTerm) {
        showEmptyState('No orders found matching your search.');
    } else if (visibleRows.length === 0 && !searchTerm) {
        showEmptyState('No orders available.');
    } else {
        hideEmptyState();
    }
}

// Show empty state
function showEmptyState(message) {
    let emptyState = document.querySelector('.empty-state');
    
    if (!emptyState) {
        emptyState = document.createElement('div');
        emptyState.className = 'empty-state';
        emptyState.innerHTML = `
            <i class="fas fa-box-open"></i>
            <h3>No Orders</h3>
            <p>${message}</p>
        `;
        
        const tableContainer = document.querySelector('.orders-table');
        if (tableContainer) {
            tableContainer.appendChild(emptyState);
        }
    } else {
        emptyState.querySelector('p').textContent = message;
        emptyState.style.display = 'block';
    }
}

// Hide empty state
function hideEmptyState() {
    const emptyState = document.querySelector('.empty-state');
    if (emptyState) {
        emptyState.style.display = 'none';
    }
}

// Modal functionality
function initializeModals() {
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Close modal with escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    closeModal(modal.id);
                }
            });
        }
    });
}

// View order details
function viewOrder(orderId) {
    // Show loading state
    const modal = document.getElementById('viewModal');
    const modalContent = modal.querySelector('.modal-content');
    modalContent.classList.add('loading');
    
    fetch('orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=get_order&order_id=${orderId}`
    })
    .then(response => response.json())
    .then(data => {
        modalContent.classList.remove('loading');
        
        if (data.success) {
            const order = data.order;
            const total = (order.product_price * order.quantity).toFixed(2);
            
            document.getElementById('modalTitle').textContent = `Order ${order.order_id}`;
            document.getElementById('modalBody').innerHTML = `
                <div class="invoice-content">
                    <div class="invoice-row">
                        <span><strong>Customer Name:</strong></span>
                        <span>${order.customer_name}</span>
                    </div>
                    <div class="invoice-row">
                        <span><strong>Email:</strong></span>
                        <span>${order.customer_email}</span>
                    </div>
                    <div class="invoice-row">
                        <span><strong>Address:</strong></span>
                        <span>${order.customer_address}</span>
                    </div>
                    <div class="invoice-row">
                        <span><strong>Product Name:</strong></span>
                        <span>${order.product_name}</span>
                    </div>
                    <div class="invoice-row">
                        <span><strong>Price:</strong></span>
                        <span>$${order.product_price}</span>
                    </div>
                    <div class="invoice-row">
                        <span><strong>Quantity:</strong></span>
                        <span>${order.quantity}</span>
                    </div>
                    <div class="invoice-row total">
                        <span><strong>Total:</strong></span>
                        <span>$${total}</span>
                    </div>
                </div>
            `;
            
            modal.style.display = 'block';
        } else {
            alert('Error loading order details: ' + data.message);
        }
    })
    .catch(error => {
        modalContent.classList.remove('loading');
        console.error('Error:', error);
        alert('Error loading order details');
    });
}

// Edit order status
function editOrder(orderId, currentStatus) {
    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
    const statusCell = row.querySelector('td:nth-child(4)');
    const actionCell = row.querySelector('td:nth-child(5)');
    
    // Get statuses from PHP
    const statuses = window.orderStatuses || ['Pending', 'Processing', 'Shipped', 'Out for Delivery', 'Delivered', 'Cancelled', 'Returned', 'Refunded'];
    
    let statusOptions = '';
    statuses.forEach(status => {
        const selected = status === currentStatus ? 'selected' : '';
        statusOptions += `<option value="${status}" ${selected}>${status}</option>`;
    });
    
    statusCell.innerHTML = `<select class="status-select" id="status_${orderId}">${statusOptions}</select>`;
    actionCell.innerHTML = `
        <button class="btn btn-ok" onclick="saveOrderStatus(${orderId})">OK</button>
        <button class="btn btn-cancel" onclick="cancelEdit(${orderId}, '${currentStatus}')">Cancel</button>
    `;
}

// Save order status
function saveOrderStatus(orderId) {
    const newStatus = document.getElementById(`status_${orderId}`).value;
    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
    
    // Show loading state
    row.classList.add('loading');
    
    fetch('orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&order_id=${orderId}&status=${encodeURIComponent(newStatus)}`
    })
    .then(response => response.json())
    .then(data => {
        row.classList.remove('loading');
        
        if (data.success) {
            // Update the row without reloading
            updateOrderRow(orderId, newStatus);
            // Update status counts
            updateStatusCounts();
            showNotification('Order status updated successfully!', 'success');
        } else {
            alert('Error updating status: ' + data.message);
        }
    })
    .catch(error => {
        row.classList.remove('loading');
        console.error('Error:', error);
        alert('Error updating status');
    });
}

// Update order row without reload
function updateOrderRow(orderId, newStatus) {
    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
    const statusCell = row.querySelector('td:nth-child(4)');
    const actionCell = row.querySelector('td:nth-child(5)');
    
    const statusClass = newStatus.toLowerCase().replace(' ', '-');
    statusCell.innerHTML = `<span class="status-badge status-${statusClass}">${newStatus}</span>`;
    actionCell.innerHTML = `
        <button class="btn btn-view" onclick="viewOrder(${orderId})">View</button>
        <button class="btn btn-update" onclick="editOrder(${orderId}, '${newStatus}')">Update</button>
    `;
}

// Cancel edit
function cancelEdit(orderId, originalStatus) {
    const row = document.querySelector(`tr[data-order-id="${orderId}"]`);
    const statusCell = row.querySelector('td:nth-child(4)');
    const actionCell = row.querySelector('td:nth-child(5)');
    
    const statusClass = originalStatus.toLowerCase().replace(' ', '-');
    statusCell.innerHTML = `<span class="status-badge status-${statusClass}">${originalStatus}</span>`;
    actionCell.innerHTML = `
        <button class="btn btn-view" onclick="viewOrder(${orderId})">View</button>
        <button class="btn btn-update" onclick="editOrder(${orderId}, '${originalStatus}')">Update</button>
    `;
}

// Close modal
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

// Update status counts
function updateStatusCounts() {
    fetch('orders.php?action=get_counts')
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Object.keys(data.counts).forEach(status => {
                const countElement = document.querySelector(`.status-box.${status.toLowerCase().replace(' ', '-')} .count`);
                if (countElement) {
                    countElement.textContent = data.counts[status];
                }
            });
        }
    })
    .catch(error => {
        console.error('Error updating counts:', error);
    });
}

// Responsive table functionality
function initializeResponsiveTable() {
    const table = document.querySelector('table');
    if (table) {
        // Add responsive wrapper if not exists
        if (!table.parentElement.classList.contains('table-responsive')) {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    }
}

// Show notification
function showNotification(message, type = 'info') {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification');
    existingNotifications.forEach(notification => notification.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    // Add styles
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInRight 0.3s ease;
    `;
    
    // Add animation styles
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
    
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Export functions for global access
window.viewOrder = viewOrder;
window.editOrder = editOrder;
window.saveOrderStatus = saveOrderStatus;
window.cancelEdit = cancelEdit;
window.closeModal = closeModal; 