<?php
// admin/access_check.php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Check if user is admin
if (!$auth->hasRole('admin')) {
    // Log unauthorized access attempt
    error_log("Unauthorized admin access attempt by user: " . $_SESSION['username']);
    
    // Redirect based on role
    if ($_SESSION['role'] === 'cashier') {
        header('Location: ../cashier/pos.php');
    } else {
        header('Location: ../login.php');
    }
    exit();
}

// Admin access granted - continue
?>