<?php
/**
 * Bakery POS System - Utility Functions
 * Contains all helper functions used throughout the application
 */

require_once 'db_connect.php';

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanitize_input($value);
        }
        return $data;
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}

/**
 * Generate unique invoice number
 */
function generate_invoice_number($prefix = 'INV') {
    $date = date('Ymd');
    $random = mt_rand(1000, 9999);
    $invoiceNumber = $prefix . $date . $random;
    
    // Check if invoice number already exists
    $db = new Database();
    $conn = $db->getConnection();
    
    $sql = "SELECT id FROM sales WHERE invoice_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $invoiceNumber);
    $stmt->execute();
    $stmt->store_result();
    
    // If exists, generate new one
    if ($stmt->num_rows > 0) {
        return generate_invoice_number($prefix);
    }
    
    return $invoiceNumber;
}

/**
 * Format currency
 */
function format_currency($amount, $currency = '$') {
    return $currency . number_format($amount, 2);
}

/**
 * Format date for display
 */
function format_date($date, $format = 'F d, Y h:i A') {
    if (empty($date) || $date == '0000-00-00 00:00:00') {
        return 'N/A';
    }
    return date($format, strtotime($date));
}

/**
 * Calculate age from birth date
 */
function calculate_age($birthDate) {
    if (empty($birthDate)) return null;
    
    $birth = new DateTime($birthDate);
    $today = new DateTime('today');
    $age = $birth->diff($today)->y;
    return $age;
}

/**
 * Validate email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number
 */
function validate_phone($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/\D/', '', $phone);
    
    // Check if phone number is between 10-15 digits
    return (strlen($phone) >= 10 && strlen($phone) <= 15);
}

/**
 * Format phone number
 */
function format_phone($phone) {
    // Remove all non-digit characters
    $phone = preg_replace('/\D/', '', $phone);
    
    // Format as (123) 456-7890
    if (strlen($phone) == 10) {
        return '(' . substr($phone, 0, 3) . ') ' . substr($phone, 3, 3) . '-' . substr($phone, 6);
    }
    
    return $phone;
}

/**
 * Get current date/time in MySQL format
 */
function get_current_datetime() {
    return date('Y-m-d H:i:s');
}

/**
 * Get current date in MySQL format
 */
function get_current_date() {
    return date('Y-m-d');
}

/**
 * Calculate discount amount
 */
function calculate_discount($amount, $discount_percent) {
    return ($amount * $discount_percent) / 100;
}

/**
 * Calculate tax amount
 */
function calculate_tax($amount, $tax_rate = 8) {
    return ($amount * $tax_rate) / 100;
}

/**
 * Calculate total with tax and discount
 */
function calculate_grand_total($subtotal, $tax_rate = 8, $discount_amount = 0) {
    $tax = calculate_tax($subtotal, $tax_rate);
    $grand_total = $subtotal + $tax - $discount_amount;
    return max(0, $grand_total); // Ensure not negative
}

/**
 * Get file extension
 */
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Check if file is an image
 */
function is_image_file($filename) {
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    $extension = get_file_extension($filename);
    return in_array($extension, $allowed_extensions);
}

/**
 * Upload file with validation
 */
function upload_file($file, $upload_dir = 'uploads/', $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf'], $max_size = 2097152) {
    $result = [
        'success' => false,
        'message' => '',
        'file_path' => ''
    ];
    
    // Check if file was uploaded
    if (!isset($file['error']) || is_array($file['error'])) {
        $result['message'] = 'Invalid file upload';
        return $result;
    }
    
    // Check for upload errors
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            $result['message'] = 'No file was uploaded';
            return $result;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            $result['message'] = 'File size is too large';
            return $result;
        default:
            $result['message'] = 'Unknown upload error';
            return $result;
    }
    
    // Check file size
    if ($file['size'] > $max_size) {
        $result['message'] = 'File size exceeds maximum limit of ' . ($max_size / 1024 / 1024) . 'MB';
        return $result;
    }
    
    // Check file type
    $extension = get_file_extension($file['name']);
    if (!in_array($extension, $allowed_types)) {
        $result['message'] = 'File type not allowed. Allowed types: ' . implode(', ', $allowed_types);
        return $result;
    }
    
    // Create upload directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $file_path = $upload_dir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        $result['success'] = true;
        $result['file_path'] = $file_path;
        $result['message'] = 'File uploaded successfully';
    } else {
        $result['message'] = 'Failed to move uploaded file';
    }
    
    return $result;
}

