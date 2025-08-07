<?php
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../includes/Member.class.php';

// Get the current manufacturer ID
$user_id = $_SESSION['manufacturer_id'];

// Initialize Member class
$member = new Member($pdo);

// Get activity period (default 30 days)
$activity_days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$activity_days = max(7, min(365, $activity_days)); // Limit between 7 and 365 days

// Get member activity data
$member_activity = $member->getMemberActivity($user_id, $activity_days);

// Calculate summary statistics
$total_members = count($member_activity);
$active_members = 0;
$total_orders_processed = 0;

foreach ($member_activity as $activity) {
    if ($activity['last_login'] && strtotime($activity['last_login']) > strtotime("-{$activity_days} days")) {
        $active_members++;
    }
    $total_orders_processed += $activity['total_orders_processed'];
}
?>

<!-- Sidebar -->
<?php include '../components/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-header">
                    <h1 class="page-title">Member Activity</h1>
                    <p>Track team member activity and performance</p>
                    <a href="team.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Team
                    </a>
                </div>

                <!-- Activity Period Filter -->
                <div class="activity-filter">
                    <form method="GET" class="form-inline">
                        <label for="days" class="mr-2">Activity Period:</label>
                        <select name="days" id="days" class="form-control mr-2" onchange="this.form.submit()">
                            <option value="7" <?php echo $activity_days === 7 ? 'selected' : ''; ?>>Last 7 days</option>
                            <option value="30" <?php echo $activity_days === 30 ? 'selected' : ''; ?>>Last 30 days</option>
                            <option value="90" <?php echo $activity_days === 90 ? 'selected' : ''; ?>>Last 90 days</option>
                            <option value="365" <?php echo $activity_days === 365 ? 'selected' : ''; ?>>Last year</option>
                        </select>
                    </form>
                </div>

                <!-- Summary Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #007bff;">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_members; ?></h3>
                            <p>Total Team Members</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #28a745;">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $active_members; ?></h3>
                            <p>Active Members</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #ffc107;">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_orders_processed; ?></h3>
                            <p>Orders Processed</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #17a2b8;">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-content">
                            <h3><?php echo $total_members > 0 ? round(($active_members / $total_members) * 100, 1) : 0; ?>%</h3>
                            <p>Activity Rate</p>
                        </div>
                    </div>
                </div>

                <!-- Member Activity Table -->
                <div class="activity-table-container">
                    <h3>Member Activity Details</h3>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Role</th>
                                    <th>Last Login</th>
                                    <th>Orders Processed</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($member_activity)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted">
                                            <i class="fas fa-users"></i>
                                            <p>No team members found</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($member_activity as $activity): ?>
                                        <tr>
                                            <td>
                                                <div class="member-info">
                                                    <div class="member-avatar">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($activity['email']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="role-badge role-<?php echo strtolower($activity['role']); ?>">
                                                    <?php echo htmlspecialchars(ucfirst($activity['role'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($activity['last_login']): ?>
                                                    <span class="last-login">
                                                        <?php echo date('M d, Y H:i', strtotime($activity['last_login'])); ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            <?php 
                                                            $days_ago = round((time() - strtotime($activity['last_login'])) / (60 * 60 * 24));
                                                            echo $days_ago === 0 ? 'Today' : ($days_ago === 1 ? 'Yesterday' : $days_ago . ' days ago');
                                                            ?>
                                                        </small>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Never</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="orders-count">
                                                    <?php echo $activity['total_orders_processed']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php 
                                                $is_active = $activity['last_login'] && strtotime($activity['last_login']) > strtotime("-{$activity_days} days");
                                                ?>
                                                <span class="status-badge status-<?php echo $is_active ? 'active' : 'inactive'; ?>">
                                                    <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="edit.php?id=<?php echo $activity['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.activity-filter {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
}

.stat-icon i {
    font-size: 20px;
    color: #fff;
}

.stat-content h3 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.stat-content p {
    margin: 5px 0 0 0;
    color: #666;
    font-size: 14px;
}

.activity-table-container {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.activity-table-container h3 {
    margin-bottom: 20px;
    color: #333;
    font-size: 18px;
    font-weight: 600;
}

.member-info {
    display: flex;
    align-items: center;
}

.member-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #007bff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
}

.member-avatar i {
    font-size: 16px;
    color: #fff;
}

.role-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.role-admin {
    background: #dc3545;
    color: #fff;
}

.role-manager {
    background: #fd7e14;
    color: #fff;
}

.role-member {
    background: #28a745;
    color: #fff;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 500;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.orders-count {
    font-weight: bold;
    color: #007bff;
}

.last-login {
    font-size: 14px;
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
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-card {
        flex-direction: column;
        text-align: center;
    }
    
    .stat-icon {
        margin-right: 0;
        margin-bottom: 10px;
    }
}
</style>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
