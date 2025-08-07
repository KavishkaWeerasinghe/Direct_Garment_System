<?php

// Prevent direct access
// if (basename($_SERVER['SCRIPT_FILENAME']) === 'config.php') {
//     header('HTTP/1.0 403 Forbidden');
//     exit;
// }


// Load environment variables
$env = parse_ini_file(__DIR__ . '/../.env');

// Site configuration
define('SITE_NAME', 'Direct Garment');
define('ROOT_DIR', dirname(__DIR__)); 
define('BASE_URL', 'http://localhost/direct-garment/');
define('MANUFACTURER_BASE_URL', BASE_URL . 'manufacture/');

// Set default timezone
date_default_timezone_set('Asia/Colombo');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_PORT', '3308');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'direct_garment');

// Email configuration (for OTP sending)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USER', $env['SMTP_USER'] ?? '');
define('SMTP_PASS', $env['SMTP_PASS'] ?? '');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('MAIL_FROM', $env['MAIL_FROM'] ?? '');
define('MAIL_FROM_NAME', $env['MAIL_FROM_NAME'] ?? '');