/**
 * Send email
 */
function send_email($to, $subject, $message, $headers = null) {
    if ($headers === null) {
        $headers = "From: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= "Reply-To: no-reply@" . $_SERVER['HTTP_HOST'] . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    }
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Get user IP address
 */
function get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Log activity
 */
function log_activity($user_id, $action, $details = '') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Create activity_logs table if not exists
        $create_table_sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )";
        $conn->query($create_table_sql);
        
        $ip = get_user_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issss", $user_id, $action, $details, $ip, $user_agent);
        $stmt->execute();
        
        return true;
    } catch (Exception $e) {
        error_log("Activity logging failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user role name
 */
function get_role_name($role) {
    $roles = [
        'admin' => 'Administrator',
        'manager' => 'Manager',
        'cashier' => 'Cashier'
    ];
    
    return isset($roles[$role]) ? $roles[$role] : 'Unknown';
}

/**
 * Get status badge HTML
 */
function get_status_badge($status) {
    $badges = [
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-danger">Inactive</span>',
        'pending' => '<span class="badge bg-warning">Pending</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        'paid' => '<span class="badge bg-success">Paid</span>',
        'unpaid' => '<span class="badge bg-warning">Unpaid</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>'
    ];
    
    return isset($badges[$status]) ? $badges[$status] : '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Get payment method name
 */
function get_payment_method_name($method) {
    $methods = [
        'cash' => 'Cash',
        'card' => 'Credit/Debit Card',
        'mobile_money' => 'Mobile Money',
        'bank_transfer' => 'Bank Transfer'
    ];
    
    return isset($methods[$method]) ? $methods[$method] : ucfirst($method);
}

/**
 * Truncate text
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $truncated = substr($text, 0, $length);
    $last_space = strrpos($truncated, ' ');
    
    if ($last_space !== false) {
        $truncated = substr($truncated, 0, $last_space);
    }
    
    return $truncated . $suffix;
}

/**
 * Get pagination data
 */
function get_pagination_data($page, $per_page, $total_items) {
    $total_pages = ceil($total_items / $per_page);
    $offset = ($page - 1) * $per_page;
    
    return [
        'current_page' => $page,
        'per_page' => $per_page,
        'total_items' => $total_items,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_previous' => $page > 1,
        'has_next' => $page < $total_pages
    ];
}

/**
 * Generate pagination HTML
 */
function generate_pagination($current_page, $total_pages, $url_pattern = '?page={page}') {
    if ($total_pages <= 1) {
        return '';
    }
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $prev_url = str_replace('{page}', $current_page - 1, $url_pattern);
        $html .= '<li class="page-item"><a class="page-link" href="' . $prev_url . '">&laquo; Previous</a></li>';
    }
    
    // Page numbers
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
    
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active = ($i == $current_page) ? ' active' : '';
        $page_url = str_replace('{page}', $i, $url_pattern);
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . $page_url . '">' . $i . '</a></li>';
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $next_url = str_replace('{page}', $current_page + 1, $url_pattern);
        $html .= '<li class="page-item"><a class="page-link" href="' . $next_url . '">Next &raquo;</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Get low stock products
 */
function get_low_stock_products($limit = 10) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "SELECT * FROM products WHERE quantity <= min_stock_level AND quantity > 0 ORDER BY quantity ASC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        return $products;
    } catch (Exception $e) {
        error_log("Error getting low stock products: " . $e->getMessage());
        return [];
    }
}

/**
 * Get out of stock products
 */
function get_out_of_stock_products($limit = 10) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "SELECT * FROM products WHERE quantity = 0 ORDER BY product_name ASC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        return $products;
    } catch (Exception $e) {
        error_log("Error getting out of stock products: " . $e->getMessage());
        return [];
    }
}

