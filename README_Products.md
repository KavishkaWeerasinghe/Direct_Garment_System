# Product Management System

This system provides a comprehensive product management solution for manufacturers in the garment industry, allowing them to create, manage, and organize their products effectively.

## 📋 **Database Structure**

### **Products Table**
- **id**: Primary key
- **manufacturer_id**: Foreign key to manufacturers table
- **name**: Product name
- **description**: Product description
- **category_id**: Foreign key to categories table
- **subcategory_id**: Foreign key to subcategories table
- **main_image**: Path to main product image
- **tags**: Product tags (JSON or comma-separated)
- **is_active**: Active status (1/0)
- **created_at**: Creation timestamp
- **updated_at**: Last update timestamp

### **Product Images Table**
- **id**: Primary key
- **product_id**: Foreign key to products table
- **image_path**: Path to image file
- **is_main**: Main image flag (1/0)
- **sort_order**: Display order
- **created_at**: Creation timestamp

### **Product Sizes Table**
- **id**: Primary key
- **product_id**: Foreign key to products table
- **size_name**: Size name (XS, S, M, L, XL, etc.)
- **cost_price**: Cost price
- **profit_margin**: Profit margin
- **selling_price**: Calculated selling price
- **is_active**: Active status (1/0)
- **sort_order**: Display order
- **created_at**: Creation timestamp
- **updated_at**: Last update timestamp

### **Product Colors Table**
- **id**: Primary key
- **product_id**: Foreign key to products table
- **color_name**: Color name
- **color_code**: Hex color code
- **is_custom**: Custom color flag (1/0)
- **created_at**: Creation timestamp

## 🎯 **Features**

### **1. Product Details Management**
- Product name and description
- Rich text description support
- Product tags with YouTube-style tag input
- Category and subcategory selection with search

### **2. Image Management**
- Multiple image upload support
- Drag and drop functionality
- Main image selection with visual indicator
- Image preview with actions (set main, delete)
- Organized folder structure: `manufacturer_id/product_id/`

### **3. Category System Integration**
- Search-based category selection
- Real-time category search with autocomplete
- Subcategory selection based on main category
- Visual category tags with remove functionality

### **4. Size and Pricing System**
- Predefined common sizes (XS, S, M, L, XL, XXL, numeric sizes)
- Individual cost price and profit margin for each size
- Automatic selling price calculation
- Checkbox-based size selection

### **5. Color Management**
- Predefined common colors (Red, Blue, Green, etc.)
- Custom color picker with name input
- Visual color preview
- Multiple color selection support

### **6. Advanced Features**
- Draft saving functionality
- Form validation
- Responsive design
- Modern UI with animations
- File upload progress tracking

## 🛠 **Product Class Features**

### **Product Management**
- `createProduct($manufacturer_id, $data)` - Create new product
- `getProductById($product_id, $manufacturer_id)` - Get specific product
- `updateProduct($product_id, $manufacturer_id, $data)` - Update product
- `deleteProduct($product_id, $manufacturer_id)` - Soft delete product
- `getManufacturerProducts($manufacturer_id, $search, $category_id, $limit, $offset)` - Get manufacturer's products

### **Image Management**
- `uploadProductImage($manufacturer_id, $product_id, $file)` - Upload product image
- `updateMainImage($product_id, $image_id, $manufacturer_id)` - Set main image
- `deleteProductImage($image_id, $manufacturer_id)` - Delete image
- `getProductImages($product_id)` - Get product images

### **Size and Price Management**
- `insertProductSizes($product_id, $sizes)` - Add product sizes
- `updateProductSizes($product_id, $sizes)` - Update product sizes
- `getProductSizes($product_id)` - Get product sizes

### **Color Management**
- `insertProductColors($product_id, $colors)` - Add product colors
- `updateProductColors($product_id, $colors)` - Update product colors
- `getProductColors($product_id)` - Get product colors
- `getCommonColors()` - Get predefined colors

### **Utility Functions**
- `getCommonSizes()` - Get predefined sizes
- `searchCategoriesForAutocomplete($search_term)` - Search categories
- `searchSubcategoriesForAutocomplete($search_term, $category_id)` - Search subcategories
- `getProductStats($manufacturer_id)` - Get product statistics

## 📁 **File Structure**

```
manufacture/
├── products/
│   ├── add.php                    # Product add page
│   ├── list.php                   # Product listing page
│   ├── edit.php                   # Product edit page
│   ├── save_product.php           # Product save handler
│   ├── upload_images.php          # Image upload handler
│   ├── update_main_image.php      # Main image update handler
│   ├── delete_image.php           # Image delete handler
│   ├── search_categories.php      # Category search API
│   ├── search_subcategories.php   # Subcategory search API
│   └── uploads/                   # Product images storage
│       └── manufacturer_{id}/
│           └── product_{id}/
├── includes/
│   ├── Product.class.php          # Product management class
│   └── Category.class.php         # Category management class
├── assets/
│   ├── css/
│   │   └── add-product.css        # Product add page styles
│   └── js/
│       └── add-product.js         # Product add page JavaScript
└── components/
    ├── header.php                 # Common header
    ├── footer.php                 # Common footer
    └── sidebar.php                # Navigation sidebar
```

