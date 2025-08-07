<?php
require_once __DIR__ . '/../../config/database.php';

class Inventory {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Get detailed inventory for a product
    public function getProductInventory($product_id) {
        $sql = "SELECT 
                    i.*,
                    ps.size_name,
                    pc.color_name,
                    pc.color_code
                FROM inventory i
                JOIN product_sizes ps ON i.size_id = ps.id
                JOIN product_colors pc ON i.color_id = pc.id
                WHERE i.product_id = :product_id AND i.is_active = 1
                ORDER BY ps.size_name, pc.color_name";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['product_id' => $product_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Add inventory for a product
    public function addInventory($product_id, $size_id, $color_id, $quantity, $low_stock_threshold = 10) {
        try {
            $this->pdo->beginTransaction();
            
            // Check if inventory record exists
            $sql = "SELECT id, quantity FROM inventory WHERE product_id = :product_id AND size_id = :size_id AND color_id = :color_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'product_id' => $product_id,
                'size_id' => $size_id,
                'color_id' => $color_id
            ]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Update existing inventory
                $new_quantity = $existing['quantity'] + $quantity;
                $sql = "UPDATE inventory SET quantity = :quantity, updated_at = NOW() WHERE id = :id";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'quantity' => $new_quantity,
                    'id' => $existing['id']
                ]);
                
