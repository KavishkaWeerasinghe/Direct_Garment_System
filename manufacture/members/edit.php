<?php
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/../includes/Member.class.php';

// Get the current manufacturer ID
$user_id = $_SESSION['manufacturer_id'] ?? ($user_data['id'] ?? null);

// Validate that we have a valid user_id
if (!$user_id) {
    die("Error: No valid manufacturer ID found. Please log in again.");
}

// Initialize Member class
$member = new Member($pdo);

// Get member ID from URL
$member_id = $_GET['id'] ?? null;

if (!$member_id) {
    header('Location: team.php');
    exit();
}

// Get member data
$member_data = $member->getMemberById($member_id, $user_id);

if (!$member_data) {
    $_SESSION['member_message'] = 'Member not found';
    header('Location: team.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $update_data = [
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'email' => trim($_POST['email']),
        'role' => $_POST['role'],
        'is_active' => isset($_POST['is_active']) ? 1 : 0
    ];
    
    // Basic validation
    $errors = [];
    
    if (empty($update_data['first_name'])) {
        $errors[] = 'First name is required';
    }
    
    if (empty($update_data['last_name'])) {
        $errors[] = 'Last name is required';
    }
    
    if (empty($update_data['email'])) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($update_data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($update_data['role'])) {
        $errors[] = 'Role is required';
    }
    
    // If no errors, update the member
    if (empty($errors)) {
        $result = $member->updateMember($member_id, $update_data, $user_id);
        
        if ($result['success']) {
            $_SESSION['member_message'] = $result['message'];
            header('Location: team.php');
            exit();
        } else {
            $errors[] = $result['message'];
        }
    }
}

$available_roles = $member->getAvailableRoles();
?>

<!-- Sidebar -->
<?php include '../components/sidebar.php'; ?>

<!-- Main Content -->
<div class="main-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="page-header">
                    <h1 class="page-title">Edit Team Member</h1>
                    <p>Update team member information</p>
                    <a href="team.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Team
                    </a>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="member-form" id="editMemberForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="first_name" 
                                               name="first_name" 
                                               value="<?php echo htmlspecialchars($member_data['first_name']); ?>"
                                               required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Last Name *</label>
                                        <input type="text" 
                                               class="form-control" 
                                               id="last_name" 
                                               name="last_name" 
                                               value="<?php echo htmlspecialchars($member_data['last_name']); ?>"
                                               required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" 
                                       class="form-control" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($member_data['email']); ?>"
                                       required>
                                <small class="form-text text-muted">This will be used for login</small>
                            </div>

                            <div class="form-group">
                                <label for="role">Role *</label>
                                <select class="form-control" id="role" name="role" required>
                                    <option value="">Select a role</option>
                                    <?php foreach ($available_roles as $role_key => $role_name): ?>
                                        <option value="<?php echo $role_key; ?>" 
                                                <?php echo ($member_data['role'] === $role_key) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($role_name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    <strong>Administrator:</strong> Full access to all features<br>
                                    <strong>Manager:</strong> Can manage products, orders, and team members<br>
                                    <strong>Team Member:</strong> Can view and process orders
                                </small>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" 
                                           class="custom-control-input" 
                                           id="is_active" 
                                           name="is_active" 
                                           <?php echo ($member_data['is_active']) ? 'checked' : ''; ?>>
                                    <label class="custom-control-label" for="is_active">
                                        Active member (can log in and access the system)
                                    </label>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Update Member
                                </button>
                                <a href="team.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.member-form {
    max-width: 800px;
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

.form-actions {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.form-actions .btn {
    margin-right: 10px;
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

.card {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.card-body {
    padding: 30px;
}
</style>

<script>
// Form validation and submission handling for team member editing
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editMemberForm');
    if (form) {
        console.log('Edit member form found, setting up validation...');
        
        form.addEventListener('submit', function(e) {
            console.log('Edit member form submit event triggered');
            
            // Basic client-side validation
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const role = document.getElementById('role').value;
            
            console.log('Edit member form data:', { firstName, lastName, email, role });
            
            if (!firstName || !lastName || !email || !role) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                console.log('Edit member validation failed - missing fields');
                return false;
            }
            
            console.log('Edit member validation passed, allowing form submission');
            
            // Show processing state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            submitBtn.disabled = true;
            
            // Store original text to restore if needed
            submitBtn.dataset.originalText = originalText;
            
            // If validation passes, allow form submission
            console.log('Form will submit normally');
            return true;
        });
    } else {
        console.error('Edit member form not found!');
    }
});
</script>

<?php require_once __DIR__ . '/../components/footer.php'; ?>
