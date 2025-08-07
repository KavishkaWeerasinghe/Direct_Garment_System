<?php
require_once '../config/database.php';
require_once 'includes/Manufacturer.class.php';

echo "<h2>Session Debug Information</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>Session Data:</p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Authentication Check</h3>";
echo "<p>Is Logged In: " . (Manufacturer::isLoggedIn() ? 'YES' : 'NO') . "</p>";
echo "<p>Current User ID: " . (Manufacturer::getCurrentUserId() ?? 'NULL') . "</p>";

if (Manufacturer::isLoggedIn()) {
    $manufacturer = new Manufacturer();
    $user_data = $manufacturer->getCurrentUserData();
    echo "<h3>User Data:</h3>";
    echo "<pre>";
    print_r($user_data);
    echo "</pre>";
} else {
    echo "<p><strong>User is not logged in!</strong></p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
}
?> 