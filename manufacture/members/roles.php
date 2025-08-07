<?php
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../includes/Member.class.php';

// Get the current manufacturer ID
$user_id = $_SESSION['manufacturer_id'];

// Initialize Member class
$member = new Member($pdo);
$available_roles = $member->getAvailableRoles();

// Define role permissions
$role_permissions = [
    'admin' => [
        'title' => 'Administrator',
        'description' => 'Full access to all features and settings',
        'permissions' => [
            'Manage all team members',
            'Access all reports and analytics',
            'Manage company settings',
            'Full product management',
            'Order management and processing',
            'Financial management',
            'System configuration'
        ],
        'color' => '#dc3545'
    ],
    'manager' => [
        'title' => 'Manager',
        'description' => 'Can manage products, orders, and team members',
        'permissions' => [
            'Manage team members',
            'Product management',
            'Order processing',
            'Inventory management',
            'Basic reporting',
            'Customer communication'
        ],
        'color' => '#fd7e14'
    ],
    'member' => [
        'title' => 'Team Member',
        'description' => 'Can view and process orders',
        'permissions' => [
            'View orders',
            'Process orders',
            'Update order status',
            'View product catalog',
            'Basic customer support'
        ],
        'color' => '#28a745'
    ]
];
?>

<!-- Sidebar -->
<?php include '../components/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-header">
                    <h1 class="page-title">Roles & Permissions</h1>
                    <p>Manage team member roles and their access permissions</p>
                    <a href="team.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Team
                    </a>
                </div>

                <div class="roles-grid">
                    <?php foreach ($role_permissions as $role_key => $role_data): ?>
                        <div class="role-card">
                            <div class="role-header" style="background: <?php echo $role_data['color']; ?>">
                                <h3><?php echo htmlspecialchars($role_data['title']); ?></h3>
                                <div class="role-icon">
                                    <i class="fas fa-<?php echo $role_key === 'admin' ? 'crown' : ($role_key === 'manager' ? 'user-tie' : 'user'); ?>"></i>
                                </div>
                            </div>
                            <div class="role-body">
                                <p class="role-description"><?php echo htmlspecialchars($role_data['description']); ?></p>
                                
                                <h4>Permissions:</h4>
                                <ul class="permissions-list">
                                    <?php foreach ($role_data['permissions'] as $permission): ?>
                                        <li>
                                            <i class="fas fa-check"></i>
                                            <?php echo htmlspecialchars($permission); ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <div class="role-stats">
                                    <div class="stat-item">
                                        <span class="stat-number">
                                            <?php 
                                            // Count members with this role
                                            $member_count = 0;
                                            $all_members = $member->getTeamMembers($user_id);
                                            foreach ($all_members as $member_data) {
                                                if ($member_data['role'] === $role_key) {
                                                    $member_count++;
                                                }
                                            }
                                            echo $member_count;
                                            ?>
                                        </span>
                                        <span class="stat-label">Members</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="permission-note">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> About Role Management</h5>
                        <ul class="mb-0">
                            <li>Roles can be assigned when adding or editing team members</li>
                            <li>Administrators have full control over the system</li>
                            <li>Managers can manage most aspects except system settings</li>
                            <li>Team members have limited access focused on order processing</li>
                            <li>Role permissions are enforced throughout the application</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.roles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

.role-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.role-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.role-header {
    color: #fff;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.role-header h3 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
}

.role-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
}

.role-icon i {
    font-size: 20px;
}

.role-body {
    padding: 20px;
}

.role-description {
    color: #666;
    margin-bottom: 20px;
    font-size: 14px;
    line-height: 1.5;
}

.role-body h4 {
    color: #333;
    font-size: 16px;
    margin-bottom: 15px;
    font-weight: 600;
}

.permissions-list {
    list-style: none;
    padding: 0;
    margin: 0 0 20px 0;
}

.permissions-list li {
    padding: 8px 0;
    color: #555;
    font-size: 14px;
    display: flex;
    align-items: center;
}

.permissions-list li i {
    color: #28a745;
    margin-right: 10px;
    font-size: 12px;
}

.role-stats {
    border-top: 1px solid #eee;
    padding-top: 15px;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.stat-label {
    color: #666;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.permission-note {
    margin-top: 30px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.page-title {
    margin: 0;
    color: #333;
    font-size: 28px;
    font-weight: 600;
}

.page-header p {
    margin: 5px 0 0 0;
    color: #666;
}

@media (max-width: 768px) {
    .roles-grid {
        grid-template-columns: 1fr;
    }
    
    .role-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
}
</style>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
