-- Garment Categories and Subcategories Database Setup
-- This file creates tables for managing garment categories and subcategories

-- Create categories table
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `icon` varchar(100) DEFAULT 'fa-tshirt',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  KEY `is_active` (`is_active`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create subcategories table
CREATE TABLE IF NOT EXISTS `subcategories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text,
  `icon` varchar(100) DEFAULT 'fa-tag',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category_id` (`category_id`),
  KEY `is_active` (`is_active`),
  KEY `sort_order` (`sort_order`),
  CONSTRAINT `fk_subcategories_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample categories
INSERT INTO `categories` (`name`, `description`, `icon`, `sort_order`) VALUES
('Men\'s Clothing', 'Clothing items designed for men', 'fa-male', 1),
('Women\'s Clothing', 'Clothing items designed for women', 'fa-female', 2),
('Kids & Baby', 'Clothing for children and infants', 'fa-child', 3),
('Accessories', 'Fashion accessories and add-ons', 'fa-gem', 4),
('Footwear', 'Shoes, boots, and other footwear', 'fa-shoe-prints', 5),
('Sportswear', 'Athletic and sports clothing', 'fa-running', 6),
('Formal Wear', 'Business and formal attire', 'fa-user-tie', 7),
('Casual Wear', 'Everyday casual clothing', 'fa-tshirt', 8),
('Traditional Wear', 'Cultural and traditional garments', 'fa-star', 9),
('Underwear & Lingerie', 'Intimate apparel', 'fa-heart', 10);

-- Insert sample subcategories for Men's Clothing
INSERT INTO `subcategories` (`category_id`, `name`, `description`, `icon`, `sort_order`) VALUES
(1, 'T-Shirts', 'Casual t-shirts for men', 'fa-tshirt', 1),
(1, 'Shirts', 'Formal and casual shirts', 'fa-user-tie', 2),
(1, 'Jeans', 'Denim jeans and pants', 'fa-socks', 3),
(1, 'Trousers', 'Formal and casual trousers', 'fa-socks', 4),
(1, 'Jackets', 'Casual and formal jackets', 'fa-user', 5),
(1, 'Sweaters', 'Knitwear and sweaters', 'fa-tshirt', 6),
(1, 'Hoodies', 'Casual hooded sweatshirts', 'fa-tshirt', 7),
(1, 'Shorts', 'Casual and sports shorts', 'fa-socks', 8),
(1, 'Suits', 'Formal business suits', 'fa-user-tie', 9),
(1, 'Blazers', 'Formal blazers and jackets', 'fa-user-tie', 10);

-- Insert sample subcategories for Women's Clothing
INSERT INTO `subcategories` (`category_id`, `name`, `description`, `icon`, `sort_order`) VALUES
(2, 'Dresses', 'Casual and formal dresses', 'fa-female', 1),
(2, 'Tops', 'Blouses, shirts, and tops', 'fa-tshirt', 2),
(2, 'Skirts', 'Various styles of skirts', 'fa-female', 3),
(2, 'Pants', 'Trousers and pants', 'fa-socks', 4),
(2, 'Jeans', 'Denim jeans for women', 'fa-socks', 5),
(2, 'Blazers', 'Formal blazers and jackets', 'fa-user-tie', 6),
(2, 'Sweaters', 'Knitwear and sweaters', 'fa-tshirt', 7),
(2, 'Cardigans', 'Casual cardigans', 'fa-tshirt', 8),
(2, 'Jumpsuits', 'One-piece jumpsuits', 'fa-female', 9),
(2, 'Coats', 'Winter and formal coats', 'fa-user', 10);

-- Insert sample subcategories for Kids & Baby
INSERT INTO `subcategories` (`category_id`, `name`, `description`, `icon`, `sort_order`) VALUES
(3, 'Baby Clothes (0-12 months)', 'Clothing for infants', 'fa-baby', 1),
(3, 'Toddler Clothes (1-3 years)', 'Clothing for toddlers', 'fa-child', 2),
(3, 'Kids Clothes (4-8 years)', 'Clothing for young children', 'fa-child', 3),
(3, 'Pre-teen Clothes (9-12 years)', 'Clothing for pre-teens', 'fa-child', 4),
(3, 'Teen Clothes (13-16 years)', 'Clothing for teenagers', 'fa-child', 5),
(3, 'School Uniforms', 'School and educational uniforms', 'fa-graduation-cap', 6),
(3, 'Kids Dresses', 'Dresses for girls', 'fa-female', 7),
(3, 'Kids T-Shirts', 'T-shirts for children', 'fa-tshirt', 8),
(3, 'Kids Jeans', 'Denim for children', 'fa-socks', 9),
(3, 'Kids Sweaters', 'Knitwear for children', 'fa-tshirt', 10);

-- Insert sample subcategories for Accessories
INSERT INTO `subcategories` (`category_id`, `name`, `description`, `icon`, `sort_order`) VALUES
(4, 'Bags', 'Handbags, backpacks, and purses', 'fa-briefcase', 1),
(4, 'Belts', 'Leather and fabric belts', 'fa-circle', 2),
(4, 'Scarves', 'Winter and fashion scarves', 'fa-scarf', 3),
(4, 'Hats', 'Caps, hats, and headwear', 'fa-hat-cowboy', 4),
(4, 'Jewelry', 'Necklaces, bracelets, and rings', 'fa-gem', 5),
(4, 'Watches', 'Fashion and luxury watches', 'fa-clock', 6),
(4, 'Sunglasses', 'Fashion and sports sunglasses', 'fa-glasses', 7),
(4, 'Gloves', 'Winter and fashion gloves', 'fa-hand-paper', 8),
(4, 'Socks', 'Fashion and sports socks', 'fa-socks', 9),
(4, 'Ties', 'Neckties and bow ties', 'fa-user-tie', 10);

-- Insert sample subcategories for Footwear
INSERT INTO `subcategories` (`category_id`, `name`, `description`, `icon`, `sort_order`) VALUES
(5, 'Sneakers', 'Casual and sports sneakers', 'fa-shoe-prints', 1),
(5, 'Formal Shoes', 'Business and formal shoes', 'fa-shoe-prints', 2),
(5, 'Boots', 'Winter and fashion boots', 'fa-shoe-prints', 3),
(5, 'Sandals', 'Casual and formal sandals', 'fa-shoe-prints', 4),
(5, 'Heels', 'Women\'s high heels', 'fa-shoe-prints', 5),
(5, 'Flats', 'Women\'s flat shoes', 'fa-shoe-prints', 6),
(5, 'Loafers', 'Casual loafers', 'fa-shoe-prints', 7),
(5, 'Athletic Shoes', 'Sports and running shoes', 'fa-running', 8),
(5, 'Kids Shoes', 'Footwear for children', 'fa-child', 9),
(5, 'Slippers', 'Indoor and casual slippers', 'fa-shoe-prints', 10);

-- Insert sample subcategories for Sportswear
INSERT INTO `subcategories` (`category_id`, `name`, `description`, `icon`, `sort_order`) VALUES
(6, 'Running Wear', 'Clothing for running and jogging', 'fa-running', 1),
(6, 'Gym Wear', 'Workout and fitness clothing', 'fa-dumbbell', 2),
(6, 'Swimming Wear', 'Swimsuits and swimwear', 'fa-swimming-pool', 3),
(6, 'Yoga Wear', 'Clothing for yoga and pilates', 'fa-pray', 4),
(6, 'Team Sports', 'Jerseys and team uniforms', 'fa-users', 5),
(6, 'Outdoor Sports', 'Hiking and outdoor clothing', 'fa-mountain', 6),
(6, 'Cycling Wear', 'Bicycle and cycling clothing', 'fa-bicycle', 7),
(6, 'Tennis Wear', 'Tennis and racket sports', 'fa-table-tennis', 8),
(6, 'Football Wear', 'Soccer and football gear', 'fa-futbol', 9),
(6, 'Basketball Wear', 'Basketball jerseys and shorts', 'fa-basketball-ball', 10);

-- Insert sample subcategories for Formal Wear
INSERT INTO `subcategories` (`category_id`, `name`, `description`, `icon`, `sort_order`) VALUES
(7, 'Business Suits', 'Professional business suits', 'fa-user-tie', 1),
(7, 'Evening Dresses', 'Formal evening gowns', 'fa-female', 2),
(7, 'Cocktail Dresses', 'Semi-formal cocktail dresses', 'fa-female', 3),
(7, 'Wedding Dresses', 'Bridal gowns and dresses', 'fa-heart', 4),
(7, 'Tuxedos', 'Formal tuxedos and suits', 'fa-user-tie', 5),
(7, 'Business Shirts', 'Professional business shirts', 'fa-user-tie', 6),
(7, 'Formal Skirts', 'Business and formal skirts', 'fa-female', 7),
(7, 'Dress Pants', 'Formal dress pants', 'fa-socks', 8),
(7, 'Blazers', 'Professional blazers', 'fa-user-tie', 9),
(7, 'Formal Accessories', 'Ties, cufflinks, and formal jewelry', 'fa-gem', 10);

-- Insert sample subcategories for Casual Wear
INSERT INTO `subcategories` (`category_id`, `name`, `description`, `icon`, `sort_order`) VALUES
(8, 'Casual T-Shirts', 'Everyday casual t-shirts', 'fa-tshirt', 1),
(8, 'Casual Shirts', 'Relaxed casual shirts', 'fa-tshirt', 2),
(8, 'Casual Dresses', 'Everyday casual dresses', 'fa-female', 3),
(8, 'Casual Pants', 'Comfortable casual pants', 'fa-socks', 4),
(8, 'Casual Shorts', 'Comfortable casual shorts', 'fa-socks', 5),
(8, 'Casual Sweaters', 'Comfortable knitwear', 'fa-tshirt', 6),
(8, 'Casual Jackets', 'Everyday jackets', 'fa-user', 7),
(8, 'Casual Hoodies', 'Comfortable hooded sweatshirts', 'fa-tshirt', 8),
(8, 'Casual Skirts', 'Comfortable casual skirts', 'fa-female', 9),
(8, 'Loungewear', 'Comfortable home wear', 'fa-home', 10);

-- Insert sample subcategories for Traditional Wear
INSERT INTO `subcategories` (`category_id`, `name`, `description`, `icon`, `sort_order`) VALUES
(9, 'Sarees', 'Traditional Indian sarees', 'fa-star', 1),
(9, 'Kurtas', 'Traditional Indian kurtas', 'fa-star', 2),
(9, 'Salwar Kameez', 'Traditional Indian salwar suits', 'fa-star', 3),
(9, 'Lehengas', 'Traditional Indian lehengas', 'fa-star', 4),
(9, 'Sherwanis', 'Traditional Indian sherwanis', 'fa-star', 5),
(9, 'Dhotis', 'Traditional Indian dhotis', 'fa-star', 6),
(9, 'Abayas', 'Traditional Islamic abayas', 'fa-star', 7),
(9, 'Thobes', 'Traditional Middle Eastern thobes', 'fa-star', 8),
(9, 'Kimono', 'Traditional Japanese kimono', 'fa-star', 9),
(9, 'Hanbok', 'Traditional Korean hanbok', 'fa-star', 10);

-- Insert sample subcategories for Underwear & Lingerie
INSERT INTO `subcategories` (`category_id`, `name`, `description`, `icon`, `sort_order`) VALUES
(10, 'Bras', 'Women\'s bras and lingerie', 'fa-heart', 1),
(10, 'Panties', 'Women\'s underwear', 'fa-heart', 2),
(10, 'Men\'s Underwear', 'Men\'s briefs and boxers', 'fa-heart', 3),
(10, 'Shapewear', 'Body shaping undergarments', 'fa-heart', 4),
(10, 'Sleepwear', 'Nightgowns and pajamas', 'fa-bed', 5),
(10, 'Lingerie Sets', 'Matching bra and panty sets', 'fa-heart', 6),
(10, 'Socks', 'Fashion and sports socks', 'fa-socks', 7),
(10, 'Stockings', 'Women\'s stockings and tights', 'fa-heart', 8),
(10, 'Loungewear', 'Comfortable home wear', 'fa-home', 9),
(10, 'Bridal Lingerie', 'Special occasion lingerie', 'fa-heart', 10);

-- Create a view for easy category-subcategory relationships
CREATE OR REPLACE VIEW `category_subcategory_view` AS
SELECT 
    c.id as category_id,
    c.name as category_name,
    c.description as category_description,
    c.icon as category_icon,
    c.is_active as category_active,
    s.id as subcategory_id,
    s.name as subcategory_name,
    s.description as subcategory_description,
    s.icon as subcategory_icon,
    s.is_active as subcategory_active,
    s.sort_order as subcategory_sort_order
FROM categories c
LEFT JOIN subcategories s ON c.id = s.category_id
WHERE c.is_active = 1 AND (s.is_active = 1 OR s.is_active IS NULL)
ORDER BY c.sort_order, s.sort_order;

-- Create indexes for better performance
CREATE INDEX `idx_categories_active_sort` ON `categories` (`is_active`, `sort_order`);
CREATE INDEX `idx_subcategories_category_active_sort` ON `subcategories` (`category_id`, `is_active`, `sort_order`);

-- Add comments for documentation
ALTER TABLE `categories` COMMENT = 'Main garment categories';
ALTER TABLE `subcategories` COMMENT = 'Subcategories within main garment categories'; 