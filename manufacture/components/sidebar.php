<?php
// Include config for base URL
require_once __DIR__ . '/../../config/config.php';

// Use the user_data from header.php (already authenticated)
// The user_data variable should be available from header.php

// Navigation menu structure with sub-menus
$navigation_menu = [
    'dashboard' => [
        'title' => 'Dashboard',
        'url' => MANUFACTURER_BASE_URL . 'dashboard.php',
        'items' => [],
    ],
    'orders' => [
        'title' => 'Orders',
        'url' => MANUFACTURER_BASE_URL . 'orders.php',
        'items' => []
    ],
    'products' => [
        'title' => 'Product Catalog',
        'items' => [
            'my_products' => ['icon' => 'fa-tshirt', 'label' => 'My Products', 'url' => MANUFACTURER_BASE_URL . 'products/list.php', 'badge' => ''],
            'add_product' => ['icon' => 'fa-plus-circle', 'label' => 'Add New Product', 'url' => MANUFACTURER_BASE_URL . 'products/add.php', 'badge' => ''],
            'inventory' => ['icon' => 'fa-warehouse', 'label' => 'Inventory', 'url' => MANUFACTURER_BASE_URL . 'products/inventory.php', 'badge' => '']
        ]
    ],
    'members' => [
        'title' => 'Members',
        'items' => [
            'team_members' => ['icon' => 'fa-users', 'label' => 'Team Members', 'url' => MANUFACTURER_BASE_URL . 'members/team.php', 'badge' => ''],
            'add_member' => ['icon' => 'fa-user-plus', 'label' => 'Add Member', 'url' => MANUFACTURER_BASE_URL . 'members/add.php', 'badge' => ''],
            'roles' => ['icon' => 'fa-user-tag', 'label' => 'Roles & Permissions', 'url' => MANUFACTURER_BASE_URL . 'members/roles.php', 'badge' => ''],
            'activity' => ['icon' => 'fa-chart-line', 'label' => 'Member Activity', 'url' => MANUFACTURER_BASE_URL . 'members/activity.php', 'badge' => '']
        ]
    ],
    'reports' => [
        'title' => 'Reports',
        'url' => MANUFACTURER_BASE_URL . 'reports.php',
        'items' => []
    ],
    'settings' => [
        'title' => 'Settings',
        'items' => [
            'profile' => ['icon' => 'fa-user-cog', 'label' => 'Profile Settings', 'url' => MANUFACTURER_BASE_URL . 'settings/profile.php', 'badge' => ''],
            'company' => ['icon' => 'fa-building', 'label' => 'Company Info', 'url' => MANUFACTURER_BASE_URL . 'settings/company.php', 'badge' => '']    
        ]
    ]
];

// Function to render navigation items
function renderNavigationItem($key, $item, $parent_key = '')
{
    $active_class = isset($item['active']) && $item['active'] ? 'active' : '';
    $badge_class = isset($item['badge_class']) ? $item['badge_class'] : '';
    $badge_html = '';

    if (!empty($item['badge'])) {
        $badge_html = "<span class='badge {$badge_class}'>{$item['badge']}</span>";
    }

    $item_id = $parent_key ? "{$parent_key}_{$key}" : $key;

    return "
        <a href='{$item['url']}' class='nav-item {$active_class}' data-item='{$item_id}'>
            <i class='fas {$item['icon']}'></i>
            <span>{$item['label']}</span>
            {$badge_html}
        </a>
    ";
}

// Function to render sub-navigation
function renderSubNavigation($items, $parent_key)
{
    $html = "<div class='submenu' id='submenu_{$parent_key}' style='display: none;'>";
    foreach ($items as $key => $item) {
        $badge_class = isset($item['badge_class']) ? $item['badge_class'] : '';
        $badge_html = '';

        if (!empty($item['badge'])) {
            $badge_html = "<span class='badge {$badge_class}'>{$item['badge']}</span>";
        }

        $html .= "
            <a href='{$item['url']}' class='submenu-item' data-item='{$parent_key}_{$key}'>
                <i class='fas {$item['icon']}'></i>
                <span>{$item['label']}</span>
                {$badge_html}
            </a>
        ";
    }
    $html .= "</div>";
    return $html;
}
?>

<button class="toggle-btn" id="sidebarToggle">
    <i class="fas fa-chevron-left collapse-icon"></i>
    <i class="fas fa-chevron-right expand-icon" style="display: none;"></i>
</button>
<nav class="sidebar">
    <div class="logo-section">
        <div class="logo">
            <i class="fas fa-industry"></i>
            <h2 class="logo-text">ManufactureHub</h2>
        </div>
    </div>
    <div class="user-info">
        <div class="user-avatar">
            <i class="fas <?php echo $user_data['avatar']; ?>"></i>
        </div>
        <div class="user-name"><?php echo htmlspecialchars($user_data['company_name']); ?></div>
        <div class="user-role"><?php echo htmlspecialchars($user_data['user_role']); ?></div>
    </div>
    <div class="nav-menu">
        <?php foreach ($navigation_menu as $section_key => $section): ?>
            <div class="nav-section">
                <?php if (empty($section['items'])): ?>
                    <!-- Single item without submenu -->
                    <a href="<?php echo $section['url']; ?>" class="nav-item">
                        <i class="fas fa-<?php echo $section_key === 'dashboard' ? 'tachometer-alt' : ($section_key === 'orders' ? 'shopping-cart' : ($section_key === 'reports' ? 'chart-line' : '')); ?>"></i>
                        <span><?php echo $section['title']; ?></span>
                    </a>
                <?php else: ?>
                    <!-- Multiple items with submenu -->
                    <div class="nav-item-with-submenu">
                        <a href="#" class="nav-item has-submenu" data-section="<?php echo $section_key; ?>">
                            <i class="fas fa-<?php echo $section_key === 'products' ? 'tshirt' : ($section_key === 'members' ? 'users' : ($section_key === 'finance' ? 'dollar-sign' : ($section_key === 'logistics' ? 'shipping-fast' : 'cog'))); ?>"></i>
                            <span><?php echo $section['title']; ?></span>
                        </a>
                        <?php echo renderSubNavigation($section['items'], $section_key); ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="logout-section">
        <a href="<?php echo MANUFACTURER_BASE_URL; ?>logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</nav>