<?php
require_once '../../config/database.php';
require_once '../includes/profile.class.php';
require_once '../includes/Manufacturer.class.php';

// Check if user is logged in
if (!Manufacturer::isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$manufacturer_id = Manufacturer::getCurrentUserId();

// Initialize Profile class
$profile = new Profile();
$profileData = $profile->getProfileData($manufacturer_id);
$loginActivity = $profile->getLoginActivity($manufacturer_id, 10);

// Handle case where profile data is not found
if (!$profileData) {
    $profileData = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'profile_photo' => '',
        'nic_number' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => null,
        'email_verified' => 0
    ];
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $updateData = [
                    'first_name' => trim($_POST['first_name']),
                    'last_name' => trim($_POST['last_name']),
                    'phone' => trim($_POST['phone']),
                    'nic_number' => trim($_POST['nic_number'])
                ];
                
                $errors = $profile->validateProfileData($updateData);
                if (empty($errors)) {
                    $result = $profile->updateProfile($manufacturer_id, $updateData);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                    if ($result['success']) {
                        $profileData = $profile->getProfileData($manufacturer_id);
                    }
                } else {
                    $message = implode('<br>', $errors);
                    $messageType = 'error';
                }
                break;

            case 'upload_photo':
                if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                    $result = $profile->uploadProfilePhoto($_FILES['profile_photo'], $manufacturer_id);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                    if ($result['success']) {
                        $profileData = $profile->getProfileData($manufacturer_id);
                    }
                } else {
                    $message = 'Please select a valid image file';
                    $messageType = 'error';
                }
                break;

            case 'change_password':
                $currentPassword = $_POST['current_password'];
                $newPassword = $_POST['new_password'];
                $confirmPassword = $_POST['confirm_password'];
                
                if ($newPassword !== $confirmPassword) {
                    $message = 'New passwords do not match';
                    $messageType = 'error';
                } else {
                    $result = $profile->changePassword($manufacturer_id, $currentPassword, $newPassword);
                    $message = $result['message'];
                    $messageType = $result['success'] ? 'success' : 'error';
                }
                break;

            case 'toggle_2fa':
                $enabled = isset($_POST['two_factor_enabled']) ? 1 : 0;
                $result = $profile->toggleTwoFactor($manufacturer_id, $enabled);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                if ($result['success']) {
                    $profileData = $profile->getProfileData($manufacturer_id);
                }
                break;

            case 'delete_account':
                $password = $_POST['delete_password'];
                $result = $profile->deleteAccount($manufacturer_id, $password);
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                if ($result['success']) {
                    Manufacturer::logout();
                    header('Location: ../login.php');
                    exit();
                }
                break;
        }
    }
}

// Include header (this will handle session and user data)
include '../components/header.php';
?>

<!-- Sidebar -->
<?php include '../components/sidebar.php'; ?>

