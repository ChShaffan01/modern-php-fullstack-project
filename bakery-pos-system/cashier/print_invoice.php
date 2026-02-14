<?php
// cashier/print_invoice.php
// session_start();
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/db_connect.php';

// Turn off error display for users
error_reporting(0);
ini_set('display_errors', 0);

$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->hasRole(['cashier', 'admin'])) {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['invoice_id'])) {
    die('Invalid invoice ID');
}

$invoiceId = intval($_GET['invoice_id']);

// Get sale details
$sql = "SELECT * FROM sales WHERE id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    die('Database error: ' . $conn->error);
}

$stmt->bind_param('i', $invoiceId);
$stmt->execute();
$result = $stmt->get_result();
$sale = $result->fetch_assoc();

if (!$sale) {
    die('Invoice not found');
}

// Get sale items
$itemsSql = "SELECT * FROM sale_items WHERE sale_id = ?";
$itemsStmt = $conn->prepare($itemsSql);

if (!$itemsStmt) {
    die('Database error in items query: ' . $conn->error);
}

$itemsStmt->bind_param('i', $invoiceId);
$itemsStmt->execute();
$itemsResult = $itemsStmt->get_result();

// Calculate totals
$subtotal = $sale['subtotal'] ?? 0;
$tax_amount = $sale['tax_amount'] ?? ($sale['tax'] ?? 0);
$total_amount = $sale['total_amount'] ?? ($sale['grand_total'] ?? 0);

