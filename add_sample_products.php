<?php
require_once __DIR__ . '/config/database.php';

echo "<h1>Add Sample Products</h1>";

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // First, let's add some categories if they don't exist
    $categories = [
        ['name' => 'T-Shirts', 'description' => 'Comfortable t-shirts for all occasions'],
        ['name' => 'Hoodies', 'description' => 'Warm and stylish hoodies'],
        ['name' => 'Caps', 'description' => 'Trendy caps and hats'],
        ['name' => 'Bags', 'description' => 'Custom bags and accessories']
    ];
    
    echo "<h3>Adding Categories...</h3>";
    foreach ($categories as $category) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description, is_active) VALUES (?, ?, 1)");
        $stmt->execute([$category['name'], $category['description']]);
        echo "Added category: " . $category['name'] . "<br>";
    }
    
    // Add subcategories
    $subcategories = [
        ['name' => 'Basic T-Shirts', 'category_name' => 'T-Shirts'],
        ['name' => 'Premium T-Shirts', 'category_name' => 'T-Shirts'],
        ['name' => 'Pullover Hoodies', 'category_name' => 'Hoodies'],
        ['name' => 'Zip-up Hoodies', 'category_name' => 'Hoodies'],
        ['name' => 'Baseball Caps', 'category_name' => 'Caps'],
        ['name' => 'Beanies', 'category_name' => 'Caps'],
        ['name' => 'Tote Bags', 'category_name' => 'Bags'],
        ['name' => 'Backpacks', 'category_name' => 'Bags']
    ];
    
    echo "<h3>Adding Subcategories...</h3>";
    foreach ($subcategories as $subcategory) {
        // Get category ID
        $catStmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $catStmt->execute([$subcategory['category_name']]);
        $categoryId = $catStmt->fetchColumn();
        
        if ($categoryId) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO subcategories (name, category_id, is_active) VALUES (?, ?, 1)");
            $stmt->execute([$subcategory['name'], $categoryId]);
            echo "Added subcategory: " . $subcategory['name'] . "<br>";
        }
    }
    
    // Check if we have a manufacturer (user with role 'Manufacture')
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'Manufacture' LIMIT 1");
    $stmt->execute();
    $manufacturerId = $stmt->fetchColumn();
    
    if (!$manufacturerId) {
        echo "<p style='color: red;'>No manufacturer found. Please create a user with role 'Manufacture' first.</p>";
        $pdo->rollback();
        exit;
    }
    
    // Add sample products
    $products = [
        [
            'name' => 'Custom Cotton T-Shirt',
            'description' => 'High-quality 100% cotton t-shirt perfect for custom printing',
            'category' => 'T-Shirts',
            'subcategory' => 'Basic T-Shirts',
            'tags' => 'cotton, basic, comfortable, custom print',
            'sizes' => [
                ['size' => 'S', 'cost' => 8.00, 'margin' => 50, 'selling' => 12.00],
                ['size' => 'M', 'cost' => 8.50, 'margin' => 50, 'selling' => 12.75],
                ['size' => 'L', 'cost' => 9.00, 'margin' => 50, 'selling' => 13.50],
                ['size' => 'XL', 'cost' => 9.50, 'margin' => 50, 'selling' => 14.25]
            ],
            'colors' => [
                ['name' => 'White', 'code' => '#FFFFFF'],
                ['name' => 'Black', 'code' => '#000000'],
                ['name' => 'Navy Blue', 'code' => '#000080'],
                ['name' => 'Red', 'code' => '#FF0000']
            ]
        ],
        [
            'name' => 'Premium Pullover Hoodie',
            'description' => 'Premium quality hoodie with soft fleece lining',
            'category' => 'Hoodies',
            'subcategory' => 'Pullover Hoodies',
            'tags' => 'hoodie, premium, warm, fleece',
            'sizes' => [
                ['size' => 'S', 'cost' => 20.00, 'margin' => 60, 'selling' => 32.00],
                ['size' => 'M', 'cost' => 21.00, 'margin' => 60, 'selling' => 33.60],
                ['size' => 'L', 'cost' => 22.00, 'margin' => 60, 'selling' => 35.20],
                ['size' => 'XL', 'cost' => 23.00, 'margin' => 60, 'selling' => 36.80]
            ],
            'colors' => [
                ['name' => 'Gray', 'code' => '#808080'],
                ['name' => 'Black', 'code' => '#000000'],
                ['name' => 'Navy', 'code' => '#000080']
            ]
        ],
        [
            'name' => 'Baseball Cap',
            'description' => 'Classic baseball cap with adjustable strap',
            'category' => 'Caps',
            'subcategory' => 'Baseball Caps',
            'tags' => 'cap, baseball, adjustable, classic',
            'sizes' => [
                ['size' => 'One Size', 'cost' => 6.00, 'margin' => 70, 'selling' => 10.20]
            ],
            'colors' => [
                ['name' => 'Black', 'code' => '#000000'],
                ['name' => 'White', 'code' => '#FFFFFF'],
                ['name' => 'Red', 'code' => '#FF0000'],
                ['name' => 'Blue', 'code' => '#0000FF']
            ]
        ],
        [
            'name' => 'Canvas Tote Bag',
            'description' => 'Eco-friendly canvas tote bag perfect for shopping',
            'category' => 'Bags',
            'subcategory' => 'Tote Bags',
            'tags' => 'tote, canvas, eco-friendly, shopping',
            'sizes' => [
                ['size' => 'Standard', 'cost' => 4.00, 'margin' => 75, 'selling' => 7.00]
            ],
            'colors' => [
                ['name' => 'Natural', 'code' => '#F5F5DC'],
                ['name' => 'Black', 'code' => '#000000'],
                ['name' => 'Navy', 'code' => '#000080']
            ]
        ]
    ];
    
    echo "<h3>Adding Products...</h3>";
    foreach ($products as $product) {
        // Get category and subcategory IDs
        $catStmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $catStmt->execute([$product['category']]);
        $categoryId = $catStmt->fetchColumn();
        
        $subStmt = $pdo->prepare("SELECT id FROM subcategories WHERE name = ?");
        $subStmt->execute([$product['subcategory']]);
        $subcategoryId = $subStmt->fetchColumn();
        
        if ($categoryId && $subcategoryId) {
            // Insert product
            $prodStmt = $pdo->prepare("INSERT INTO products (manufacturer_id, name, description, category_id, subcategory_id, tags, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
            $prodStmt->execute([$manufacturerId, $product['name'], $product['description'], $categoryId, $subcategoryId, $product['tags']]);
            $productId = $pdo->lastInsertId();
            
            echo "Added product: " . $product['name'] . " (ID: $productId)<br>";
            
            // Add product image (placeholder)
            $imgStmt = $pdo->prepare("INSERT INTO product_images (product_id, image_path, is_main, sort_order) VALUES (?, ?, 1, 0)");
            $imgStmt->execute([$productId, 'src/images/products/placeholder-' . strtolower(str_replace(' ', '-', $product['name'])) . '.jpg']);
            
            // Add sizes
            foreach ($product['sizes'] as $size) {
                $sizeStmt = $pdo->prepare("INSERT INTO product_sizes (product_id, size_name, cost_price, profit_margin, selling_price, is_active) VALUES (?, ?, ?, ?, ?, 1)");
                $sizeStmt->execute([$productId, $size['size'], $size['cost'], $size['margin'], $size['selling']]);
            }
            
            // Add colors
            foreach ($product['colors'] as $color) {
                $colorStmt = $pdo->prepare("INSERT INTO product_colors (product_id, color_name, color_code) VALUES (?, ?, ?)");
                $colorStmt->execute([$productId, $color['name'], $color['code']]);
            }
        }
    }
    
    // Commit transaction
    $pdo->commit();
    
    echo "<h3 style='color: green;'>✓ Sample products added successfully!</h3>";
    echo "<p><a href='product.php'>View Products Page</a></p>";
    
} catch (Exception $e) {
    $pdo->rollback();
    echo "<h3 style='color: red;'>Error: " . $e->getMessage() . "</h3>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1, h3 { color: #333; }
</style>

