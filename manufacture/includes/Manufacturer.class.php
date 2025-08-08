<?php
require_once __DIR__ . '/../../config/database.php';

class Manufacturer {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    // Register a new manufacturer
    public function register($firstName, $lastName, $email, $password, $phone) {
        // Validate inputs
        if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($phone)) {
            return ['success' => false, 'message' => 'All fields are required'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters'];
        }

        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $password)) {
            return ['success' => false, 'message' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character'];
        }

        if ($this->emailExists($email)) {
            return ['success' => false, 'message' => 'Email already registered'];
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Generate OTP
        $otp = rand(100000, 999999);
        $expiry = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        try {
            $stmt = $this->pdo->prepare("INSERT INTO manufacturers (first_name, last_name, email, password, phone, verification_code, code_expiry) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $phone, $otp, $expiry]);

            // Send OTP email (in a real application)
            $this->sendVerificationEmail($email, $otp);

            return ['success' => true, 'message' => 'Registration successful. Please check your email for verification code.'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }

    // Verify email with OTP
    public function verifyEmail($email, $otp) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM manufacturers WHERE email = ? AND verification_code = ? AND code_expiry > NOW()");
            $stmt->execute([$email, $otp]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $updateStmt = $this->pdo->prepare("UPDATE manufacturers SET email_verified = 1, verification_code = NULL, code_expiry = NULL WHERE email = ?");
                $updateStmt->execute([$email]);
                return ['success' => true, 'message' => 'Email verified successfully'];
            } else {
                return ['success' => false, 'message' => 'Invalid or expired OTP'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()];
        }
    }

    // Login manufacturer
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM manufacturers WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }

            if (!$user['email_verified']) {
                return ['success' => false, 'message' => 'Please verify your email first'];
            }

            if (password_verify($password, $user['password'])) {
                $_SESSION['manufacturer_id'] = $user['id'];
                $_SESSION['manufacturer_email'] = $user['email'];
                $_SESSION['manufacturer_name'] = $user['first_name'] . ' ' . $user['last_name'];
                
                // Record login activity
                $this->recordLoginActivity($user['id']);
                
                return ['success' => true, 'message' => 'Login successful'];
            } else {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }

    // Record login activity
    private function recordLoginActivity($manufacturerId) {
        try {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            $stmt = $this->pdo->prepare("
                INSERT INTO manufacturing_login_history (manufacturer_id, ip_address, user_agent, login_time) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$manufacturerId, $ipAddress, $userAgent]);

            // Update last login time
            $stmt = $this->pdo->prepare("UPDATE manufacturers SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$manufacturerId]);
            
        } catch (PDOException $e) {
            // Log error but don't fail login
            error_log("Failed to record login activity: " . $e->getMessage());
        }
    }

    // Check if email exists
    private function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM manufacturers WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch() !== false;
    }

    // Send password reset link
    public function sendPasswordResetLink($email) {
        try {
            if (!$this->emailExists($email)) {
                return ['success' => false, 'message' => 'Email not found'];
            }

            $token = bin2hex(random_bytes(50));
            $expiry = date('Y-m-d H:i:s', strtotime('+30 minutes'));

            // Delete any existing tokens
            $this->pdo->prepare("DELETE FROM password_resets WHERE email = ?")
                    ->execute([$email]);

            // Insert new token with correct column names
            $stmt = $this->pdo->prepare("
                INSERT INTO password_resets (email, token, expiry, used) 
                VALUES (?, ?, ?, 0)
            ");
            $stmt->execute([$email, $token, $expiry]);

            // Send email
            if ($this->sendResetEmail($email, $token)) {
                return ['success' => true, 'message' => 'Password reset link sent'];
            } else {
                throw new Exception('Failed to send reset email');
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    // Reset password
    public function resetPassword($token, $newPassword) {
        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/", $newPassword)) {
            return ['success' => false, 'message' => 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character'];
        }

        try {
            $stmt = $this->pdo->prepare("SELECT * FROM password_resets WHERE token = ? AND expiry > NOW() AND used = 0");
            $stmt->execute([$token]);
            $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$resetRequest) {
                return ['success' => false, 'message' => 'Invalid or expired token'];
            }

            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
            $updateUser = $this->pdo->prepare("UPDATE manufacturers SET password = ? WHERE email = ?");
            $updateUser->execute([$hashedPassword, $resetRequest['email']]);

            $this->pdo->prepare("UPDATE password_resets SET used = 1 WHERE token = ?")->execute([$token]);

            return ['success' => true, 'message' => 'Password reset successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Password reset failed: ' . $e->getMessage()];
        }
    }

    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['manufacturer_id']);
    }

    // Get current user ID
    public static function getCurrentUserId() {
        return $_SESSION['manufacturer_id'] ?? null;
    }

    // Get current user data
    public function getCurrentUserData() {
        if (!self::isLoggedIn()) {
            return null;
        }

        $stmt = $this->pdo->prepare("SELECT * FROM manufacturers WHERE id = ?");
        $stmt->execute([self::getCurrentUserId()]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Logout
    public static function logout() {
        session_unset();
        session_destroy();
    }

    // Helper function to send verification email (mock implementation)
    private function sendVerificationEmail($email, $otp) {
        require_once __DIR__ . '/../../config/phpmailer/PHPMailer.php';
        require_once __DIR__ . '/../../config/phpmailer/SMTP.php';
        require_once __DIR__ . '/../../config/phpmailer/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port       = SMTP_PORT;

            // Recipients
            $mail->setFrom(SMTP_USERNAME, SITE_NAME);
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Your Verification Code';
            $mail->Body    = "
                <h2>Direct Garment Manufacturer Portal</h2>
                <p>Your verification code is: <strong>$otp</strong></p>
                <p>This code will expire in 15 minutes.</p>
            ";
            $mail->AltBody = "Your verification code is: $otp";
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Email send error: " . $e->getMessage());
            return false;
        }
    }

    private function sendResetEmail($email, $token) {
        require_once __DIR__ . '/../../config/phpmailer/PHPMailer.php';
        require_once __DIR__ . '/../../config/phpmailer/SMTP.php';
        require_once __DIR__ . '/../../config/phpmailer/Exception.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        try {
            $resetLink = MANUFACTURER_BASE_URL . "reset-password.php?token=" . $token;
            
            // Server settings
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port       = SMTP_PORT;

            // Recipients
            $mail->setFrom(SMTP_USERNAME, SITE_NAME);
            $mail->addAddress($email);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "
                <html>
                <head>
                    <title>Password Reset</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; }
                        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                        .header { background-color: #3f51b5; color: white; padding: 10px; text-align: center; }
                        .content { padding: 20px; }
                        .button { display: inline-block; padding: 10px 20px; background-color: #3f51b5; color: white; text-decoration: none; border-radius: 4px; }
                        .footer { margin-top: 20px; font-size: 12px; color: #777; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h2>Password Reset</h2>
                        </div>
                        <div class='content'>
                            <p>Hello,</p>
                            <p>We received a request to reset your password for the Garment Manufacturer Portal. Click the button below to reset your password:</p>
                            <p><a href='{$resetLink}' class='button'>Reset Password</a></p>
                            <p>If you didn't request a password reset, please ignore this email.</p>
                            <p>This link will expire in 30 minutes.</p>
                        </div>
                        <div class='footer'>
                            <p>© " . date('Y') . " Garment Manufacturer Portal. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";
            $mail->AltBody = "To reset your password, please visit: $resetLink";
            
            return $mail->send();
        } catch (Exception $e) {
            error_log("Password reset email failed: " . $e->getMessage());
            return false;
        }
    }

    public function testEmailSending($email) {
        $otp = rand(100000, 999999);
        return $this->sendVerificationEmail($email, $otp);
    }
}
?>