## 🔧 **Setup Instructions**

### **1. Run Database Scripts**
```bash
# First run categories setup
mysql -u your_username -p your_database < database_categories.sql

# Then run products setup
mysql -u your_username -p your_database < database_products.sql
```

### **2. Create Upload Directories**
```bash
mkdir -p manufacture/products/uploads
chmod 755 manufacture/products/uploads
```

### **3. Include Product Class**
```php
require_once 'includes/Product.class.php';
$productObj = new Product($pdo);
```

### **4. Basic Usage Examples**

#### **Create a new product:**
```php
$productData = [
    'name' => 'Premium Cotton T-Shirt',
    'description' => 'High-quality cotton t-shirt with modern design',
    'category_id' => 1,
    'subcategory_id' => 1,
    'main_image' => 'path/to/main/image.jpg',
    'tags' => 'cotton, premium, t-shirt, casual',
    'images' => [
        ['path' => 'path/to/image1.jpg', 'is_main' => true],
        ['path' => 'path/to/image2.jpg', 'is_main' => false]
    ],
    'sizes' => [
        ['name' => 'S', 'cost_price' => 15.00, 'profit_margin' => 10.00],
        ['name' => 'M', 'cost_price' => 15.00, 'profit_margin' => 10.00],
        ['name' => 'L', 'cost_price' => 15.00, 'profit_margin' => 10.00]
    ],
    'colors' => [
        ['name' => 'Black', 'code' => '#000000', 'is_custom' => false],
        ['name' => 'White', 'code' => '#FFFFFF', 'is_custom' => false]
    ]
];

$product_id = $productObj->createProduct($manufacturer_id, $productData);
```

#### **Upload product image:**
```php
$image_id = $productObj->uploadProductImage($manufacturer_id, $product_id, $_FILES['image']);
```

#### **Get product with all details:**
```php
$product = $productObj->getProductById($product_id, $manufacturer_id);
```

## 🎨 **UI Features**

### **Modern Design**
- Gradient backgrounds and modern color scheme
- Smooth animations and transitions
- Responsive design for all devices
- Card-based layout with shadows

### **Interactive Elements**
- Drag and drop image upload
- Real-time search with autocomplete
- Visual color picker
- YouTube-style tag input
- Dynamic form validation

### **User Experience**
- Progress indicators
- Success/error notifications
- Confirmation dialogs
- Auto-save functionality
- Draft saving option

## 📱 **Responsive Design**

The system is fully responsive and works on:
- **Desktop**: Full feature set with sidebar navigation
- **Tablet**: Optimized layout with collapsible sidebar
- **Mobile**: Single-column layout with touch-friendly controls

## 🔒 **Security Features**

- File type validation for images
- File size limits
- Secure file upload handling
- Manufacturer-specific access control
- SQL injection prevention with prepared statements
- XSS protection with proper output escaping

## 🚀 **Future Enhancements**

- **Bulk Operations**: Import/export products
- **Advanced Search**: Filter by multiple criteria
- **Product Variants**: Size/color combinations
- **Inventory Management**: Stock tracking
- **Product Analytics**: Views, sales tracking
- **SEO Optimization**: Meta tags, URL slugs
- **Product Reviews**: Customer feedback system
- **Integration**: E-commerce platform integration
- **API**: RESTful API for external access
- **Multi-language**: Internationalization support

## 📊 **Performance Optimizations**

- Database indexes on frequently queried columns
- Image compression and optimization
- Lazy loading for product images
- Caching for category and color data
- Efficient file storage structure
- AJAX for dynamic content loading

## 🛠 **Development Notes**

### **File Upload Structure**
```
manufacture/products/uploads/
├── manufacturer_1/
│   ├── product_101/
│   │   ├── image1.jpg
│   │   ├── image2.jpg
│   │   └── image3.jpg
│   └── product_102/
│       ├── image1.jpg
│       └── image2.jpg
└── manufacturer_2/
    └── product_201/
        └── image1.jpg
```

### **Database Relationships**
- Products belong to one manufacturer
- Products have one category and one subcategory
- Products can have multiple images, sizes, and colors
- Images have a main image flag
- Sizes have individual pricing
- Colors can be predefined or custom

### **Form Validation Rules**
- Product name: Required, max 255 characters
- Description: Required, max 1000 characters
- Category: Required, must exist in categories table
- Subcategory: Required, must belong to selected category
- Images: At least one required, max 5MB each
- Sizes: At least one required
- Colors: At least one required
- Tags: Optional, comma-separated

This comprehensive product management system provides manufacturers with all the tools they need to effectively manage their product catalog with a modern, user-friendly interface. 