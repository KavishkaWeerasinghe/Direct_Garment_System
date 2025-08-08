<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Category.class.php';

class Product {
    private $pdo;
    private $categoryObj;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->categoryObj = new Category($pdo);
    }
    
    // Create new product
    public function createProduct($manufacturer_id, $data) {
        try {
            $this->pdo->beginTransaction();
            
            // Insert main product data
            $sql = "INSERT INTO products (
                manufacturer_id, name, description, category_id, subcategory_id, 
                tags, is_active, created_at, updated_at
            ) VALUES (
                :manufacturer_id, :name, :description, :category_id, :subcategory_id,
                :tags, 1, NOW(), NOW()
            )";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'manufacturer_id' => $manufacturer_id,
                'name' => $data['name'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'subcategory_id' => $data['subcategory_id'],
                'tags' => $data['tags']
            ]);
            
            $product_id = $this->pdo->lastInsertId();
            
            // Insert product images
            if (!empty($data['images'])) {
                $this->insertProductImages($product_id, $data['images']);
            }
            
            // Insert product sizes and prices
            if (!empty($data['sizes'])) {
                $this->insertProductSizes($product_id, $data['sizes']);
            }
            
            // Insert product colors
            if (!empty($data['colors'])) {
                $this->insertProductColors($product_id, $data['colors']);
            }
            
            $this->pdo->commit();
            return $product_id;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Error creating product: " . $e->getMessage());
        }
    }
    
    // Insert product images
    private function insertProductImages($product_id, $images) {
        $sql = "INSERT INTO product_images (product_id, image_path, is_main, sort_order, created_at) VALUES (:product_id, :image_path, :is_main, :sort_order, NOW())";
        $stmt = $this->pdo->prepare($sql);
        
        $has_main_image = false;
        foreach ($images as $index => $image) {
            // Set first image as main if no main image is specified
            $is_main = 0;
            if ($index === 0 && !$has_main_image) {
                $is_main = 1;
                $has_main_image = true;
            } elseif (isset($image['is_main']) && $image['is_main'] && !$has_main_image) {
                $is_main = 1;
                $has_main_image = true;
            }
            
            $stmt->execute([
                'product_id' => $product_id,
                'image_path' => $image['path'],
                'is_main' => $is_main,
                'sort_order' => $index + 1
            ]);
        }
    }
    
    // Insert product sizes and prices
    private function insertProductSizes($product_id, $sizes) {
        $sql = "INSERT INTO product_sizes (product_id, size_name, cost_price, profit_margin, selling_price, is_active, created_at) VALUES (:product_id, :size_name, :cost_price, :profit_margin, :selling_price, 1, NOW())";
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($sizes as $size) {
            $selling_price = $size['cost_price'] + $size['profit_margin'];
            $stmt->execute([
                'product_id' => $product_id,
                'size_name' => $size['name'],
                'cost_price' => $size['cost_price'],
                'profit_margin' => $size['profit_margin'],
                'selling_price' => $selling_price
            ]);
        }
    }
    
    // Insert product colors
    private function insertProductColors($product_id, $colors) {
        $sql = "INSERT INTO product_colors (product_id, color_name, color_code, is_custom, created_at) VALUES (:product_id, :color_name, :color_code, :is_custom, NOW())";
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($colors as $color) {
            $stmt->execute([
                'product_id' => $product_id,
                'color_name' => $color['name'],
                'color_code' => $color['code'],
                'is_custom' => $color['is_custom'] ? 1 : 0
            ]);
        }
    }
    
    // Get product by ID
    public function getProductById($product_id, $manufacturer_id = null) {
        $sql = "SELECT p.*, c.name as category_name, s.name as subcategory_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN subcategories s ON p.subcategory_id = s.id 
                WHERE p.id = :product_id AND p.is_active = 1";
        
        if ($manufacturer_id) {
            $sql .= " AND p.manufacturer_id = :manufacturer_id";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $params = ['product_id' => $product_id];
        if ($manufacturer_id) {
            $params['manufacturer_id'] = $manufacturer_id;
        }
        $stmt->execute($params);
        
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($product) {
            $product['images'] = $this->getProductImages($product_id);
            $product['sizes'] = $this->getProductSizes($product_id);
            $product['colors'] = $this->getProductColors($product_id);
        }
        
        return $product;
    }
    
    // Get product images
    public function getProductImages($product_id) {
        $sql = "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY sort_order";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['product_id' => $product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get product sizes
    public function getProductSizes($product_id) {
        $sql = "SELECT * FROM product_sizes WHERE product_id = :product_id AND is_active = 1 ORDER BY sort_order";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['product_id' => $product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Get product colors
    public function getProductColors($product_id) {
        $sql = "SELECT * FROM product_colors WHERE product_id = :product_id ORDER BY color_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['product_id' => $product_id]);
        $colors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fix incomplete color codes
        foreach ($colors as &$color) {
            $color['color_code'] = $this->fixColorCode($color['color_code'], $color['color_name']);
        }
        
        return $colors;
    }
    
    // Fix incomplete color codes
    private function fixColorCode($colorCode, $colorName) {
        // If color code is already a valid hex, return it
        if (preg_match('/^#[0-9A-Fa-f]{6}$/', $colorCode)) {
            return $colorCode;
        }
        
        // If it's an incomplete rgb format, convert based on color name
        if (strpos($colorCode, 'rgb(') === 0) {
            return $this->getColorCodeByName($colorName);
        }
        
        // Default fallback based on color name
        return $this->getColorCodeByName($colorName);
    }
    
    // Get proper hex color code by color name
    private function getColorCodeByName($colorName) {
        $colorMap = [
            'Red' => '#FF0000',
            'Blue' => '#0000FF',
            'Green' => '#008000',
            'Yellow' => '#FFFF00',
            'Black' => '#000000',
            'White' => '#FFFFFF',
            'Gray' => '#808080',
            'Grey' => '#808080',
            'Brown' => '#A52A2A',
            'Pink' => '#FFC0CB',
            'Purple' => '#800080',
            'Orange' => '#FFA500',
            'Cyan' => '#00FFFF',
            'Magenta' => '#FF00FF',
            'Navy' => '#000080',
            'Maroon' => '#800000',
            'Lime' => '#00FF00',
            'Teal' => '#008080',
            'Aqua' => '#00FFFF',
            'Silver' => '#C0C0C0',
            'Gold' => '#FFD700',
            'Indigo' => '#4B0082',
            'Violet' => '#EE82EE',
            'Coral' => '#FF7F50',
            'Salmon' => '#FA8072',
            'Khaki' => '#F0E68C',
            'Olive' => '#808000',
            'Turquoise' => '#40E0D0',
            'Plum' => '#DDA0DD'
        ];
        
        return $colorMap[strtolower($colorName)] ?? '#808080'; // Default to gray if not found
    }
    
    // Update main image
    public function updateMainImage($product_id, $image_id, $manufacturer_id = null) {
        try {
            $this->pdo->beginTransaction();
            
            // Reset all images to not main
            $sql = "UPDATE product_images SET is_main = 0 WHERE product_id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['product_id' => $product_id]);
            
            // Set selected image as main
            $sql = "UPDATE product_images SET is_main = 1 WHERE id = :image_id AND product_id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['image_id' => $image_id, 'product_id' => $product_id]);
            
            // Get the updated main image path
            $sql = "SELECT image_path FROM product_images WHERE id = :image_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['image_id' => $image_id]);
            $image = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->pdo->commit();
            return $image ? $image['image_path'] : null;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Error updating main image: " . $e->getMessage());
        }
    }
    
    // Delete product image
    public function deleteProductImage($image_id, $manufacturer_id = null) {
        // Get image details
        $sql = "SELECT pi.*, p.manufacturer_id FROM product_images pi 
                JOIN products p ON pi.product_id = p.id 
                WHERE pi.id = :image_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['image_id' => $image_id]);
        $image = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$image) {
            throw new Exception("Image not found");
        }
        
        if ($manufacturer_id && $image['manufacturer_id'] != $manufacturer_id) {
            throw new Exception("Unauthorized access");
        }
        
        // Delete file
        $filepath = __DIR__ . "/../" . $image['image_path'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Delete from database
        $sql = "DELETE FROM product_images WHERE id = :image_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['image_id' => $image_id]);
    }
    
    // Get manufacturer's products
    public function getManufacturerProducts($manufacturer_id, $search = '', $category_id = null, $limit = 20, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name, s.name as subcategory_name,
                       (SELECT pi.image_path FROM product_images pi WHERE pi.product_id = p.id AND pi.is_main = 1 LIMIT 1) as main_image
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN subcategories s ON p.subcategory_id = s.id 
                WHERE p.manufacturer_id = :manufacturer_id AND p.is_active = 1";
        
        $params = ['manufacturer_id' => $manufacturer_id];
        
        if ($search) {
            $sql .= " AND (p.name LIKE :search OR p.description LIKE :search OR p.tags LIKE :search)";
            $params['search'] = '%' . $search . '%';
        }
        
        if ($category_id) {
            $sql .= " AND p.category_id = :category_id";
            $params['category_id'] = $category_id;
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $params['limit'] = $limit;
        $params['offset'] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure main_image is always set
        foreach ($products as &$product) {
            if (!isset($product['main_image']) || $product['main_image'] === null) {
                $product['main_image'] = '';
            }
        }
        
        return $products;
    }
    
    // Get product statistics for manufacturer
    public function getProductStats($manufacturer_id) {
        $stats = [
            'total_products' => 0,
            'new_this_week' => 0,
            'new_this_month' => 0,
            'active_products' => 0
        ];
        
        try {
            // Total products
            $sql = "SELECT COUNT(*) as total FROM products WHERE manufacturer_id = :manufacturer_id AND is_active = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['manufacturer_id' => $manufacturer_id]);
            $stats['total_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            $stats['active_products'] = $stats['total_products'];
            
            // New this week
            $sql = "SELECT COUNT(*) as total FROM products WHERE manufacturer_id = :manufacturer_id AND is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['manufacturer_id' => $manufacturer_id]);
            $stats['new_this_week'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // New this month
            $sql = "SELECT COUNT(*) as total FROM products WHERE manufacturer_id = :manufacturer_id AND is_active = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['manufacturer_id' => $manufacturer_id]);
            $stats['new_this_month'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
        } catch (Exception $e) {
            error_log("Error getting product stats: " . $e->getMessage());
        }
        
        return $stats;
    }
    
    // Get total product count for manufacturer
    public function getProductCount($manufacturer_id) {
        try {
            $sql = "SELECT COUNT(*) as total FROM products WHERE manufacturer_id = :manufacturer_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['manufacturer_id' => $manufacturer_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            error_log("Error getting product count: " . $e->getMessage());
            return 0;
        }
    }
    
    // Get active product count for manufacturer
    public function getActiveProductCount($manufacturer_id) {
        try {
            $sql = "SELECT COUNT(*) as total FROM products WHERE manufacturer_id = :manufacturer_id AND is_active = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['manufacturer_id' => $manufacturer_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            error_log("Error getting active product count: " . $e->getMessage());
            return 0;
        }
    }
    
    // Delete product
    public function deleteProduct($product_id, $manufacturer_id) {
        try {
            $this->pdo->beginTransaction();
            
            // Verify product belongs to manufacturer
            $sql = "SELECT id FROM products WHERE id = :product_id AND manufacturer_id = :manufacturer_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'product_id' => $product_id,
                'manufacturer_id' => $manufacturer_id
            ]);
            
            if (!$stmt->fetch()) {
                throw new Exception("Product not found or access denied");
            }
            
            // Delete product images
            $sql = "DELETE FROM product_images WHERE product_id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['product_id' => $product_id]);
            
            // Delete product sizes
            $sql = "DELETE FROM product_sizes WHERE product_id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['product_id' => $product_id]);
            
            // Delete product colors
            $sql = "DELETE FROM product_colors WHERE product_id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['product_id' => $product_id]);
            
            // Delete inventory records
            $sql = "DELETE FROM inventory WHERE product_id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['product_id' => $product_id]);
            
            // Delete the product
            $sql = "DELETE FROM products WHERE id = :product_id AND manufacturer_id = :manufacturer_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'product_id' => $product_id,
                'manufacturer_id' => $manufacturer_id
            ]);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Error deleting product: " . $e->getMessage());
        }
    }
    
    // Get common colors
    public function getCommonColors() {
        return [
            ['name' => 'Red', 'code' => '#FF0000'],
            ['name' => 'Blue', 'code' => '#0000FF'],
            ['name' => 'Green', 'code' => '#008000'],
            ['name' => 'Yellow', 'code' => '#FFFF00'],
            ['name' => 'Black', 'code' => '#000000'],
            ['name' => 'White', 'code' => '#FFFFFF'],
            ['name' => 'Gray', 'code' => '#808080'],
            ['name' => 'Brown', 'code' => '#A52A2A'],
            ['name' => 'Pink', 'code' => '#FFC0CB'],
            ['name' => 'Purple', 'code' => '#800080'],
            ['name' => 'Orange', 'code' => '#FFA500'],
            ['name' => 'Cyan', 'code' => '#00FFFF'],
            ['name' => 'Magenta', 'code' => '#FF00FF'],
            ['name' => 'Navy', 'code' => '#000080'],
            ['name' => 'Maroon', 'code' => '#800000']
        ];
    }
    
    // Get available colors from database (for filtering)
    public function getAvailableColors() {
        try {
            $sql = "SELECT DISTINCT color_name, color_code FROM product_colors ORDER BY color_name ASC LIMIT 20";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            
            $colors = [];
            while ($row = $stmt->fetch()) {
                $colors[] = [
                    'name' => $row['color_name'],
                    'code' => $this->fixColorCode($row['color_code'], $row['color_name'])
                ];
            }
            
            return $colors;
            
        } catch (Exception $e) {
            error_log("Error getting available colors: " . $e->getMessage());
            return [];
        }
    }
    
    // Get common sizes
    public function getCommonSizes() {
        return [
            'XS', 'S', 'M', 'L', 'XL', 'XXL', 'XXXL',
            '28', '30', '32', '34', '36', '38', '40', '42', '44', '46',
            '2', '4', '6', '8', '10', '12', '14', '16', '18', '20'
        ];
    }
    
    // Search categories for autocomplete
    public function searchCategoriesForAutocomplete($search_term) {
        return $this->categoryObj->searchCategories($search_term);
    }
    
    // Search subcategories for autocomplete
    public function searchSubcategoriesForAutocomplete($search_term, $category_id = null) {
        return $this->categoryObj->searchSubcategories($search_term, $category_id);
    }
    
    // Update product
    public function updateProduct($product_id, $manufacturer_id, $data) {
        try {
            $this->pdo->beginTransaction();
            
            // Update main product data
            $sql = "UPDATE products SET 
                    name = :name, description = :description, category_id = :category_id, 
                    subcategory_id = :subcategory_id, tags = :tags, updated_at = NOW()
                    WHERE id = :product_id AND manufacturer_id = :manufacturer_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'product_id' => $product_id,
                'manufacturer_id' => $manufacturer_id,
                'name' => $data['name'],
                'description' => $data['description'],
                'category_id' => $data['category_id'],
                'subcategory_id' => $data['subcategory_id'],
                'tags' => $data['tags']
            ]);
            
            // Update sizes if provided
            if (isset($data['sizes'])) {
                $this->updateProductSizes($product_id, $data['sizes']);
            }
            
            // Update colors if provided
            if (isset($data['colors'])) {
                $this->updateProductColors($product_id, $data['colors']);
            }
            
            // Update images if provided
            if (isset($data['images'])) {
                $this->updateProductImages($product_id, $data['images']);
            }
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Error updating product: " . $e->getMessage());
        }
    }
    
    // Update product sizes
    private function updateProductSizes($product_id, $sizes) {
        // Delete existing sizes
        $sql = "DELETE FROM product_sizes WHERE product_id = :product_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['product_id' => $product_id]);
        
        // Insert new sizes
        $this->insertProductSizes($product_id, $sizes);
    }
    
    // Update product colors
    private function updateProductColors($product_id, $colors) {
        // Delete existing colors
        $sql = "DELETE FROM product_colors WHERE product_id = :product_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['product_id' => $product_id]);
        
        // Insert new colors
        $this->insertProductColors($product_id, $colors);
    }
    
    // Update product images
    private function updateProductImages($product_id, $images) {
        // Get existing image IDs that should be kept
        $existing_ids = [];
        $new_images = [];
        
        foreach ($images as $image) {
            if (isset($image['id'])) {
                // Existing image - keep it
                $existing_ids[] = $image['id'];
                
                // Update main image flag if needed
                if ($image['is_main']) {
                    $sql = "UPDATE product_images SET is_main = 0 WHERE product_id = :product_id";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute(['product_id' => $product_id]);
                    
                    $sql = "UPDATE product_images SET is_main = 1 WHERE id = :image_id AND product_id = :product_id";
                    $stmt = $this->pdo->prepare($sql);
                    $stmt->execute(['image_id' => $image['id'], 'product_id' => $product_id]);
                }
            } else {
                // New image
                $new_images[] = $image;
            }
        }
        
        // Delete images that are no longer in the list
        if (!empty($existing_ids)) {
            $placeholders = str_repeat('?,', count($existing_ids) - 1) . '?';
            $sql = "DELETE FROM product_images WHERE product_id = ? AND id NOT IN ($placeholders)";
            $params = array_merge([$product_id], $existing_ids);
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        } else {
            // No existing images to keep, delete all
            $sql = "DELETE FROM product_images WHERE product_id = :product_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['product_id' => $product_id]);
        }
        
        // Insert new images
        if (!empty($new_images)) {
            $this->insertProductImages($product_id, $new_images);
        }
    }
}
?> 