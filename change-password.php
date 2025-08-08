<?php
require_once __DIR__ . '/config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
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
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
                    
            if ($user && password_verify($current_password, $user['password'])) {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->execute([$hashed_password, $user_id]);
                
                $success_message = 'Password changed successfully';
            } else {
                $error_message = 'Current password is incorrect';
            }
        } catch (Exception $e) {
            $error_message = 'An error occurred: ' . $e->getMessage();
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