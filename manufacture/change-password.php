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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All fields are required';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match';
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        if ($stmt === false) {
            $error_message = "Error preparing statement: " . $conn->error;
        } else {
            $stmt->bind_param("i", $user_id);
            if (!$stmt->execute()) {
                $error_message = "Error executing statement: " . $stmt->error;
            } else {
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                if (password_verify($current_password, $user['password'])) {
                    // Hash new password
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    // Update password
                    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                    if ($update_stmt === false) {
                        $error_message = "Error preparing update statement: " . $conn->error;
                    } else {
                        $update_stmt->bind_param("si", $hashed_password, $user_id);
                        
                        if ($update_stmt->execute()) {
                            $success_message = 'Password changed successfully';
                        } else {
                            $error_message = 'Failed to change password: ' . $update_stmt->error;
                        }
                        $update_stmt->close();
                    }
                } else {
                    $error_message = 'Current password is incorrect';
                }
            }
            $stmt->close();
        }
    }
}
?>

<?php include 'components/header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="card-title mb-4">Change Password</h2>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success"><?php echo $success_message; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger"><?php echo $error_message; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input type="password" 
                                   name="current_password" 
                                   class="form-control" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password</label>
                            <input type="password" 
                                   name="new_password" 
                                   class="form-control" 
                                   required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" 
                                   name="confirm_password" 
                                   class="form-control" 
                                   required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'components/footer.php'; ?> 