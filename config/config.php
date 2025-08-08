<?php
/**
 * Application Configuration
 * Loads configuration from environment variables with fallback to default values
 */

// Load environment variables
require_once __DIR__ . '/environment.php';

// Site configuration
define('SITE_NAME', 'Direct Garment');
define('ROOT_DIR', dirname(__DIR__)); 
define('BASE_URL', 'http://localhost/direct-garment/');
define('MANUFACTURER_BASE_URL', BASE_URL . 'manufacture/');

// Set default timezone
date_default_timezone_set('Asia/Colombo');

// Database Configuration
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_PORT', env('DB_PORT', '3308'));
define('DB_NAME', env('DB_NAME', 'direct_garment'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));

// Application Configuration
define('APP_NAME', env('APP_NAME', 'GarmentDirect'));
define('APP_URL', env('APP_URL', 'http://localhost/direct-garment'));
define('APP_VERSION', env('APP_VERSION', '1.0.0'));

// File Upload Configuration
define('UPLOAD_MAX_SIZE', (int)env('UPLOAD_MAX_SIZE', 5 * 1024 * 1024)); // 5MB default
define('ALLOWED_IMAGE_TYPES', explode(',', env('ALLOWED_IMAGE_TYPES', 'jpg,jpeg,png,gif,webp')));
define('UPLOAD_PATH', __DIR__ . '/../' . env('UPLOAD_PATH', 'uploads/'));

// Session Configuration
define('SESSION_LIFETIME', (int)env('SESSION_LIFETIME', 3600)); // 1 hour default
define('SESSION_NAME', env('SESSION_NAME', 'garmentdirect_session'));

// Security Configuration
define('PASSWORD_MIN_LENGTH', (int)env('PASSWORD_MIN_LENGTH', 6));
define('LOGIN_MAX_ATTEMPTS', (int)env('LOGIN_MAX_ATTEMPTS', 5));
define('LOGIN_LOCKOUT_TIME', (int)env('LOGIN_LOCKOUT_TIME', 900)); // 15 minutes default

// Email Configuration
define('SMTP_HOST', env('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_PORT', (int)env('SMTP_PORT', 587));
define('SMTP_USERNAME', env('SMTP_USERNAME', 'your-email@gmail.com'));
define('SMTP_PASSWORD', env('SMTP_PASSWORD', 'your-app-password'));
define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION', 'tls'));

// Timezone Configuration
date_default_timezone_set(env('TIMEZONE', 'Asia/Colombo'));

// Environment Configuration
$environment = env('ENVIRONMENT', 'development');
if ($environment === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Logging Configuration
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_LEVEL', env('LOG_LEVEL', 'INFO')); // DEBUG, INFO, WARNING, ERROR

// Create logs directory if it doesn't exist
if (!file_exists(LOG_PATH)) {
    mkdir(LOG_PATH, 0777, true);
}
?>
