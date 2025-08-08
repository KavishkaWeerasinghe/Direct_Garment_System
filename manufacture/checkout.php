<?php
if (!isset($_COOKIE['user_id'])) {
    header('Location: login.php?tab=signin');
    exit;
}

// Get cart items for order summary
require_once 'includes/db_connection.php';
require_once 'includes/cart_operations.php';

$user_id = $_COOKIE['user_id'];
$cart_items = getCartItems($user_id);
$order_total = calculateOrderTotal($cart_items);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GarmentDirect - Checkout</title>
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
        .checkout-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .order-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }
        .form-control:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        .payment-method {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .payment-method:hover {
            border-color: #007bff;
        }
        .payment-method.selected {
            border-color: #007bff;
            background-color: #f8f9ff;
        }
    </style>
</head>
<body>
    <?php include 'components/header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8">
                <div class="checkout-container">
                    <h2 class="mb-4">Checkout</h2>
                    
                    <!-- Shipping Information -->
                    <div class="mb-4">
                        <h4 class="mb-3">Shipping Information</h4>
                        <form id="shippingForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="firstName" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="firstName" name="firstName" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="lastName" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="lastName" name="lastName" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address *</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="city" class="form-label">City *</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="postalCode" class="form-label">Postal Code *</label>
                                    <input type="text" class="form-control" id="postalCode" name="postalCode" required>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Payment Method -->
                    <div class="mb-4">
                        <h4 class="mb-3">Payment Method</h4>
                        <div class="payment-method selected" data-method="cod">
                            <div class="d-flex align-items-center">
                                <input type="radio" name="paymentMethod" value="cod" checked class="me-3">
                                <div>
                                    <strong>Cash on Delivery</strong>
                                    <p class="mb-0 text-muted">Pay when you receive your order</p>
                                </div>
                            </div>
                        </div>
                        <div class="payment-method" data-method="card">
                            <div class="d-flex align-items-center">
                                <input type="radio" name="paymentMethod" value="card" class="me-3">
                                <div>
                                    <strong>Credit/Debit Card</strong>
                                    <p class="mb-0 text-muted">Pay securely with your card</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Details (Hidden by default) -->
                    <div id="cardDetails" class="mb-4" style="display: none;">
                        <h4 class="mb-3">Card Details</h4>
                        <div class="mb-3">
                            <label for="cardNumber" class="form-label">Card Number *</label>
                            <input type="text" class="form-control" id="cardNumber" name="cardNumber" placeholder="1234 5678 9012 3456">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="expiryDate" class="form-label">Expiry Date *</label>
                                <input type="text" class="form-control" id="expiryDate" name="expiryDate" placeholder="MM/YY">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="cvv" class="form-label">CVV *</label>
                                <input type="text" class="form-control" id="cvv" name="cvv" placeholder="123">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="order-summary">
                    <h4 class="mb-3">Order Summary</h4>
                    
                    <?php foreach ($cart_items as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?php echo htmlspecialchars($item['product_name']); ?> x <?php echo $item['quantity']; ?></span>
                        <span>Rs. <?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                    <?php endforeach; ?>
                    
                    <hr>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>Rs. <?php echo number_format($order_total['subtotal'], 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping</span>
                        <span>Rs. <?php echo number_format($order_total['shipping'], 2); ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <strong>Total</strong>
                        <strong>Rs. <?php echo number_format($order_total['total'], 2); ?></strong>
                    </div>
                    
                    <button class="btn btn-primary w-100 mb-2" onclick="placeOrder()">
                        <i class="fas fa-lock me-2"></i>Place Order
                    </button>
                    <button class="btn btn-outline-secondary w-100" onclick="window.location.href='cart.php'">
                        <i class="fas fa-arrow-left me-2"></i>Back to Cart
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    // Payment method selection
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            // Remove selected class from all methods
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            // Add selected class to clicked method
            this.classList.add('selected');
            // Check the radio button
            this.querySelector('input[type="radio"]').checked = true;
            
            // Show/hide card details
            const paymentMethod = this.querySelector('input[type="radio"]').value;
            const cardDetails = document.getElementById('cardDetails');
            if (paymentMethod === 'card') {
                cardDetails.style.display = 'block';
            } else {
                cardDetails.style.display = 'none';
            }
        });
    });

    function placeOrder() {
        // Get form data
        const formData = new FormData();
        
        // Shipping information
        formData.append('firstName', document.getElementById('firstName').value);
        formData.append('lastName', document.getElementById('lastName').value);
        formData.append('email', document.getElementById('email').value);
        formData.append('phone', document.getElementById('phone').value);
        formData.append('address', document.getElementById('address').value);
        formData.append('city', document.getElementById('city').value);
        formData.append('postalCode', document.getElementById('postalCode').value);
        
        // Payment method
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
        formData.append('paymentMethod', paymentMethod);
        
        // Card details (if applicable)
        if (paymentMethod === 'card') {
            formData.append('cardNumber', document.getElementById('cardNumber').value);
            formData.append('expiryDate', document.getElementById('expiryDate').value);
            formData.append('cvv', document.getElementById('cvv').value);
        }
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        const button = document.querySelector('button[onclick="placeOrder()"]');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        button.disabled = true;
        
        // Submit order
        fetch('includes/process_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Redirect to order confirmation
                window.location.href = 'order_confirmation.php?order_id=' + data.order_id;
            } else {
                alert('Error: ' + data.message);
                // Restore button
                button.innerHTML = originalText;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error processing order. Please try again.');
            // Restore button
            button.innerHTML = originalText;
            button.disabled = false;
        });
    }

    function validateForm() {
        const requiredFields = ['firstName', 'lastName', 'email', 'phone', 'address', 'city', 'postalCode'];
        
        for (let field of requiredFields) {
            const value = document.getElementById(field).value.trim();
            if (!value) {
                alert('Please fill in all required fields.');
                document.getElementById(field).focus();
                return false;
            }
        }
        
        // Validate email
        const email = document.getElementById('email').value;
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            alert('Please enter a valid email address.');
            document.getElementById('email').focus();
            return false;
        }
        
        // Validate card details if card payment is selected
        const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
        if (paymentMethod === 'card') {
            const cardNumber = document.getElementById('cardNumber').value.trim();
            const expiryDate = document.getElementById('expiryDate').value.trim();
            const cvv = document.getElementById('cvv').value.trim();
            
            if (!cardNumber || !expiryDate || !cvv) {
                alert('Please fill in all card details.');
                return false;
            }
        }
        
        return true;
    }
    </script>
</body>
</html>
