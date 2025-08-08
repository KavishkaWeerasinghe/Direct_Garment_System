<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../includes/Manufacturer.class.php';

// Check if user is logged in
if (!Manufacturer::isLoggedIn()) {
    header('Location: ' . MANUFACTURER_BASE_URL . 'login.php');
    exit;
}

// Get current user data
$manufacturer = new Manufacturer();
$user_data = $manufacturer->getCurrentUserData();

if (!$user_data) {
    // If user data not found, logout and redirect to login
    Manufacturer::logout();
    header('Location: ' . MANUFACTURER_BASE_URL . 'login.php');
    exit;
}

// Set user data for display
$user_data['company_name'] = $user_data['company_name'] ?? $user_data['first_name'] . ' ' . $user_data['last_name'];
$user_data['user_role'] = 'Manufacturer';
$user_data['avatar'] = 'fa-user';

require_once __DIR__ . '/../../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manufacturer Panel - Garment Multi-Vendor Platform</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="<?php echo MANUFACTURER_BASE_URL; ?>assets/css/sidebar.css" rel="stylesheet">
    <link href="<?php echo MANUFACTURER_BASE_URL; ?>assets/css/add.css" rel="stylesheet">
    <link href="<?php echo MANUFACTURER_BASE_URL; ?>assets/css/orders.css" rel="stylesheet">
    <link href="<?php echo MANUFACTURER_BASE_URL; ?>assets/css/add-product.css" rel="stylesheet">
    <link href="<?php echo MANUFACTURER_BASE_URL; ?>assets/css/product-list.css" rel="stylesheet">
    <link href="<?php echo MANUFACTURER_BASE_URL; ?>assets/css/inventory.css" rel="stylesheet">
    <link href="<?php echo MANUFACTURER_BASE_URL; ?>assets/css/profile.css" rel="stylesheet">
    <link href="<?php echo MANUFACTURER_BASE_URL; ?>assets/css/reports.css" rel="stylesheet">
</head>
<body>