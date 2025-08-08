<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>GarmentDirect</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            background: #f8fafc; 
            font-family: 'Inter', sans-serif;
        }
        .navbar {
            min-height: 80px;
        }
        .feature-icon { 
            font-size: 2rem; 
            color: #2563eb; 
        }
        .footer-link { 
            color: #6b7280; 
            text-decoration: none; 
        }
        .footer-link:hover { 
            text-decoration: underline; 
        }
        .footer-bg { 
            background: #fff; 
        }
        .card-img-top { 
            object-fit: cover; 
            height: 300px; 
        }
        .btn-link {
            text-decoration: none;
        }
        .profile-dropdown {
            position: relative;
        }
        .profile-dropdown-menu {
            position: absolute;
            right: 0;
            top: 100%;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 8px 0;
            min-width: 200px;
            display: none;
            z-index: 1000;
        }
        .profile-dropdown-menu.show {
            display: block;
        }
        .profile-dropdown-item {
            padding: 8px 16px;
            color: #374151;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .profile-dropdown-item:hover {
            background: #f3f4f6;
        }
        .profile-img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            object-fit: cover;
            cursor: pointer;
        }
        .cart-icon {
            position: relative;
            margin-right: 20px;
            cursor: pointer;
        }
        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary" href="index.php">GarmentDirect</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="product.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link" href="aboutUs.php">About</a></li>
                    <li class="nav-item"><a class="nav-link" href="contactUs.php">Contact Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="faq.php">FAQ</a></li>
                </ul>
                <div class="d-flex align-items-center">
                    <?php
                    if (isset($_SESSION['user_id'])) {
                        // User is logged in
                        $profile_img = isset($_SESSION['profile_photo']) && !empty($_SESSION['profile_photo']) 
                            ? $_SESSION['profile_photo'] 
                            : 'src/images/profile.jpeg';
                        ?>
                        <a href="cart.php" class="cart-icon text-decoration-none">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <span class="cart-count" id="cartCount">0</span>
                        </a>
                        <div class="profile-dropdown">
                            <img src="<?php echo htmlspecialchars($profile_img); ?>" 
                                 alt="Profile" 
                                 class="profile-img" 
                                 id="profileDropdown">
                            <div class="profile-dropdown-menu" id="profileMenu">
                                <a href="profile.php" class="profile-dropdown-item">
                                    <i class="fas fa-user"></i> Update Profile
                                </a>
                                <a href="change-password.php" class="profile-dropdown-item">
                                    <i class="fas fa-key"></i> Change Password
                                </a>
                                <a href="logout.php" class="profile-dropdown-item">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </div>
                        </div>
                        <?php
                    } else {
                        // User is not logged in
                        ?>
                        <a href="login.php?tab=signin" class="cart-icon text-decoration-none">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <span class="cart-count" id="cartCount">0</span>
                        </a>
                        <a href="login.php?tab=signin" class="btn btn-link text-primary">Sign In</a>
                        <a href="login.php?tab=signup" class="btn btn-primary">Get Started</a>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </nav>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const profileDropdown = document.getElementById('profileDropdown');
        const profileMenu = document.getElementById('profileMenu');
        
        if (profileDropdown && profileMenu) {
            profileDropdown.addEventListener('click', function(e) {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
            });
            
            document.addEventListener('click', function(e) {
                if (!profileMenu.contains(e.target) && !profileDropdown.contains(e.target)) {
                    profileMenu.classList.remove('show');
                }
            });
        }

        // Update cart count
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

        // Update cart count on page load
        updateCartCount();
    });
    </script>
</body>
</html>
