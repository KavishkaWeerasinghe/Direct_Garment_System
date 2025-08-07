<?php
require_once __DIR__ . '/includes/Manufacturer.class.php';
$manufacturer = new Manufacturer();

$error = '';
$success = '';
$email = $_GET['email'] ?? '';

if (empty($email)) {
    header("Location: register.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';
    
    if (empty($otp)) {
        $error = 'Please enter the OTP';
    } else {
        $result = $manufacturer->verifyEmail($email, $otp);
        if ($result['success']) {
            $success = $result['message'];
            // Redirect to login after 3 seconds
            header("Refresh: 3; url=login.php");
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - Garment Manufacturer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="garment-bg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow-lg my-5">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex align-items-center">
                            <img src="assets/images/garment-logo.png" alt="Logo" class="me-2" style="height: 40px;">
                            <h4 class="mb-0">Verify Your Email</h4>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        
                        <?php if (empty($success)): ?>
                            <p class="text-center">We've sent a 6-digit verification code to <strong><?php echo htmlspecialchars($email); ?></strong>.</p>
                            <p class="text-center mb-4">Please check your email and enter the code below.</p>
                            
                            <form action="verify-email.php?email=<?php echo urlencode($email); ?>" method="POST">
                                <div class="mb-3">
                                    <label for="otp" class="form-label">Verification Code</label>
                                    <input type="text" class="form-control" id="otp" name="otp" required maxlength="6" pattern="\d{6}">
                                    <div class="form-text">Enter the 6-digit code you received</div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Verify Email</button>
                            </form>
                            
                            <div class="mt-3 text-center">
                                <p>Didn't receive the code? <a href="#" id="resend-link">Resend code</a></p>
                                <p id="resend-timer" class="text-muted">You can request a new code in <span id="countdown">60</span> seconds</p>
                            </div>
                        <?php else: ?>
                            <p class="text-center">Your email has been verified successfully. You will be redirected to the login page shortly.</p>
                            <div class="text-center">
                                <a href="login.php" class="btn btn-primary">Login Now</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resend OTP functionality
        document.addEventListener('DOMContentLoaded', function() {
            const resendLink = document.getElementById('resend-link');
            const resendTimer = document.getElementById('resend-timer');
            const countdownElement = document.getElementById('countdown');
            
            let countdown = 60;
            let canResend = false;
            let timer;
            
            // Hide resend link initially
            resendLink.style.display = 'none';
            
            // Function to start countdown
            function startCountdown() {
                countdown = 60;
                canResend = false;
                resendLink.style.display = 'none';
                resendTimer.style.display = 'block';
                countdownElement.textContent = countdown;
                
                // Clear any existing timer
                if (timer) {
                    clearInterval(timer);
                }
                
                timer = setInterval(function() {
                    countdown--;
                    countdownElement.textContent = countdown;
                    
                    if (countdown <= 0) {
                        clearInterval(timer);
                        resendTimer.style.display = 'none';
                        resendLink.style.display = 'inline';
                        canResend = true;
                    }
                }, 1000);
            }
            
            // Start initial countdown
            startCountdown();
            
            // Handle resend click
            resendLink.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (canResend) {
                // Show loading state
                const originalText = resendLink.innerHTML;
                resendLink.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Sending...';
                resendLink.disabled = true;
                
                fetch('resend-otp.php?email=<?php echo urlencode($email); ?>')
                    .then(response => {
                        // First check if the response is JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            return response.text().then(text => {
                                throw new Error(`Invalid response: ${text}`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert('A new verification code has been sent to your email.');
                            // Reset timer
                            countdown = 60;
                            canResend = false;
                            resendLink.style.display = 'none';
                            resendLink.innerHTML = originalText;
                            resendLink.disabled = false;
                            resendTimer.style.display = 'block';
                            
                            const newTimer = setInterval(function() {
                                countdown--;
                                countdownElement.textContent = countdown;
                                
                                if (countdown <= 0) {
                                    clearInterval(newTimer);
                                    resendTimer.style.display = 'none';
                                    resendLink.style.display = 'inline';
                                    canResend = true;
                                }
                            }, 1000);
                        } else {
                            throw new Error(data.message || 'Failed to resend verification code');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert(error.message || 'An error occurred while trying to resend the code.');
                        resendLink.innerHTML = originalText;
                        resendLink.disabled = false;
                    });
            }
        });
        });
    </script>
</body>
</html>