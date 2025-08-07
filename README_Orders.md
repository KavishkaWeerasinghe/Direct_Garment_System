# Order Management System

This order management system has been created for the ManufactureHub application. It provides comprehensive order tracking and management capabilities for manufacturers.

## Features

### 1. Order Status Dashboard
- **Status Count Boxes**: Displays count of orders by status (Pending, Processing, Shipped, Out for Delivery, Delivered, Cancelled, Returned, Refunded)
- **Real-time Updates**: Counts update automatically when order statuses change

### 2. Order Table
- **Search Functionality**: Search by Order ID, Customer Name, Product Name, or Status
- **Order Information**: Displays Order ID, Item Name, Item Count, and Order Status
- **Action Buttons**: View and Update buttons for each order

### 3. View Order Modal
- **Customer Information**: Name, Email, and Address
- **Invoice Details**: Product name, price, quantity, and total amount
- **Professional Layout**: Clean, invoice-style presentation

### 4. Update Order Status
- **Inline Editing**: Click Update to change status via dropdown
- **OK/Cancel Buttons**: Confirm or cancel status changes
- **Real-time Updates**: Page refreshes to show updated counts

## Files Created

### 1. `manufacture/includes/Order.class.php`
Contains all order-related functions:
- `getOrderCountsByStatus()` - Get counts by status
- `getOrders()` - Get orders with search functionality
- `getOrderById()` - Get single order details
- `updateOrderStatus()` - Update order status
- `getOrderStatuses()` - Get available statuses
- `createSampleOrders()` - Create sample data (for testing)

### 2. `manufacture/orders.php`
Main order management page with:
- Status count dashboard
- Search functionality
- Order table with actions
- View modal for order details
- Update functionality for order status

### 3. `database_setup.sql`
SQL script to create the orders table with all necessary fields.

## Database Setup

1. **Create the orders table**:
   ```sql
   -- Run the database_setup.sql file in your MySQL database
   ```

2. **Table Structure**:
   - `id` - Primary key
   - `order_id` - Unique order identifier
   - `manufacturer_id` - Foreign key to manufacturers table
   - `customer_name` - Customer's full name
   - `customer_email` - Customer's email address
   - `customer_address` - Customer's shipping address
   - `product_name` - Name of the product
   - `product_price` - Price per unit
   - `quantity` - Number of items ordered
   - `status` - Order status (enum with predefined values)
   - `created_at` - Order creation timestamp
   - `updated_at` - Last update timestamp

## Usage

### Accessing the Orders Page
1. Navigate to `manufacture/orders.php`
2. Ensure you're logged in as a manufacturer
3. The page will automatically load with the sidebar and order data

### Managing Orders
1. **View Order Details**: Click the "View" button to see customer information and invoice
2. **Update Order Status**: Click "Update" to change the order status
3. **Search Orders**: Use the search bar to filter orders by various criteria
4. **Monitor Status Counts**: View the status boxes at the top for quick overview

### Sample Data
The system automatically creates sample orders if no orders exist in the database. This is useful for testing and demonstration purposes.

## Integration

### Sidebar Integration
The orders page integrates with the existing sidebar system:
- Uses the same sidebar component (`components/sidebar.php`)
- Follows the same styling and behavior patterns
- Maintains consistent navigation experience

### Database Integration
- Uses the existing database connection from `config/database.php`
- Follows the same PDO patterns as other classes
- Maintains data integrity with proper foreign key relationships

## Security Features

1. **Session Validation**: Checks for logged-in manufacturer
2. **SQL Injection Prevention**: Uses prepared statements
3. **XSS Prevention**: HTML escaping for all output
4. **Authorization**: Only allows manufacturers to access their own orders

## Customization

### Adding New Statuses
To add new order statuses:
1. Update the `getOrderStatuses()` method in `Order.class.php`
2. Add the new status to the database enum
3. Add corresponding CSS classes for styling

### Modifying the Interface
The interface uses standard CSS and can be customized by:
1. Modifying the inline styles in `orders.php`
2. Creating separate CSS files for better organization
3. Updating the modal and table layouts as needed

## Troubleshooting

### Common Issues
1. **Orders not showing**: Check if the orders table exists and has data
2. **Sidebar not working**: Ensure `sidebar.js` is properly loaded
3. **Database connection errors**: Verify database credentials in `config/database.php`

### Testing
1. Create sample orders using the `createSampleOrders()` method
2. Test all status updates
3. Verify search functionality works correctly
4. Check modal display and functionality

## Future Enhancements

Potential improvements for the order management system:
1. **Bulk Operations**: Update multiple orders at once
2. **Export Functionality**: Export orders to CSV/PDF
3. **Email Notifications**: Notify customers of status changes
4. **Order History**: Track all status changes with timestamps
5. **Advanced Filtering**: Filter by date ranges, price ranges, etc. 