                $inventory_id = $existing['id'];
                $previous_quantity = $existing['quantity'];
            } else {
                // Create new inventory record
                $sql = "INSERT INTO inventory (product_id, size_id, color_id, quantity, low_stock_threshold, created_at, updated_at) 
                        VALUES (:product_id, :size_id, :color_id, :quantity, :low_stock_threshold, NOW(), NOW())";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([
                    'product_id' => $product_id,
                    'size_id' => $size_id,
                    'color_id' => $color_id,
                    'quantity' => $quantity,
                    'low_stock_threshold' => $low_stock_threshold
                ]);
                
                $inventory_id = $this->pdo->lastInsertId();
                $previous_quantity = 0;
            }
            
            // Log the inventory change
            $this->logInventoryChange($inventory_id, 'add', $quantity, $previous_quantity, $previous_quantity + $quantity, 'Manual inventory addition');
            
            $this->pdo->commit();
            return $inventory_id;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Error adding inventory: " . $e->getMessage());
        }
    }
    
    // Update inventory quantity
    public function updateInventory($inventory_id, $new_quantity, $reason = 'Manual update') {
        try {
            $this->pdo->beginTransaction();
            
            // Get current inventory
            $sql = "SELECT quantity FROM inventory WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['id' => $inventory_id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$current) {
                throw new Exception("Inventory record not found");
            }
            
            $previous_quantity = $current['quantity'];
            $quantity_change = $new_quantity - $previous_quantity;
            
            // Update inventory
            $sql = "UPDATE inventory SET quantity = :quantity, updated_at = NOW() WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'quantity' => $new_quantity,
                'id' => $inventory_id
            ]);
            
            // Log the change
            $this->logInventoryChange($inventory_id, 'adjust', $quantity_change, $previous_quantity, $new_quantity, $reason);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Error updating inventory: " . $e->getMessage());
        }
    }
    
    // Log inventory changes
    private function logInventoryChange($inventory_id, $action, $quantity_change, $previous_quantity, $new_quantity, $reason) {
        $sql = "INSERT INTO inventory_log (inventory_id, action, quantity_change, previous_quantity, new_quantity, reason, user_type, created_at) 
                VALUES (:inventory_id, :action, :quantity_change, :previous_quantity, :new_quantity, :reason, 'manufacturer', NOW())";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'inventory_id' => $inventory_id,
            'action' => $action,
            'quantity_change' => $quantity_change,
            'previous_quantity' => $previous_quantity,
            'new_quantity' => $new_quantity,
            'reason' => $reason
        ]);
    }
    
    // Get inventory statistics for manufacturer
    public function getInventoryStats($manufacturer_id) {
        // Simplified query to avoid complex GROUP BY issues
        $sql = "SELECT 
                    COUNT(DISTINCT p.id) as total_products,
                    0 as in_stock_products,
                    0 as low_stock_products,
                    0 as out_of_stock_products,
                    COUNT(DISTINCT p.id) as no_inventory_products,
                    0 as total_available_quantity
                FROM products p
                WHERE p.manufacturer_id = :manufacturer_id AND p.is_active = 1";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['manufacturer_id' => $manufacturer_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If no results, return default values
            if (!$result) {
                return [
                    'total_products' => 0,
                    'in_stock_products' => 0,
                    'low_stock_products' => 0,
                    'out_of_stock_products' => 0,
                    'no_inventory_products' => 0,
                    'total_available_quantity' => 0
                ];
            }
            
            return $result;
        } catch (Exception $e) {
            error_log("Error in getInventoryStats: " . $e->getMessage());
            return [
                'total_products' => 0,
                'in_stock_products' => 0,
                'low_stock_products' => 0,
                'out_of_stock_products' => 0,
                'no_inventory_products' => 0,
                'total_available_quantity' => 0
            ];
        }
    }
    
    // Reserve inventory stock
    public function reserveInventory($inventory_id, $reserve_quantity, $reason = 'Manual reservation') {
        try {
            $this->pdo->beginTransaction();
            
            // Get current inventory
            $sql = "SELECT * FROM inventory WHERE id = :inventory_id AND is_active = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['inventory_id' => $inventory_id]);
            $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$inventory) {
                throw new Exception("Inventory record not found");
            }
            
            $current_reserved = $inventory['reserved_quantity'];
            $available_quantity = $inventory['quantity'] - $current_reserved;
            
            // Check if we can reserve the requested quantity
            if ($reserve_quantity > $available_quantity) {
                throw new Exception("Cannot reserve more than available quantity. Available: $available_quantity, Requested: $reserve_quantity");
            }
            
            $new_reserved = $current_reserved + $reserve_quantity;
            $new_available = $inventory['quantity'] - $new_reserved;
            
            // Update inventory
            $sql = "UPDATE inventory SET 
                    reserved_quantity = :reserved_quantity,
                    available_quantity = :available_quantity,
                    updated_at = NOW()
                    WHERE id = :inventory_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'reserved_quantity' => $new_reserved,
                'available_quantity' => $new_available,
                'inventory_id' => $inventory_id
            ]);
            
            // Log the reservation
            $this->logInventoryChange($inventory_id, 'reserve', $reserve_quantity, $current_reserved, $new_reserved, $reason);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Error reserving inventory: " . $e->getMessage());
        }
    }
    
    // Release reserved inventory
    public function releaseReservedInventory($inventory_id, $release_quantity, $reason = 'Manual release') {
        try {
            $this->pdo->beginTransaction();
            
            // Get current inventory
            $sql = "SELECT * FROM inventory WHERE id = :inventory_id AND is_active = 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['inventory_id' => $inventory_id]);
            $inventory = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$inventory) {
                throw new Exception("Inventory record not found");
            }
            
            $current_reserved = $inventory['reserved_quantity'];
            
            // Check if we can release the requested quantity
            if ($release_quantity > $current_reserved) {
                throw new Exception("Cannot release more than reserved quantity. Reserved: $current_reserved, Requested: $release_quantity");
            }
            
            $new_reserved = $current_reserved - $release_quantity;
            $new_available = $inventory['quantity'] - $new_reserved;
            
            // Update inventory
            $sql = "UPDATE inventory SET 
                    reserved_quantity = :reserved_quantity,
                    available_quantity = :available_quantity,
                    updated_at = NOW()
                    WHERE id = :inventory_id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'reserved_quantity' => $new_reserved,
                'available_quantity' => $new_available,
                'inventory_id' => $inventory_id
            ]);
            
            // Log the release
            $this->logInventoryChange($inventory_id, 'release', -$release_quantity, $current_reserved, $new_reserved, $reason);
            
            $this->pdo->commit();
            return true;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw new Exception("Error releasing inventory: " . $e->getMessage());
        }
    }
}
?> 