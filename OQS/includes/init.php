<?php
// Turn on error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Prevent output buffering issues
ob_start();

// Include database connection
require_once 'db.php';

// Include authentication
require_once 'auth.php';

// Include functions
require_once 'functions.php';

// Function for safe redirects
function redirect($url) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    if (headers_sent()) {
        echo '<script>window.location.href = "' . htmlspecialchars($url) . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url) . '"></noscript>';
        exit();
    } else {
        header("Location: " . $url);
        exit();
    }
}

// Function to check if it's a POST request
function is_post() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Function to check if it's a GET request
function is_get() {
    return $_SERVER['REQUEST_METHOD'] === 'GET';
}

// Clean output buffer on shutdown
function clean_output() {
    if (ob_get_length()) {
        ob_end_flush();
    }
}

register_shutdown_function('clean_output');
?>