<?php
require_once __DIR__ . '/includes/db_connection.php';

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
if (!isset($_COOKIE['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_COOKIE['user_id'];
$success_message = '';
$error_message = '';

// Handle profile deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_profile'])) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete user's cart items
        $delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        if ($delete_cart === false) {
            throw new Exception("Failed to prepare cart deletion statement: " . $conn->error);
        }
        $delete_cart->bind_param("i", $user_id);
        if (!$delete_cart->execute()) {
            throw new Exception("Failed to delete cart items: " . $delete_cart->error);
        }
        
        // Delete user's profile photo if exists
        $stmt = $conn->prepare("SELECT profile_photo FROM users WHERE id = ?");
        if ($stmt === false) {
            throw new Exception("Failed to prepare profile photo selection statement: " . $conn->error);
        }
        $stmt->bind_param("i", $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to get profile photo: " . $stmt->error);
        }
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if ($user && $user['profile_photo'] && file_exists($user['profile_photo'])) {
            unlink($user['profile_photo']);
        }
        
        // Delete user account
        $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
        if ($delete_user === false) {
            throw new Exception("Failed to prepare user deletion statement: " . $conn->error);
        }
        $delete_user->bind_param("i", $user_id);
        if (!$delete_user->execute()) {
            throw new Exception("Failed to delete user account: " . $delete_user->error);
        }
        
        // Commit transaction
        $conn->commit();
        
        // Clear all cookies
        setcookie('user_id', '', time() - 3600, '/');
        setcookie('user_name', '', time() - 3600, '/');
        setcookie('profile_photo', '', time() - 3600, '/');
        
        // Redirect to home page
        header('Location: index.php');
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error_message = "Error deleting profile: " . $e->getMessage();
        logError("Profile deletion error: " . $e->getMessage());
    }
}

// Get user data
$stmt = $conn->prepare("SELECT name, email, phone_number, address, profile_photo FROM users WHERE id = ?");
if ($stmt === false) {
    $error = $conn->error;
    logError("Failed to prepare user data statement: " . $error);
    die("Error preparing statement: " . $error);
}

if (!$stmt->bind_param("i", $user_id)) {
    $error = $stmt->error;
    logError("Failed to bind user ID parameter: " . $error);
    die("Error binding parameter: " . $error);
}

if (!$stmt->execute()) {
    $error = $stmt->error;
    logError("Failed to execute user data query: " . $error);
    die("Error executing statement: " . $error);
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    logError("User not found with ID: " . $user_id);
    die("User not found");
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
    $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone_number = ?, address = ?, profile_photo = ? WHERE id = ?");
    if ($update_stmt === false) {
        $error = $conn->error;
        logError("Failed to prepare update statement: " . $error);
        $error_message = "Error preparing update statement: " . $error;
    } else {
        if (!$update_stmt->bind_param("sssssi", $name, $email, $phone_number, $address, $profile_photo, $user_id)) {
            $error = $update_stmt->error;
            logError("Failed to bind update parameters: " . $error);
            $error_message = "Error binding update parameters: " . $error;
        } else {
            if (!$update_stmt->execute()) {
                $error = $update_stmt->error;
                logError("Failed to execute update: " . $error);
                $error_message = "Failed to update profile: " . $error;
            } else {
                // Update cookies
                setcookie('user_name', $name, time() + (86400 * 30), "/");
                setcookie('profile_photo', $profile_photo, time() + (86400 * 30), "/");
                
                $success_message = 'Profile updated successfully';
                // Refresh user data
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            }
        }
        $update_stmt->close();
    }
}

$stmt->close();
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