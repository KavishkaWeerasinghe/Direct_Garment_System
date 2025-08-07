<?php
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../includes/Member.class.php';

// Get the current manufacturer ID
$user_id = $_SESSION['manufacturer_id'];

// Initialize Member class
$member = new Member($pdo);

// Get all team members for the current manufacturer
$team_members = $member->getTeamMembers($user_id);
?>

<!-- Sidebar -->
<?php include '../components/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-header">
                    <h1 class="page-title">Team Members</h1>
                    <p>Manage your team members and their roles</p>
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Add New Member
                    </a>
                </div>

                <?php if (isset($_SESSION['member_message'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['member_message']; ?>
                        <?php unset($_SESSION['member_message']); ?>
                    </div>
                <?php endif; ?>

                <div class="team-members-grid">
                    <?php if (empty($team_members)): ?>
                        <div class="empty-state">
                            <i class="fas fa-users"></i>
                            <h3>No Team Members Yet</h3>
                            <p>Start building your team by adding the first member.</p>
                            <a href="add.php" class="btn btn-primary">Add First Member</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($team_members as $member_data): ?>
                            <div class="member-card">
                                <div class="member-avatar">
                                    <i class="fas fa-user"></i>
                                </div>
                                <div class="member-info">
                                    <h4><?php echo htmlspecialchars($member_data['first_name'] . ' ' . $member_data['last_name']); ?></h4>
                                    <p class="member-email"><?php echo htmlspecialchars($member_data['email']); ?></p>
                                    <p class="member-role">
                                        <span class="role-badge role-<?php echo strtolower($member_data['role']); ?>">
                                            <?php echo htmlspecialchars($member_data['role']); ?>
                                        </span>
                                    </p>
                                    <p class="member-status">
                                        <span class="status-badge status-<?php echo $member_data['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $member_data['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="member-actions">
                                    <a href="edit.php?id=<?php echo $member_data['id']; ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($member_data['id'] != $user_id): ?>
                                        <button class="btn btn-sm btn-outline-danger" onclick="deleteMember(<?php echo $member_data['id']; ?>)">
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.team-members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.member-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
}

.member-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.member-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #007bff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
}

.member-avatar i {
    font-size: 24px;
    color: #fff;
}

.member-info h4 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 18px;
}

.member-email {
    color: #666;
    margin: 0 0 10px 0;
    font-size: 14px;
}

.member-role, .member-status {
    margin: 5px 0;
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

.member-actions {
    margin-top: 15px;
    display: flex;
    gap: 10px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    grid-column: 1 / -1;
}

.empty-state i {
    font-size: 48px;
    color: #ccc;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

.empty-state p {
    color: #999;
    margin-bottom: 20px;
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
</style>

<script>
function deleteMember(memberId) {
    if (confirm('Are you sure you want to remove this team member? This action cannot be undone.')) {
        // Create a form to submit the delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete.php';
        
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'member_id';
        input.value = memberId;
        
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
