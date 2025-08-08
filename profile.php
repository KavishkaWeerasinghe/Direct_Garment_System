<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth/user_auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to log errors
function logError($message) {
    $log_dir = dirname(__FILE__) . '/logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $log_file = $log_dir . '/profile_errors.log';
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, $log_file);
}

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle profile deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_profile'])) {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete user's cart items
        $delete_cart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $delete_cart->execute([$user_id]);
        
        // Delete user's profile photo if exists
        $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_data = $stmt->fetch();
        
        if ($user_data && $user_data['profile_photo'] && file_exists($user_data['profile_photo'])) {
            unlink($user_data['profile_photo']);
        }
        
        // Delete user account
        $delete_user = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $delete_user->execute([$user_id]);
        
        // Commit transaction
        $pdo->commit();
        
        // Clear session and cookies
        logoutUser();
        
        // Redirect to home page
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollback();
        $error_message = "Error deleting profile: " . $e->getMessage();
        logError("Profile deletion error: " . $e->getMessage());
    }
}

// Get user data
try {
    $stmt = $pdo->prepare("SELECT name, email, phone_number, address, profile_photo FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        logError("User not found with ID: " . $user_id);
        die("User not found");
    }
} catch (PDOException $e) {
    logError("Failed to get user data: " . $e->getMessage());
    die("Error getting user data: " . $e->getMessage());
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Handle profile photo upload
    $profile_photo = $user['profile_photo']; // Keep existing photo by default
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profile_photos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $new_filename = uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                // Delete old profile photo if exists
                if ($user['profile_photo'] && file_exists($user['profile_photo'])) {
                    unlink($user['profile_photo']);
                }
                $profile_photo = $upload_path;
            }
        }
    }
    
    // Update user data
    try {
        $update_stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, phone_number = ?, address = ?, profile_photo = ? WHERE id = ?");
        $update_stmt->execute([$name, $email, $phone_number, $address, $profile_photo, $user_id]);
        
        // Update session variables
        $_SESSION['user_name'] = $name;
        
        $success_message = 'Profile updated successfully';
        
        // Refresh user data
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
    } catch (PDOException $e) {
        logError("Failed to update profile: " . $e->getMessage());
        $error_message = "Failed to update profile: " . $e->getMessage();
    }
}
?>

<?php include 'components/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title mb-4">Update Profile</h2>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <div class="text-center mb-4">
                            <img src="<?php echo htmlspecialchars($user['profile_photo'] ?? 'src/images/default-profile.png'); ?>" 
                                 alt="Profile Photo" 
                                 class="rounded-circle mb-3"
                                 style="width: 150px; height: 150px; object-fit: cover;">
                            <div>
                                <label for="profile_photo" class="btn btn-outline-primary">
                                    <i class="fas fa-camera"></i> Change Photo
                                </label>
                                <input type="file" 
                                       id="profile_photo" 
                                       name="profile_photo" 
                                       accept="image/*" 
                                       class="d-none"
                                       onchange="previewImage(this)">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Name</label>
                            <input type="text" 
                                   name="name" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" 
                                   name="email" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" 
                                   name="phone_number" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" 
                                      class="form-control" 
                                      rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                            <button type="button" 
                                    class="btn btn-danger" 
                                    data-bs-toggle="modal" 
                                    data-bs-target="#deleteProfileModal">
                                Delete Profile
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Profile Modal -->
<div class="modal fade" id="deleteProfileModal" tabindex="-1" aria-labelledby="deleteProfileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProfileModalLabel">Delete Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    Warning: This action cannot be undone. All your data will be permanently deleted.
                </p>
                <p>Are you sure you want to delete your profile?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" class="d-inline">
                    <button type="submit" name="delete_profile" class="btn btn-danger">Delete Profile</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function previewImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const img = input.parentElement.previousElementSibling;
            img.src = e.target.result;
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

<?php include 'components/footer.php'; ?>
</body>
</html> 