/**
 * Get today's sales total
 */
function get_today_sales_total() {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "SELECT SUM(grand_total) as total FROM sales WHERE DATE(sale_date) = CURDATE() AND payment_status = 'paid'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        
        return $row['total'] ? (float)$row['total'] : 0;
    } catch (Exception $e) {
        error_log("Error getting today's sales: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get today's sales count
 */
function get_today_sales_count() {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "SELECT COUNT(*) as count FROM sales WHERE DATE(sale_date) = CURDATE()";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        
        return $row['count'] ? (int)$row['count'] : 0;
    } catch (Exception $e) {
        error_log("Error getting today's sales count: " . $e->getMessage());
        return 0;
    }
}

/**
 * Get monthly sales data
 */
function get_monthly_sales($month = null, $year = null) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        if ($month === null) $month = date('m');
        if ($year === null) $year = date('Y');
        
        $sql = "SELECT 
                    DAY(sale_date) as day,
                    SUM(grand_total) as total_sales,
                    COUNT(*) as transaction_count
                FROM sales 
                WHERE MONTH(sale_date) = ? AND YEAR(sale_date) = ? 
                AND payment_status = 'paid'
                GROUP BY DAY(sale_date)
                ORDER BY day";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $month, $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sales_data = [];
        while ($row = $result->fetch_assoc()) {
            $sales_data[] = $row;
        }
        
        return $sales_data;
    } catch (Exception $e) {
        error_log("Error getting monthly sales: " . $e->getMessage());
        return [];
    }
}

/**
 * Get top selling products
 */
function get_top_selling_products($limit = 10, $period = 'month') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $date_condition = '';
        if ($period === 'today') {
            $date_condition = "AND DATE(s.sale_date) = CURDATE()";
        } elseif ($period === 'week') {
            $date_condition = "AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
        } elseif ($period === 'month') {
            $date_condition = "AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
        } elseif ($period === 'year') {
            $date_condition = "AND s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)";
        }
        
        $sql = "SELECT 
                    p.id,
                    p.product_name,
                    p.product_code,
                    SUM(si.quantity) as total_quantity,
                    SUM(si.total_price) as total_revenue,
                    COUNT(DISTINCT s.id) as times_sold
                FROM sale_items si
                JOIN sales s ON si.sale_id = s.id
                JOIN products p ON si.product_id = p.id
                WHERE s.payment_status = 'paid' $date_condition
                GROUP BY p.id, p.product_name, p.product_code
                ORDER BY total_quantity DESC
                LIMIT ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        
        return $products;
    } catch (Exception $e) {
        error_log("Error getting top selling products: " . $e->getMessage());
        return [];
    }
}

/**
 * Generate barcode HTML
 */
function generate_barcode_html($code, $type = 'code128') {
    // Simple barcode using CSS
    $barcode = '<div class="barcode" style="font-family: \'Libre Barcode 128\', cursive; font-size: 24px; text-align: center; margin: 10px 0;">';
    $barcode .= '*' . $code . '*';
    $barcode .= '</div>';
    
    return $barcode;
}

/**
 * Generate QR code URL
 */
function generate_qr_code_url($data, $size = 150) {
    $encoded_data = urlencode($data);
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encoded_data}";
}

/**
 * Export data to CSV
 */
