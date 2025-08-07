<?php
require_once __DIR__ . '/includes/db_connection.php';

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
    // Verify user exists first
    $check_user = $conn->prepare("SELECT id, profile_photo FROM users WHERE id = ?");
    $check_user->bind_param("i", $user_id);
    $check_user->execute();
    $user_result = $check_user->get_result();
    
    if ($user_result->num_rows === 0) {
        $error_message = "User not found";
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            $user_data = $user_result->fetch_assoc();
            
            // Delete user's cart items
            $delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $delete_cart->bind_param("i", $user_id);
            if (!$delete_cart->execute()) {
                throw new Exception("Failed to delete cart items: " . $delete_cart->error);
            }
            
            // Delete user's orders
            $delete_orders = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
            $delete_orders->bind_param("i", $user_id);
            if (!$delete_orders->execute()) {
                throw new Exception("Failed to delete orders: " . $delete_orders->error);
            }
            
            // Delete user's profile photo if exists
            if (!empty($user_data['profile_photo']) && file_exists($user_data['profile_photo'])) {
                if (!unlink($user_data['profile_photo'])) {
                    throw new Exception("Failed to delete profile photo");
                }
            }
            
            // Delete user account
            $delete_user = $conn->prepare("DELETE FROM users WHERE id = ?");
            $delete_user->bind_param("i", $user_id);
            if (!$delete_user->execute()) {
                throw new Exception("Failed to delete user: " . $delete_user->error);
            }
            
            // Commit transaction if all operations succeeded
            $conn->commit();
            
            // Clear all cookies
            $cookie_params = session_get_cookie_params();
            setcookie('user_id', '', time() - 3600, '/');
            setcookie('user_name', '', time() - 3600, '/');
            setcookie('profile_photo', '', time() - 3600, '/');
            setcookie('user_role', '', time() - 3600, '/');
            
            // Clear session if exists
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_unset();
                session_destroy();
            }
            
            // Redirect to home page
            header('Location: index.php');
            exit;
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error_message = "Error deleting profile: " . $e->getMessage();
        }
    }
}

// Get user data
$stmt = $conn->prepare("SELECT name, email, phone_number, address, profile_photo FROM users WHERE id = ?");
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
if (!$stmt->execute()) {
    die("Error executing statement: " . $stmt->error);
}

$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
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
            if (!mkdir($upload_dir, 0777, true)) {
                $error_message = 'Failed to create upload directory';
            }
        }
        
        if (empty($error_message)) {
            $file_extension = strtolower(pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                $new_filename = uniqid() . '.' . $file_extension;
                $upload_path = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    // Delete old profile photo if exists
                    if (!empty($user['profile_photo']) && file_exists($user['profile_photo'])) {
                        unlink($user['profile_photo']);
                    }
                    $profile_photo = $upload_path;
                } else {
                    $error_message = 'Failed to upload profile photo';
                }
            } else {
                $error_message = 'Invalid file type. Only JPG, JPEG, and PNG are allowed.';
            }
        }
    }
    
    // Update user data
    if (empty($error_message)) {
        $update_stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone_number = ?, address = ?, profile_photo = ? WHERE id = ?");
        if ($update_stmt === false) {
            $error_message = "Error preparing update statement: " . $conn->error;
        } else {
            $update_stmt->bind_param("sssssi", $name, $email, $phone_number, $address, $profile_photo, $user_id);
            
            if ($update_stmt->execute()) {
                // Update cookies
                setcookie('user_name', $name, time() + (86400 * 30), "/");
                setcookie('profile_photo', $profile_photo, time() + (86400 * 30), "/");
                
                $success_message = 'Profile updated successfully';
                // Refresh user data
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $error_message = 'Failed to update profile: ' . $update_stmt->error;
            }
            $update_stmt->close();
        }
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