<?php
// includes/config.php

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'bakery_pos'); // Your database name
define('DB_USER', 'root'); // Your database username
define('DB_PASS', ''); // Your database password

// Site configuration
define('SITE_NAME', 'Bakery POS System');
define('SITE_URL', 'http://localhost/bakery-pos'); // Update with your URL
define('ADMIN_EMAIL', 'admin@bakery.com');

// Security
define('SECRET_KEY', 'your-secret-key-here-change-this'); // Change this to a random string
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_COST', 12);

// Session configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('SESSION_NAME', 'BAKERY_ADMIN_SESSION');

// Admin settings
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 900); // 15 minutes in seconds

// File upload settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Error reporting (set to false in production)
define('DEBUG_MODE', true);

// Start session with secure settings
session_start([
    'name' => SESSION_NAME,
    'cookie_lifetime' => SESSION_TIMEOUT,
    'cookie_secure' => isset($_SERVER['HTTPS']), // Only send over HTTPS
    'cookie_httponly' => true, // Prevent JavaScript access
    'cookie_samesite' => 'Strict' // CSRF protection
]);

// Database connection function
function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '+00:00', NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
        } catch (PDOException $e) {
            if (DEBUG_MODE) {
                die("Database Connection Failed: " . $e->getMessage());
            } else {
                error_log("Database Connection Error: " . $e->getMessage());
                die("Database connection error. Please try again later.");
            }
        }
    }
    
    return $conn;
}

// Function to log errors
function logError($message, $level = 'ERROR') {
    $logFile = __DIR__ . '/../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$level] $message" . PHP_EOL;
    
    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    error_log($logMessage, 3, $logFile);
}

// Auto-load classes
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Handle fatal errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logError("Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}");
        if (!DEBUG_MODE) {
            http_response_code(500);
            echo "An unexpected error occurred. Please try again later.";
        }
    }
});

// Set timezone
date_default_timezone_set('UTC');

// Set locale
setlocale(LC_ALL, 'en_US.UTF-8');
?>