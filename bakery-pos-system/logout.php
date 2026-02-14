<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$message = '';
$autoRedirect = true;

// Check if user is actually logged in
if (!isset($_SESSION['user_id'])) {
    $message = 'You are not currently logged in.';
    $autoRedirect = false;
} else {
    // Get user info for logging
    $userId = $_SESSION['user_id'];
    $username = $_SESSION['username'] ?? 'Unknown';
    $fullName = $_SESSION['full_name'] ?? 'Unknown';
    $role = $_SESSION['role'] ?? 'Unknown';
    
    // Log the logout
    // $this->logLogoutActivity($userId, $username, $fullName, $role);
    
    // Clear all session data
    $_SESSION = array();
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
    
    $message = 'You have been successfully logged out.';
}

// Function to log logout activity
function logLogoutActivity($userId, $username, $fullName, $role) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Create logout_logs table if not exists
        $createTableSQL = "CREATE TABLE IF NOT EXISTS logout_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            username VARCHAR(50),
            full_name VARCHAR(100),
            role VARCHAR(20),
            ip_address VARCHAR(45),
            logout_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        $conn->query($createTableSQL);
        
        // Insert logout record
        $sql = "INSERT INTO logout_logs (user_id, username, full_name, role, ip_address, logout_time) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issss", $userId, $username, $fullName, $role, $ip);
            $stmt->execute();
        }
    } catch (Exception $e) {
        // Silently fail, don't interrupt logout process
        error_log("Logout logging failed: " . $e->getMessage());
    }
}

// If AJAX request, return JSON response
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message]);
    exit();
}

// HTML Page for non-AJAX logout
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logout-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 500px;
            overflow: hidden;
        }
        .logout-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            color: #4e73df;
        }
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="logout-card p-5 text-center">
        <div class="logout-icon">
            <i class="fas fa-sign-out-alt"></i>
        </div>
        
        <h2 class="mb-3">Logging Out</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($autoRedirect): ?>
            <div class="spinner"></div>
            <p class="text-muted mt-3">Redirecting to login page...</p>
            
            <script>
                // Auto-redirect after 3 seconds
                setTimeout(function() {
                    window.location.href = 'login.php?logout=success';
                }, 3000);
                
                // Optional: Countdown timer
                let seconds = 3;
                const countdownElement = document.querySelector('.text-muted');
                const countdownInterval = setInterval(function() {
                    seconds--;
                    if (seconds > 0) {
                        countdownElement.textContent = `Redirecting to login page in ${seconds} seconds...`;
                    } else {
                        clearInterval(countdownInterval);
                    }
                }, 1000);
            </script>
        <?php else: ?>
            <div class="mt-4">
                <a href="login.php" class="btn btn-primary me-2">
                    <i class="fas fa-sign-in-alt"></i> Go to Login
                </a>
                <a href="index.php" class="btn btn-outline-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        <?php endif; ?>
        
        <div class="mt-4 text-muted">
            <small>For security reasons, please close your browser if you're on a shared computer.</small>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>