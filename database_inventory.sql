-- Inventory Database Schema
-- This file creates tables for managing product inventory

-- Create inventory table
CREATE TABLE IF NOT EXISTS `inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `size_id` int(11) NOT NULL,
  `color_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `reserved_quantity` int(11) NOT NULL DEFAULT 0,
  `available_quantity` int(11) GENERATED ALWAYS AS (quantity - reserved_quantity) STORED,
  `low_stock_threshold` int(11) DEFAULT 10,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_size_color` (`product_id`, `size_id`, `color_id`),
  KEY `product_id` (`product_id`),
  KEY `size_id` (`size_id`),
  KEY `color_id` (`color_id`),
  KEY `is_active` (`is_active`),
  KEY `available_quantity` (`available_quantity`),
  CONSTRAINT `fk_inventory_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_size` FOREIGN KEY (`size_id`) REFERENCES `product_sizes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inventory_color` FOREIGN KEY (`color_id`) REFERENCES `product_colors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create inventory_log table for tracking inventory changes
CREATE TABLE IF NOT EXISTS `inventory_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inventory_id` int(11) NOT NULL,
  `action` enum('add','remove','reserve','unreserve','adjust') NOT NULL,
  `quantity_change` int(11) NOT NULL,
  `previous_quantity` int(11) NOT NULL,
  `new_quantity` int(11) NOT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_type` enum('manufacturer','admin','system') DEFAULT 'system',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `inventory_id` (`inventory_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_inventory_log_inventory` FOREIGN KEY (`inventory_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better performance
CREATE INDEX `idx_inventory_product_active` ON `inventory` (`product_id`, `is_active`);
CREATE INDEX `idx_inventory_available` ON `inventory` (`available_quantity`);
CREATE INDEX `idx_inventory_low_stock` ON `inventory` (`available_quantity`, `low_stock_threshold`);

-- Create a view for product inventory summary
CREATE OR REPLACE VIEW `product_inventory_summary` AS
SELECT 
    p.id as product_id,
    p.name as product_name,
    p.is_active as product_active,
    COUNT(DISTINCT i.id) as inventory_records,
    COALESCE(SUM(i.available_quantity), 0) as total_available,
    COALESCE(SUM(i.quantity), 0) as total_quantity,
    COALESCE(SUM(i.reserved_quantity), 0) as total_reserved,
    CASE 
        WHEN COUNT(DISTINCT i.id) = 0 THEN 'No Inventory'
        WHEN SUM(i.available_quantity) = 0 THEN 'Out of Stock'
        WHEN SUM(i.available_quantity) <= SUM(i.low_stock_threshold) THEN 'Low Stock'
        ELSE 'In Stock'
    END as stock_status
FROM products p
LEFT JOIN inventory i ON p.id = i.product_id AND i.is_active = 1
GROUP BY p.id, p.name, p.is_active;

-- Add comments for documentation
ALTER TABLE `inventory` COMMENT = 'Product inventory tracking by size and color';
ALTER TABLE `inventory_log` COMMENT = 'Inventory change history and audit trail'; 