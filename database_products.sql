-- Products Database Schema
-- This file creates tables for managing products, images, sizes, and colors

-- Create products table
CREATE TABLE IF NOT EXISTS `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `manufacturer_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `category_id` int(11) NOT NULL,
  `subcategory_id` int(11) NOT NULL,
  `tags` text,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `manufacturer_id` (`manufacturer_id`),
  KEY `category_id` (`category_id`),
  KEY `subcategory_id` (`subcategory_id`),
  KEY `is_active` (`is_active`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `fk_products_manufacturer` FOREIGN KEY (`manufacturer_id`) REFERENCES `manufacturers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_products_subcategory` FOREIGN KEY (`subcategory_id`) REFERENCES `subcategories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create product_images table
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(500) NOT NULL,
  `is_main` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `is_main` (`is_main`),
  KEY `sort_order` (`sort_order`),
  CONSTRAINT `fk_product_images_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create product_sizes table
CREATE TABLE IF NOT EXISTS `product_sizes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `size_name` varchar(50) NOT NULL,
  `cost_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `profit_margin` decimal(10,2) NOT NULL DEFAULT 0.00,
  `selling_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `is_active` (`is_active`),
  KEY `sort_order` (`sort_order`),
  CONSTRAINT `fk_product_sizes_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create product_colors table
CREATE TABLE IF NOT EXISTS `product_colors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `color_name` varchar(100) NOT NULL,
  `color_code` varchar(7) NOT NULL,
  `is_custom` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `is_custom` (`is_custom`),
  CONSTRAINT `fk_product_colors_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create indexes for better performance
CREATE INDEX `idx_products_manufacturer_active` ON `products` (`manufacturer_id`, `is_active`);
CREATE INDEX `idx_products_category_active` ON `products` (`category_id`, `is_active`);
CREATE INDEX `idx_products_created_at` ON `products` (`created_at`);
CREATE INDEX `idx_product_images_product_main` ON `product_images` (`product_id`, `is_main`);
CREATE INDEX `idx_product_sizes_product_active` ON `product_sizes` (`product_id`, `is_active`);
CREATE INDEX `idx_product_colors_product` ON `product_colors` (`product_id`);

-- Create a view for product details with category information
CREATE OR REPLACE VIEW `product_details_view` AS
SELECT 
    p.id,
    p.manufacturer_id,
    p.name,
    p.description,
    p.category_id,
    c.name as category_name,
    p.subcategory_id,
    s.name as subcategory_name,
    p.tags,
    p.is_active,
    p.created_at,
    p.updated_at,
    COUNT(DISTINCT pi.id) as image_count,
    COUNT(DISTINCT ps.id) as size_count,
    COUNT(DISTINCT pc.id) as color_count,
    (SELECT pi2.image_path FROM product_images pi2 WHERE pi2.product_id = p.id AND pi2.is_main = 1 LIMIT 1) as main_image
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
LEFT JOIN subcategories s ON p.subcategory_id = s.id
LEFT JOIN product_images pi ON p.id = pi.product_id
LEFT JOIN product_sizes ps ON p.id = ps.product_id AND ps.is_active = 1
LEFT JOIN product_colors pc ON p.id = pc.product_id
GROUP BY p.id, p.manufacturer_id, p.name, p.description, p.category_id, c.name, p.subcategory_id, s.name, p.tags, p.is_active, p.created_at, p.updated_at;

-- Add comments for documentation
ALTER TABLE `products` COMMENT = 'Main products table';
ALTER TABLE `product_images` COMMENT = 'Product images with main image flag';
ALTER TABLE `product_sizes` COMMENT = 'Product sizes with pricing information';
ALTER TABLE `product_colors` COMMENT = 'Product colors including custom colors'; 