-- Migration Script: Remove main_image from products table
-- This script migrates existing data to use product_images.is_main instead

-- Step 1: Create a backup of the main_image data
CREATE TEMPORARY TABLE temp_main_images AS
SELECT id, main_image FROM products WHERE main_image IS NOT NULL AND main_image != '';

-- Step 2: Update product_images table to set is_main = 1 for existing main images
UPDATE product_images pi
JOIN temp_main_images tmi ON pi.product_id = tmi.id
SET pi.is_main = 1
WHERE pi.image_path = tmi.main_image;

-- Step 3: For products that have main_image but no matching product_images record,
-- we need to create a product_images record
INSERT INTO product_images (product_id, image_path, is_main, sort_order, created_at)
SELECT 
    tmi.id as product_id,
    tmi.main_image as image_path,
    1 as is_main,
    1 as sort_order,
    NOW() as created_at
FROM temp_main_images tmi
WHERE NOT EXISTS (
    SELECT 1 FROM product_images pi 
    WHERE pi.product_id = tmi.id AND pi.image_path = tmi.main_image
);

-- Step 4: Remove the main_image column from products table
ALTER TABLE products DROP COLUMN main_image;

-- Step 5: Clean up temporary table
DROP TEMPORARY TABLE temp_main_images;

-- Step 6: Update the product_details_view to use the new structure
DROP VIEW IF EXISTS product_details_view;

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