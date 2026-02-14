<?php
require_once 'db_connect.php';

class Auth {
    private $db;
    private $conn;
    
    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->getConnection();
    }
    
public function login($username, $password) {
    // Check login attempts
    $this->checkLoginAttempts($username);
    
    $sql = "SELECT * FROM users WHERE username = ? AND status = 'active'";
    $stmt = $this->conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $this->conn->error);
    }
    
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Debug: Log user data
        error_log("User found: " . print_r($user, true));
        
        if (password_verify($password, $user['password'])) {
            // Reset login attempts
            $this->resetLoginAttempts($username);
            
            // Update last login
            $this->updateLastLogin($user['id']);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Debug: Log session data
            error_log("Session set - Role: " . $user['role']);
            
            // Set session timeout
            $_SESSION['last_activity'] = time();
            
            // Generate CSRF token
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            return ['success' => true, 'role' => $user['role']];
        } else {
            error_log("Password verification failed for user: " . $username);
        }
    } else {
        error_log("User not found or inactive: " . $username);
    }
    
    // Record failed attempt
    $this->recordFailedAttempt($username);
    
    return ['success' => false, 'message' => 'Invalid username or password'];
}
    







    // Registration method
    public function register($userData) {
        // Validate input
        $errors = $this->validateRegistration($userData);
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if username already exists
        if ($this->usernameExists($userData['username'])) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email already exists
        if ($this->emailExists($userData['email'])) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // Default to cashier role for new registrations
        $role = isset($userData['role']) ? $userData['role'] : 'cashier';
        $status = 'active'; // Or 'pending' for admin approval
        
        $sql = "INSERT INTO users (username, password, full_name, email, phone, role, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        
        $phone = isset($userData['phone']) ? $userData['phone'] : null;
        $stmt->bind_param("sssssss", 
            $userData['username'],
            $hashedPassword,
            $userData['full_name'],
            $userData['email'],
            $phone,
            $role,
            $status
        );
        
        if ($stmt->execute()) {
            $userId = $stmt->insert_id;
            
            // Log registration
            $this->logRegistration($userId);
            
            return ['success' => true, 'user_id' => $userId, 'message' => 'Registration successful'];
        } else {
            return ['success' => false, 'message' => 'Registration failed: ' . $stmt->error];
        }
    }
    
    // Validate registration data
    private function validateRegistration($data) {
        $errors = [];
        
        // Validate full name
        if (empty(trim($data['full_name']))) {
            $errors[] = 'Full name is required';
        } elseif (strlen(trim($data['full_name'])) < 2) {
            $errors[] = 'Full name must be at least 2 characters';
        } elseif (strlen(trim($data['full_name'])) > 100) {
            $errors[] = 'Full name cannot exceed 100 characters';
        }
        
        // Validate username
        if (empty(trim($data['username']))) {
            $errors[] = 'Username is required';
        } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $data['username'])) {
            $errors[] = 'Username must be 3-50 characters (letters, numbers, underscore only)';
        }
        
        // Validate email
        if (empty(trim($data['email']))) {
            $errors[] = 'Email is required';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format';
        } elseif (strlen($data['email']) > 100) {
            $errors[] = 'Email cannot exceed 100 characters';
        }
        
        // Validate password
        if (empty($data['password'])) {
            $errors[] = 'Password is required';
        } elseif (strlen($data['password']) < 6) {
            $errors[] = 'Password must be at least 6 characters';
        }
        
        // Validate phone (optional)
        if (!empty($data['phone']) && !preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $data['phone'])) {
            $errors[] = 'Invalid phone number format';
        }
        
        return $errors;
    }
    
    // Check if username exists
    private function usernameExists($username) {
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
    
    // Check if email exists
    private function emailExists($email) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
    
    // Log registration
    private function logRegistration($userId) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Create registration_logs table if not exists
        $this->createRegistrationLogsTable();
        
        $sql = "INSERT INTO registration_logs (user_id, ip_address, user_agent, registration_time) 
                VALUES (?, ?, ?, NOW())";
        
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iss", $userId, $ip, $userAgent);
            $stmt->execute();
        }
    }
    
    // Create registration logs table
    private function createRegistrationLogsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS registration_logs (
                id INT PRIMARY KEY AUTO_INCREMENT,
                user_id INT NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                registration_time DATETIME NOT NULL,
                approved_by INT NULL,
                approval_time DATETIME NULL,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )";
        
        $this->conn->query($sql);
    }
    
    // Login attempt methods
    private function checkLoginAttempts($username) {
        // Simple session-based attempt tracking
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = md5($username . $ip);
        
        if (isset($_SESSION['login_attempts'][$key])) {
            $attempts = $_SESSION['login_attempts'][$key];
            
            if ($attempts['count'] >= MAX_LOGIN_ATTEMPTS) {
                $timeSinceFirstAttempt = time() - $attempts['first_attempt'];
                $timeout = 15 * 60; // 15 minutes timeout
                
                if ($timeSinceFirstAttempt < $timeout) {
                    $remainingTime = $timeout - $timeSinceFirstAttempt;
                    throw new Exception("Too many login attempts. Please try again in " . ceil($remainingTime/60) . " minutes.");
                } else {
                    unset($_SESSION['login_attempts'][$key]);
                }
            }
        }
    }
    
    private function recordFailedAttempt($username) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = md5($username . $ip);
        
        if (!isset($_SESSION['login_attempts'])) {
            $_SESSION['login_attempts'] = [];
        }
        
        if (!isset($_SESSION['login_attempts'][$key])) {
            $_SESSION['login_attempts'][$key] = [
                'count' => 1,
                'first_attempt' => time(),
                'last_attempt' => time()
            ];
        } else {
            $_SESSION['login_attempts'][$key]['count']++;
            $_SESSION['login_attempts'][$key]['last_attempt'] = time();
        }
    }
    
    private function resetLoginAttempts($username) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = md5($username . $ip);
        
        if (isset($_SESSION['login_attempts'][$key])) {
            unset($_SESSION['login_attempts'][$key]);
        }
    }
    
    private function updateLastLogin($userId) {
        $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
        }
    }
    
    // Check username availability for AJAX
    public function checkUsernameAvailability($username) {
        // Basic validation
        if (strlen($username) < 3) {
            return ['available' => false, 'message' => 'Username must be at least 3 characters'];
        }
        
        if (strlen($username) > 50) {
            return ['available' => false, 'message' => 'Username cannot exceed 50 characters'];
        }
        
        // Check valid characters
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['available' => false, 'message' => 'Username can only contain letters, numbers, and underscores'];
        }
        
        // Check reserved usernames
        $reservedUsernames = ['admin', 'administrator', 'root', 'system', 'superuser', 'guest', 'user'];
        if (in_array(strtolower($username), $reservedUsernames)) {
            return ['available' => false, 'message' => 'This username is reserved'];
        }
        
        // Check database
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        
        return [
            'available' => $stmt->num_rows === 0,
            'message' => $stmt->num_rows === 0 ? 'Username is available' : 'Username is already taken'
        ];
    }
    
    // Generate username suggestions
    public function generateUsernameSuggestions($username) {
        $suggestions = [];
        
        // Add numbers
        for ($i = 1; $i <= 5; $i++) {
            $suggestions[] = $username . $i;
            $suggestions[] = $username . '_' . $i;
        }
        
        // Add common suffixes
        $suffixes = ['bakery', 'shop', 'store', '2024', 'user', 'bak', 'corner', 'cafe'];
        
        foreach ($suffixes as $suffix) {
            $suggestions[] = $username . '_' . $suffix;
            $suggestions[] = $username . $suffix;
        }
        
        // Remove duplicates and limit
        $suggestions = array_unique($suggestions);
        return array_slice($suggestions, 0, 8);
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
            $this->logout();
            return false;
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Verify user still exists and is active
        $userId = $_SESSION['user_id'];
        $sql = "SELECT id FROM users WHERE id = ? AND status = 'active'";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows === 0) {
                $this->logout();
                return false;
            }
        }
        
        return true;
    }
    
    // Check role
     public function hasRole($role) {
        if (!isset($_SESSION['role'])) {
            return false;
        }
        
        // If $role is an array, check if user has any of those roles
        if (is_array($role)) {
            return in_array($_SESSION['role'], $role);
        }
        
        // If $role is a string, check for that specific role
        return $_SESSION['role'] === $role;
    }
    
    // Logout
    public function logout() {
        // Clear session
        $_SESSION = [];
        
        // Delete session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    // CSRF methods
    public function validateCSRF($token) {
        return isset($_SESSION['csrf_token']) && 
               hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    // Get user by ID
    public function getUserById($userId) {
        $sql = "SELECT id, username, full_name, email, phone, role, status, created_at, last_login 
                FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    // Update user profile
    public function updateUserProfile($userId, $data) {
        $errors = [];
        
        // Validate email if provided
        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            } elseif ($this->emailExistsForOtherUser($data['email'], $userId)) {
                $errors['email'] = 'Email already exists for another user';
            }
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Build update query
        $fields = [];
        $params = [];
        $types = '';
        
        if (isset($data['full_name'])) {
            $fields[] = "full_name = ?";
            $params[] = $data['full_name'];
            $types .= 's';
        }
        
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
            $types .= 's';
        }
        
        if (isset($data['phone'])) {
            $fields[] = "phone = ?";
            $params[] = $data['phone'];
            $types .= 's';
        }
        
        if (isset($data['role']) && $_SESSION['role'] === 'admin') {
            $fields[] = "role = ?";
            $params[] = $data['role'];
            $types .= 's';
        }
        
        if (isset($data['status']) && $_SESSION['role'] === 'admin') {
            $fields[] = "status = ?";
            $params[] = $data['status'];
            $types .= 's';
        }
        
        if (empty($fields)) {
            return ['success' => false, 'message' => 'No data to update'];
        }
        
        $params[] = $userId;
        $types .= 'i';
        
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Profile updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
            }
        }
        
        return ['success' => false, 'message' => 'Prepare failed'];
    }
    
    private function emailExistsForOtherUser($email, $userId) {
        $sql = "SELECT id FROM users WHERE email = ? AND id != ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $email, $userId);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
    
    // Change password
    public function changePassword($userId, $currentPassword, $newPassword) {
        // Get current password
        $sql = "SELECT password FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify current password
            if (password_verify($currentPassword, $user['password'])) {
                // Hash new password
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // Update password
                $updateSql = "UPDATE users SET password = ? WHERE id = ?";
                $updateStmt = $this->conn->prepare($updateSql);
                $updateStmt->bind_param("si", $newHash, $userId);
                
                if ($updateStmt->execute()) {
                    return ['success' => true, 'message' => 'Password updated successfully'];
                } else {
                    return ['success' => false, 'message' => 'Failed to update password'];
                }
            } else {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
        }
        
        return ['success' => false, 'message' => 'User not found'];
    }
    
    // Get all users (for admin)
    public function getAllUsers($limit = 100) {
        $sql = "SELECT u.*, COUNT(r.id) as total_registrations 
                FROM users u 
                LEFT JOIN registration_logs r ON u.id = r.user_id 
                GROUP BY u.id 
                ORDER BY u.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
    
    // Delete user (admin only)
    public function deleteUser($userId, $adminId) {
        // Prevent self-deletion
        if ($userId == $adminId) {
            return ['success' => false, 'message' => 'You cannot delete your own account'];
        }
        
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete user'];
        }
    }
}
?>