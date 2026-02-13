<?php
ob_start();

// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require_once 'includes/auth.php';
$auth->logout();
?>