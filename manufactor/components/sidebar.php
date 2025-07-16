<?php
/*
 * Sidebar component for ManufactureHub dashboard
 */
?>
<style>
    #sidebar-wrapper {
        width: 250px;
        min-height: 100vh;
        background-color: #fff;
        border-right: 1px solid #e0e0e0;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .sidebar-heading {
        font-weight: bold;
        font-size: 1.2rem;
        margin-bottom: 30px;
        text-align: center;
    }
    .sidebar-nav .list-group-item {
        border: none;
        margin-bottom: 5px;
        font-weight: 500;
        border-radius: 5px;
        transition: background-color 0.2s ease-in-out;
    }
     .sidebar-nav .list-group-item.active {
         background-color: #2563eb;
         color: #fff;
     }
    .sidebar-nav .list-group-item i {
        margin-right: 10px;
        color: #6c757d;
    }
    .sidebar-nav .list-group-item.active i {
         color: #fff;
    }
     .user-info {
         display: flex;
         flex-direction: column;
         padding-top: 20px;
         border-top: 1px solid #e0e0e0;
     }
     .user-info .profile {
         display: flex;
         align-items: center;
         margin-bottom: 15px;
     }
     .user-info img {
         width: 40px;
         height: 40px;
         border-radius: 50%;
         margin-right: 10px;
     }
     .logout-btn {
         display: flex;
         align-items: center;
         padding: 8px 15px;
         border: none;
         background-color: #f8f9fa;
         color: #dc3545;
         border-radius: 5px;
         font-weight: 500;
         transition: all 0.2s ease;
         text-decoration: none;
         width: 100%;
     }
     .logout-btn:hover {
         background-color: #dc3545;
         color: #fff;
     }
     .logout-btn i {
         margin-right: 8px;
     }
</style>

<div id="sidebar-wrapper">
    <div>
        <div class="sidebar-heading">ManufactureHub</div>
        <div class="list-group list-group-flush sidebar-nav">
            <a href="dashboard.php" class="list-group-item list-group-item-action <?php echo ($active_item == 'dashboard') ? 'active' : ''; ?>"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="product.php" class="list-group-item list-group-item-action <?php echo ($active_item == 'products') ? 'active' : ''; ?>"><i class="fas fa-box"></i> Product</a>
            <a href="inventory.php" class="list-group-item list-group-item-action <?php echo ($active_item == 'inventory') ? 'active' : ''; ?>"><i class="fas fa-warehouse"></i> Inventory</a>
            <a href="orders.php" class="list-group-item list-group-item-action <?php echo ($active_item == 'orders') ? 'active' : ''; ?>"><i class="fas fa-shopping-cart"></i> Orders</a>
            <a href="members.php" class="list-group-item list-group-item-action <?php echo ($active_item == 'members') ? 'active' : ''; ?>"><i class="fas fa-users"></i> Members</a>
            <a href="#" class="list-group-item list-group-item-action <?php echo ($active_item == 'reports') ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="#" class="list-group-item list-group-item-action <?php echo ($active_item == 'settings') ? 'active' : ''; ?>"><i class="fas fa-cogs"></i> Settings</a>
        </div>
    </div>
    <div class="user-info">
        <div class="profile">
            <img src="../src/images/web/aboutUs/profile_1.png" alt="User Avatar">
            <div>
                <h6 class="mb-0"><?php echo $_SESSION['user_name'] ?? 'John Smith'; ?></h6>
                <small class="text-muted"><?php echo $_SESSION['user_role'] ?? 'Admin'; ?></small>
            </div>
        </div>
        <a href="../logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</div> 