<?php
if (!isset($_COOKIE['user_id'])) {
    header('Location: login.php?tab=signin');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GarmentDirect - Shopping Cart</title>
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
        .cart-item {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .cart-item img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 4px;
        }
        .quantity-input {
            width: 70px;
            text-align: center;
        }
        .cart-summary {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container py-5">
        <h1 class="mb-4">Shopping Cart</h1>
        
        <div class="row">
            <div class="col-md-8">
                <div id="cartItems">
                    <!-- Cart items will be loaded here -->
                </div>
            </div>
            <div class="col-md-4">
                <div class="cart-summary">
                    <h4 class="mb-3">Order Summary</h4>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span id="subtotal">Rs. 0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span id="shipping">Rs. 0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total</strong>
                        <strong id="total">Rs. 0.00</strong>
                    </div>
                    <button class="btn btn-primary w-100 mb-2" onclick="checkout()">Buy Now</button>
                    <button class="btn btn-outline-primary w-100" onclick="continueShopping()">Continue Shopping</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        loadCartItems();
    });

    function loadCartItems() {
        fetch('includes/cart_operations.php?action=get_items')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCartItems(data.items);
                    updateSummary(data.items);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading cart items');
            });
    }

    function displayCartItems(items) {
        const container = document.getElementById('cartItems');
        
        if (items.length === 0) {
            container.innerHTML = `
                <div class="text-center py-5">
                    <h3 class="mb-3">Your cart is empty</h3>
                    <p class="text-muted mb-4">Add some items to your cart to see them here.</p>
                    <button class="btn btn-primary" onclick="continueShopping()">
                        <i class="fas fa-shopping-bag me-2"></i>Go Shopping
                    </button>
                </div>`;
            return;
        }
        
        container.innerHTML = items.map(item => `
            <div class="cart-item" id="cart-item-${item.id}">
                <div class="row align-items-center">
                    <div class="col-md-2">
                        <img src="${item.image_url}" alt="${item.product_name}" class="img-fluid">
                    </div>
                    <div class="col-md-4">
                        <h5 class="mb-1">${item.product_name}</h5>
                        <p class="text-muted mb-0">Rs. ${item.price}</p>
                    </div>
                    <div class="col-md-3">
                        <div class="input-group">
                            <button class="btn btn-outline-secondary" onclick="updateQuantity(${item.id}, ${item.quantity - 1})">-</button>
                            <input type="number" class="form-control quantity-input" value="${item.quantity}" 
                                   onchange="updateQuantity(${item.id}, this.value)">
                            <button class="btn btn-outline-secondary" onclick="updateQuantity(${item.id}, ${item.quantity + 1})">+</button>
                        </div>
                    </div>
                    <div class="col-md-2 text-end">
                        <p class="mb-0">Rs. ${(item.price * item.quantity).toFixed(2)}</p>
                    </div>
                    <div class="col-md-1 text-end">
                        <button class="btn btn-link text-danger" onclick="removeItem(${item.id})">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function updateQuantity(cartId, quantity) {
        if (quantity < 1) return;
        
        const formData = new FormData();
        formData.append('action', 'update_quantity');
        formData.append('cart_id', cartId);
        formData.append('quantity', quantity);
        
        fetch('includes/cart_operations.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCartItems();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating quantity');
        });
    }

    function removeItem(cartId) {
        if (!confirm('Are you sure you want to remove this item?')) return;
        
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('cart_id', cartId);
        
        fetch('includes/cart_operations.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadCartItems();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error removing item');
        });
    }

    function updateSummary(items) {
        const subtotal = items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const shipping = subtotal > 0 ? 10 : 0; // Rs. 10 shipping if there are items
        const total = subtotal + shipping;
        
        document.getElementById('subtotal').textContent = `Rs. ${subtotal.toFixed(2)}`;
        document.getElementById('shipping').textContent = `Rs. ${shipping.toFixed(2)}`;
        document.getElementById('total').textContent = `Rs. ${total.toFixed(2)}`;
    }

    function checkout() {
        // Implement checkout functionality
        alert('Checkout functionality will be implemented here');
    }

    function continueShopping() {
        window.location.href = 'product.php';
    }
    </script>
</body>
</html> 