<?php
class LogoutHandler {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    public function logoutUser($userId, $username, $fullName, $role) {
        try {
            // Log the logout
            $this->logLogout($userId, $username, $fullName, $role);
            
            // Clear user session data
            $this->clearUserSession();
            
            // Destroy session
            $this->destroySession();
            
            return true;
        } catch (Exception $e) {
            error_log("Logout error: " . $e->getMessage());
            return false;
        }
    }
    
    private function logLogout($userId, $username, $fullName, $role) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $sql = "INSERT INTO logout_logs (user_id, username, full_name, role, ip_address) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("issss", $userId, $username, $fullName, $role, $ip);
            $stmt->execute();
        }
    }
    
    private function clearUserSession() {
        // Clear all session variables
        $_SESSION = array();
        
        // Clear session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
    }
    
    private function destroySession() {
        // Regenerate session ID before destroying
        session_regenerate_id(true);
        
        // Destroy the session
        session_destroy();
    }
}
?>