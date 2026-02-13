<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function register($name, $email, $password) {
        // Check if email exists
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            return false; // Email already exists
        }
        
        // Hash password and insert user
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        
        if ($stmt->execute([$name, $email, $hashedPassword])) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    }
    
    public function login($email, $password) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            return true;
        }
        
        return false;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            // Use JavaScript redirect if headers already sent
            if (headers_sent()) {
                echo '<script>window.location.href = "../login.php";</script>';
                exit();
            } else {
                header("Location: ../login.php");
                exit();
            }
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            // Use JavaScript redirect if headers already sent
            if (headers_sent()) {
                echo '<script>window.location.href = "../dashboard.php";</script>';
                exit();
            } else {
                header("Location: ../dashboard.php");
                exit();
            }
        }
    }
    
    public function logout() {
        session_destroy();
        // Use JavaScript redirect if headers already sent
        if (headers_sent()) {
            echo '<script>window.location.href = "login.php";</script>';
            exit();
        } else {
            header("Location: login.php");
            exit();
        }
    }
    
    public function getUser($id) {
        $stmt = $this->pdo->prepare("SELECT id, name, email, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}

$auth = new Auth($pdo);
?>