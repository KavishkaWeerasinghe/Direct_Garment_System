<?php
require_once __DIR__ . '/../db_connection.php';

function registerUser($name, $email, $password) {
    global $conn;
    
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

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already exists'];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
    
    $stmt->bind_param("sss", $name, $email, $hashed_password);
    
    if ($stmt->execute()) {
        return ['success' => true, 'user_id' => $stmt->insert_id, 'name' => $name];
    }
    return ['success' => false, 'message' => 'Registration failed: ' . $conn->error];
}

function loginUser($email, $password, $remember = false) {
    global $conn;
    
    // Validate input
    if (empty($email) || empty($password)) {
        return ['success' => false, 'message' => 'Email and password are required'];
    }

    // Get user
    $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
    if (!$stmt) {
        return ['success' => false, 'message' => 'Database error: ' . $conn->error];
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows !== 1) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    $user = $result->fetch_assoc();
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid email or password'];
    }

    // Handle remember me
    if ($remember) {
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30); // 30 days
        
        $stmt = $conn->prepare("UPDATE users SET remember_token = ?, token_expiry = ? WHERE id = ?");
        $stmt->bind_param("ssi", $token, $expiry, $user['id']);
        $stmt->execute();
        
        setcookie('remember_token', $token, time() + 60 * 60 * 24 * 30, '/', '', true, true);
    }

    return ['success' => true, 'user_id' => $user['id'], 'name' => $user['name']];
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
        global $conn;
        
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE remember_token = ? AND token_expiry > NOW()");
        $stmt->bind_param("s", $_COOKIE['remember_token']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            return true;
        }
    }
    
    return false;
}

function logoutUser() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Clear session
    session_unset();
    session_destroy();
    
    // Clear remember token
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
?>