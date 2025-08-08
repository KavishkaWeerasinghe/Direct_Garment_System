<?php
require_once __DIR__ . '/../../config/database.php';

function registerUser($name, $email, $password) {
    global $pdo;
    
    // Log debugging info
    error_log("Registration attempt for email: $email");
    
    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'All fields are required'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }

    if (strlen($password) < 8) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters'];
    }

    try {
        // Debug: Check PDO connection
        if (!$pdo) {
            error_log("PDO connection is null!");
            return ['success' => false, 'message' => 'Database connection error'];
        }
        
        // Ensure autocommit is enabled
        $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
        error_log("PDO autocommit enabled");
        
        // Check if email exists
        error_log("Checking if email exists: $email");
        $stmt = $pdo->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing_user = $stmt->fetch();
        
        if ($existing_user) {
            error_log("Email already exists in database: " . print_r($existing_user, true));
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        error_log("Email does not exist, proceeding with registration");
        
        // Start explicit transaction to ensure data consistency
        $pdo->beginTransaction();
        error_log("Transaction started");
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        error_log("Password hashed successfully");
        
        // Insert user
        error_log("Attempting to insert user into database");
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'Customer')");
        $result = $stmt->execute([$name, $email, $hashed_password]);
        
        error_log("Insert result: " . ($result ? 'SUCCESS' : 'FAILED'));
        
        if ($result) {
            $user_id = $pdo->lastInsertId();
            error_log("Last insert ID: $user_id");
            
            if ($user_id) {
                // Commit the transaction before verification
                $pdo->commit();
                error_log("Transaction committed successfully");
                
                // Double-check that the user was actually inserted
                $verify_stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE id = ?");
                $verify_stmt->execute([$user_id]);
                $inserted_user = $verify_stmt->fetch();
                
                if ($inserted_user) {
                    error_log("User successfully inserted and verified: " . print_r($inserted_user, true));
                    return ['success' => true, 'user_id' => $user_id, 'name' => $name];
                } else {
                    error_log("User insert failed verification - user not found in database after commit");
                    return ['success' => false, 'message' => 'Registration failed - user not saved after commit'];
                }
            } else {
                $pdo->rollback();
                error_log("Failed to get user ID after registration - transaction rolled back");
                return ['success' => false, 'message' => 'Failed to get user ID after registration'];
            }
        } else {
            $pdo->rollback();
            error_log("Failed to execute insert statement - transaction rolled back");
            return ['success' => false, 'message' => 'Failed to execute insert statement'];
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollback();
            error_log("Transaction rolled back due to error");
        }
        
        // Log the actual error for debugging
        error_log('Registration PDO error: ' . $e->getMessage());
        error_log('Error code: ' . $e->getCode());
        error_log('SQL State: ' . $e->errorInfo[0] ?? 'N/A');
        return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
    }
}

function loginUser($email, $password, $remember = false) {
    global $pdo;
    
    // Validate input
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required'];
    }

    try {
        // Get user
        $stmt = $pdo->prepare("SELECT id, name, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() !== 1) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        $user = $stmt->fetch();
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Invalid email or password'];
        }

        // Handle remember me
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30); // 30 days
            
            $stmt = $pdo->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
            $stmt->execute([$token, $expiry, $user['id']]);
            
            setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/', '', true, true);
        }

        return ['success' => true, 'user_id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
    }
}

function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check session first
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check remember token
    if (isset($_COOKIE['remember_token'])) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE remember_token = ? AND token_expiry > NOW()");
            $stmt->execute([$_COOKIE['remember_token']]);
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];
                
                // Set manufacturer_id if user is a manufacturer
                if ($user['role'] === 'Manufacture') {
                    $_SESSION['manufacturer_id'] = $user['id'];
                }
                
                return true;
            }
        } catch (PDOException $e) {
            // Log error but don't expose it
            error_log('Remember token check failed: ' . $e->getMessage());
        }
    }
    
    return false;
}

function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear remember token from database
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        try {
            $stmt = $pdo->prepare("UPDATE users SET remember_token = NULL, token_expiry = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } catch (PDOException $e) {
            error_log('Failed to clear remember token: ' . $e->getMessage());
        }
    }
    
    // Clear session
    session_unset();
    session_destroy();
    
    // Clear remember token cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Debug function to check user existence
function debugCheckUser($email) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ? $user : false;
    } catch (PDOException $e) {
        error_log('Debug check user error: ' . $e->getMessage());
        return false;
    }
}

// Debug function to list all users
function debugListAllUsers() {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Debug list users error: ' . $e->getMessage());
        return [];
    }
}

// Function to clear problematic user data
function clearUserByEmail($email) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE email = ?");
        $result = $stmt->execute([$email]);
        error_log("Cleared user with email $email: " . ($result ? 'SUCCESS' : 'FAILED'));
        return $result;
    } catch (PDOException $e) {
        error_log('Clear user error: ' . $e->getMessage());
        return false;
    }
}
?>