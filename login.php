<?php
// Prevent any output before JSON response
ob_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth/user_auth.php';

// Function to log errors
function logError($message) {
    $log_dir = dirname(__FILE__) . '/logs';
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $log_file = $log_dir . '/auth_errors.log';
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, $log_file);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Clear any output buffer and set JSON header
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'login':
                    $email = trim($_POST['email'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $remember = isset($_POST['remember']);
                    
                    // Get user with role
                    $stmt = $pdo->prepare("SELECT id, name, password, role, profile_photo FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    
                    if ($stmt->rowCount() !== 1) {
                        throw new Exception('Invalid email or password');
                    }
                    
                    $user = $stmt->fetch();
                    
                    // Verify password
                    if (!password_verify($password, $user['password'])) {
                        throw new Exception('Invalid email or password');
                    }
                    
                    // Ensure session is started (it should already be from database.php)
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['profile_photo'] = $user['profile_photo'] ?? null;
                    
                    // If user is a manufacturer, set manufacturer_id
                    if ($user['role'] === 'Manufacture') {
                        $_SESSION['manufacturer_id'] = $user['id'];
                    }
                    
                    // Handle remember me
                    if ($remember) {
                        $token = bin2hex(random_bytes(32));
                        $expiry = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30); // 30 days
                        
                        $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
                        $stmt->execute([$token, $expiry, $user['id']]);
                        
                        setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/', '', true, true);
                    }
                    
                    echo json_encode([
                        'success' => true, 
                        'message' => 'Login successful',
                        'redirect' => $user['role'] === 'Manufacture' ? 'manufacture/dashboard.php' : 'product.php'
                    ]);
                    break;
                    
                case 'register':
                    $name = trim($_POST['name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $password = $_POST['password'] ?? '';
                    $confirm_password = $_POST['confirm_password'] ?? '';
                    
                    // Validation
                    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
                        throw new Exception('Please fill in all fields');
                    }
                    
                    if ($password !== $confirm_password) {
                        throw new Exception('Passwords do not match');
                    }
                    
                    $result = registerUser($name, $email, $password);
                    
                    if ($result['success']) {
                        // Ensure session is started (it should already be from database.php)
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }
                        
                        // Set session variables
                        $_SESSION['user_id'] = $result['user_id'];
                        $_SESSION['user_name'] = $result['name'];
                        $_SESSION['user_role'] = 'Customer';
                        $_SESSION['profile_photo'] = null; // New users don't have profile photo yet
                        
                        echo json_encode([
                            'success' => true, 
                            'message' => 'Registration successful',
                            'redirect' => 'index.php'
                        ]);
                    } else {
                        throw new Exception($result['message']);
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid action');
            }
        } else {
            throw new Exception('No action specified');
        }
    } catch (Exception $e) {
        // Clear any output buffer to prevent HTML before JSON
        ob_clean();
        logError($e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } catch (Error $e) {
        // Handle PHP fatal errors
        ob_clean();
        logError('PHP Error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again.']);
    }
    exit;
}

// Get the current tab
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'signin';
?>

<?php include 'components/header.php'; ?>

<style>
    .nav-link.active, .nav-link.active:focus {
        border-bottom: none !important;
        color: #2563eb !important;
        background: none !important;
    }
    .form-panel-minheight {
        min-height: 520px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: min-height 0.4s cubic-bezier(0.4,0,0.2,1);
    }
    .toggle-btn {
        width: 50%;
        border-radius: 0;
        border: none;
        background: #f8fafc;
        color: #16204e;
        font-weight: 600;
        padding: 0.75rem 0;
        transition: background 0.2s, color 0.2s;
        text-decoration: none !important;
        box-shadow: none !important;
    }
    .toggle-btn.active {
        background: #fff;
        color: #2563eb;
        border-bottom: none;
    }
    .form-label {
        font-size: 0.95rem;
        font-weight: 400 !important;
        margin-bottom: 0.25rem;
    }
    .form-control {
        min-height: 48px;
        font-size: 1.08rem;
        padding: 0.6rem 1rem;
    }
    .input-group {
        margin-bottom: 0.7rem;
    }
    .form-check {
        margin-bottom: 0.7rem;
    }
    @media (max-width: 767px) {
        .form-panel-minheight {
            min-height: 0;
        }
    }
</style>

