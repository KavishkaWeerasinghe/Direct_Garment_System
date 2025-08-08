# Database Setup Instructions

This document provides instructions for setting up the database for the GarmentDirect application.

## 📋 **Prerequisites**

- MySQL/MariaDB server (version 5.7 or higher)
- PHP 7.4 or higher
- Web server (Apache/Nginx)

## 🗄️ **Database Setup**

### 1. **Create Database**

First, create a new MySQL database:

```sql
CREATE DATABASE garmentdirect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. **Run the Complete Setup Script**

Execute the complete database setup script:

```bash
mysql -u root -p garmentdirect < database_complete_setup.sql
```

Or import it through phpMyAdmin.

## 📁 **File Structure**

```
direct-garment/
├── config/
│   ├── config.php          # Application configuration
│   └── database.php        # Database connection (PDO)
├── includes/
│   ├── db_connection.php   # Legacy mysqli connection (for backward compatibility)
│   ├── cart_operations.php # Cart management
│   ├── product_operations.php # Product management
│   └── auth/
│       ├── auth_controller.php # Authentication controller
│       └── user_auth.php   # User authentication functions
├── manufacture/            # Manufacturer dashboard
├── uploads/               # File uploads directory
├── logs/                  # Application logs
└── database_complete_setup.sql # Complete database setup
```

## 🔧 **Configuration**

### 1. **Database Configuration**

Edit `config/config.php` to match your database settings:

```php
// Database Configuration
define('DB_HOST', 'localhost');     // Your database host
define('DB_PORT', '3306');          // Your database port
define('DB_NAME', 'garmentdirect'); // Your database name
define('DB_USER', 'root');          // Your database username
define('DB_PASS', '');              // Your database password
```

### 2. **Application Configuration**

Update the application settings in `config/config.php`:

```php
// Application Configuration
define('APP_NAME', 'GarmentDirect');
define('APP_URL', 'http://localhost/direct-garment'); // Your application URL
define('APP_VERSION', '1.0.0');
```

## 📊 **Database Tables**

The setup script creates the following tables:

### **Core Tables**
- `users` - User accounts (customers, manufacturers, admins)
- `manufacturers` - Manufacturer profiles
- `team_members` - Team members for manufacturers

### **Product Management**
- `categories` - Product categories
- `subcategories` - Product subcategories
- `products` - Main products table
- `product_images` - Product images
- `product_sizes` - Product sizes and pricing
- `product_colors` - Product colors

### **Inventory Management**
- `inventory` - Product inventory
- `inventory_log` - Inventory change tracking

### **E-commerce**
- `cart` - Shopping cart items
- `orders` - Customer orders

### **Legacy Tables** (for backward compatibility)
- `product` - Legacy product table
- `category` - Legacy category table

## 🔐 **Default Users**

The setup script creates these default users (password: `123456`):

1. **Admin User**
   - Email: `admin@garmentdirect.com`
   - Role: Admin

2. **Manufacturer User**
   - Email: `john@manufacturer.com`
   - Role: Manufacture
   - Company: Fashion Forward Ltd

3. **Customer User**
   - Email: `jane@customer.com`
   - Role: Customer

## 🚀 **Usage**

### **For Customers**
- Access: `http://localhost/direct-garment/`
- Login with: `jane@customer.com` / `123456`

### **For Manufacturers**
- Access: `http://localhost/direct-garment/manufacture/`
- Login with: `john@manufacturer.com` / `123456`

### **For Admins**
- Access: `http://localhost/direct-garment/`
- Login with: `admin@garmentdirect.com` / `123456`

## 🔄 **Database Connections**

The application uses two database connection methods:

### **1. PDO Connection (Primary)**
```php
require_once 'config/database.php';
// $pdo is available for all database operations
```

### **2. MySQLi Connection (Legacy)**
```php
require_once 'includes/db_connection.php';
// $conn is available for backward compatibility
```

## 📝 **File Updates**

All files have been updated to use the standardized database connection:

- ✅ `login.php` - Updated to use PDO
- ✅ `profile.php` - Updated to use PDO
- ✅ `change-password.php` - Updated to use PDO
- ✅ `includes/cart_operations.php` - Updated to use PDO
- ✅ `includes/product_operations.php` - Updated to use PDO
- ✅ `includes/auth/auth_controller.php` - Updated to use PDO

## 🛠️ **Troubleshooting**

### **Common Issues**

1. **Database Connection Failed**
   - Check database credentials in `config/config.php`
   - Ensure MySQL service is running
   - Verify database exists

2. **Permission Denied**
   - Ensure web server has read/write permissions
   - Create `uploads/` and `logs/` directories with proper permissions

3. **Session Issues**
   - Check session configuration in `config/config.php`
   - Ensure session directory is writable

### **Logs**

Check the following log files for errors:
- `logs/auth_errors.log` - Authentication errors
- `logs/cart_errors.log` - Cart operation errors
- `logs/product_errors.log` - Product operation errors
- `logs/profile_errors.log` - Profile operation errors

## 🔒 **Security Notes**

1. **Change Default Passwords**
   - Update default user passwords after first login
   - Use strong, unique passwords

2. **Database Security**
   - Use a dedicated database user with limited permissions
   - Enable SSL for database connections in production
   - Regularly backup your database

3. **File Permissions**
   - Set proper file permissions for sensitive directories
   - Restrict access to configuration files

## 📞 **Support**

If you encounter any issues:

1. Check the log files in the `logs/` directory
2. Verify database connection settings
3. Ensure all required PHP extensions are enabled
4. Check file permissions

## 🔄 **Migration from Old System**

If migrating from an existing system:

1. Backup your current database
2. Export data from old tables
3. Run the new setup script
4. Import data into new table structure
5. Update any custom code to use new table names

---

**Note**: This setup provides a complete foundation for the GarmentDirect application. All database connections are now standardized and use the `config/database.php` file as the single source of truth for database configuration.
