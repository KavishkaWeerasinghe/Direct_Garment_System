# Garment Categories and Subcategories System

This system provides a comprehensive category and subcategory management solution for the garment industry, allowing manufacturers to organize their products effectively.

## 📋 **Database Structure**

### **Categories Table**
- **id**: Primary key
- **name**: Category name (unique)
- **description**: Category description
- **icon**: FontAwesome icon class
- **is_active**: Active status (1/0)
- **sort_order**: Display order
- **created_at**: Creation timestamp
- **updated_at**: Last update timestamp

### **Subcategories Table**
- **id**: Primary key
- **category_id**: Foreign key to categories table
- **name**: Subcategory name
- **description**: Subcategory description
- **icon**: FontAwesome icon class
- **is_active**: Active status (1/0)
- **sort_order**: Display order
- **created_at**: Creation timestamp
- **updated_at**: Last update timestamp

## 🎯 **Features**

### **1. Hierarchical Organization**
- One main category can have multiple subcategories
- Categories and subcategories can be sorted independently
- Soft delete functionality (is_active flag)

### **2. Sample Data Included**
The system comes with 10 main categories and 100 subcategories covering:

#### **Main Categories:**
1. **Men's Clothing** (10 subcategories)
   - T-Shirts, Shirts, Jeans, Trousers, Jackets, Sweaters, Hoodies, Shorts, Suits, Blazers

2. **Women's Clothing** (10 subcategories)
   - Dresses, Tops, Skirts, Pants, Jeans, Blazers, Sweaters, Cardigans, Jumpsuits, Coats

3. **Kids & Baby** (10 subcategories)
   - Baby Clothes, Toddler Clothes, Kids Clothes, Pre-teen Clothes, Teen Clothes, School Uniforms, Kids Dresses, Kids T-Shirts, Kids Jeans, Kids Sweaters

4. **Accessories** (10 subcategories)
   - Bags, Belts, Scarves, Hats, Jewelry, Watches, Sunglasses, Gloves, Socks, Ties

5. **Footwear** (10 subcategories)
   - Sneakers, Formal Shoes, Boots, Sandals, Heels, Flats, Loafers, Athletic Shoes, Kids Shoes, Slippers

6. **Sportswear** (10 subcategories)
   - Running Wear, Gym Wear, Swimming Wear, Yoga Wear, Team Sports, Outdoor Sports, Cycling Wear, Tennis Wear, Football Wear, Basketball Wear

7. **Formal Wear** (10 subcategories)
   - Business Suits, Evening Dresses, Cocktail Dresses, Wedding Dresses, Tuxedos, Business Shirts, Formal Skirts, Dress Pants, Blazers, Formal Accessories

8. **Casual Wear** (10 subcategories)
   - Casual T-Shirts, Casual Shirts, Casual Dresses, Casual Pants, Casual Shorts, Casual Sweaters, Casual Jackets, Casual Hoodies, Casual Skirts, Loungewear

9. **Traditional Wear** (10 subcategories)
   - Sarees, Kurtas, Salwar Kameez, Lehengas, Sherwanis, Dhotis, Abayas, Thobes, Kimono, Hanbok

10. **Underwear & Lingerie** (10 subcategories)
    - Bras, Panties, Men's Underwear, Shapewear, Sleepwear, Lingerie Sets, Socks, Stockings, Loungewear, Bridal Lingerie

## 🛠 **Category Class Features**

### **Category Management**
- `getAllCategories()` - Get all active categories
- `getCategoryById($id)` - Get specific category
- `createCategory($name, $description, $icon, $sort_order)` - Create new category
- `updateCategory($id, $name, $description, $icon, $sort_order, $is_active)` - Update category
- `deleteCategory($id)` - Soft delete category

### **Subcategory Management**
- `getSubcategoriesByCategoryId($category_id)` - Get subcategories for a category
- `getSubcategoryById($id)` - Get specific subcategory
- `createSubcategory($category_id, $name, $description, $icon, $sort_order)` - Create new subcategory
- `updateSubcategory($id, $category_id, $name, $description, $icon, $sort_order, $is_active)` - Update subcategory
- `deleteSubcategory($id)` - Soft delete subcategory

### **Advanced Features**
- `getCategoriesWithSubcategories()` - Get hierarchical structure
- `searchCategories($search_term)` - Search categories
- `searchSubcategories($search_term, $category_id)` - Search subcategories
- `getCategoryStats()` - Get category statistics
- `categoryExists($name, $exclude_id)` - Check for duplicate categories
- `subcategoryExists($name, $category_id, $exclude_id)` - Check for duplicate subcategories
- `getCategoriesForDropdown()` - Get categories for dropdown menus
- `getSubcategoriesForDropdown($category_id)` - Get subcategories for dropdown menus

## 📊 **Database Views**

### **category_subcategory_view**
A convenient view that joins categories and subcategories for easy querying:
```sql
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
```

## 🔧 **Setup Instructions**

### **1. Run the SQL Script**
```bash
mysql -u your_username -p your_database < database_categories.sql
```

### **2. Include the Category Class**
```php
require_once 'includes/Category.class.php';
$categoryObj = new Category($pdo);
```

### **3. Basic Usage Examples**

#### **Get all categories with subcategories:**
```php
$categories = $categoryObj->getCategoriesWithSubcategories();
foreach ($categories as $category) {
    echo "Category: " . $category['name'] . "\n";
    foreach ($category['subcategories'] as $subcategory) {
        echo "  - " . $subcategory['name'] . "\n";
    }
}
```

#### **Create a new category:**
```php
$category_id = $categoryObj->createCategory(
    'New Category',
    'Description of new category',
    'fa-star',
    5
);
```

#### **Add subcategory to existing category:**
```php
$subcategory_id = $categoryObj->createSubcategory(
    $category_id,
    'New Subcategory',
    'Description of new subcategory',
    'fa-tag',
    1
);
```

## 🎨 **Icon System**

The system uses FontAwesome icons for visual representation:
- Categories: `fa-male`, `fa-female`, `fa-child`, `fa-gem`, etc.
- Subcategories: `fa-tshirt`, `fa-user-tie`, `fa-socks`, `fa-running`, etc.

## 📈 **Performance Optimizations**

- Indexes on frequently queried columns
- Foreign key constraints for data integrity
- Soft delete functionality to preserve data
- Efficient hierarchical queries

## 🔒 **Data Integrity**

- Foreign key constraints ensure referential integrity
- Unique constraints on category names
- Soft delete prevents data loss
- Proper error handling for duplicate entries

## 🚀 **Future Enhancements**

- Category images and banners
- Category-specific attributes
- Multi-level subcategories (nested)
- Category analytics and reporting
- Bulk import/export functionality
- Category-based pricing rules 