<?php

require_once 'config.php';
// Start session
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters to ensure it works across all directories
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Database connection
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";port=".DB_PORT.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Ensure autocommit is enabled by default
    $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    
    // Set SQL mode and timezone
    $pdo->exec("SET time_zone = '+05:30';");
    $pdo->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