<div class="py-4" style="background: #16204e; min-height: 100vh;">
  <div class="container">
    <div class="row justify-content-center align-items-center" style="min-height: 80vh;">
      <div class="col-lg-10">
        <div class="row g-0 shadow-lg rounded-4 overflow-hidden" style="background: transparent;">
          <!-- Left: Welcome & Image -->
          <div class="col-md-6 d-flex flex-column justify-content-center align-items-center p-5" style="background: #1e2a5a;">
            <h2 class="fw-bold text-white mb-3">
              <?php echo $tab === 'signup' ? 'Create Account' : 'Welcome Back!'; ?>
            </h2>
            <p class="text-white-50 mb-4">
              <?php echo $tab === 'signup' ? 'Sign up to start your journey with us.' : 'Sign in to access your account and continue your journey.'; ?>
            </p>
            <img src="src/images/web/signIn.png" alt="Sign In" class="img-fluid rounded shadow" style="max-width: 350px; background: #fff; padding: 1rem;">
          </div>
          <!-- Right: Login/Signup Form -->
          <div class="col-md-6 bg-white p-5 form-panel-minheight" id="formPanel">
            <div class="d-flex mb-4">
              <a href="?tab=signin" class="toggle-btn<?php if($tab==='signin') echo ' active'; ?> text-center">Sign In</a>
              <a href="?tab=signup" class="toggle-btn<?php if($tab==='signup') echo ' active'; ?> text-center">Sign Up</a>
            </div>
            
            <!-- Display error/success messages -->
            <?php if (isset($_SESSION['error_message'])): ?>
              <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>
            
            <?php if($tab === 'signup'): ?>
            <form id="signupForm" method="POST" action="?tab=signup">
              <div>
                <div class="mb-1">
                  <label class="form-label">Name</label>
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-regular fa-user"></i></span>
                    <input type="text" name="name" class="form-control" placeholder="Enter your name" required>
                  </div>
                </div>
                <div class="mb-1">
                  <label class="form-label">Email Address</label>
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-regular fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                  </div>
                </div>
                <div class="mb-1">
                  <label class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                  </div>
                </div>
                <div class="mb-1">
                  <label class="form-label">Confirm Password</label>
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
                  </div>
                </div>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                  <label class="form-check-label small" for="agreeTerms">
                    I agree to the <a href="#" class="text-primary">terms and conditions</a>
                  </label>
                </div>
              </div>
              <div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold mb-3" style="background: #2563eb; border: none;">Sign Up</button>
                <div class="text-center text-secondary mb-2">Or continue with</div>
                <div class="d-flex justify-content-center gap-2">
                  <button type="button" class="btn btn-outline-light border rounded-2 px-3 py-2" style="background: #f8fafc;"><i class="fab fa-google fa-lg text-dark"></i></button>
                  <button type="button" class="btn btn-outline-light border rounded-2 px-3 py-2" style="background: #f8fafc;"><i class="fab fa-apple fa-lg text-dark"></i></button>
                  <button type="button" class="btn btn-outline-light border rounded-2 px-3 py-2" style="background: #f8fafc;"><i class="fab fa-facebook-f fa-lg text-dark"></i></button>
                </div>
              </div>
            </form>
            <?php else: ?>
            <form id="signinForm" method="POST" action="?tab=signin">
              <div>
                <div class="mb-1">
                  <label class="form-label">Email Address</label>
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-regular fa-envelope"></i></span>
                    <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                  </div>
                </div>
                <div class="mb-1">
                  <label class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" name="password" class="form-control" placeholder="Enter your password" required>
                  </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="rememberMe" name="remember">
                    <label class="form-check-label small" for="rememberMe">Remember me</label>
                  </div>
                  <a href="#" class="small text-primary fw-semibold">Forgot Password?</a>
                </div>
              </div>
              <div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold mb-3" style="background: #2563eb; border: none;">Sign In</button>
                <div class="text-center text-secondary mb-2">Or continue with</div>
                <div class="d-flex justify-content-center gap-2">
                  <button type="button" class="btn btn-outline-light border rounded-2 px-3 py-2" style="background: #f8fafc;"><i class="fab fa-google fa-lg text-dark"></i></button>
                  <button type="button" class="btn btn-outline-light border rounded-2 px-3 py-2" style="background: #f8fafc;"><i class="fab fa-apple fa-lg text-dark"></i></button>
                  <button type="button" class="btn btn-outline-light border rounded-2 px-3 py-2" style="background: #f8fafc;"><i class="fab fa-facebook-f fa-lg text-dark"></i></button>
                </div>
              </div>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const signupForm = document.getElementById('signupForm');
    const signinForm = document.getElementById('signinForm');
    
    function showMessage(form, message, isError = false, redirectUrl = null) {
        // Remove any existing messages
        const existingAlert = form.querySelector('.alert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${isError ? 'danger' : 'success'} mt-3`;
        alertDiv.textContent = message;
        form.insertBefore(alertDiv, form.firstChild);
        
        if (!isError && redirectUrl) {
            setTimeout(() => {
                window.location.href = redirectUrl;
            }, 1000);
        }
    }
    
    function handleFormSubmit(e) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const isSignup = form === signupForm;
        
        // Add action parameter
        formData.append('action', isSignup ? 'register' : 'login');
        
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
        
        fetch('login.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage(form, data.message, false, data.redirect);
            } else {
                showMessage(form, data.message, true);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage(form, 'An error occurred. Please try again.', true);
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = isSignup ? 'Sign Up' : 'Sign In';
        });
    }
    
    if (signupForm) {
        signupForm.addEventListener('submit', handleFormSubmit);
    }
    
    if (signinForm) {
        signinForm.addEventListener('submit', handleFormSubmit);
    }
});
</script>

<?php include 'components/footer.php'; ?>