// Get data
$cashier_name = $sale['cashier_name'] ?? $sale['cashier_username'] ?? 'Cashier';
$customer_name = $sale['customer_name'] ?? 'Customer';
$invoice_no = $sale['invoice_no'] ?? 'INV-XXXX';
$sale_date = $sale['sale_date'] ?? date('Y-m-d');
$payment_method = $sale['payment_method'] ?? 'cash';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice <?php echo htmlspecialchars($invoice_no); ?> - <?php echo SITE_NAME; ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Quicksand:wght@300;400;500;600;700&family=Dancing+Script:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Bakery Theme Styles */
        :root {
            --primary-brown: #8B4513;
            --warm-brown: #A0522D;
            --light-brown: #D2691E;
            --cream: #FFF8DC;
            --golden: #DAA520;
            --dark-chocolate: #5D4037;
            --bread-color: #E6C9A8;
            --pastry-pink: #FFE4E1;
            --success-green: #2E7D32;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Quicksand', sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark-chocolate);
            min-height: 100vh;
        }
        
        /* Print Styles */
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .invoice-card {
                box-shadow: none !important;
                border: none !important;
                margin: 0 !important;
                padding: 10px !important;
                max-width: 100% !important;
            }
            .decoration-bread {
                opacity: 0.3 !important;
            }
        }
        
        /* Invoice Card */
        .invoice-card {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(139, 69, 19, 0.15);
            overflow: hidden;
            position: relative;
            border: 8px solid var(--cream);
        }
        
        /* Decorative Elements */
        .decoration-corner {
            position: absolute;
            width: 100px;
            height: 100px;
            opacity: 0.1;
        }
        
        .corner-tl {
            top: 0;
            left: 0;
            border-top: 15px solid var(--golden);
            border-left: 15px solid var(--golden);
            border-top-left-radius: 20px;
        }
        
        .corner-tr {
            top: 0;
            right: 0;
            border-top: 15px solid var(--golden);
            border-right: 15px solid var(--golden);
            border-top-right-radius: 20px;
        }
        
        .corner-bl {
            bottom: 0;
            left: 0;
            border-bottom: 15px solid var(--golden);
            border-left: 15px solid var(--golden);
            border-bottom-left-radius: 20px;
        }
        
        .corner-br {
            bottom: 0;
            right: 0;
            border-bottom: 15px solid var(--golden);
            border-right: 15px solid var(--golden);
            border-bottom-right-radius: 20px;
        }
        
        /* Header */
        .bakery-header {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--warm-brown) 100%);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .bakery-header::before {
            content: "🍞🥐🥖🥨";
            position: absolute;
            top: 10px;
            left: 0;
            right: 0;
            font-size: 40px;
            opacity: 0.2;
            animation: float 3s ease-in-out infinite;
        }
        
        .bakery-title {
            font-family: 'Pacifico', cursive;
            font-size: 3.2rem;
            margin: 0;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }
        
        .bakery-tagline {
            font-family: 'Dancing Script', cursive;
            font-size: 1.5rem;
            margin: 5px 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        /* Invoice Info */
        .invoice-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            background: var(--cream);
            border-bottom: 3px dashed var(--light-brown);
        }
        
        .invoice-title {
            font-family: 'Dancing Script', cursive;
            font-size: 2.8rem;
            color: var(--primary-brown);
            margin: 0;
        }
        
        .invoice-details {
            text-align: right;
        }
        
        .invoice-number {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--warm-brown);
            background: white;
            padding: 8px 15px;
            border-radius: 10px;
            display: inline-block;
            border: 2px solid var(--golden);
        }
        
        .invoice-date {
            margin-top: 8px;
            font-size: 0.95rem;
            color: var(--dark-chocolate);
        }
        
        /* Customer Info Sections */
        .info-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            padding: 30px;
        }
        
        .info-card {
            background: var(--pastry-pink);
            padding: 20px;
            border-radius: 15px;
            border-left: 5px solid var(--light-brown);
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .info-card h3 {
            font-family: 'Dancing Script', cursive;
            font-size: 1.8rem;
            color: var(--primary-brown);
            margin: 0 0 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-card h3 i {
            color: var(--golden);
        }
        
        .customer-name {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--dark-chocolate);
            margin: 0 0 10px 0;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0;
            color: var(--warm-brown);
        }
        
        /* Items Table */
        .items-section {
            padding: 0 30px;
        }
        
        .items-title {
            font-family: 'Dancing Script', cursive;
            font-size: 2rem;
            color: var(--primary-brown);
            text-align: center;
            margin: 0 0 20px 0;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .items-table thead {
            background: linear-gradient(135deg, var(--light-brown) 0%, var(--primary-brown) 100%);
            color: white;
        }
        
        .items-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .items-table tbody tr {
            border-bottom: 1px solid var(--cream);
            transition: all 0.3s ease;
        }
        
        .items-table tbody tr:hover {
            background: var(--pastry-pink);
        }
        
        .items-table tbody tr:nth-child(even) {
            background: rgba(255, 228, 225, 0.3);
        }
        
        .items-table td {
            padding: 15px;
            color: var(--dark-chocolate);
        }
        
        .product-name {
            font-weight: 500;
            color: var(--primary-brown);
        }
        
        .text-right {
            text-align: right;
        }
        
        /* Totals */
        .totals-section {
            padding: 30px;
            background: linear-gradient(135deg, var(--cream) 0%, #f8f4e6 100%);
            border-top: 3px dashed var(--light-brown);
        }
        
        .totals-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            max-width: 400px;
            margin-left: auto;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 20px;
            background: white;
            border-radius: 10px;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }
        
        .total-row:hover {
            border-color: var(--golden);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(218, 165, 32, 0.2);
        }
        
        .total-label {
            font-size: 1.1rem;
            color: var(--dark-chocolate);
            font-weight: 500;
        }
        
        .total-amount {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--primary-brown);
        }
        
        .grand-total {
            background: linear-gradient(135deg, var(--golden) 0%, #ffd700 100%);
            color: var(--dark-chocolate);
            font-size: 1.3rem;
            font-weight: 700;
            border: 3px solid var(--light-brown);
        }
        
        .grand-total .total-label {
            color: var(--dark-chocolate);
            font-weight: 700;
        }
        
        .grand-total .total-amount {
            color: var(--dark-chocolate);
            font-size: 1.5rem;
        }
        
        /* Footer */
        .bakery-footer {
            padding: 25px 30px;
            text-align: center;
            background: var(--dark-chocolate);
            color: white;
            border-top: 5px solid var(--golden);
        }
        
        .thank-you {
            font-family: 'Dancing Script', cursive;
            font-size: 2rem;
            margin: 0 0 10px 0;
            color: var(--golden);
        }
        
        .footer-text {
            margin: 5px 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .bakery-stamp {
            width: 120px;
            height: 120px;
            background: white;
            border-radius: 50%;
            margin: 20px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 5px solid var(--golden);
            position: relative;
        }
        
        .stamp-text {
            font-family: 'Pacifico', cursive;
            color: var(--primary-brown);
            font-size: 1.2rem;
            text-align: center;
            padding: 10px;
        }
        
        /* Print Controls */
        .print-controls {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            display: flex;
            gap: 10px;
        }
        
        .print-btn, .close-btn {
            padding: 12px 25px;
            border: none;
            border-radius: 50px;
            font-family: 'Quicksand', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .print-btn {
            background: linear-gradient(135deg, var(--primary-brown) 0%, var(--warm-brown) 100%);
            color: white;
        }
        
        .print-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(139, 69, 19, 0.3);
        }
        
        .close-btn {
            background: white;
            color: var(--dark-chocolate);
            border: 2px solid var(--dark-chocolate);
        }
        
        .close-btn:hover {
            background: var(--dark-chocolate);
            color: white;
        }
        
        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .info-sections {
                grid-template-columns: 1fr;
            }
            
            .invoice-info {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .bakery-title {
                font-size: 2.5rem;
            }
            
            .print-controls {
                position: static;
                justify-content: center;
                margin-bottom: 20px;
            }
        }
        
        /* Decorative Bread Icons */
        .decoration-bread {
            position: absolute;
            font-size: 24px;
            opacity: 0.1;
            z-index: 0;
        }
        
        .bread-1 { top: 15%; left: 5%; }
        .bread-2 { top: 25%; right: 10%; }
        .bread-3 { bottom: 20%; left: 8%; }
        .bread-4 { bottom: 30%; right: 5%; }
    </style>
</head>
<body>
    <!-- Print Controls -->
    <div class="print-controls no-print">
        <button class="print-btn" onclick="window.print()">
            <span>🖨️</span> Print Invoice
        </button>
        <button class="close-btn" onclick="window.close()">
            <span>✕</span> Close
        </button>
    </div>
    
    <div class="invoice-card fade-in">
        <!-- Decorative Corners -->
        <div class="decoration-corner corner-tl"></div>
        <div class="decoration-corner corner-tr"></div>
        <div class="decoration-corner corner-bl"></div>
        <div class="decoration-corner corner-br"></div>
        
        <!-- Decorative Bread Icons -->
        <div class="decoration-bread bread-1">🥖</div>
        <div class="decoration-bread bread-2">🥐</div>
        <div class="decoration-bread bread-3">🍞</div>
        <div class="decoration-bread bread-4">🥨</div>
        
        <!-- Header -->
        <div class="bakery-header">
            <h1 class="bakery-title"><?php echo SITE_NAME; ?></h1>
            <p class="bakery-tagline">Fresh from our oven to your heart</p>
        </div>
        
        <!-- Invoice Info -->
        <div class="invoice-info">
            <div>
                <h2 class="invoice-title">Sweet Receipt</h2>
            </div>
            <div class="invoice-details">
                <div class="invoice-number"><?php echo htmlspecialchars($invoice_no); ?></div>
                <div class="invoice-date">
                    <?php echo date('F j, Y', strtotime($sale_date)); ?> • 
                    <?php echo date('h:i A', strtotime($sale['sale_time'] ?? 'now')); ?>
                </div>
            </div>
        </div>
        
        <!-- Customer & Store Info -->
        <div class="info-sections">
            <div class="info-card">
                <h3><i>👤</i> Baked For</h3>
                <p class="customer-name"><?php echo htmlspecialchars($customer_name); ?></p>
                <?php if (!empty($sale['customer_phone'])): ?>
                <div class="detail-item">
                    <span>📞</span>
                    <span><?php echo htmlspecialchars($sale['customer_phone']); ?></span>
                </div>
                <?php endif; ?>
                <div class="detail-item">
                    <span>📅</span>
                    <span><?php echo date('l, F j, Y', strtotime($sale_date)); ?></span>
                </div>
            </div>
            
            <div class="info-card">
                <h3><i>👨‍🍳</i> Served By</h3>
                <p class="customer-name"><?php echo htmlspecialchars($cashier_name); ?></p>
                <div class="detail-item">
                    <span>💳</span>
                    <span>Payment: <?php echo ucfirst($payment_method); ?></span>
                </div>
                <div class="detail-item">
                    <span>🆔</span>
                    <span>Order ID: #<?php echo $invoiceId; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <div class="items-section">
            <h3 class="items-title">Your Delicious Order</h3>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Product</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    $itemsResult->data_seek(0);
                    $calculated_subtotal = 0;
                    while ($item = $itemsResult->fetch_assoc()):
                        $item_total = $item['unit_price'] * $item['quantity'];
                        $calculated_subtotal += $item_total;
                        $emoji = match(true) {
                            stripos($item['product_name'], 'bread') !== false => '🍞',
                            stripos($item['product_name'], 'cake') !== false => '🍰',
                            stripos($item['product_name'], 'cookie') !== false => '🍪',
                            stripos($item['product_name'], 'muffin') !== false => '🧁',
                            stripos($item['product_name'], 'croissant') !== false => '🥐',
                            stripos($item['product_name'], 'donut') !== false => '🍩',
                            default => '🥖'
                        };
                    ?>
                    <tr>
                        <td><?php echo $counter++; ?></td>
                        <td>
                            <span class="product-name">
                                <?php echo $emoji . ' ' . htmlspecialchars($item['product_name']); ?>
                            </span>
                        </td>
                        <td class="text-right"><?php echo $item['quantity']; ?></td>
                        <td class="text-right">$<?php echo number_format($item['unit_price'], 2); ?></td>
                        <td class="text-right">$<?php echo number_format($item_total, 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Totals -->
        <div class="totals-section">
           <?php
// Items calculate karte waqt hi totals store karlo
$calculated_subtotal = 0;
$itemsResult->data_seek(0);
while ($item = $itemsResult->fetch_assoc()) {
    $calculated_subtotal += ($item['unit_price'] * $item['quantity']);
}

// Database values ko check karo
$db_subtotal = $sale['subtotal'] ?? 0;
$db_tax = $sale['tax_amount'] ?? ($sale['tax'] ?? 0);
$db_total = $sale['total_amount'] ?? ($sale['grand_total'] ?? 0);

// Debug ke liye values check karo
error_log("Database Values - Subtotal: $db_subtotal, Tax: $db_tax, Total: $db_total");
error_log("Calculated Subtotal: $calculated_subtotal");

// Final totals decide karo
$final_subtotal = ($db_subtotal > 0) ? $db_subtotal : $calculated_subtotal;
$final_tax = ($db_tax > 0) ? $db_tax : ($final_subtotal * 0.08); // 8% tax
$final_total = ($db_total > 0) ? $db_total : ($final_subtotal + $final_tax);

// Double-check ki tax sahi se add ho raha hai
if ($final_total !== ($final_subtotal + $final_tax)) {
    $final_total = $final_subtotal + $final_tax;
}

error_log("Final Values - Subtotal: $final_subtotal, Tax: $final_tax, Total: $final_total");
?>
            
       <!-- Totals Section -->
<div class="totals-section">
    <div class="totals-grid">
        <div class="total-row">
            <div class="total-label">Subtotal</div>
            <div class="total-amount">$<?php echo number_format($final_subtotal, 2); ?></div>
        </div>
        
        <div class="total-row">
            <div class="total-label">Tax (8%)</div>
            <div class="total-amount">$<?php echo number_format($final_tax, 2); ?></div>
        </div>
        
        <div class="total-row grand-total">
            <div class="total-label">TOTAL AMOUNT</div>
            <div class="total-amount">$<?php echo number_format($final_total, 2); ?></div>
        </div>
        
        <!-- Verification row (debugging ke liye) -->
        <div style="text-align: center; margin-top: 15px; font-size: 12px; color: #666;">
            <small>Verification: $<?php echo number_format($final_subtotal, 2); ?> + $<?php echo number_format($final_tax, 2); ?> = $<?php echo number_format($final_subtotal + $final_tax, 2); ?></small>
        </div>
    </div>
</div>
        <!-- Footer -->
        <div class="bakery-footer">
            <div class="bakery-stamp">
                <div class="stamp-text">
                    PAID<br>
                    <span style="font-size: 0.9rem;"><?php echo date('M j, Y'); ?></span>
                </div>
            </div>
            
            <h3 class="thank-you">Thank You!</h3>
            <p class="footer-text">We hope you enjoy every bite!</p>
            <p class="footer-text">123 Bakery Street • NSR , KPK</p>
            <p class="footer-text">📞 0308 6367 941 • ✉️ hello@<?php echo strtolower(str_replace(' ', '', SITE_NAME)); ?>.com</p>
            <p class="footer-text">
                <small>
                    Invoice generated on <?php echo date('F j, Y, h:i A'); ?> • 
                    Freshness guaranteed for 2 days
                </small>
            </p>
        </div>
    </div>
    
    <!-- Print Script -->
<!-- Print Script -->
<script>
    // REMOVE auto-print completely
    // window.addEventListener('load', function() {
    //     setTimeout(function() {
    //         window.print();
    //     }, 1000);
    // });
    
    // Close window function (manual only)
    function closeWindow() {
        window.close();
    }
    
    // Optional: Keyboard shortcut for print (Ctrl+P)
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            window.print();
        }
    });
</script>
</body>
</html>