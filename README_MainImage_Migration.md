# Main Image Migration

## Overview
This migration removes the `main_image` column from the `products` table and uses the `is_main` flag in the `product_images` table instead. This provides better normalization and allows for more flexible image management.

## Changes Made

### 1. Database Schema Changes

#### Products Table
- **Removed:** `main_image` varchar(500) column
- **Reason:** Better normalization - main image is now tracked in product_images table

#### Product Images Table
- **Uses:** `is_main` tinyint(1) flag to mark the main image
- **Benefit:** Only one image per product can be marked as main

#### Product Details View
- **Updated:** Now uses subquery to get main image from product_images table
- **Query:** `(SELECT pi2.image_path FROM product_images pi2 WHERE pi2.product_id = p.id AND pi2.is_main = 1 LIMIT 1) as main_image`

### 2. Code Changes

#### Product.class.php
- **createProduct():** Removed main_image parameter from INSERT query
- **insertProductImages():** Automatically sets first image as main if no main is specified
- **updateMainImage():** Now only updates product_images table, returns image path

#### add.php
- **Image Processing:** Collects all image paths and marks main image
- **Data Structure:** Images array now includes `is_main` flag

#### add-product.js
- **Image Collection:** Gathers all image paths from DOM
- **Main Image:** Uses primary class to identify main image
- **Form Submission:** Sends all images with main image flag

### 3. Migration Process

#### For Existing Databases
1. **Backup:** Create temporary table with existing main_image data
2. **Update:** Set is_main = 1 for existing main images in product_images
3. **Insert:** Create product_images records for orphaned main images
4. **Remove:** Drop main_image column from products table
5. **Update View:** Recreate product_details_view

#### For New Installations
- Use the updated `database_products.sql` file
- No migration needed

## Benefits

### 1. Better Normalization
- Main image is now properly related to product_images table
- Eliminates data duplication

### 2. Improved Flexibility
- Can easily change main image without updating products table
- Better support for image management features

### 3. Consistent Data Structure
- All image data is in one table
- Simpler queries and relationships

## Usage

### Setting Main Image
```javascript
// JavaScript - Click on image to set as main
function setMainImage(imageId) {
    // Remove primary class from all images
    document.querySelectorAll('.image-preview-item').forEach(item => {
        item.classList.remove('primary');
    });
    
    // Add primary class to clicked image
    const clickedImage = document.querySelector(`[data-image-id="${imageId}"]`);
    if (clickedImage) {
        clickedImage.classList.add('primary');
    }
}
```

### Database Query
```sql
-- Get product with main image
SELECT p.*, 
       (SELECT pi.image_path FROM product_images pi 
        WHERE pi.product_id = p.id AND pi.is_main = 1 
        LIMIT 1) as main_image
FROM products p
WHERE p.id = ?;
```

### PHP Code
```php
// Update main image
public function updateMainImage($product_id, $image_id) {
    // Reset all images to not main
    $sql = "UPDATE product_images SET is_main = 0 WHERE product_id = ?";
    
    // Set selected image as main
    $sql = "UPDATE product_images SET is_main = 1 WHERE id = ? AND product_id = ?";
}
```

## Migration Steps

1. **Backup Database**
   ```bash
   mysqldump -u username -p database_name > backup.sql
   ```

2. **Run Migration Script**
   ```bash
   mysql -u username -p database_name < database_migration_remove_main_image.sql
   ```

3. **Update Code Files**
   - Replace old Product.class.php
   - Replace old add.php
   - Replace old add-product.js

4. **Test Functionality**
   - Create new product with images
   - Set main image
   - Verify data in database

## Rollback Plan

If migration fails, you can rollback by:

1. **Restore Database**
   ```bash
   mysql -u username -p database_name < backup.sql
   ```

2. **Revert Code Changes**
   - Restore previous versions of files
   - Keep main_image column in products table

## Notes

- The migration preserves existing main image data
- New products will automatically set first image as main
- The view provides backward compatibility for queries
- All existing functionality remains the same from user perspective 