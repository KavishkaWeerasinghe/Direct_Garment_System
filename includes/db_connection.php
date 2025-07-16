<?php
// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; 
$database = 'garmentdirect';

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Set timezone
date_default_timezone_set('Asia/Colombo');  // Set to your timezone
?> 