function export_to_csv($data, $filename = 'export.csv') {
    if (empty($data)) {
        return false;
    }
    
    // Set headers for download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    
    // Add headers
    if (isset($data[0])) {
        fputcsv($output, array_keys($data[0]));
    }
    
    // Add data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Export data to Excel (simple HTML table)
 */
function export_to_excel($data, $filename = 'export.xls') {
    if (empty($data)) {
        return false;
    }
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo '<table border="1">';
    
    // Add headers
    if (isset($data[0])) {
        echo '<tr>';
        foreach (array_keys($data[0]) as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr>';
    }
    
    // Add data
    foreach ($data as $row) {
        echo '<tr>';
        foreach ($row as $cell) {
            echo '<td>' . htmlspecialchars($cell) . '</td>';
        }
        echo '</tr>';
    }
    
    echo '</table>';
    exit;
}

/**
 * Backup database
 */
function backup_database($backup_dir = 'backups/') {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Create backup directory if not exists
        if (!file_exists($backup_dir)) {
            mkdir($backup_dir, 0755, true);
        }
        
        $backup_file = $backup_dir . 'backup_' . date('Y-m-d_H-i-s') . '.sql';
        
        // Get all tables
        $tables = [];
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
        
        $sql_script = "-- Bakery POS Database Backup\n";
        $sql_script .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
        $sql_script .= "-- Database: " . DB_NAME . "\n\n";
        
        // Loop through tables
        foreach ($tables as $table) {
            // Drop table if exists
            $sql_script .= "DROP TABLE IF EXISTS `$table`;\n";
            
            // Create table
            $create_table = $conn->query("SHOW CREATE TABLE `$table`");
            $row = $create_table->fetch_row();
            $sql_script .= "\n" . $row[1] . ";\n\n";
            
            // Insert data
            $result = $conn->query("SELECT * FROM `$table`");
            if ($result->num_rows > 0) {
                $sql_script .= "INSERT INTO `$table` VALUES\n";
                $rows = [];
                while ($row = $result->fetch_row()) {
                    $values = array_map(function($value) use ($conn) {
                        return is_null($value) ? 'NULL' : "'" . $conn->real_escape_string($value) . "'";
                    }, $row);
                    $rows[] = '(' . implode(', ', $values) . ')';
                }
                $sql_script .= implode(",\n", $rows) . ";\n\n";
            }
        }
        
        // Write to file
        if (file_put_contents($backup_file, $sql_script)) {
            return [
                'success' => true,
                'file' => $backup_file,
                'size' => filesize($backup_file)
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to write backup file'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Restore database from backup
 */
function restore_database($backup_file) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Read backup file
        $sql = file_get_contents($backup_file);
        
        // Execute SQL queries
        if ($conn->multi_query($sql)) {
            do {
                // Store first result set
                if ($result = $conn->store_result()) {
                    $result->free();
                }
            } while ($conn->more_results() && $conn->next_result());
            
            return [
                'success' => true,
                'message' => 'Database restored successfully'
            ];
        } else {
            return [
                'success' => false,
                'error' => 'Failed to execute backup SQL: ' . $conn->error
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Get disk usage information
 */
function get_disk_usage() {
    $total = disk_total_space(__DIR__);
    $free = disk_free_space(__DIR__);
    $used = $total - $free;
    
    return [
        'total' => $total,
        'free' => $free,
        'used' => $used,
        'used_percent' => ($total > 0) ? ($used / $total) * 100 : 0,
        'free_percent' => ($total > 0) ? ($free / $total) * 100 : 0
    ];
}

/**
 * Format file size
 */
function format_file_size($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return '1 byte';
    } else {
        return '0 bytes';
    }
}

/**
 * Get system information
 */
function get_system_info() {
    return [
        'php_version' => PHP_VERSION,
        'mysql_version' => function_exists('mysqli_get_client_info') ? mysqli_get_client_info() : 'N/A',
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'N/A',
        'server_os' => php_uname('s') . ' ' . php_uname('r'),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'timezone' => date_default_timezone_get()
    ];
}

/**
 * Check if string is JSON
 */
function is_json($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Convert array to JSON with error handling
 */
function safe_json_encode($data) {
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return json_encode(['error' => 'JSON encoding failed: ' . json_last_error_msg()]);
    }
    return $json;
}

/**
 * Convert JSON to array with error handling
 */
function safe_json_decode($json) {
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'JSON decoding failed: ' . json_last_error_msg()];
    }
    return $data;
}

/**
 * Generate password hash
 */
function generate_password_hash($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate password reset token
 */
function generate_reset_token() {
    return bin2hex(random_bytes(32));
}

/**
 * Validate password strength
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must contain at least one uppercase letter';
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must contain at least one lowercase letter';
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must contain at least one number';
    }
    
    if (!preg_match('/[\W_]/', $password)) {
        $errors[] = 'Password must contain at least one special character';
    }
    
    return $errors;
}

/**
 * Get client browser information
 */
function get_browser_info() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $browser = 'Unknown';
    $platform = 'Unknown';
    
    // Platform detection
    if (preg_match('/linux/i', $user_agent)) {
        $platform = 'Linux';
    } elseif (preg_match('/macintosh|mac os x/i', $user_agent)) {
        $platform = 'Mac';
    } elseif (preg_match('/windows|win32/i', $user_agent)) {
        $platform = 'Windows';
    }
    
    // Browser detection
    if (preg_match('/MSIE/i', $user_agent) && !preg_match('/Opera/i', $user_agent)) {
        $browser = 'Internet Explorer';
    } elseif (preg_match('/Firefox/i', $user_agent)) {
        $browser = 'Firefox';
    } elseif (preg_match('/Chrome/i', $user_agent)) {
        $browser = 'Chrome';
    } elseif (preg_match('/Safari/i', $user_agent)) {
        $browser = 'Safari';
    } elseif (preg_match('/Opera/i', $user_agent)) {
        $browser = 'Opera';
    } elseif (preg_match('/Netscape/i', $user_agent)) {
        $browser = 'Netscape';
    }
    
    return [
        'browser' => $browser,
        'platform' => $platform,
        'user_agent' => $user_agent
    ];
}

/**
 * Check if request is AJAX
 */
function is_ajax_request() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Redirect with message
 */
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header('Location: ' . $url);
    exit();
}

/**
 * Get flash message
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        
        return [
            'message' => $message,
            'type' => $type
        ];
    }
    return null;
}

/**
 * Display flash message
 */
function display_flash_message() {
    $flash = get_flash_message();
    if ($flash) {
        $alert_class = 'alert-' . $flash['type'];
        return '<div class="alert ' . $alert_class . ' alert-dismissible fade show" role="alert">
                    ' . htmlspecialchars($flash['message']) . '
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>';
    }
    return '';
}

/**
 * Generate breadcrumbs
 */
function generate_breadcrumbs($pages) {
    if (empty($pages)) {
        return '';
    }
    
    $html = '<nav aria-label="breadcrumb">';
    $html .= '<ol class="breadcrumb">';
    
    foreach ($pages as $i => $page) {
        $is_last = ($i === count($pages) - 1);
        
        if ($is_last) {
            $html .= '<li class="breadcrumb-item active" aria-current="page">' . htmlspecialchars($page['title']) . '</li>';
        } else {
            $html .= '<li class="breadcrumb-item"><a href="' . htmlspecialchars($page['url']) . '">' . htmlspecialchars($page['title']) . '</a></li>';
        }
    }
    
    $html .= '</ol>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Get settings from database
 */
function get_settings() {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Create settings table if not exists
        $create_table_sql = "CREATE TABLE IF NOT EXISTS settings (
            id INT PRIMARY KEY AUTO_INCREMENT,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'text',
            category VARCHAR(50) DEFAULT 'general',
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($create_table_sql);
        
        // Get all settings
        $sql = "SELECT setting_key, setting_value, setting_type FROM settings";
        $result = $conn->query($sql);
        
        $settings = [];
        while ($row = $result->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Set default settings if not exists
        $default_settings = [
            'site_name' => 'Bakery POS System',
            'site_email' => 'info@bakerypos.com',
            'site_phone' => '+1234567890',
            'site_address' => '123 Bakery Street, City, Country',
            'tax_rate' => '8',
            'currency_symbol' => '$',
            'currency_code' => 'USD',
            'timezone' => 'America/New_York',
            'date_format' => 'F d, Y',
            'time_format' => 'h:i A',
            'items_per_page' => '20',
            'low_stock_threshold' => '10',
            'receipt_footer' => 'Thank you for your business!',
            'enable_email_notifications' => '1',
            'enable_stock_alerts' => '1',
            'backup_enabled' => '1',
            'backup_frequency' => 'daily'
        ];
        
        foreach ($default_settings as $key => $value) {
            if (!isset($settings[$key])) {
                // Insert default setting
                $insert_sql = "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)";
                $stmt = $conn->prepare($insert_sql);
                $stmt->bind_param("ss", $key, $value);
                $stmt->execute();
                
                $settings[$key] = $value;
            }
        }
        
        return $settings;
    } catch (Exception $e) {
        error_log("Error getting settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Update setting
 */
function update_setting($key, $value) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "INSERT INTO settings (setting_key, setting_value) 
                VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE setting_value = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $key, $value, $value);
        $stmt->execute();
        
        return $stmt->affected_rows > 0;
    } catch (Exception $e) {
        error_log("Error updating setting: " . $e->getMessage());
        return false;
    }
}

/**
 * Get setting value
 */
function get_setting($key, $default = '') {
    $settings = get_settings();
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * Number to words converter
 */
function number_to_words($number) {
    $whole = floor($number);
    $fraction = round(($number - $whole) * 100);
    
    $words = convert_number_to_words($whole);
    
    if ($fraction > 0) {
        return $words . ' and ' . convert_number_to_words($fraction) . ' cents';
    }
    
    return $words;
}

function convert_number_to_words($number) {
    $ones = array(
        0 => 'Zero', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four',
        5 => 'Five', 6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
        10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen',
        14 => 'Fourteen', 15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen',
        18 => 'Eighteen', 19 => 'Nineteen'
    );
    
    $tens = array(
        2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
        6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
    );
    
    if ($number < 20) {
        return $ones[$number];
    }
    
    if ($number < 100) {
        $ten = floor($number / 10);
        $unit = $number % 10;
        $words = $tens[$ten];
        if ($unit > 0) {
            $words .= '-' . $ones[$unit];
        }
        return $words;
    }
    
    if ($number < 1000) {
        $hundred = floor($number / 100);
        $remainder = $number % 100;
        $words = $ones[$hundred] . ' Hundred';
        if ($remainder > 0) {
            $words .= ' and ' . convert_number_to_words($remainder);
        }
        return $words;
    }
    
    if ($number < 1000000) {
        $thousand = floor($number / 1000);
        $remainder = $number % 1000;
        $words = convert_number_to_words($thousand) . ' Thousand';
        if ($remainder > 0) {
            $words .= ' ' . convert_number_to_words($remainder);
        }
        return $words;
    }
    
    if ($number < 1000000000) {
        $million = floor($number / 1000000);
        $remainder = $number % 1000000;
        $words = convert_number_to_words($million) . ' Million';
        if ($remainder > 0) {
            $words .= ' ' . convert_number_to_words($remainder);
        }
        return $words;
    }
    
    return 'Number too large';
}

/**
 * Calculate profit margin
 */
function calculate_profit_margin($cost_price, $selling_price) {
    if ($cost_price == 0) {
        return 0;
    }
    
    $profit = $selling_price - $cost_price;
    $margin = ($profit / $selling_price) * 100;
    
    return round($margin, 2);
}

/**
 * Calculate markup percentage
 */
function calculate_markup_percentage($cost_price, $selling_price) {
    if ($cost_price == 0) {
        return 0;
    }
    
    $markup = (($selling_price - $cost_price) / $cost_price) * 100;
    
    return round($markup, 2);
}

/**
 * Get profit/loss for a sale
 */
function calculate_sale_profit($sale_id) {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "SELECT 
                    SUM(si.total_price) as total_revenue,
                    SUM(si.quantity * p.cost) as total_cost
                FROM sale_items si
                JOIN products p ON si.product_id = p.id
                WHERE si.sale_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $sale_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $revenue = $row['total_revenue'] ? (float)$row['total_revenue'] : 0;
            $cost = $row['total_cost'] ? (float)$row['total_cost'] : 0;
            $profit = $revenue - $cost;
            
            return [
                'revenue' => $revenue,
                'cost' => $cost,
                'profit' => $profit,
                'margin' => ($revenue > 0) ? ($profit / $revenue) * 100 : 0
            ];
        }
        
        return [
            'revenue' => 0,
            'cost' => 0,
            'profit' => 0,
            'margin' => 0
        ];
    } catch (Exception $e) {
        error_log("Error calculating sale profit: " . $e->getMessage());
        return [
            'revenue' => 0,
            'cost' => 0,
            'profit' => 0,
            'margin' => 0
        ];
    }
}

/**
 * Get dashboard statistics
 */
function get_dashboard_stats() {
    $stats = [];
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        // Total products
        $sql = "SELECT COUNT(*) as total FROM products";
        $result = $conn->query($sql);
        $stats['total_products'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Low stock products
        $sql = "SELECT COUNT(*) as total FROM products WHERE quantity <= min_stock_level AND quantity > 0";
        $result = $conn->query($sql);
        $stats['low_stock'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Out of stock products
        $sql = "SELECT COUNT(*) as total FROM products WHERE quantity = 0";
        $result = $conn->query($sql);
        $stats['out_of_stock'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Today's sales
        $sql = "SELECT SUM(grand_total) as total FROM sales WHERE DATE(sale_date) = CURDATE() AND payment_status = 'paid'";
        $result = $conn->query($sql);
        $stats['today_sales'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Today's transactions
        $sql = "SELECT COUNT(*) as total FROM sales WHERE DATE(sale_date) = CURDATE()";
        $result = $conn->query($sql);
        $stats['today_transactions'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Monthly sales
        $sql = "SELECT SUM(grand_total) as total FROM sales WHERE MONTH(sale_date) = MONTH(CURDATE()) AND YEAR(sale_date) = YEAR(CURDATE()) AND payment_status = 'paid'";
        $result = $conn->query($sql);
        $stats['monthly_sales'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Total customers (unique customers from sales)
        $sql = "SELECT COUNT(DISTINCT customer_name) as total FROM sales WHERE customer_name != ''";
        $result = $conn->query($sql);
        $stats['total_customers'] = $result->fetch_assoc()['total'] ?? 0;
        
        // Active users
        $sql = "SELECT COUNT(*) as total FROM users WHERE status = 'active'";
        $result = $conn->query($sql);
        $stats['active_users'] = $result->fetch_assoc()['total'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
        
        // Set default values
        $stats = [
            'total_products' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'today_sales' => 0,
            'today_transactions' => 0,
            'monthly_sales' => 0,
            'total_customers' => 0,
            'active_users' => 0
        ];
    }
    
    return $stats;
}

/**
 * Create activity logs table
 */
function create_activity_logs_table() {
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            action VARCHAR(255) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_created_at (created_at),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )";
        
        $conn->query($sql);
        return true;
    } catch (Exception $e) {
        error_log("Error creating activity logs table: " . $e->getMessage());
        return false;
    }
}

/**
 * Initialize default settings
 */
function initialize_default_settings() {
    $default_settings = [
        'site_name' => ['value' => 'Bakery POS System', 'type' => 'text', 'category' => 'general'],
        'site_email' => ['value' => 'info@bakerypos.com', 'type' => 'email', 'category' => 'general'],
        'site_phone' => ['value' => '+1234567890', 'type' => 'text', 'category' => 'general'],
        'site_address' => ['value' => '123 Bakery Street, City, Country', 'type' => 'textarea', 'category' => 'general'],
        'tax_rate' => ['value' => '8', 'type' => 'number', 'category' => 'financial'],
        'currency_symbol' => ['value' => '$', 'type' => 'text', 'category' => 'financial'],
        'currency_code' => ['value' => 'USD', 'type' => 'text', 'category' => 'financial'],
        'timezone' => ['value' => 'America/New_York', 'type' => 'select', 'category' => 'general'],
        'date_format' => ['value' => 'F d, Y', 'type' => 'select', 'category' => 'general'],
        'time_format' => ['value' => 'h:i A', 'type' => 'select', 'category' => 'general'],
        'items_per_page' => ['value' => '20', 'type' => 'number', 'category' => 'display'],
        'low_stock_threshold' => ['value' => '10', 'type' => 'number', 'category' => 'inventory'],
        'receipt_footer' => ['value' => 'Thank you for your business!', 'type' => 'textarea', 'category' => 'receipt'],
        'enable_email_notifications' => ['value' => '1', 'type' => 'checkbox', 'category' => 'notifications'],
        'enable_stock_alerts' => ['value' => '1', 'type' => 'checkbox', 'category' => 'notifications'],
        'backup_enabled' => ['value' => '1', 'type' => 'checkbox', 'category' => 'backup'],
        'backup_frequency' => ['value' => 'daily', 'type' => 'select', 'category' => 'backup'],
        'invoice_prefix' => ['value' => 'INV', 'type' => 'text', 'category' => 'invoicing'],
        'invoice_start_number' => ['value' => '1000', 'type' => 'number', 'category' => 'invoicing'],
        'receipt_header' => ['value' => 'Bakery POS System', 'type' => 'textarea', 'category' => 'receipt'],
        'receipt_show_logo' => ['value' => '1', 'type' => 'checkbox', 'category' => 'receipt'],
        'receipt_show_barcode' => ['value' => '1', 'type' => 'checkbox', 'category' => 'receipt']
    ];
    
    try {
        $db = new Database();
        $conn = $db->getConnection();
        
        foreach ($default_settings as $key => $data) {
            $sql = "INSERT IGNORE INTO settings (setting_key, setting_value, setting_type, category) 
                    VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $key, $data['value'], $data['type'], $data['category']);
            $stmt->execute();
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error initializing default settings: " . $e->getMessage());
        return false;
    }
}

/**
 * Get timezone list
 */
function get_timezone_list() {
    $timezones = DateTimeZone::listIdentifiers(DateTimeZone::ALL);
    $timezone_list = [];
    
    foreach ($timezones as $timezone) {
        $timezone_list[$timezone] = $timezone;
    }
    
    return $timezone_list;
}

/**
 * Get date format options
 */
function get_date_format_options() {
    return [
        'Y-m-d' => 'YYYY-MM-DD',
        'd-m-Y' => 'DD-MM-YYYY',
        'm/d/Y' => 'MM/DD/YYYY',
        'd/m/Y' => 'DD/MM/YYYY',
        'F d, Y' => 'Month Day, Year',
        'D, M d, Y' => 'Day, Month Day, Year'
    ];
}

/**
 * Get time format options
 */
function get_time_format_options() {
    return [
        'H:i' => '24-hour format (14:30)',
        'h:i A' => '12-hour format (02:30 PM)',
        'h:i:s A' => '12-hour with seconds (02:30:45 PM)',
        'H:i:s' => '24-hour with seconds (14:30:45)'
    ];
}

/**
 * Get backup frequency options
 */
function get_backup_frequency_options() {
    return [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'never' => 'Never'
    ];
}

/**
 * Format date according to settings
 */
function format_date_setting($date_string) {
    $date_format = get_setting('date_format', 'F d, Y');
    $time_format = get_setting('time_format', 'h:i A');
    
    if (empty($date_string)) {
        return 'N/A';
    }
    
    $timestamp = strtotime($date_string);
    if ($timestamp === false) {
        return $date_string;
    }
    
    return date($date_format . ' ' . $time_format, $timestamp);
}

/**
 * Check if maintenance mode is enabled
 */
function is_maintenance_mode() {
    $maintenance = get_setting('maintenance_mode', '0');
    return $maintenance == '1';
}

/**
 * Enable maintenance mode
 */
function enable_maintenance_mode($message = 'System is under maintenance. Please check back later.') {
    update_setting('maintenance_mode', '1');
    update_setting('maintenance_message', $message);
    
    // Log maintenance mode activation
    if (isset($_SESSION['user_id'])) {
        log_activity($_SESSION['user_id'], 'MAINTENANCE_MODE_ENABLED', $message);
    }
    
    return true;
}

/**
 * Disable maintenance mode
 */
function disable_maintenance_mode() {
    update_setting('maintenance_mode', '0');
    
    // Log maintenance mode deactivation
    if (isset($_SESSION['user_id'])) {
        log_activity($_SESSION['user_id'], 'MAINTENANCE_MODE_DISABLED', '');
    }
    
    return true;
}

/**
 * Get maintenance message
 */
function get_maintenance_message() {
    return get_setting('maintenance_message', 'System is under maintenance. Please check back later.');
}

// Initialize activity logs table on include
create_activity_logs_table();

// Initialize default settings
initialize_default_settings();
?>