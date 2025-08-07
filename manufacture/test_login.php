<?php
require_once '../config/database.php';
require_once 'includes/Manufacturer.class.php';

echo "<h2>Login Test</h2>";

// Check if already logged in
if (Manufacturer::isLoggedIn()) {
    echo "<p>Already logged in as user ID: " . Manufacturer::getCurrentUserId() . "</p>";
    echo "<p><a href='logout.php'>Logout</a></p>";
    echo "<p><a href='settings/profile.php'>Go to Profile</a></p>";
    exit;
}

// Test login with a known user (you'll need to replace with actual credentials)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $manufacturer = new Manufacturer();
    $result = $manufacturer->login($email, $password);
    
    if ($result['success']) {
        echo "<p style='color: green;'>Login successful!</p>";
        echo "<p>Session ID: " . session_id() . "</p>";
        echo "<p>User ID: " . Manufacturer::getCurrentUserId() . "</p>";
        echo "<p><a href='settings/profile.php'>Go to Profile</a></p>";
        echo "<p><a href='logout.php'>Logout</a></p>";
    } else {
        echo "<p style='color: red;'>Login failed: " . $result['message'] . "</p>";
    }
}
?>

<form method="POST">
    <h3>Test Login</h3>
    <p>Email: <input type="email" name="email" required></p>
    <p>Password: <input type="password" name="password" required></p>
    <p><input type="submit" value="Login"></p>
</form>

<p><a href="test_session.php">Check Session Status</a></p> 