<?php
// cashier/process_sale.php
session_start();

// Turn off ALL errors
error_reporting(0);
ini_set('display_errors', 0);

// Start session

// Set JSON header
header('Content-Type: application/json');

// Simple response function
function sendJson($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit();
}

try {
    // Include required files
    @include_once '../includes/config.php';
    @include_once '../includes/auth.php';
    @include_once '../includes/db_connect.php';
    
    // Check if files were included
    if (!class_exists('Auth')) {
        sendJson(false, 'System configuration error');
    }
    
    // Create auth instance
    $auth = new Auth();
    
    // Check if logged in
    if (!$auth->isLoggedIn()) {
        sendJson(false, 'Please login first');
    }
    
    // Check role - FIX: Check for each role separately
    $userRole = $_SESSION['role'] ?? '';
    if ($userRole !== 'cashier' && $userRole !== 'admin') {
        sendJson(false, 'Access denied. Cashier or admin access required');
    }
    
    // Get POST data
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendJson(false, 'No data received');
    }
    
    // Decode JSON
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJson(false, 'Invalid data format');
    }
    
    // Check if sale_data exists
    if (!isset($data['sale_data'])) {
        sendJson(false, 'Missing sale data');
    }
    
    $sale = $data['sale_data'];
    
    // Validate required fields
    if (empty($sale['customer_name'])) {
        sendJson(false, 'Customer name is required');
    }
    
    if (empty($sale['items']) || !is_array($sale['items'])) {
        sendJson(false, 'No items in cart');
    }
    
    // Get database connection
    global $conn;
    if (!$conn) {
        sendJson(false, 'Database connection failed');
    }
    
    // Test connection
    if (!$conn->ping()) {
        sendJson(false, 'Database server not responding');
    }
    
    // Generate invoice number
    $invoiceNumber = 'INV-' . date('Ymd-His') . '-' . rand(100, 999);
    
    // Get user info from session
    $cashierId = $_SESSION['user_id'] ?? 0;
    $cashierName = $_SESSION['full_name'] ?? 'Cashier';
    
    // Calculate totals from items to double-check
    $calculatedSubtotal = 0;
    foreach ($sale['items'] as $item) {
        $calculatedSubtotal += ($item['price'] * $item['quantity']);
    }
    $calculatedTax = $calculatedSubtotal * 0.08;
    $calculatedTotal = $calculatedSubtotal + $calculatedTax;
    
    // Use calculated totals or provided ones
    $subtotal = $sale['subtotal'] ?? $calculatedSubtotal;
    $tax = $sale['tax'] ?? $calculatedTax;
    $total = $sale['grand_total'] ?? $calculatedTotal;
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // 1. Insert sale record
        $stmt = $conn->prepare("
            INSERT INTO sales (
                invoice_no, customer_name, user_id, cashier_name,
                payment_method, total_amount, tax, grand_total, sale_date
            ) VALUES (?, ?, ?, ?, 'cash', ?, ?, ?, CURDATE())
        ");
        
        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $stmt->bind_param("ssisddd", 
            $invoiceNumber,
            $sale['customer_name'],
            $cashierId,
            $cashierName,
            $subtotal,
            $tax,
            $total
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save sale: ' . $stmt->error);
        }
        
        $saleId = $conn->insert_id;
        $stmt->close();
        
        // 2. Insert sale items and update stock
        foreach ($sale['items'] as $item) {
            $productId = intval($item['id']);
            $productName = $item['name'];
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);
            $itemTotal = $price * $quantity;
            
            // Insert sale item
            $itemStmt = $conn->prepare("
                INSERT INTO sale_items (
                    sale_id, product_id, product_name, quantity, unit_price, total_price
                ) VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $itemStmt->bind_param("iisidd", 
                $saleId,
                $productId,
                $productName,
                $quantity,
                $price,
                $itemTotal
            );
            
            if (!$itemStmt->execute()) {
                throw new Exception('Failed to save item: ' . $itemStmt->error);
            }
            $itemStmt->close();
            
            // Update product stock
            $updateStmt = $conn->prepare("
                UPDATE products 
                SET quantity = quantity - ? 
                WHERE id = ?
            ");
            
            $updateStmt->bind_param("ii", $quantity, $productId);
            
            if (!$updateStmt->execute()) {
                throw new Exception('Failed to update stock: ' . $updateStmt->error);
            }
            $updateStmt->close();
        }
        
        // Commit transaction
        $conn->commit();
        
        // // Return success
        // sendJson(true, 'Sale processed successfully', [
        //     'invoice_no' => $invoiceNumber,
        //     'invoice_id' => $saleId,
        //     'total_amount' => $total
        // ]);
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    sendJson(false, 'Error: ' . $e->getMessage());
}
sendJson(true, 'Sale processed successfully', [
    'invoice_no' => $invoiceNumber,  // Make sure this is defined
    'invoice_id' => $saleId,
    'total_amount' => $total
]);
?>