<div class="main-content">
    <div class="profile-container">
        <div class="profile-header">
            <h1><i class="fas fa-user-circle me-2"></i>Profile Settings</h1>
            <p>Manage your account information and security settings</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Profile Information -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user me-2"></i>Profile Information</h5>
                    </div>
                    <div class="card-body">
                        <!-- Profile Photo Section -->
                        <div class="text-center mb-4">
                            <div class="profile-photo-container">
                                <?php 
                                $photoSrc = '../assets/images/default-avatar.png';
                                if ($profileData['profile_photo'] && file_exists('../' . $profileData['profile_photo'])) {
                                    $photoSrc = '../' . $profileData['profile_photo'];
                                }
                                ?>
                                <img src="<?php echo $photoSrc; ?>" 
                                     alt="Profile Photo" class="profile-photo" id="profilePhoto"
                                     onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxNTAiIGhlaWdodD0iMTUwIiBmaWxsPSIjRjVGNUY1Ii8+CjxjaXJjbGUgY3g9Ijc1IiBjeT0iNjAiIHI9IjIwIiBmaWxsPSIjQ0NDIi8+CjxwYXRoIGQ9Ik0yNSAxMjBDMjUgMTAwIDQ1IDg1IDc1IDg1QzEwNSA4NSAxMjUgMTAwIDEyNSAxMjBIMjVaIiBmaWxsPSIjQ0NDIi8+Cjwvc3ZnPgo='">
                                <label for="photoUpload" class="photo-upload-btn">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>
                            <form method="POST" enctype="multipart/form-data" id="photoForm" style="display: none;">
                                <input type="hidden" name="action" value="upload_photo">
                                <input type="file" name="profile_photo" id="photoUpload" accept="image/*">
                            </form>
                        </div>

                        <!-- Profile Form -->
                        <form method="POST" class="profile-form">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-section">
                                <div class="section-header">
                                    <h6><i class="fas fa-user me-2"></i>Personal Information</h6>
                                    <p class="text-muted">Update your personal details</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">
                                            <i class="fas fa-user me-1"></i>First Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($profileData['first_name']); ?>" 
                                               placeholder="Enter your first name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">
                                            <i class="fas fa-user me-1"></i>Last Name <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($profileData['last_name']); ?>" 
                                               placeholder="Enter your last name" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-header">
                                    <h6><i class="fas fa-address-book me-2"></i>Contact Information</h6>
                                    <p class="text-muted">Your contact details for communication</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">
                                            <i class="fas fa-envelope me-1"></i>Email Address
                                        </label>
                                        <div class="input-group">
                                            <input type="email" class="form-control" id="email" 
                                                   value="<?php echo htmlspecialchars($profileData['email']); ?>" readonly>
                                            <span class="input-group-text">
                                                <i class="fas fa-lock" title="Email cannot be changed"></i>
                                            </span>
                                        </div>

                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="phone" class="form-label">
                                            <i class="fas fa-phone me-1"></i>Phone Number <span class="text-danger">*</span>
                                        </label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($profileData['phone']); ?>" 
                                               placeholder="+94 11 234 5678" required>
                                    </div>
                                </div>
                            </div>

                            <div class="form-section">
                                <div class="section-header">
                                    <h6><i class="fas fa-id-card me-2"></i>Additional Information</h6>
                                    <p class="text-muted">Optional details for account verification</p>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="nic_number" class="form-label">
                                            <i class="fas fa-id-badge me-1"></i>NIC/ID Number
                                        </label>
                                        <input type="text" class="form-control" id="nic_number" name="nic_number" 
                                               value="<?php echo htmlspecialchars($profileData['nic_number']); ?>" 
                                               placeholder="e.g., 123456789V">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">
                                            <i class="fas fa-calendar-alt me-1"></i>Account Created
                                        </label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" 
                                                   value="<?php echo date('F j, Y', strtotime($profileData['created_at'])); ?>" readonly>
                                            <span class="input-group-text">
                                                <i class="fas fa-clock" title="Account creation date"></i>
                                            </span>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save me-2"></i>Update Profile
                                </button>
                                <button type="reset" class="btn btn-outline-secondary btn-lg ms-2">
                                    <i class="fas fa-undo me-2"></i>Reset Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Settings</h5>
                    </div>
                    <div class="card-body">
                        <!-- Change Password -->
                        <form method="POST" class="mb-4">
                            <input type="hidden" name="action" value="change_password">
                            <div class="security-section">
                                <div class="security-header">
                                    <h6><i class="fas fa-key me-2"></i>Change Password</h6>
                                    <p class="text-muted mb-3">Update your password to keep your account secure</p>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <div class="password-input-group">
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <div class="password-input-group">
                                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength mt-2" id="passwordStrength"></div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="confirm_password" class="form-label">Confirm Password</label>
                                        <div class="password-input-group">
                                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </button>
                                </div>
                            </div>
                        </form>

                        <!-- Two-Factor Authentication -->
                        <form method="POST">
                            <input type="hidden" name="action" value="toggle_2fa">
                            <div class="security-section">
                                <div class="security-header">
                                    <h6><i class="fas fa-mobile-alt me-2"></i>Two-Factor Authentication</h6>
                                    <p class="text-muted mb-3">Add an extra layer of security to your account</p>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="two_factor_enabled" name="two_factor_enabled" 
                                           <?php echo $profileData['two_factor_enabled'] ?? false ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="two_factor_enabled">
                                        Enable two-factor authentication for enhanced security
                                    </label>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-toggle-on me-2"></i>Update 2FA Settings
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Delete Account -->
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</h5>
                    </div>
                    <div class="card-body">
                        <h6>Delete Account</h6>
                        <p class="text-muted">Once you delete your account, there is no going back. Please be certain.</p>
                        <button type="button" class="btn btn-danger" id="deleteAccountBtn">
                            <i class="fas fa-trash me-2"></i>Delete Account
                        </button>
                    </div>
                </div>
            </div>

            <!-- Login Activity -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i>Login Activity</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($loginActivity)): ?>
                            <p class="text-muted text-center">No login activity recorded</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($loginActivity as $activity): ?>
                                            <tr>
                                                <td><?php echo date('M j, g:i A', strtotime($activity['login_time'])); ?></td>
                                                <td>
                                                    <small class="text-muted"><?php echo htmlspecialchars($activity['ip_address']); ?></small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Account Modal -->
<div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAccountModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Account Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. All your data will be permanently deleted.
                </div>
                <p>To confirm deletion, please enter your password:</p>
                <form id="deletePasswordForm" method="POST">
                    <input type="hidden" name="action" value="delete_account">
                    <div class="mb-3">
                        <label for="deletePasswordInput" class="form-label">Password</label>
                        <input type="password" class="form-control" id="deletePasswordInput" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-2"></i>Delete Account
                </button>
            </div>
        </div>
    </div>
</div>

<?php include '../components/footer.php'; ?>
