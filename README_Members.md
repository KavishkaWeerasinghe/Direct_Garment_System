# Members Management System

## Overview

The Members Management System allows manufacturers to manage their team members with different roles and permissions. This system provides a comprehensive solution for team collaboration and access control.

## Features

### 1. Team Member Management
- **Add New Members**: Create new team members with specific roles
- **Edit Members**: Update member information and roles
- **Remove Members**: Delete team members from the system
- **Member Status**: Activate/deactivate team members

### 2. Role-Based Access Control
- **Administrator**: Full access to all features and settings
- **Manager**: Can manage products, orders, and team members
- **Team Member**: Can view and process orders

### 3. Activity Tracking
- **Last Login Tracking**: Monitor when members last accessed the system
- **Order Processing**: Track which member processed specific orders
- **Activity Statistics**: View member activity over different time periods

## Database Structure

### Team Members Table
```sql
CREATE TABLE `team_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `manufacturer_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','member') NOT NULL DEFAULT 'member',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_login` datetime NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_email_manufacturer` (`email`, `manufacturer_id`)
);
```

### Orders Table Enhancement
```sql
ALTER TABLE `orders` ADD COLUMN `processed_by` int(11) NULL;
```

## File Structure

```
manufacture/
├── members/
│   ├── team.php          # Team members listing
│   ├── add.php           # Add new member
│   ├── edit.php          # Edit member (to be created)
│   ├── delete.php        # Delete member
│   ├── roles.php         # Roles and permissions
│   └── activity.php      # Member activity tracking
├── includes/
│   └── Member.class.php  # Member management class
└── components/
    └── sidebar.php       # Updated with Members section
```

## Usage

### 1. Accessing Members Section
- Navigate to the Members section in the sidebar
- Click on "Team Members" to view all team members

### 2. Adding a New Member
1. Click "Add New Member" button
2. Fill in the required information:
   - First Name
   - Last Name
   - Email Address
   - Password (minimum 6 characters)
   - Role (Administrator, Manager, or Team Member)
   - Active status
3. Click "Add Team Member"

### 3. Managing Roles
- Visit "Roles & Permissions" to see detailed role information
- Each role has specific permissions and access levels
- Role assignments can be changed when editing members

### 4. Tracking Activity
- Visit "Member Activity" to see activity statistics
- Filter by different time periods (7, 30, 90, 365 days)
- View last login times and order processing counts

## Security Features

### 1. Email Uniqueness
- Each email address must be unique within a manufacturer's team
- Prevents duplicate accounts

### 2. Password Security
- Passwords are hashed using PHP's `password_hash()` function
- Minimum 6 character requirement

### 3. Access Control
- Members can only access data for their assigned manufacturer
- Role-based permissions are enforced throughout the system

### 4. Self-Protection
- Members cannot delete their own accounts
- Administrators have full control over team management

## Role Permissions

### Administrator
- ✅ Manage all team members
- ✅ Access all reports and analytics
- ✅ Manage company settings
- ✅ Full product management
- ✅ Order management and processing
- ✅ Financial management
- ✅ System configuration

### Manager
- ✅ Manage team members
- ✅ Product management
- ✅ Order processing
- ✅ Inventory management
- ✅ Basic reporting
- ✅ Customer communication

### Team Member
- ✅ View orders
- ✅ Process orders
- ✅ Update order status
- ✅ View product catalog
- ✅ Basic customer support

## API Methods

### Member Class Methods

```php
// Get all team members
$members = $member->getTeamMembers($manufacturer_id);

// Get specific member
$member_data = $member->getMemberById($member_id, $manufacturer_id);

// Add new member
$result = $member->addMember($data, $manufacturer_id);

// Update member
$result = $member->updateMember($member_id, $data, $manufacturer_id);

// Delete member
$result = $member->deleteMember($member_id, $manufacturer_id);

// Get member activity
$activity = $member->getMemberActivity($manufacturer_id, $days);

// Get available roles
$roles = $member->getAvailableRoles();
```

## Installation

1. **Database Setup**
   ```sql
   -- Run the database_team_members.sql file
   source database_team_members.sql;
   ```

2. **File Structure**
   - Ensure all files are in the correct directories
   - Verify file permissions are set correctly

3. **Configuration**
   - No additional configuration required
   - System integrates with existing authentication

## Future Enhancements

### Planned Features
- **Member Invitations**: Email invitations for new members
- **Two-Factor Authentication**: Enhanced security for member accounts
- **Activity Logs**: Detailed activity tracking and audit trails
- **Permission Granularity**: More detailed permission controls
- **Member Groups**: Group-based access control
- **API Integration**: REST API for member management

### Potential Improvements
- **Bulk Operations**: Add/remove multiple members at once
- **Member Profiles**: Detailed member profiles with avatars
- **Notification System**: Email notifications for member activities
- **Advanced Analytics**: More detailed activity and performance metrics

## Troubleshooting

### Common Issues

1. **Member Not Found**
   - Verify the member exists for the current manufacturer
   - Check if the member has been deleted

2. **Email Already Exists**
   - Each email must be unique within a manufacturer's team
   - Use a different email address or remove the existing member

3. **Permission Denied**
   - Ensure the current user has appropriate role permissions
   - Check if the member account is active

4. **Database Errors**
   - Verify the team_members table exists
   - Check foreign key constraints
   - Ensure proper database permissions

## Support

For technical support or questions about the Members Management System, please refer to the main documentation or contact the development team.
