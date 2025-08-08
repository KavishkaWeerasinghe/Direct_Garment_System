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
                                    <button class="btn btn-sm btn-outline-primary" onclick="editMember(<?php echo $member_data['id']; ?>, '<?php echo htmlspecialchars($member_data['first_name']); ?>', '<?php echo htmlspecialchars($member_data['last_name']); ?>', '<?php echo htmlspecialchars($member_data['email']); ?>', '<?php echo htmlspecialchars($member_data['role']); ?>', <?php echo $member_data['is_active'] ? 'true' : 'false'; ?>)">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
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

<!-- Edit Member Modal -->
<div class="modal fade" id="editMemberModal" tabindex="-1" aria-labelledby="editMemberModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="padding: 0px;">
            <div class="modal-header">
                <h5 class="modal-title" id="editMemberModalLabel">Edit Team Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editMemberForm" method="POST" action="edit.php">
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    <input type="hidden" id="edit_member_id" name="member_id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_first_name">First Name *</label>
                                <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_last_name">Last Name *</label>
                                <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="edit_email">Email Address *</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                        <small class="form-text text-muted">This will be used for login</small>
                    </div>

                    <div class="form-group">
                        <label for="edit_role">Role *</label>
                        <select class="form-control" id="edit_role" name="role" required>
                            <option value="">Select a role</option>
                            <option value="admin">Administrator</option>
                            <option value="manager">Manager</option>
                            <option value="member">Team Member</option>
                        </select>
                        <small class="form-text text-muted">
                            <strong>Administrator:</strong> Full access to all features<br>
                            <strong>Manager:</strong> Can manage products, orders, and team members<br>
                            <strong>Team Member:</strong> Can view and process orders
                        </small>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="edit_is_active" name="is_active">
                            <label class="custom-control-label" for="edit_is_active">
                                Active member (can log in and access the system)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Member
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">
                    <i class="fas fa-exclamation-triangle text-danger"></i> Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to remove this team member?</p>
                <p class="text-muted mb-0"><strong>This action cannot be undone.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Remove Member
                </button>
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

/* Modal styles */
.modal-dialog {
    max-width: 600px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: #333;
    margin-bottom: 5px;
}

.form-control {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px 12px;
    font-size: 14px;
}

.form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #007bff;
    border-color: #007bff;
}

/* Fallback modal styles */
.modal.show {
    display: block !important;
}

.modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 1040;
}

.modal {
    z-index: 1050;
}

.modal-open {
    overflow: hidden;
}

/* Enhanced scrollbar styles for edit modal */
.modal-body::-webkit-scrollbar {
    width: 8px;
}

.modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.modal-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Firefox scrollbar styles */
.modal-body {
    scrollbar-width: thin;
    scrollbar-color: #c1c1c1 #f1f1f1;
}

/* Ensure modal body has proper padding for scrollbar */
.modal-body {
    padding-right: 20px;
}

/* Add some spacing at the bottom of modal body for better scrolling */
.modal-body .form-group:last-child {
    margin-bottom: 10px;
}
</style>

<script>
function deleteMember(memberId) {
    // Store the member ID for deletion
    window.memberToDelete = memberId;
    
    // Show the delete confirmation modal
    try {
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
            modal.show();
        } else {
            // Fallback: show modal manually
            document.getElementById('deleteConfirmModal').style.display = 'block';
            document.getElementById('deleteConfirmModal').classList.add('show');
            document.body.classList.add('modal-open');
            
            // Add backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'deleteModalBackdrop';
            document.body.appendChild(backdrop);
        }
    } catch (error) {
        console.error('Error showing delete modal:', error);
        // Fallback: use alert
        if (confirm('Are you sure you want to remove this team member? This action cannot be undone.')) {
            submitDeleteForm(memberId);
        }
    }
}

function submitDeleteForm(memberId) {
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

function editMember(memberId, firstName, lastName, email, role, isActive) {
    // Populate the modal with member data
    document.getElementById('edit_member_id').value = memberId;
    document.getElementById('edit_first_name').value = firstName;
    document.getElementById('edit_last_name').value = lastName;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_role').value = role;
    document.getElementById('edit_is_active').checked = isActive;
    
    // Show the modal with fallback
    try {
        if (typeof bootstrap !== 'undefined') {
            const modal = new bootstrap.Modal(document.getElementById('editMemberModal'));
            modal.show();
        } else {
            // Fallback: show modal manually
            document.getElementById('editMemberModal').style.display = 'block';
            document.getElementById('editMemberModal').classList.add('show');
            document.body.classList.add('modal-open');
            
            // Add backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'modalBackdrop';
            document.body.appendChild(backdrop);
        }
    } catch (error) {
        console.error('Error showing modal:', error);
        // Fallback: redirect to edit page
        window.location.href = 'edit.php?id=' + memberId;
    }
}

// Handle edit form submission
document.addEventListener('DOMContentLoaded', function() {
    const editForm = document.getElementById('editMemberForm');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            // Add the member ID to the form action
            const memberId = document.getElementById('edit_member_id').value;
            this.action = 'edit.php?id=' + memberId;
            
            // Show processing state
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
        });
    }
    
    // Handle delete confirmation button
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', function() {
            if (window.memberToDelete) {
                submitDeleteForm(window.memberToDelete);
            }
        });
    }
    
    // Handle modal close buttons with fallback
    const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal);
            }
        });
    });
    
    // Handle backdrop clicks
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            closeModal(e.target);
        }
    });
});

// Function to close modal with fallback
function closeModal(modal) {
    try {
        if (typeof bootstrap !== 'undefined') {
            const bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) {
                bsModal.hide();
            }
        } else {
            // Fallback: hide modal manually
            modal.style.display = 'none';
            modal.classList.remove('show');
            document.body.classList.remove('modal-open');
            
            // Remove backdrops
            const backdrop = document.getElementById('modalBackdrop');
            if (backdrop) {
                backdrop.remove();
            }
            const deleteBackdrop = document.getElementById('deleteModalBackdrop');
            if (deleteBackdrop) {
                deleteBackdrop.remove();
            }
        }
    } catch (error) {
        console.error('Error closing modal:', error);
        // Fallback: hide modal manually
        modal.style.display = 'none';
        modal.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
